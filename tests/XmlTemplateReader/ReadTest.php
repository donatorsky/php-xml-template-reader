<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::read
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    public function testSuccessfullyReadXmlFromString(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));

        self::assertInstanceOf(Node::class, $xmlTemplateReader->read(self::getDataXml(self::XML_CORRECT)));
    }
}
