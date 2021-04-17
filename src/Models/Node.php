<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use JetBrains\PhpStorm\ArrayShape;

class Node implements NodeInterface
{
    private string $nodeName;

    private ?NodeInterface $parent;

    private ?string $contents;

    /**
     * @var array<string,mixed>
     */
    private array $attributes;

    /**
     * @var array<string,NodeInterface>
     */
    private array $relations = [];

    /**
     * @var array<string,NodeInterface[]>
     */
    private array $children = [];

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(
        string $nodeName,
        ?self $parent = null,
        ?string $contents = null,
        array $attributes = []
    ) {
        $this->nodeName = $nodeName;
        $this->parent = $parent;
        $this->contents = $contents;
        $this->attributes = $attributes;
    }

    #[ArrayShape(['node_name' => 'string', 'contents' => 'null|string', 'attributes' => 'array', 'relations' => 'array[]', 'children' => 'array[]'])]
    public function toArray(): array
    {
        return [
            'node_name'                                  => $this->nodeName,
            'contents'                                   => $this->contents,
            'attributes'                                 => $this->attributes,
            'relations'                                  => \array_map(
                static fn (self $nodeValueObject): array => $nodeValueObject->toArray(),
                $this->relations,
            ),
            'children'                                       => \array_map(
                static fn (array $nodeValueObjects): array   => \array_map(
                    static fn (self $nodeValueObject): array => $nodeValueObject->toArray(),
                    $nodeValueObjects,
                ),
                $this->children,
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name];
    }

    /**
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function removeAttribute(string $name): self
    {
        unset($this->attributes[$name]);

        return $this;
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

    public function getParent(): ?NodeInterface
    {
        return $this->parent;
    }

    public function setParent(?NodeInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function addRelation(string $name, NodeInterface $nodeValueObject): self
    {
        $this->relations[$name] = $nodeValueObject;

        return $this;
    }

    /**
     * @return array<string,NodeInterface>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    public function addChild(string $name, NodeInterface $nodeValueObject): self
    {
        $this->children[$name] ??= [];

        $this->children[$name][] = $nodeValueObject;

        return $this;
    }

    public function noopValidateRule(): bool
    {
        return true;
    }

    /**
     * @param mixed $value
     */
    public function noopProcessRule($value)
    {
        return $value;
    }
}
