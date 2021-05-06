<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\Models;

use Donatorsky\XmlTemplate\Reader\Models\Collection;
use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Models\Node
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Models\Node
 */
class NodeTest extends TestCase
{
    use WithFaker;
    use ProphecyTrait;

    public function testNodeCanBeConstructedWithDefaultParameters(): Node
    {
        $nodeName = $this->faker->word();

        $node = new Node($nodeName);

        self::assertSame($nodeName, $node->getNodeName());
        self::assertFalse($node->hasParent());
        self::assertFalse($node->hasContents());
        self::assertEmpty($node->getAttributes());
        self::assertEmpty($node->getRelations());
        self::assertEmpty($node->getChildren());

        self::assertSame([
            'node_name'  => $nodeName,
            'contents'   => null,
            'attributes' => [],
            'relations'  => [],
            'children'   => [],
        ], $node->toArray());

        return $node;
    }

    public function testNodeCanBeConstructedWithCustomParameters(): void
    {
        $nodeName = $this->faker->word();
        $nodeDummy = $this->prophesize(NodeInterface::class)->reveal();
        $contents = $this->faker->sentence();
        $attributes = [
            'foo'                          => 'bar',
            $this->faker->unique()->word() => null,
            $this->faker->unique()->word() => $this->faker->numberBetween(),
            $this->faker->unique()->word() => $this->faker->randomFloat(),
            $this->faker->unique()->word() => $this->faker->sentence(),
            $this->faker->unique()->word() => $this->faker->boolean(),
        ];

        $node = new Node($nodeName, $nodeDummy, $contents, $attributes);

        self::assertSame($nodeName, $node->getNodeName());
        self::assertTrue($node->hasParent());
        self::assertSame($nodeDummy, $node->getParent());
        self::assertTrue($node->hasContents());
        self::assertSame($contents, $node->getContents());
        self::assertSame($attributes, $node->getAttributes()->toArray());
        self::assertEmpty($node->getRelations());
        self::assertEmpty($node->getChildren());

        self::assertSame([
            'node_name'  => $nodeName,
            'contents'   => $contents,
            'attributes' => $attributes,
            'relations'  => [],
            'children'   => [],
        ], $node->toArray());
    }

    /**
     * @depends testNodeCanBeConstructedWithDefaultParameters
     */
    public function testParentCanBeSet(Node $node): void
    {
        $parentDummy = $this->prophesize(NodeInterface::class)->reveal();

        self::assertSame($node, $node->setParent($parentDummy));
        self::assertTrue($node->hasParent());
        self::assertSame($parentDummy, $node->getParent());
    }

    /**
     * @depends testNodeCanBeConstructedWithDefaultParameters
     */
    public function testContentsCanBeSet(Node $node): void
    {
        $contents = $this->faker->sentence();

        self::assertSame($node, $node->setContents($contents));
        self::assertTrue($node->hasContents());
        self::assertSame($contents, $node->getContents());
    }

    /**
     * @depends testNodeCanBeConstructedWithDefaultParameters
     */
    public function testNodeCanBeTransformedToArrayWithAllRelations(Node $node): void
    {
        // Attributes
        $attributes = [
            $this->faker->unique()->word() => $this->faker->sentence(),
            $this->faker->unique()->word() => $this->faker->numberBetween(),
        ];

        foreach ($attributes as $name => $value) {
            $node->getAttributes()->set($name, $value);
        }

        // Relations
        $relationName1 = $this->faker->unique()->word();
        $relationName2 = $this->faker->unique()->word();

        $relationNode1Name = $this->faker->unique()->word();
        $relationNode2Name = $this->faker->unique()->word();

        $node->getRelations()
            ->set($relationName1, new Node($relationNode1Name))
            ->set($relationName2, new Node($relationNode2Name, null, 'lipsum'));

        // Child-elements
        $childrenName1 = $this->faker->unique()->word();
        $childrenName2 = $this->faker->unique()->word();

        $child1Name = $this->faker->unique()->word();
        $child2Name = $this->faker->unique()->word();
        $child3Name = $this->faker->unique()->word();

        $node->getChildren()
            ->set($childrenName1, new Collection([new Node($child1Name), new Node($child2Name, null, 'lipsum')]))
            ->set($childrenName2, new Collection([new Node($child3Name)]));

        self::assertSame([
            'node_name'  => $node->getNodeName(),
            'contents'   => $node->getContents(),
            'attributes' => $attributes,

            'relations' => [
                $relationName1 => [
                    'node_name'  => $relationNode1Name,
                    'contents'   => null,
                    'attributes' => [],
                    'relations'  => [],
                    'children'   => [],
                ],

                $relationName2 => [
                    'node_name'  => $relationNode2Name,
                    'contents'   => 'lipsum',
                    'attributes' => [],
                    'relations'  => [],
                    'children'   => [],
                ],
            ],

            'children' => [
                $childrenName1 => [
                    [
                        'node_name'  => $child1Name,
                        'contents'   => null,
                        'attributes' => [],
                        'relations'  => [],
                        'children'   => [],
                    ],
                    [
                        'node_name'  => $child2Name,
                        'contents'   => 'lipsum',
                        'attributes' => [],
                        'relations'  => [],
                        'children'   => [],
                    ],
                ],

                $childrenName2 => [
                    [
                        'node_name'  => $child3Name,
                        'contents'   => null,
                        'attributes' => [],
                        'relations'  => [],
                        'children'   => [],
                    ],
                ],
            ],
        ], $node->toArray());
    }
}
