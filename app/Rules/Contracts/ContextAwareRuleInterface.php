<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Rules\Contracts;

use Donatorsky\XmlTemplate\Reader\Models\Node;

interface ContextAwareRuleInterface
{
    public function withContext(Node $context): void;
}
