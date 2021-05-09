<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

class FloatNumber extends Numeric
{
    /**
     * @param numeric-string|float|int $value
     */
    public function process($value): float
    {
        return parent::process($value);
    }
}
