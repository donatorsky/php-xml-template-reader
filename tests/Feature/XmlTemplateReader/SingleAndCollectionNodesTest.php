<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Exceptions\UnexpectedMultipleNodeReadException;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class SingleAndCollectionNodesTest extends AbstractXmlTemplateReaderTest
{
    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml('single-and-collection-nodes'));
    }

    public function testSingleNodeAsCollection(): void
    {
        $this->expectException(UnexpectedMultipleNodeReadException::class);
        $this->expectExceptionMessage('The node "root/singleNode" is expected to be a single node, but another was read');

        $this->xmlTemplateReader->read(<<<'XML'
<root>
    <singleNode>1</singleNode>
    <singleNode>2</singleNode>
    <singleNode>3</singleNode>
</root>
XML
        );
    }

    public function testFiltersPass(): void
    {
        $node = $this->xmlTemplateReader->read(<<<'XML'
<root>
    <singleNode>1</singleNode>
    <multipleNode>2</multipleNode>
    <multipleNode>3</multipleNode>
</root>
XML
        );

        $relationsMap = $node->getRelations();
        $childrenMap = $node->getChildren();

        self::assertTrue($relationsMap->has('singleNode'));
        self::assertTrue($childrenMap->has('multipleNode'));
        self::assertCount(2, $childrenMap->get('multipleNode'));
    }
}
