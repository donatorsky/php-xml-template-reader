<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models\Contracts;

use JetBrains\PhpStorm\ArrayShape;

interface NodeInterface
{
    #[ArrayShape(['node_name' => 'string', 'contents' => 'null|string', 'attributes' => 'array', 'relations' => 'array[]', 'children' => 'array[]'])]
    public function toArray(): array;

    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array;

    /**
     * @return mixed
     */
    public function getAttribute(string $name);

    /**
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): self;

    public function removeAttribute(string $name): self;

    public function getContents(): ?string;

    public function setContents(?string $contents): self;

    public function getParent(): ?self;

    public function setParent(?self $parent): self;

    public function addRelation(string $name, self $nodeValueObject): self;

    /**
     * @return array<string,NodeInterface>
     */
    public function getRelations(): array;

    public function addChild(string $name, self $nodeValueObject): self;

    public function noopValidateRule(): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function noopProcessRule($value);
}
