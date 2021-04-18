<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Exceptions;

use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use RuntimeException;
use Throwable;

class RuleValidationFailedException extends RuntimeException implements XmlTemplateReaderException
{
    private string $attributeName;

    /**
     * @var mixed
     */
    private $attributeValue;

    /**
     * @var mixed
     */
    private $attributeOriginalValue;

    private string $fullNodePath;

    private RuleInterface $rule;

    /**
     * @param mixed $attributeValue
     * @param mixed $attributeOriginalValue
     */
    public function __construct(
        string $attributeName,
        $attributeValue,
        $attributeOriginalValue,
        string $fullNodePath,
        RuleInterface $rule,
        ?Throwable $previous = null
    ) {
        parent::__construct(\sprintf(
            'Value "%s" of attribute "%s" in node "%s" does not pass %s rule',
            $attributeValue,
            $attributeName,
            $fullNodePath,
            \get_class($rule),
        ), 0, $previous);

        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
        $this->attributeOriginalValue = $attributeOriginalValue;
        $this->fullNodePath = $fullNodePath;
        $this->rule = $rule;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * Returns the current (i.e. including all transformations already performed) value that failed the transformation.
     *
     * @return mixed
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    /**
     * Returns the value of the attribute before transformation started.
     *
     * @return mixed
     */
    public function getAttributeOriginalValue()
    {
        return $this->attributeOriginalValue;
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
