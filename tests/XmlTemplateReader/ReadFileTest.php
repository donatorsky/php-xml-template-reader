<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::readFile
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadFileTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    public function testSuccessfullyReadXmlFromFile(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));

        self::assertInstanceOf(Node::class, $xmlTemplateReader->readFile(self::getXmlPath(self::XML_CORRECT, 'data')));
    }
}
