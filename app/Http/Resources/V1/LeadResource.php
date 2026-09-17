<?php

namespace App\Http\Resources\V1;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lead
 */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'city' => $this->city,
            'requirement' => $this->requirement,
            'message' => $this->message,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'owner' => $this->whenLoaded('assignee', fn () => $this->assignee ? ['id' => $this->assignee->id, 'name' => $this->assignee->name] : null),
            'form' => $this->whenLoaded('form', fn () => $this->form?->key),
            'product' => $this->whenLoaded('product', fn () => $this->product ? ['id' => $this->product->id, 'slug' => $this->product->slug, 'name' => $this->product->name] : null),
            'service' => $this->whenLoaded('service', fn () => $this->service ? ['id' => $this->service->id, 'slug' => $this->service->slug, 'name' => $this->service->name] : null),
            'attribution' => [
                'first' => ['source' => $this->first_source, 'medium' => $this->first_medium, 'campaign' => $this->first_campaign, 'landing_page' => $this->first_landing_page],
                'last' => ['source' => $this->last_source, 'medium' => $this->last_medium, 'campaign' => $this->last_campaign, 'landing_page' => $this->last_landing_page],
            ],
            'submitted_from_url' => $this->submitted_from_url,
            'consent_given_at' => $this->consent_given_at?->toIso8601String(),
            'duplicate_of_lead_id' => $this->duplicate_of_lead_id,
            'timeline' => $this->whenLoaded('activities', fn () => $this->activities->map(fn ($a) => ['at' => $a->created_at?->toIso8601String(), 'type' => $a->type->value, 'by' => $a->user?->name, 'body' => $a->body, 'properties' => $a->properties])),
        ];
    }
}
