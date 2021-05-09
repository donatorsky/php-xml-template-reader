<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use JetBrains\PhpStorm\ArrayShape;

class Node implements NodeInterface
{
    protected ?string $contents;

    /**
     * @var Map<mixed>
     */
    protected Map $attributes;

    /**
     * @var Map<NodeInterface>
     */
    protected Map $relations;

    /**
     * @var Map<Collection<NodeInterface>>
     */
    protected Map $children;

    private string $nodeName;

    private ?NodeInterface $parent;

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(
        string $nodeName,
        ?NodeInterface $parent = null,
        ?string $contents = null,
        array $attributes = []
    ) {
        $this->nodeName = $nodeName;
        $this->parent = $parent;
        $this->contents = $contents;

        $this->attributes = new Map($attributes);
        $this->relations = new Map();
        $this->children = new Map();
    }

    #[ArrayShape(['node_name' => 'string', 'contents' => 'null|string', 'attributes' => 'array', 'relations' => 'array[]', 'children' => 'array[]'])]
    public function toArray(): array
    {
        return [
            'node_name'  => $this->nodeName,
            'contents'   => $this->contents,
            'attributes' => $this->attributes->toArray(),

            'relations'                                  => array_map(
                static fn (self $nodeValueObject): array => $nodeValueObject->toArray(),
                $this->relations->toArray(),
            ),

            'children'                                          => array_map(
                static fn (Collection $nodeValueObjects): array => array_map(
                    static fn (self $nodeValueObject): array    => $nodeValueObject->toArray(),
                    $nodeValueObjects->toArray(),
                ),
                $this->children->toArray(),
            ),
        ];
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @return Map<mixed>
     */
    public function getAttributes(): Map
    {
        return $this->attributes;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function setContents(?string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    public function appendContents(string $contents): self
    {
        if (null === $this->contents) {
            $this->contents = $contents;
        } else {
            $this->contents .= $contents;
        }

        return $this;
    }

    public function hasContents(): bool
    {
        return null !== $this->contents;
    }

    public function getParent(): ?NodeInterface
    {
        return $this->parent;
    }

    public function setParent(?NodeInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    public function getRelations(): Map
    {
        return $this->relations;
    }

    public function getChildren(): Map
    {
        return $this->children;
    }
}
