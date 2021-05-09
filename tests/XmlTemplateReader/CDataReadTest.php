<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

class CDataReadTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CDATA = 'cdata';

    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CDATA));

        $this->xmlTemplateReader->preloadTemplate()->open();
    }

    /**
     * Returns the following data XML:
     * ```xml
     * <root>Foo<data>Bar</data></root>
     * ```
     * But chinked into different parts.
     */
    public function xmlChunksDataProvider(): iterable
    {
        yield [
            '<root>Fo',
            'o<data>Bar</data>Baz</root>',
        ];

        yield [
            '<root>F',
            'o',
            'o<data>Bar</data>Baz</root>',
        ];

        yield [
            '<root>Foo<data>Ba',
            'r</data>Baz</root>',
        ];

        yield [
            '<root',
            '>Foo<',
            'data>Ba',
            'r</data>Baz</root>',
        ];

        yield [
            '<root>',
            'F',
            'o',
            'o',
            '<data>',
            'Bar',
            '</data>',
            'Baz',
            '</root>',
        ];
    }

    /**
     * @dataProvider xmlChunksDataProvider
     */
    public function testCDataWasReadProperly1(string ...$chunks): void
    {
        foreach ($chunks as $chunk) {
            $this->xmlTemplateReader->update($chunk);
        }

        self::assertSame([
            'node_name'  => 'root',
            'contents'   => 'FooBaz',
            'attributes' => [],

            'relations' => [
                'data' => [
                    'node_name'  => 'data',
                    'contents'   => 'Bar',
                    'attributes' => [],
                    'relations'  => [],
                    'children'   => [],
                ],
            ],

            'children' => [],
        ], $this->xmlTemplateReader->close()->toArray());
    }
}
