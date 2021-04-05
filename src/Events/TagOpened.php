<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Events;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;

class TagOpened
{
    private NodeInterface $parentNodeValueObject;

    private string $nodeName;

    private array $attributes;

    private string $parentNodeHash;

    public function __construct(
        NodeInterface $parentNodeValueObject,
        string $nodeName,
        array $attributes,
        string $parentNodeHash
    ) {
        $this->parentNodeValueObject = $parentNodeValueObject;
        $this->nodeName = $nodeName;
        $this->attributes = $attributes;
        $this->parentNodeHash = $parentNodeHash;
    }

    public function getParentNodeValueObject(): NodeInterface
    {
        return $this->parentNodeValueObject;
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getParentNodeHash(): string
    {
        return $this->parentNodeHash;
    }
}
