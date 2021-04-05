<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Events;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;

class TagClosed
{
    private NodeInterface $currentNodeValueObject;

    private string $nodeName;

    public function __construct(
        NodeInterface $currentNodeValueObject,
        string $nodeName
    ) {
        $this->currentNodeValueObject = $currentNodeValueObject;
        $this->nodeName = $nodeName;
    }

    public function getCurrentNodeValueObject(): NodeInterface
    {
        return $this->currentNodeValueObject;
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }
}
