<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\ContextAwareRuleInterface;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Callback implements RuleInterface, ContextAwareRuleInterface
{
    private string $validateWith;

    private string $processWith;

    private array $parameters;

    private Node $context;

    public function __construct(
        string $validateWith,
        string $processWith,
        array $parameters = []
    ) {
        $this->validateWith = $validateWith;
        $this->processWith = $processWith;
        $this->parameters = $parameters;
    }

    public function passes($value): bool
    {
        return \call_user_func([$this->context, $this->validateWith], $value, ...$this->parameters);
    }

    public function process($value)
    {
        return \call_user_func([$this->context, $this->processWith], $value, ...$this->parameters);
    }

    public function withContext(Node $context): void
    {
        $this->context = $context;
    }
}
