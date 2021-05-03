<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class BasicTemplateReaderTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    public function testFailToConstructWithInvalidXmlTemplateSyntax(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('String could not be parsed as XML');

        (new XmlTemplateReader())->loadTemplate('<wrong-xml');
    }

    public function testFailToConstructWithNoXmlTemplateNamespace(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 0 provided');

        (new XmlTemplateReader())->loadTemplate(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template>
    <root>
    </root>
</template>
XML
        );
    }

    public function testFailToConstructWithMoreThanOneXmlTemplateNamespace(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 2 provided');

        (new XmlTemplateReader())->loadTemplate(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd"
          xmlns:tpl2="http://www.w3.org/2001/XMLSchema-instance"
          tpl2:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd">
    <root tpl:attribute1=""
          tpl2:attribute2="">
    </root>
</template>
XML
        );
    }

    public function testFailToConstructWithOneXmlTemplateNamespaceWithoutSchemaLocation(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('You need to specify exactly one template namespace, 0 provided');

        (new XmlTemplateReader())->loadTemplate(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance">
    <root tpl:attribute="">
    </root>
</template>
XML
        );
    }

    public function testFailToConstructFromXmlTemplateWithUnknownRule(): void
    {
        $this->expectException(\Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException::class);
        $this->expectExceptionMessage('The rule "nonExistentRule" is unknown');

        (new XmlTemplateReader())->loadTemplate(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd">
    <root tpl:attribute="" id="nonExistentRule">
    </root>
</template>
XML
        );
    }

    public function testCanBeConstructedWithCustomDispatcher(): void
    {
        $eventDispatcher = new EventDispatcher();

        $xmlTemplateReader = new XmlTemplateReader($eventDispatcher);

        $xmlTemplateReader->loadTemplate(self::getTemplateXml(self::XML_CORRECT));

        self::assertSame('tpl', $xmlTemplateReader->getNamespace());
        self::assertSame($eventDispatcher, $xmlTemplateReader->getEventDispatcher());
    }

    public function testCanBeConstructedWithDefaultDispatcher(): XmlTemplateReader
    {
        $xmlTemplateReader = new XmlTemplateReader();

        $xmlTemplateReader->loadTemplate(self::getTemplateXml(self::XML_CORRECT));

        self::assertSame('tpl', $xmlTemplateReader->getNamespace());
        self::assertInstanceOf(EventDispatcher::class, $xmlTemplateReader->getEventDispatcher());

        return $xmlTemplateReader;
    }

    /**
     * @depends clone testCanBeConstructedWithDefaultDispatcher
     */
    public function testFailToOpenWhenAlreadyIsOpened(XmlTemplateReader $xmlTemplateReader): void
    {
        self::assertFalse($xmlTemplateReader->isOpened());

        $xmlTemplateReader->open();

        self::assertTrue($xmlTemplateReader->isOpened());

        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Reading is already in progress');

        $xmlTemplateReader->open();
    }

    /**
     * @depends testCanBeConstructedWithDefaultDispatcher
     */
    public function testXmlCanBeParsedFromString(XmlTemplateReader $xmlTemplateReader): NodeInterface
    {
        $nodeValueObject = $xmlTemplateReader->read(self::getDataXml(self::XML_CORRECT));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    /**
     * @depends testCanBeConstructedWithDefaultDispatcher
     */
    public function testXmlCanBeParsedFromFile(XmlTemplateReader $xmlTemplateReader): NodeInterface
    {
        $nodeValueObject = $xmlTemplateReader->readFile(self::getXmlPath(self::XML_CORRECT, 'data'));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    /**
     * @depends testCanBeConstructedWithDefaultDispatcher
     */
    public function testXmlCanBeParsedFromResource(XmlTemplateReader $xmlTemplateReader): NodeInterface
    {
        $nodeValueObject = $xmlTemplateReader->readStream(\fopen(self::getXmlPath(self::XML_CORRECT, 'data'), 'r'));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    public function chunkSizeDataProvider(): iterable
    {
        for ($chunkSize = 2, $maxChunkSize = \ceil(\sqrt(\filesize(self::getXmlPath(self::XML_CORRECT, 'data')))); $chunkSize <= $maxChunkSize; ++$chunkSize) {
            yield \sprintf('Chunk size = %d', $chunkSize) => [$chunkSize];
        }
    }

    /**
     * @dataProvider chunkSizeDataProvider
     * @depends      testCanBeConstructedWithDefaultDispatcher
     */
    public function testXmlCanBeParsedAsStream(int $chunkSize, XmlTemplateReader $xmlTemplateReader): void
    {
        $nodeValueObject = self::customReadByChunkSize($xmlTemplateReader, $chunkSize);

        self::assertNodeObjectIsComplete($nodeValueObject);
    }

    /**
     * @depends testCanBeConstructedWithDefaultDispatcher
     */
    public function testXmlCanBeParsedByByte(XmlTemplateReader $xmlTemplateReader): NodeInterface
    {
        $nodeValueObject = self::customReadByChunkSize($xmlTemplateReader, 1);

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    /**
     * @depends testXmlCanBeParsedFromString
     * @depends testXmlCanBeParsedFromFile
     * @depends testXmlCanBeParsedFromResource
     * @depends testXmlCanBeParsedByByte
     * @depends testXmlCanBeParsedAsStream
     */
    public function testAllMethodsResultInTheSameOutput(
        NodeInterface $nodeFromString,
        NodeInterface $nodeFromFile,
        NodeInterface $nodeFromResource,
        NodeInterface $nodeFromStream
    ): void {
        self::assertSame($nodeFromString->toArray(), $nodeFromFile->toArray());
        self::assertSame($nodeFromString->toArray(), $nodeFromResource->toArray());
        self::assertSame($nodeFromString->toArray(), $nodeFromStream->toArray());
    }

    private static function assertNodeObjectIsComplete(NodeInterface $nodeValueObject): void
    {
        self::assertInstanceOf(Node::class, $nodeValueObject);
    }

    private static function customReadByChunkSize(XmlTemplateReader $xmlTemplateReader, int $chunkSize): NodeInterface
    {
        $xmlTemplateReader->open();

        $stream = \str_split(self::getDataXml(self::XML_CORRECT), $chunkSize);

        foreach ($stream as $packet) {
            $xmlTemplateReader->update($packet);
        }

        return $xmlTemplateReader->close();
    }
}
