<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Exceptions;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Throwable;

class RuleValidationFailedException extends RuntimeException implements XmlTemplateReaderException
{
    private string $attributeName;

    /**
     * @var mixed
     */
    private $attributeValue;

    private string $fullNodePath;

    private RuleInterface $rule;

    /**
     * @param mixed $attributeValue
     */
    #[Pure]
    public function __construct(
        string $attributeName,
        $attributeValue,
        string $fullNodePath,
        RuleInterface $rule,
        ?Throwable $previous = null
    ) {
        parent::__construct(sprintf(
            'Value "%s" of attribute "%s" in node "%s" does not pass %s rule',
            $attributeValue,
            $attributeName,
            $fullNodePath,
            get_class($rule),
        ), 0, $previous);

        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
        $this->fullNodePath = $fullNodePath;
        $this->rule = $rule;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * @return mixed
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    public function getFullNodePath(): string
    {
        return $this->fullNodePath;
    }

    public function getRule(): RuleInterface
    {
        return $this->rule;
    }
}
