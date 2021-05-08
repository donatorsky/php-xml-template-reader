<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::readFile
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadFileTest extends AbstractXmlTemplateReaderTest
{
    public function testSuccessfullyReadXmlFromFile(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE);

        self::assertInstanceOf(Node::class, $xmlTemplateReader->readFile(self::DUMMY_XML_FILE_PATH));
    }
}
