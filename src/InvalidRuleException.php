<?php

declare(strict_types=1);

namespace Anthony\Recurrence;

use InvalidArgumentException;

/**
 * Thrown when a recurrence rule string cannot be parsed.
 */
class InvalidRuleException extends InvalidArgumentException {}