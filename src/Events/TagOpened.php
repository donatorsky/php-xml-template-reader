<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Events;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;

class TagOpened
{
    private NodeInterface $parentNodeValueObject;

    private string $nodeName;

    /**
     * @var array<string,string>
     */
    private array $attributes;

    private string $parentNodeHash;

    /**
     * @param array<string,string> $attributes
     */
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

    /**
     * @return array<string,string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getParentNodeHash(): string
    {
        return $this->parentNodeHash;
    }
}
