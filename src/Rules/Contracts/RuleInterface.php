<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules\Contracts;

interface RuleInterface
{
    /**
     * @param mixed $value
     */
    public function passes($value): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function process($value);
}
