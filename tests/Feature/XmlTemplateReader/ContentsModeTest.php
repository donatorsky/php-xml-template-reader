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
class ContentsModeTest extends AbstractXmlTemplateReaderTest
{
    private const XML_VALID = 'configuration-contents-valid';

    private const XML_INVALID = 'configuration-contents-invalid';

    public function testRelationsWereRead(): Map
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_VALID));

        $node = $xmlTemplateReader->read(self::getDataXml(self::XML_VALID));

        $relationsMap = $node->getRelations();
        self::assertTrue($relationsMap->has('none'));
        self::assertTrue($relationsMap->has('raw'));
        self::assertTrue($relationsMap->has('trimmed'));
        self::assertTrue($relationsMap->has('noneWithCData'));
        self::assertTrue($relationsMap->has('rawWithCData'));
        self::assertTrue($relationsMap->has('trimmedWithCData'));

        return $relationsMap;
    }

    /**
     * @depends testRelationsWereRead
     *
     * @param \Donatorsky\XmlTemplate\Reader\Models\Map<\Donatorsky\XmlTemplate\Reader\Models\Node> $relationsMap
     */
    public function testContentsNone(Map $relationsMap): void
    {
        $noneNode = $relationsMap->get('none');
        $noneWithCDataNode = $relationsMap->get('noneWithCData');

        self::assertNull($noneNode->getContents());
        self::assertNull($noneWithCDataNode->getContents());
    }

    /**
     * @depends testRelationsWereRead
     *
     * @param \Donatorsky\XmlTemplate\Reader\Models\Map<\Donatorsky\XmlTemplate\Reader\Models\Node> $relationsMap
     */
    public function testContentsRaw(Map $relationsMap): void
    {
        $rawNode = $relationsMap->get('raw');
        $rawWithCDataNode = $relationsMap->get('rawWithCData');

        self::assertSame('
        Contents of: raw
    ', $rawNode->getContents());

        self::assertSame('
 Contents "of" & <raw>
        ', $rawWithCDataNode->getContents());
    }

    /**
     * @depends testRelationsWereRead
     *
     * @param \Donatorsky\XmlTemplate\Reader\Models\Map<\Donatorsky\XmlTemplate\Reader\Models\Node> $relationsMap
     */
    public function testContentsTrimmed(Map $relationsMap): void
    {
        $trimmedNode = $relationsMap->get('trimmed');
        $trimmedWithCDataNode = $relationsMap->get('trimmedWithCData');

        self::assertSame('Contents of: trimmed', $trimmedNode->getContents());
        self::assertSame('Contents "of" & <trimmed>', $trimmedWithCDataNode->getContents());
    }

    public function testFailsForInvalidValue(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_INVALID));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "root/invalid" node\'s tpl:contents attribute value "invalid value" is invalid, expecting one of: none, raw, trimmed');

        ($xmlTemplateReader)->preloadTemplate();
    }
}
