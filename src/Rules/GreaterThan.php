<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use JetBrains\PhpStorm\Pure;

class GreaterThan implements RuleInterface
{
    private float $threshold;

    public function __construct(string $threshold)
    {
        $this->threshold = (float) $threshold;
    }

    #[Pure]
    public function passes(
        $value
    ): bool {
        return is_numeric($value) && $value > $this->threshold;
    }

    #[Pure]
    public function process(
        $value
    ): int {
        return $value;
    }
}
