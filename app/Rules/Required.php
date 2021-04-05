<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use JetBrains\PhpStorm\Pure;

class Required implements RuleInterface
{

    #[Pure]
    public function passes(
        $value
    ): bool {
        return !empty($value);
    }

    #[Pure]
    public function process(
        $value
    ) {
        return $value;
    }
}
