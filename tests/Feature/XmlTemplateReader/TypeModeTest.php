<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Exceptions\UnexpectedMultipleNodeReadException;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class TypeModeTest extends AbstractXmlTemplateReaderTest
{
    private const XML_VALID = 'configuration-type-valid';

    private const XML_INVALID = 'configuration-type-invalid';

    public function testSingleNodeAsCollection(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_VALID));

        $this->expectException(UnexpectedMultipleNodeReadException::class);
        $this->expectExceptionMessage('The node "root/singleNode" is expected to be a single node, but another was read');

        $xmlTemplateReader->read(
            <<<'XML'
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
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_VALID));

        $node = $xmlTemplateReader->read(
            <<<'XML'
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

    public function testFailsForInvalidMode(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_INVALID));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "root/invalid" node\'s tpl:type attribute value "invalid value" is invalid, expecting one of: single, collection');

        $xmlTemplateReader->preloadTemplate();
    }
}
