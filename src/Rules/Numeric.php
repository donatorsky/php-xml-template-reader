<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Numeric implements RuleInterface
{
    public function passes($value): bool
    {
        return \is_numeric($value);
    }

    /**
     * @param mixed $value
     *
     * @return float|int
     */
    public function process($value)
    {
        return $value + 0;
    }
}
