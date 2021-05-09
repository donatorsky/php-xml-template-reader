<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class BasicTemplateReaderTest extends AbstractXmlTemplateReaderTest
{
    private const XML_CORRECT = 'correct';

    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml(self::XML_CORRECT));

        $this->xmlTemplateReader->preloadTemplate();

        self::assertFalse($this->xmlTemplateReader->isOpened());
    }

    public function testXmlCanBeParsedFromString(): NodeInterface
    {
        $nodeValueObject = $this->xmlTemplateReader->read(self::getDataXml(self::XML_CORRECT));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    public function testXmlCanBeParsedFromFile(): NodeInterface
    {
        $nodeValueObject = $this->xmlTemplateReader->readFile(self::getXmlPath(self::XML_CORRECT, 'data'));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    public function testXmlCanBeParsedFromResource(): NodeInterface
    {
        $nodeValueObject = $this->xmlTemplateReader->readStream(fopen(self::getXmlPath(self::XML_CORRECT, 'data'), 'rb'));

        self::assertNodeObjectIsComplete($nodeValueObject);

        return $nodeValueObject;
    }

    public function chunkSizeDataProvider(): iterable
    {
        for (
            $chunkSize = 1, $maxChunkSize = ceil(sqrt(filesize(self::getXmlPath(self::XML_CORRECT, 'data'))));
            $chunkSize <= $maxChunkSize;
            ++$chunkSize
        ) {
            yield sprintf('Chunk size = %d', $chunkSize) => [$chunkSize];
        }
    }

    /**
     * @dataProvider chunkSizeDataProvider
     */
    public function testXmlCanBeParsedFromStreamWithCustomChunks(int $chunkSize): void
    {
        $nodeValueObject = $this->xmlTemplateReader->readStream(fopen(self::getXmlPath(self::XML_CORRECT, 'data'), 'rb'), $chunkSize);

        self::assertNodeObjectIsComplete($nodeValueObject);
    }

    /**
     * @dataProvider chunkSizeDataProvider
     */
    public function testXmlCanBeParsedAsStream(int $chunkSize): void
    {
        self::assertNodeObjectIsComplete(self::customReadByChunkSize($this->xmlTemplateReader, $chunkSize));
    }

    public function testXmlCanBeParsedByByte(): NodeInterface
    {
        $nodeValueObject = self::customReadByChunkSize($this->xmlTemplateReader, 1);

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

    private static function assertNodeObjectIsComplete(NodeInterface $rootNode): void
    {
        // root node
        self::assertInstanceOf(Node::class, $rootNode);
        self::assertNode('root', null, [
            'xmlns:foo' => 'https://www.foo.org/',
            'xmlns:bar' => 'https://bar.org',
        ], null, $rootNode);
        self::assertNodeHasNoChildren($rootNode);

        $rootNodeRelationsMap = $rootNode->getRelations();
        self::assertCount(3, $rootNodeRelationsMap);

        /**
         * root/actors node.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node $rootActorsNode
         */
        $rootActorsNode = $rootNodeRelationsMap['actors'];

        self::assertNode('actors', null, [], $rootNode, $rootActorsNode);
        self::assertNodeHasNoRelations($rootActorsNode);

        $rootActorsChildNodes = $rootActorsNode->getChildren();
        self::assertCount(1, $rootActorsChildNodes);
        self::assertArrayHasKey('actor', $rootActorsChildNodes);

        /**
         * root/actors/actor nodes.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node[] $actorsArray
         */
        $actorsArray = $rootActorsChildNodes->get('actor')->toArray();
        self::assertCount(3, $actorsArray);

        self::assertLeafNode('actor', 'Lorem Ipsum', ['id' => 1], $rootActorsNode, $actorsArray[0]);
        self::assertLeafNode('actor', 'Dolor Sit', ['id' => 2], $rootActorsNode, $actorsArray[1]);
        self::assertLeafNode('actor', 'Amet Enim', ['id' => 3], $rootActorsNode, $actorsArray[2]);

        /**
         * root/foo:singers node.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node $rootFooSingersNode
         */
        $rootFooSingersNode = $rootNodeRelationsMap['foo:singers'];

        self::assertNode('foo:singers', null, [], $rootNode, $rootFooSingersNode);
        self::assertNodeHasNoRelations($rootFooSingersNode);

        $rootFooSingersChildNodes = $rootFooSingersNode->getChildren();
        self::assertCount(1, $rootFooSingersChildNodes);
        self::assertArrayHasKey('foo:singer', $rootFooSingersChildNodes);

        /**
         * root/foo:singers/foo:singer nodes.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node[] $actorsArray
         */
        $fooSingersArray = $rootFooSingersChildNodes->get('foo:singer')->toArray();
        self::assertCount(3, $fooSingersArray);

        self::assertLeafNode('foo:singer', 'Etiam Ullamcorper', ['id' => '4'], $rootFooSingersNode, $fooSingersArray[0]);
        self::assertLeafNode('foo:singer', "Suspendisse a'Pellentesque", ['id' => '5'], $rootFooSingersNode, $fooSingersArray[1]);
        self::assertLeafNode('foo:singer', 'Dui von Felis', ['id' => '6a', 'bar:id' => 6], $rootFooSingersNode, $fooSingersArray[2]);

        /**
         * root/bar.writers node.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node $rootBarWritersNode
         */
        $rootBarWritersNode = $rootNodeRelationsMap['bar.writers'];

        self::assertNode('bar.writers', null, [], $rootNode, $rootBarWritersNode);
        self::assertNodeHasNoRelations($rootBarWritersNode);

        $rootBarWritersChildNodes = $rootBarWritersNode->getChildren();
        self::assertCount(1, $rootBarWritersChildNodes);
        self::assertArrayHasKey('bar.writer', $rootBarWritersChildNodes);

        /**
         * root/foo:singers/foo:singer nodes.
         *
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node[] $actorsArray
         */
        $barWriterArray = $rootBarWritersChildNodes->get('bar.writer')->toArray();
        self::assertCount(3, $barWriterArray);

        self::assertLeafNode('bar.writer', 'Maecenas Malesuada', ['id' => '7'], $rootBarWritersNode, $barWriterArray[0]);
        self::assertLeafNode('bar.writer', 'Elit Lectus', ['id' => '8'], $rootBarWritersNode, $barWriterArray[1]);
        self::assertLeafNode('bar.writer', 'Felis Malesuada', ['id' => '9a', 'bar.id' => 9], $rootBarWritersNode, $barWriterArray[2]);

        // toArray check
        self::assertSame([
            'node_name' => 'root',
            'contents'  => null,

            'attributes' => [
                'xmlns:foo' => 'https://www.foo.org/',
                'xmlns:bar' => 'https://bar.org',
            ],

            'relations' => [
                'actors' => [
                    'node_name'  => 'actors',
                    'contents'   => null,
                    'attributes' => [],
                    'relations'  => [],

                    'children' => [
                        'actor' => [
                            0 => [
                                'node_name'  => 'actor',
                                'contents'   => 'Lorem Ipsum',
                                'attributes' => [
                                    'id' => 1,
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            1 => [
                                'node_name'  => 'actor',
                                'contents'   => 'Dolor Sit',
                                'attributes' => [
                                    'id' => 2,
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            2 => [
                                'node_name'  => 'actor',
                                'contents'   => 'Amet Enim',
                                'attributes' => [
                                    'id' => 3,
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                        ],
                    ],
                ],

                'foo:singers' => [
                    'node_name'  => 'foo:singers',
                    'contents'   => null,
                    'attributes' => [],
                    'relations'  => [],

                    'children' => [
                        'foo:singer' => [
                            0 => [
                                'node_name'  => 'foo:singer',
                                'contents'   => 'Etiam Ullamcorper',
                                'attributes' => [
                                    'id' => '4',
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            1 => [
                                'node_name'  => 'foo:singer',
                                'contents'   => "Suspendisse a'Pellentesque",
                                'attributes' => [
                                    'id' => '5',
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            2 => [
                                'node_name'  => 'foo:singer',
                                'contents'   => 'Dui von Felis',
                                'attributes' => [
                                    'id'     => '6a',
                                    'bar:id' => 6,
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                        ],
                    ],
                ],

                'bar.writers' => [
                    'node_name'  => 'bar.writers',
                    'contents'   => null,
                    'attributes' => [],
                    'relations'  => [],

                    'children' => [
                        'bar.writer' => [
                            0 => [
                                'node_name'  => 'bar.writer',
                                'contents'   => 'Maecenas Malesuada',
                                'attributes' => [
                                    'id' => '7',
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            1 => [
                                'node_name'  => 'bar.writer',
                                'contents'   => 'Elit Lectus',
                                'attributes' => [
                                    'id' => '8',
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                            2 => [
                                'node_name'  => 'bar.writer',
                                'contents'   => 'Felis Malesuada',
                                'attributes' => [
                                    'id'     => '9a',
                                    'bar.id' => 9,
                                ],
                                'relations' => [],
                                'children'  => [],
                            ],
                        ],
                    ],
                ],
            ],

            'children' => [],
        ], $rootNode->toArray());
    }

    private static function assertNode(
        string $nodeName,
        ?string $contents,
        array $attributes,
        ?NodeInterface $parentNode,
        NodeInterface $node
    ): void {
        self::assertInstanceOf(Node::class, $node);
        self::assertSame($nodeName, $node->getNodeName(), 'The node name is different than expected.');
        self::assertSame($parentNode, $node->getParent(), 'The parent node is different than expected.');
        self::assertSame($contents, $node->getContents(), 'The contents is different than expected.');
        self::assertSame($attributes, $node->getAttributes()->toArray(), 'Attributes are different than expected.');
    }

    private static function assertNodeHasNoRelations(NodeInterface $node): void
    {
        self::assertEmpty($node->getRelations());
    }

    private static function assertNodeHasNoChildren(NodeInterface $node): void
    {
        self::assertEmpty($node->getChildren());
    }

    private static function assertLeafNode(
        string $nodeName,
        ?string $contents,
        array $attributes,
        ?NodeInterface $parentNode,
        NodeInterface $node
    ): void {
        self::assertNode($nodeName, $contents, $attributes, $parentNode, $node);
        self::assertNodeHasNoRelations($node);
        self::assertNodeHasNoChildren($node);
    }

    private static function customReadByChunkSize(XmlTemplateReader $xmlTemplateReader, int $chunkSize): NodeInterface
    {
        $xmlTemplateReader->open();

        $stream = str_split(self::getDataXml(self::XML_CORRECT), $chunkSize);

        foreach ($stream as $packet) {
            $xmlTemplateReader->update($packet);
        }

        return $xmlTemplateReader->close();
    }
}
