# anthony/recurrence

Evaluate whether a recurring rule string is due on a given date. Pure PHP 8.2+, no framework dependencies.

## Installation

```bash
composer require anthony/recurrence
```

## Usage

```php
use Anthony\Recurrence\Recurrence;
use Carbon\Carbon;

$recurrence = new Recurrence('weekly:mon,wed,fri');
$recurrence->isDueOn(Carbon::parse('2026-04-13')); // Monday → true
$recurrence->isDueOn(Carbon::parse('2026-04-14')); // Tuesday → false
```

For rules that require an anchor date (`biweekly` and `interval`), pass it as the second constructor argument:

```php
$recurrence = new Recurrence('biweekly:mon', anchorDate: Carbon::parse('2025-01-06'));
$recurrence->isDueOn(Carbon::parse('2025-01-06')); // week 0 → true
$recurrence->isDueOn(Carbon::parse('2025-01-13')); // week 1 → false
$recurrence->isDueOn(Carbon::parse('2025-01-20')); // week 2 → true

$recurrence = new Recurrence('interval:7', anchorDate: Carbon::parse('2025-01-01'));
$recurrence->isDueOn(Carbon::parse('2025-01-01')); // day 0 → true
$recurrence->isDueOn(Carbon::parse('2025-01-08')); // day 7 → true
$recurrence->isDueOn(Carbon::parse('2025-01-09')); // day 8 → false
```

## Rule format

| Rule string           | Meaning                                              |
|-----------------------|------------------------------------------------------|
| `null`                | Every day (same as `daily`)                          |
| `daily`               | Every day                                            |
| `weekly:mon,wed,fri`  | Specific days of the week (lowercase 3-letter codes) |
| `biweekly:mon`        | Every other week on that day, anchored to `anchorDate` |
| `monthly:15`          | The 15th of each month                               |
| `monthly:last`        | The last day of each month                           |
| `interval:N`          | Every N days from `anchorDate` (N < 1 is never due)  |

Valid day-of-week codes: `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`.

## Exceptions

- `Anthony\Recurrence\InvalidRuleException` (extends `\InvalidArgumentException`) — thrown when the rule string cannot be parsed (e.g. `whenever:tuesday`, `monthly:foo`).
- `\LogicException` — thrown when `isDueOn()` is called with a `biweekly` or `interval` rule but no `anchorDate` was provided.

## Running tests

```bash
composer install
./vendor/bin/phpunit
```