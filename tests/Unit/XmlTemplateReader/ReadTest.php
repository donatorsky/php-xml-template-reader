<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::read
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadTest extends AbstractXmlTemplateReaderTest
{
    public function testSuccessfullyReadXmlFromString(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE);

        self::assertInstanceOf(Node::class, $xmlTemplateReader->read(self::DUMMY_XML_CONTENTS));
    }
}
