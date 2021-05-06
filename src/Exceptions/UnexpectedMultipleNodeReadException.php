<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Exceptions;

use RuntimeException;
use Throwable;

class UnexpectedMultipleNodeReadException extends RuntimeException implements XmlTemplateReaderException
{
    private string $fullNodePath;

    public function __construct(string $fullNodePath, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'The node "%s" is expected to be a single node, but another was read',
            $fullNodePath,
        ), 0, $previous);

        $this->fullNodePath = $fullNodePath;
    }

    public function getFullNodePath(): string
    {
        return $this->fullNodePath;
    }
}
