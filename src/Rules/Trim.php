<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Trim implements RuleInterface
{
    public function passes($value): bool
    {
        return \is_string($value);
    }

    public function process($value): string
    {
        return \trim((string) $value);
    }
}
