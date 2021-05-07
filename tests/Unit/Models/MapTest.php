<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\Models;

use Donatorsky\XmlTemplate\Reader\Models\Map;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Models\Map
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Models\Map
 */
class MapTest extends TestCase
{
    use WithFaker;

    public function testEmptyMapCanBeConstructed(): Map
    {
        $map = new Map();

        self::assertEmpty($map);

        return $map;
    }

    public function testMapCanBeConstructedFromArray(): void
    {
        $data = [
            $this->faker->unique()->word() => null,
            $this->faker->unique()->word() => $this->faker->sentence(),
            $this->faker->unique()->word() => $this->faker->numberBetween(),
            $this->faker->unique()->word() => $this->faker->randomFloat(),
            $this->faker->unique()->word() => $this->faker->boolean(),
            $this->faker->unique()->word() => [],
            $this->faker->unique()->word() => new stdClass(),
        ];

        $map = new Map($data);

        self::assertNotEmpty($map);
        self::assertSame($data, $map->toArray());
    }

    /**
     * @depends testEmptyMapCanBeConstructed
     */
    public function testFailToGetNonExistentItemByName(Map $map): void
    {
        $name = $this->faker->unique()->word();

        self::assertFalse($map->has($name));

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage(sprintf('The element with name "%s" does not exist', $name));

        $map->get($name);
    }

    /**
     * @depends testEmptyMapCanBeConstructed
     */
    public function testFailToGetItemByNonStringName(Map $map): void
    {
        $offset = $this->faker->numberBetween();

        $this->expectException(TypeError::class);

        if (PHP_MAJOR_VERSION < 8) {
            $this->expectExceptionMessage(sprintf('Argument 1 passed to %s::get() must be of the type string, int given', Map::class));
        } else {
            $this->expectExceptionMessage(sprintf('%s::get(): Argument #1 ($name) must be of type string, int given', Map::class));
        }

        $map->offsetGet($offset);
    }

    /**
     * @depends testEmptyMapCanBeConstructed
     */
    public function testFailToSetItemWithNonStringName(Map $map): void
    {
        $offset = $this->faker->numberBetween();

        $this->expectException(TypeError::class);

        if (PHP_MAJOR_VERSION < 8) {
            $this->expectExceptionMessage(sprintf('Argument 1 passed to %s::set() must be of the type string, int given', Map::class));
        } else {
            $this->expectExceptionMessage(sprintf('%s::set(): Argument #1 ($name) must be of type string, int given', Map::class));
        }

        $map->offsetSet($offset, $this->faker->sentence());
    }

    /**
     * @depends testEmptyMapCanBeConstructed
     */
    public function testMapCanBeManipulated(Map $map): void
    {
        $name1 = $this->faker->unique()->word();
        $name2 = $this->faker->unique()->word();
        $value1 = $this->faker->unique()->sentence();
        $value2 = $this->faker->unique()->numberBetween();

        self::assertTrue($map->isEmpty());
        self::assertFalse($map->has($name1));
        self::assertArrayNotHasKey($name2, $map);
        self::assertCount(0, $map);
        self::assertNull($map->first());

        self::assertSame($map, $map->set($name1, $value1));
        self::assertFalse($map->isEmpty());
        self::assertCount(1, $map);
        self::assertTrue($map->has($name1));
        self::assertSame($value1, $map->first());

        $map[$name2] = $value2;
        self::assertFalse($map->isEmpty());
        self::assertCount(2, $map);
        self::assertArrayHasKey($name2, $map);
        self::assertSame($value1, $map->first());

        self::assertSame($value1, $map->get($name1));
        self::assertSame($value2, $map[$name2]);
        self::assertSame([
            $name1 => $value1,
            $name2 => $value2,
        ], $map->toArray());
        self::assertSame([
            $name1 => $value1,
            $name2 => $value2,
        ], iterator_to_array($map));

        self::assertSame($map, $map->remove($name1));
        self::assertFalse($map->isEmpty());
        self::assertCount(1, $map);
        self::assertFalse($map->has($name1));
        self::assertTrue($map->has($name2));
        self::assertSame($value2, $map->first());

        unset($map[$name2]);
        self::assertTrue($map->isEmpty());
        self::assertCount(0, $map);
        self::assertFalse($map->has($name1));
        self::assertFalse($map->has($name2));
        self::assertNull($map->first());
    }
}
