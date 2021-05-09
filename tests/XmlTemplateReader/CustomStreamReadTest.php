<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Exceptions\XmlParsingFailedException;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::open
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::update
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::close
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::isOpened
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CustomStreamReadTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));
    }

    public function testFailToOpenStreamWhenItIsAlreadyOpened(): void
    {
        self::assertFalse($this->xmlTemplateReader->isOpened());

        $this->xmlTemplateReader->open();
        self::assertTrue($this->xmlTemplateReader->isOpened());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reading is already in progress');

        $this->xmlTemplateReader->open();
    }

    public function testFailToUpdateStreamWhenWhenItWasNotOpened(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Streamed reading has not been started yet, ::open() it first.');

        $this->xmlTemplateReader->update('<root></root>');
    }

    public function testFailToCloseStreamedReadingWhenItWasNotOpened(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Streamed reading has not been started yet, ::open() it first.');

        $this->xmlTemplateReader->close();
    }

    public function testFailToCloseStreamedReadingWhenThereAreStillSomeNodesOpened(): void
    {
        $this->xmlTemplateReader->open()->update('<root>');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Streamed reading has not been finished yet, there are still 1 node(s) opened.');

        $this->xmlTemplateReader->close();
    }

    public function testFailToUpdateStreamWithInvalidXml(): void
    {
        $this->expectException(XmlParsingFailedException::class);
        $this->expectExceptionMessage('XML parsing failed: Not well-formed (invalid token)');

        $this->xmlTemplateReader->open()->update(__METHOD__);
    }

    public function testSuccessfullyReadXmlData(): void
    {
        self::assertFalse($this->xmlTemplateReader->isOpened());

        $this->xmlTemplateReader->open();
        self::assertTrue($this->xmlTemplateReader->isOpened());

        foreach (self::chunkXmlContentsIncreasingly() as $chunk) {
            self::assertTrue($this->xmlTemplateReader->update($chunk)->isOpened());
        }

        self::assertInstanceOf(Node::class, $this->xmlTemplateReader->close());
    }

    private static function chunkXmlContentsIncreasingly(): iterable
    {
        $data = self::getDataXml(self::XML_CORRECT);

        $currentChunkLength = 0;
        $currentChunkMaxLength = 0;
        $buffer = '';

        for ($index = 0, $indexMax = \strlen($data); $index < $indexMax; ++$index) {
            if ($currentChunkLength === $currentChunkMaxLength) {
                yield $buffer;

                $buffer = '';
                $currentChunkLength = 0;
                ++$currentChunkMaxLength;
            }

            $buffer .= $data[$index];

            ++$currentChunkLength;
        }

        if ('' !== $buffer) {
            yield $buffer;
        }
    }
}
