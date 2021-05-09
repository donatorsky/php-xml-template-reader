<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Models;

use Donatorsky\XmlTemplate\Reader\Models\Collection;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Models\Collection
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Models\Collection
 */
class CollectionTest extends TestCase
{
    use WithFaker;

    public function testEmptyCollectionCanBeConstructed(): void
    {
        $collection = new Collection();

        self::assertEmpty($collection);
        self::assertSame([], $collection->toArray());
    }

    public function testCollectionCanBeConstructedWithElements(): void
    {
        $data = [
            null,
            $this->faker->sentence(),
            $this->faker->numberBetween(),
            $this->faker->randomFloat(),
            $this->faker->boolean(),
            new stdClass(),
        ];

        $collection = new Collection($data);

        self::assertNotEmpty($collection);
        self::assertSame($data, $collection->toArray());
    }

    public function testCollectionCanBeTransformedToArray(): void
    {
        $collection = new Collection();

        $item = $this->faker->sentence();

        $collection->push($item);

        self::assertNotEmpty($collection);
        self::assertSame([$item], $collection->toArray());
    }
}
