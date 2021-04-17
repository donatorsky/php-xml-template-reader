<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class GreaterThan implements RuleInterface
{
    private float $threshold;

    public function __construct(string $threshold)
    {
        $this->threshold = (float) $threshold;
    }

    public function passes($value): bool
    {
        return \is_numeric($value) && $value > $this->threshold;
    }

    public function process($value): int
    {
        return $value;
    }
}
