<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use JetBrains\PhpStorm\Pure;

class Trim implements RuleInterface
{

    #[Pure]
    public function passes(
        $value
    ): bool {
        return is_string($value);
    }

    #[Pure]
    public function process(
        $value
    ): string {
        return trim((string) $value);
    }
}
