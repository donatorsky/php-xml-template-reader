<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules\Contracts;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;

interface ContextAwareRuleInterface
{
    public function withContext(NodeInterface $context): void;
}
