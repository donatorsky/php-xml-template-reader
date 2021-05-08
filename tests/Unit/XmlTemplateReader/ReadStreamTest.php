<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::readStream
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadStreamTest extends AbstractXmlTemplateReaderTest
{
    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::DUMMY_TEMPLATE);
    }

    public function testSuccessfullyReadXmlFromStream(): void
    {
        self::assertInstanceOf(Node::class, $this->xmlTemplateReader->readStream(fopen(self::DUMMY_XML_FILE_PATH, 'rb')));
    }

    public function invalidChunkSizeDataProvider(): array
    {
        return [
            'Zero'     => ['chunkSize' => 0],
            'Negative' => ['chunkSize' => -1],
        ];
    }

    /**
     * @dataProvider invalidChunkSizeDataProvider
     */
    public function testFailForInvalidChunkSize(int $chunkSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Provided read chunk size %d must be greater than 0.', $chunkSize));

        $this->xmlTemplateReader->readStream(fopen(self::DUMMY_XML_FILE_PATH, 'rb'), $chunkSize);
    }
}
