<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Events;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;

class CDataRead
{
    private NodeInterface $currentNodeValueObject;

    private ?string $contents;

    public function __construct(
        NodeInterface $currentNodeValueObject,
        ?string $contents
    ) {
        $this->currentNodeValueObject = $currentNodeValueObject;
        $this->contents = $contents;
    }

    public function getCurrentNodeValueObject(): NodeInterface
    {
        return $this->currentNodeValueObject;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }
}
