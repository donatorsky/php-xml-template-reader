<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CastToModeTest extends AbstractXmlTemplateReaderTest
{
    private const XML_VALID = 'configuration-cast-to-valid';

    private const XML_INVALID_NON_EXISTENT_CLASS = 'configuration-cast-to-invalid-non-existent-class';

    private const XML_INVALID_UNSUPPORTED_CLASS = 'configuration-cast-to-invalid-unsupported-class';

    public function testSingleNodeAsCollection(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_VALID));

        $node = $xmlTemplateReader->read(
            <<<'XML'
<root>
    <casted/>
    <uncasted/>

    <children/>
    <children/>
    <children/>
</root>
XML
        );

        self::assertInstanceOf(Node::class, $node);

        $rootNodeRelationsMap = $node->getRelations();

        self::assertTrue($rootNodeRelationsMap->has('casted'));
        self::assertInstanceOf(CastedRelationNode::class, $rootNodeRelationsMap->get('casted'));

        self::assertTrue($rootNodeRelationsMap->has('uncasted'));
        self::assertInstanceOf(Node::class, $rootNodeRelationsMap->get('casted'));

        $rootNodeChildrenMap = $node->getChildren();

        self::assertTrue($rootNodeChildrenMap->has('children'));

        $children = $rootNodeChildrenMap->get('children');
        self::assertCount(3, $children);

        foreach ($children as $child) {
            self::assertInstanceOf(CastedChildNode::class, $child);
        }
    }

    public function testFailToCastToNonExistentClass(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_INVALID_NON_EXISTENT_CLASS));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "root/nonExistentClass" node\'s tpl:castTo attribute value "\Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader\NonExistentNodeClass" refers to non-existent class FQN');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testFailToCastToUnsupportedClass(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_INVALID_UNSUPPORTED_CLASS));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "root/unsupportedClass" node\'s tpl:castTo attribute value "\stdClass" refers to a class that does not implement "%s" interface', NodeInterface::class));

        $xmlTemplateReader->preloadTemplate();
    }
}

class CastedRelationNode extends Node
{
}

class CastedChildNode extends Node
{
}
