<?php

namespace App\Http\Controllers\Api\V1;

use App\Attribution\Attribution;
use App\Attribution\Touch;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Resources\V1\LeadResource;
use App\Leads\LeadCaptureService;
use App\Leads\LeadSubmission;
use App\Models\Form;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Lead read/write for integrations. Creation goes through LeadCaptureService (the one pipeline):
 * idempotent by Idempotency-Key, attributed from the payload's utm fields, never bypassing the
 * form definition. Reads are audited.
 */
class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->audit($request, 'leads.index');

        return LeadResource::collection(Lead::query()->notSpam()->with(['assignee', 'product', 'service', 'form'])
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['since'] ?? null, fn ($q, $since) => $q->where('updated_at', '>=', $since))
            ->latest('id')->paginate((int) ($data['per_page'] ?? 25)));
    }

    public function show(Request $request, Lead $lead): LeadResource
    {
        $this->audit($request, 'leads.show', $lead->id);

        return new LeadResource($lead->load(['assignee', 'product', 'service', 'form', 'activities.user']));
    }

    public function store(Request $request, LeadCaptureService $capture): JsonResponse
    {
        $data = $request->validate([
            'form_key' => ['required', 'string', Rule::exists('forms', 'key')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+\d][\d\s().-]{5,}$/', 'required_without:email'],
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'requirement' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'solution_id' => ['nullable', 'integer', 'exists:solutions,id'],
            'custom' => ['nullable', 'array', 'max:20'],
            'custom.*' => ['nullable', 'string', 'max:2000'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'consent_given' => ['nullable', 'boolean'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'utm_term' => ['nullable', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
            'referrer' => ['nullable', 'string', 'max:255'],
        ]);

        $form = Form::query()->where('key', $data['form_key'])->firstOrFail();

        if (! $form->is_active) {
            return response()->json(['message' => 'The form is not accepting submissions.'], 422);
        }

        if ($form->requires_consent && ! ($data['consent_given'] ?? false)) {
            return response()->json(['message' => 'This form requires consent_given=true.', 'errors' => ['consent_given' => ['Consent is required.']]], 422);
        }

        $token = $request->header('Idempotency-Key');
        $token = is_string($token) && preg_match('/^[A-Za-z0-9._:-]{8,120}$/', $token) ? 'api:'.$token : (string) Str::uuid();

        $touch = filled($data['utm_source'] ?? null) || filled($data['utm_campaign'] ?? null) || filled($data['referrer'] ?? null)
            ? new Touch(Str::lower((string) ($data['utm_source'] ?? 'api')), isset($data['utm_medium']) ? Str::lower($data['utm_medium']) : 'api', isset($data['utm_campaign']) ? Str::lower($data['utm_campaign']) : null, $data['utm_term'] ?? null, $data['utm_content'] ?? null, $data['referrer'] ?? null, $data['source_url'] ?? '/', now()->toIso8601String())
            : new Touch('api', 'integration', null, null, null, null, $data['source_url'] ?? '/', now()->toIso8601String());

        $core = array_filter(array_intersect_key($data, array_flip(Form::CORE_FIELDS)), fn ($v) => $v !== null && $v !== '');
        $result = $capture->capture(new LeadSubmission(
            form: $form,
            core: $core,
            custom: array_filter($data['custom'] ?? [], fn ($v) => $v !== null && $v !== ''),
            relations: ['product_id' => $data['product_id'] ?? null, 'service_id' => $data['service_id'] ?? null, 'industry_id' => $data['industry_id'] ?? null, 'solution_id' => $data['solution_id'] ?? null, 'landing_page_id' => null],
            attribution: new Attribution((string) Str::uuid(), $touch, $touch, 1),
            conversionPath: isset($data['source_url']) ? mb_substr($data['source_url'], 0, 500) : null,
            consentGiven: (bool) ($data['consent_given'] ?? false),
            submissionToken: $token,
            userAgent: 'api-key:'.$request->attributes->get(AuthenticateApiKey::ATTRIBUTE)?->id,
            ip: null,
            locale: null,
        ));

        $this->audit($request, $result->created ? 'leads.store' : 'leads.store.replayed', $result->lead->id);

        return (new LeadResource($result->lead->load(['assignee', 'product', 'service', 'form'])))->response()->setStatusCode($result->created ? 201 : 200);
    }

    protected function audit(Request $request, string $endpoint, ?int $leadId = null): void
    {
        $key = $request->attributes->get(AuthenticateApiKey::ATTRIBUTE);

        activity('api')->withProperties(array_filter(['api_key_id' => $key?->id, 'endpoint' => $endpoint, 'lead_id' => $leadId, 'request_id' => $request->attributes->get('request_id')]))->event('api')->log("API {$endpoint}");
    }
}
