<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models\Contracts;

use Donatorsky\XmlTemplate\Reader\Models\Collection;
use Donatorsky\XmlTemplate\Reader\Models\Map;
use JetBrains\PhpStorm\ArrayShape;

interface NodeInterface
{
    /**
     * @return array<string,mixed>
     */
    #[ArrayShape(['node_name' => 'string', 'contents' => 'null|string', 'attributes' => 'array', 'relations' => 'array[]', 'children' => 'array[]'])]
    public function toArray(): array;

    /**
     * @return Map<mixed>
     */
    public function getAttributes(): Map;

    public function getContents(): ?string;

    public function setContents(?string $contents): self;

    public function hasContents(): bool;

    public function getParent(): ?self;

    public function setParent(?self $parent): self;

    public function hasParent(): bool;

    /**
     * @return Map<NodeInterface>
     */
    public function getRelations(): Map;

    /**
     * @return Map<Collection<NodeInterface>>
     */
    public function getChildren(): Map;
}
