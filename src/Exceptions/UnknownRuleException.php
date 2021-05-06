<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Exceptions;

use RuntimeException;
use Throwable;

class UnknownRuleException extends RuntimeException implements XmlTemplateReaderException
{
    /**
     * @var mixed
     */
    private $ruleName;

    /**
     * @param mixed $ruleName
     */
    public function __construct($ruleName, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('The rule "%s" is unknown', $ruleName), 0, $previous);

        $this->ruleName = $ruleName;
    }

    /**
     * @return mixed
     */
    public function getRuleName()
    {
        return $this->ruleName;
    }
}
