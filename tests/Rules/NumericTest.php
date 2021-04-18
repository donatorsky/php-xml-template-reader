<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Numeric;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\Numeric
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\Numeric
 */
class NumericTest extends TestCase
{
    use WithFaker {
        setUp as setUpFaker;
    }

    private Numeric $rule;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->rule = new Numeric();
    }

    public function nonNumericValueDataProvider(): array
    {
        return [
            'null'                        => ['value' => null],
            'true'                        => ['value' => true],
            'false'                       => ['value' => false],
            'object'                      => ['value' => new stdClass()],
            'integer string with prefix'  => ['value' => 'foo1'],
            'integer string with postfix' => ['value' => '1foo'],
            'float string with prefix'    => ['value' => 'foo1.1'],
            'float string with postfix'   => ['value' => '1.1foo'],
            'float string with two dots'  => ['value' => '1.1.1'],
            'array'                       => ['value' => []],
        ];
    }

    /**
     * @dataProvider nonNumericValueDataProvider
     *
     * @param mixed $value
     */
    public function testValidationFailsForNonNumericValue($value): void
    {
        self::assertFalse($this->rule->passes($value));
    }

    public function numericValueDataProvider(): array
    {
        $this->setUpFaker();

        return [
            'integer'            => ['value' => $this->faker->numberBetween()],
            'integer string'     => ['value' => (string) $this->faker->numberBetween()],
            'float'              => ['value' => $this->faker->randomFloat(6, -1000.0, 1000.0)],
            'float string'       => ['value' => (string) $this->faker->randomFloat(6, -1000.0, 1000.0)],
            'exponential string' => ['value' => '1.23e4'],
        ];
    }

    /**
     * @dataProvider numericValueDataProvider
     *
     * @param mixed $value
     */
    public function testValidationPassesForNumericValue($value): void
    {
        self::assertTrue($this->rule->passes($value));
    }

    public function testProcessTransformsValueToFloat(): void
    {
        $value = $this->faker->randomFloat(6, -1000.0, 1000.0);

        self::assertSame($value, $this->rule->process((string) $value));
        self::assertSame(12300.0, $this->rule->process('1.23e4'));
    }
}
