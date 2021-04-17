<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\ContextAwareRuleInterface;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;

class Callback implements RuleInterface, ContextAwareRuleInterface
{
    private string $validateWith;

    private string $processWith;

    /**
     * @var mixed[]
     */
    private array $parameters;

    private NodeInterface $context;

    /**
     * @param mixed ...$parameters
     */
    public function __construct(
        string $validateWith,
        string $processWith,
        ...$parameters
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

    public function withContext(NodeInterface $context): void
    {
        $this->context = $context;
    }
}
