<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::__construct
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::getEventDispatcher
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ConstructTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    public function testCanBeConstructedWithCustomDispatcher(): void
    {
        $eventDispatcher = new EventDispatcher();

        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT), $eventDispatcher);

        self::assertSame($eventDispatcher, $xmlTemplateReader->getEventDispatcher());
    }

    public function testCanBeConstructedWithDefaultDispatcher(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));

        self::assertInstanceOf(EventDispatcher::class, $xmlTemplateReader->getEventDispatcher());
    }
}
