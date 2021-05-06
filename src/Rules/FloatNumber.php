<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

class FloatNumber extends Numeric
{
    public function process($value): float
    {
        return (float) parent::process($value);
    }
}
