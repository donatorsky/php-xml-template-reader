<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\Trim;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\Trim
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\Trim
 */
class TrimTest extends TestCase
{
    use WithFaker {
        setUp as setUpFaker;
    }

    private Trim $rule;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->rule = new Trim();
    }

    public function nonStringValueDataProvider(): array
    {
        $this->setUpFaker();

        return [
            'integer' => ['value' => $this->faker->numberBetween()],
            'float'   => ['value' => $this->faker->randomFloat()],
            'bool'    => ['value' => $this->faker->boolean],
            'array'   => ['value' => []],
            'object'  => ['value' => new stdClass()],
        ];
    }

    /**
     * @dataProvider nonStringValueDataProvider
     *
     * @param mixed $value
     */
    public function testNonStringValueFailsValidation($value): void
    {
        self::assertFalse($this->rule->passes($value));
    }

    public function testStringPassesValidation(): void
    {
        self::assertTrue($this->rule->passes($this->faker->sentence));
    }

    public function testStringIsTrimmed(): void
    {
        $value = $this->faker->sentence;

        self::assertSame($value, $this->rule->process(\sprintf('  %s  ', $value)));
    }
}
