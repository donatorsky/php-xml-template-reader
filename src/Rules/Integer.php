<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Integer implements RuleInterface
{
    public function passes($value): bool
    {
        return \is_numeric($value);
    }

    public function process($value): int
    {
        return (int) $value;
    }
}
