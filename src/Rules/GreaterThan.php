<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Assert\Assertion;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class GreaterThan implements RuleInterface
{
    private float $threshold;

    /**
     * @throws \Assert\AssertionFailedException When $threshold is not numeric
     */
    public function __construct(string $threshold)
    {
        Assertion::numeric($threshold);

        $this->threshold = (float) $threshold;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function passes($value): bool
    {
        return \is_numeric($value) && $value > $this->threshold;
    }

    /**
     * @param mixed $value
     *
     * @return float|int
     */
    public function process($value)
    {
        return $value;
    }
}
