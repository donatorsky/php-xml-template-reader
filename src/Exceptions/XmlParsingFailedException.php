<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Exceptions;

use RuntimeException;

class XmlParsingFailedException extends RuntimeException implements XmlTemplateReaderException
{
    private int $currentLineNumber;

    private int $currentColumnNumber;

    private int $currentByteIndex;

    public function __construct(
        int $errorCode,
        string $errorString,
        int $currentLineNumber,
        int $currentColumnNumber,
        int $currentByteIndex
    ) {
        parent::__construct(sprintf('XML parsing failed: %s', $errorString), $errorCode);

        $this->currentLineNumber = $currentLineNumber;
        $this->currentColumnNumber = $currentColumnNumber;
        $this->currentByteIndex = $currentByteIndex;
    }

    public function getCurrentLineNumber(): int
    {
        return $this->currentLineNumber;
    }

    public function getCurrentColumnNumber(): int
    {
        return $this->currentColumnNumber;
    }

    public function getCurrentByteIndex(): int
    {
        return $this->currentByteIndex;
    }
}
