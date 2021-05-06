<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Required implements RuleInterface
{
    public function passes($value): bool
    {
        return !empty($value);
    }

    public function process($value)
    {
        return $value;
    }
}
