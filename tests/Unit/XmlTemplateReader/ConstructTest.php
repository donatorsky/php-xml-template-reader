<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::__construct
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::getEventDispatcher
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ConstructTest extends AbstractXmlTemplateReaderTest
{
    public function testCanBeConstructedWithCustomDispatcher(): void
    {
        $eventDispatcher = new EventDispatcher();

        $xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE, $eventDispatcher);

        self::assertSame($eventDispatcher, $xmlTemplateReader->getEventDispatcher());
    }

    public function testCanBeConstructedWithDefaultDispatcher(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE);

        self::assertInstanceOf(EventDispatcher::class, $xmlTemplateReader->getEventDispatcher());
    }
}
