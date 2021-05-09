<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use function Safe\fopen as fopen;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader::readStream
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class ReadStreamTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));
    }

    public function testSuccessfullyReadXmlFromStream(): void
    {
        self::assertInstanceOf(Node::class, $this->xmlTemplateReader->readStream(self::getXmlDataHandler()));
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

        $this->xmlTemplateReader->readStream(self::getXmlDataHandler(), $chunkSize);
    }

    /**
     * @return resource
     */
    private static function getXmlDataHandler()
    {
        return fopen(self::getXmlPath(self::XML_CORRECT, 'data'), 'rb');
    }
}
