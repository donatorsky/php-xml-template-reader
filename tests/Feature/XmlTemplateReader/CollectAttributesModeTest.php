<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Models\Map;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CollectAttributesModeTest extends AbstractXmlTemplateReaderTest
{
    private const XML_VALID = 'configuration-collect-attributes-valid';

    private const XML_INVALID = 'configuration-collect-attributes-invalid';

    public function testRelationsWereRead(): Map
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_VALID));

        $node = $xmlTemplateReader->read(self::getDataXml(self::XML_VALID));

        $relationsMap = $node->getRelations();
        self::assertTrue($relationsMap->has('all'));
        self::assertTrue($relationsMap->has('validated'));

        return $relationsMap;
    }

    /**
     * @depends testRelationsWereRead
     *
     * @param \Donatorsky\XmlTemplate\Reader\Models\Map<\Donatorsky\XmlTemplate\Reader\Models\Node> $relationsMap
     */
    public function testCollectAll(Map $relationsMap): void
    {
        $attributesMap = $relationsMap->get('all')->getAttributes();

        self::assertCount(2, $attributesMap);
        self::assertArrayHasKey('validatedAttribute', $attributesMap);
        self::assertSame('value 1', $attributesMap['validatedAttribute']);
        self::assertArrayHasKey('otherAttribute', $attributesMap);
        self::assertSame('other 1', $attributesMap['otherAttribute']);
    }

    /**
     * @depends testRelationsWereRead
     *
     * @param \Donatorsky\XmlTemplate\Reader\Models\Map<\Donatorsky\XmlTemplate\Reader\Models\Node> $relationsMap
     */
    public function testCollectValidated(Map $relationsMap): void
    {
        $attributesMap = $relationsMap->get('validated')->getAttributes();

        self::assertCount(1, $attributesMap);
        self::assertArrayHasKey('validatedAttribute', $attributesMap);
        self::assertSame('value 2', $attributesMap['validatedAttribute']);
        self::assertArrayNotHasKey('otherAttribute', $attributesMap);
    }

    public function testFailsForInvalidValue(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_INVALID));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "root/invalid" node\'s tpl:collectAttributes attribute value "invalid value" is invalid, expecting one of: all, validated');

        $xmlTemplateReader->preloadTemplate();
    }
}
