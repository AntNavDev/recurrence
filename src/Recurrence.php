<?php

declare(strict_types=1);

namespace AntNavDev\Recurrence;

use Carbon\Carbon;

/**
 * Evaluates whether a recurring rule string is due on a given date.
 *
 * Supported rule formats:
 *   null / "daily"         → every day
 *   "weekly:mon,wed,fri"   → specific days of the week (3-letter lowercase, comma-separated)
 *   "biweekly:mon"         → every other week on that day, anchored to $anchorDate
 *   "monthly:15"           → the 15th of each month
 *   "monthly:last"         → the last day of each month
 *   "interval:N"           → every N days from $anchorDate (N < 1 is never due)
 */
class Recurrence
{
    /**
     * @param string|null $rule       The recurrence rule string. Null is treated as "daily".
     * @param Carbon|null $anchorDate Required for "biweekly" and "interval" rules.
     */
    public function __construct(
        private readonly ?string $rule,
        private readonly ?Carbon $anchorDate = null,
    ) {}

    /**
     * Determine whether this recurrence is due on the given date.
     *
     * @throws InvalidRuleException if the rule string cannot be parsed.
     * @throws \LogicException      if the rule requires an anchor date but none was provided.
     */
    public function isDueOn(Carbon $date): bool
    {
        $rule = $this->rule ?? 'daily';

        if ($rule === 'daily') {
            return true;
        }

        if (str_starts_with($rule, 'weekly:')) {
            $days = explode(',', substr($rule, 7));

            return in_array(strtolower($date->format('D')), $days, true);
        }

        if (str_starts_with($rule, 'biweekly:')) {
            $days = explode(',', substr($rule, 9));

            if (! in_array(strtolower($date->format('D')), $days, true)) {
                return false;
            }

            // Count full weeks between the anchor's Monday and $date's Monday.
            $anchor = $this->requireAnchor('biweekly');
            $anchorWeek = $anchor->copy()->startOfWeek();
            $targetWeek = $date->copy()->startOfWeek();
            $weeks = (int) $anchorWeek->diffInWeeks($targetWeek);

            return $weeks % 2 === 0;
        }

        if (str_starts_with($rule, 'monthly:')) {
            $part = substr($rule, 8);

            if ($part === 'last') {
                return $date->isLastOfMonth();
            }

            if (ctype_digit($part)) {
                return $date->day === (int) $part;
            }

            throw new InvalidRuleException('Invalid monthly rule: "' . $rule . '".');
        }

        if (str_starts_with($rule, 'interval:')) {
            $n = (int) substr($rule, 9);

            if ($n < 1) {
                return false;
            }

            $anchor = $this->requireAnchor('interval');
            $diff = (int) $anchor->copy()->startOfDay()->diffInDays($date->copy()->startOfDay());

            return $diff % $n === 0;
        }

        throw new InvalidRuleException('Unrecognized recurrence rule: "' . $rule . '".');
    }

    /**
     * Return the anchor date or throw if it was not provided.
     *
     * @throws \LogicException
     */
    private function requireAnchor(string $ruleType): Carbon
    {
        if ($this->anchorDate === null) {
            throw new \LogicException(
                'An anchor date is required for "' . $ruleType . '" rules but none was provided.'
            );
        }

        return $this->anchorDate;
    }
}