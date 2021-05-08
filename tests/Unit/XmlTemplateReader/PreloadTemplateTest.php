<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use Exception;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::preloadTemplate
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::isPreloaded
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::getNamespace
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::addListenersFromTemplate
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class PreloadTemplateTest extends AbstractXmlTemplateReaderTest
{
    use ProphecyTrait;

    public function testFailToConstructWithInvalidXmlTemplateSyntax(): void
    {
        $xmlTemplateReader = new XmlTemplateReader('<wrong-xml');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('String could not be parsed as XML');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testFailToConstructWithNoXmlTemplateNamespace(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template>
    <root />
</template>
XML
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 0 provided');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testFailToConstructWithMoreThanOneXmlTemplateNamespace(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd"
          xmlns:tpl2="http://www.w3.org/2001/XMLSchema-instance"
          tpl2:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd">
    <root tpl:attribute1=""
          tpl2:attribute2="" />
</template>
XML
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 2 provided');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testFailToConstructWithOneXmlTemplateNamespaceWithoutSchemaLocation(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance">
    <root tpl:attribute="" />
</template>
XML
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 0 provided');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testFailToConstructFromXmlTemplateWithUnknownRule(): void
    {
        $xmlTemplateReader = new XmlTemplateReader(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd">
    <root tpl:attribute="" id="nonExistentRule" />
</template>
XML
        );

        $this->expectException(UnknownRuleException::class);
        $this->expectExceptionMessage('The rule "nonExistentRule" is unknown');

        $xmlTemplateReader->preloadTemplate();
    }

    public function testSuccessfullyPreloadTemplate(): void
    {
        /** @var EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy $eventDispatcherProphecy */
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);

        $xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE, $eventDispatcherProphecy->reveal());

        $eventDispatcherProphecy->addListener(Argument::containingString('open@'), Argument::type('callable'))
            ->shouldBeCalledOnce();

        $eventDispatcherProphecy->addListener(Argument::containingString('cdata@'), Argument::type('callable'))
            ->shouldBeCalledOnce();

        $eventDispatcherProphecy->addListener(Argument::containingString('close@'), Argument::type('callable'))
            ->shouldBeCalledOnce();

        self::assertFalse($xmlTemplateReader->isPreloaded());
        self::assertSame($xmlTemplateReader, $xmlTemplateReader->preloadTemplate());
        self::assertTrue($xmlTemplateReader->isPreloaded());
        self::assertSame('tpl', $xmlTemplateReader->getNamespace());

        // Second call should be cached
        self::assertSame($xmlTemplateReader, $xmlTemplateReader->preloadTemplate());
        self::assertTrue($xmlTemplateReader->isPreloaded());
    }
}
