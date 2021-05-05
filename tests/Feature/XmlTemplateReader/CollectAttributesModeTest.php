<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Map;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CollectAttributesModeTest extends AbstractXmlTemplateReaderTest
{
    private const XML = 'configuration-collect-attributes';

    public function testRelationsWereRead(): Map
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML));

        $node = $xmlTemplateReader->read(self::getDataXml(self::XML));

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
}
