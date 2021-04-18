<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Required;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\Required
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\Required
 */
class RequiredTest extends TestCase
{
    use WithFaker {
        setUp as setUpFaker;
    }

    private Required $rule;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->rule = new Required();
    }

    public function emptyValueDataProvider(): array
    {
        return [
            ['value' => 0],
            ['value' => '0'],
            ['value' => ''],
            ['value' => null],
            ['value' => false],
            ['value' => []],
        ];
    }

    /**
     * @dataProvider emptyValueDataProvider
     *
     * @param mixed $value
     */
    public function testEmptyValueFailsValidation($value): void
    {
        self::assertFalse($this->rule->passes($value));
    }

    public function nonEmptyValueDataProvider(): array
    {
        return [
            ['value' => 1],
            ['value' => '1'],
            ['value' => ' '],
            ['value' => true],
            ['value' => [1]],
            ['value' => new stdClass()],
        ];
    }

    /**
     * @dataProvider nonEmptyValueDataProvider
     *
     * @param mixed $value
     */
    public function testNonEmptyValuePassesValidation($value): void
    {
        self::assertTrue($this->rule->passes($value));
    }

    public function exampleValueDataProvider(): array
    {
        $this->setUpFaker();

        return [
            'string'  => ['value' => $this->faker->sentence],
            'integer' => ['value' => $this->faker->numberBetween()],
            'float'   => ['value' => $this->faker->randomFloat()],
            'bool'    => ['value' => $this->faker->boolean],
            'array'   => ['value' => []],
            'object'  => ['value' => new stdClass()],
        ];
    }

    /**
     * @dataProvider exampleValueDataProvider
     *
     * @param mixed $value
     */
    public function testProcessReturnsTheSameValueAsPassed($value): void
    {
        self::assertSame($value, $this->rule->process($value));
    }
}
