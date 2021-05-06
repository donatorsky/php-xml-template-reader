<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

class IntegerNumber extends Numeric
{
    public function passes($value): bool
    {
        return parent::passes($value) && false !== filter_var($value, FILTER_VALIDATE_INT);
    }

    public function process($value): int
    {
        return (int) parent::process($value);
    }
}
