<?php

namespace App\Automation;

use App\Enums\AutomationRunStatus;
use App\Enums\AutomationTrigger;
use App\Jobs\RetryAutomationRun;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Lead;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Evaluates active rules for a trigger and subject. Each (rule, subject, occurrence) gets exactly
 * one run row keyed by a unique idempotency key: duplicate events and retries never act twice.
 * Failures are recorded, never thrown to the caller, and retried by the queued job (attempts).
 */
class AutomationEngine
{
    public const int MAX_ATTEMPTS = 3;

    public function __construct(private readonly ConditionMatcher $matcher, private readonly ActionRunner $actions) {}

    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, AutomationRun>
     */
    public function handle(AutomationTrigger $trigger, Lead $lead, string $occurrence, array $context = []): Collection
    {
        $runs = collect();

        foreach (AutomationRule::query()->active()->forTrigger($trigger)->get() as $rule) {
            $run = $this->claim($rule, $lead, "{$rule->id}:lead:{$lead->id}:{$trigger->value}:{$occurrence}");

            if ($run === null) {
                continue;
            }

            $runs->push($this->execute($run, $rule, $lead->refresh(), $context));
        }

        return $runs;
    }

    /**
     * Re-runs a failed run that still has attempts left.
     */
    public function retry(AutomationRun $run): AutomationRun
    {
        $lead = Lead::query()->find($run->subject_id);
        $rule = $run->rule;

        if ($lead === null || $rule === null || ! $rule->is_active) {
            $run->fill(['status' => AutomationRunStatus::Skipped, 'error' => 'subject or rule no longer available', 'finished_at' => now()])->save();

            return $run;
        }

        return $this->execute($run, $rule, $lead, $run->result['context'] ?? []);
    }

    protected function claim(AutomationRule $rule, Lead $lead, string $key): ?AutomationRun
    {
        try {
            return AutomationRun::query()->create([
                'automation_rule_id' => $rule->id,
                'subject_type' => $lead->getMorphClass(),
                'subject_id' => $lead->id,
                'idempotency_key' => mb_substr($key, 0, 160),
                'status' => AutomationRunStatus::Pending,
                'attempts' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function execute(AutomationRun $run, AutomationRule $rule, Lead $lead, array $context): AutomationRun
    {
        $run->fill(['attempts' => $run->attempts + 1, 'started_at' => now()])->save();

        try {
            if (! $this->matcher->matches($lead, RuleVocabulary::validateConditions($rule->conditions), $context)) {
                $run->fill(['status' => AutomationRunStatus::Skipped, 'result' => ['reason' => 'conditions not met', 'context' => $context], 'finished_at' => now()])->save();

                return $run;
            }

            $results = [];

            foreach (RuleVocabulary::validateActions($rule->actions) as $action) {
                $results[] = $this->actions->run($lead, $action, $run->idempotency_key);
                $lead->refresh();
            }

            $run->fill(['status' => AutomationRunStatus::Succeeded, 'result' => ['actions' => $results, 'context' => $context], 'error' => null, 'finished_at' => now()])->save();
            $rule->forceFill(['run_count' => $rule->run_count + 1, 'last_run_at' => now()])->saveQuietly();
        } catch (Throwable $exception) {
            report($exception);
            $run->fill(['status' => AutomationRunStatus::Failed, 'error' => mb_substr($exception->getMessage(), 0, 500), 'result' => ['context' => $context], 'finished_at' => now()])->save();

            if ($run->attempts < self::MAX_ATTEMPTS) {
                RetryAutomationRun::dispatch($run->id)->delay(now()->addSeconds(30 * $run->attempts));
            }
        }

        return $run;
    }
}
