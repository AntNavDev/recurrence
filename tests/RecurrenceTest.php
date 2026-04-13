<?php

declare(strict_types=1);

namespace Anthony\Recurrence\Tests;

use Anthony\Recurrence\InvalidRuleException;
use Anthony\Recurrence\Recurrence;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class RecurrenceTest extends TestCase
{
    // ─── null / daily ────────────────────────────────────────────────────────

    public function test_null_rule_is_due_every_day(): void
    {
        $r = new Recurrence(null);

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-03-15')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-12-31')));
    }

    public function test_daily_rule_is_due_every_day(): void
    {
        $r = new Recurrence('daily');

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-06-15')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-11-01')));
    }

    // ─── weekly ──────────────────────────────────────────────────────────────

    public function test_weekly_fires_on_specified_days(): void
    {
        $r = new Recurrence('weekly:mon,wed,fri');

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-03-31')));  // Monday
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-04-02')));  // Wednesday
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-04-04')));  // Friday
    }

    public function test_weekly_does_not_fire_on_unspecified_days(): void
    {
        $r = new Recurrence('weekly:mon,wed,fri');

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-04-01')));  // Tuesday
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-04-03')));  // Thursday
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-04-05')));  // Saturday
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-04-06')));  // Sunday
    }

    public function test_weekly_single_day(): void
    {
        $r = new Recurrence('weekly:sat');

        $this->assertTrue($r->isDueOn(Carbon::parse('2026-04-11')));   // Saturday
        $this->assertFalse($r->isDueOn(Carbon::parse('2026-04-12')));  // Sunday
    }

    // ─── biweekly ────────────────────────────────────────────────────────────

    public function test_biweekly_fires_on_anchor_week_and_every_other_week(): void
    {
        // Jan 6, 2025 is a Monday
        $r = new Recurrence('biweekly:mon', anchorDate: Carbon::parse('2025-01-06'));

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-06')));   // week 0 → due
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-13')));  // week 1 → skip
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-20')));   // week 2 → due
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-27')));  // week 3 → skip
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-02-03')));   // week 4 → due
    }

    public function test_biweekly_does_not_fire_on_wrong_day_of_week(): void
    {
        $r = new Recurrence('biweekly:mon', anchorDate: Carbon::parse('2025-01-06'));

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-07')));  // Tuesday, anchor week
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-14')));  // Tuesday, off-week
    }

    public function test_biweekly_anchor_mid_week_still_uses_week_boundary(): void
    {
        // Anchor is a Wednesday — the week boundary (Monday) is still used for even/odd calc
        $r = new Recurrence('biweekly:wed', anchorDate: Carbon::parse('2025-01-08'));  // Wednesday

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-08')));   // week 0 → due
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-15')));  // week 1 → skip
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-22')));   // week 2 → due
    }

    public function test_biweekly_throws_logic_exception_without_anchor(): void
    {
        $r = new Recurrence('biweekly:mon');

        $this->expectException(\LogicException::class);
        $r->isDueOn(Carbon::parse('2025-01-06'));
    }

    // ─── monthly ─────────────────────────────────────────────────────────────

    public function test_monthly_fires_on_specific_day(): void
    {
        $r = new Recurrence('monthly:15');

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-15')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-06-15')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-12-15')));
    }

    public function test_monthly_does_not_fire_on_other_days(): void
    {
        $r = new Recurrence('monthly:15');

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-14')));
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-16')));
    }

    public function test_monthly_last_fires_on_last_day_of_each_month(): void
    {
        $r = new Recurrence('monthly:last');

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-31')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-02-28')));  // non-leap year
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-04-30')));
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-12-31')));
    }

    public function test_monthly_last_fires_on_feb_29_in_leap_year(): void
    {
        $r = new Recurrence('monthly:last');

        $this->assertTrue($r->isDueOn(Carbon::parse('2024-02-29')));   // leap year
        $this->assertFalse($r->isDueOn(Carbon::parse('2024-02-28')));  // not the last day in a leap year
    }

    public function test_monthly_last_does_not_fire_before_last_day(): void
    {
        $r = new Recurrence('monthly:last');

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-30')));
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-02-27')));
    }

    public function test_monthly_invalid_part_throws(): void
    {
        $r = new Recurrence('monthly:foo');

        $this->expectException(InvalidRuleException::class);
        $r->isDueOn(Carbon::parse('2025-01-15'));
    }

    // ─── interval ────────────────────────────────────────────────────────────

    public function test_interval_fires_every_n_days_from_anchor(): void
    {
        $r = new Recurrence('interval:7', anchorDate: Carbon::parse('2025-01-01'));

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-01')));   // day 0
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-06')));  // day 5
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-08')));   // day 7
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-14')));  // day 13
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-15')));   // day 14
    }

    public function test_interval_fires_across_month_boundary(): void
    {
        $r = new Recurrence('interval:10', anchorDate: Carbon::parse('2025-01-05'));

        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-05')));   // day 0
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-10')));  // day 5
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-15')));   // day 10
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-01-25')));   // day 20
        $this->assertTrue($r->isDueOn(Carbon::parse('2025-02-04')));   // day 30
    }

    public function test_interval_zero_is_never_due(): void
    {
        $r = new Recurrence('interval:0', anchorDate: Carbon::parse('2025-01-01'));

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-01')));
        $this->assertFalse($r->isDueOn(Carbon::parse('2025-06-15')));
    }

    public function test_interval_negative_is_never_due(): void
    {
        $r = new Recurrence('interval:-3', anchorDate: Carbon::parse('2025-01-01'));

        $this->assertFalse($r->isDueOn(Carbon::parse('2025-01-01')));
    }

    public function test_interval_throws_logic_exception_without_anchor(): void
    {
        $r = new Recurrence('interval:7');

        $this->expectException(\LogicException::class);
        $r->isDueOn(Carbon::parse('2025-01-08'));
    }

    // ─── invalid rules ───────────────────────────────────────────────────────

    public function test_unrecognized_rule_throws_invalid_rule_exception(): void
    {
        $r = new Recurrence('whenever:tuesday');

        $this->expectException(InvalidRuleException::class);
        $r->isDueOn(Carbon::parse('2025-01-01'));
    }

    public function test_invalid_rule_exception_extends_invalid_argument_exception(): void
    {
        $this->assertInstanceOf(
            \InvalidArgumentException::class,
            new InvalidRuleException('test'),
        );
    }
}