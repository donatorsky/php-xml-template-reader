<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber
 */
class IntegerNumberTest extends TestCase
{
    use WithFaker {
        setUp as setUpFaker;
    }

    private IntegerNumber $rule;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->rule = new IntegerNumber();
    }

    public function nonIntegerishValueDataProvider(): array
    {
        return [
            'float'        => ['value' => 1.1],
            'float string' => ['value' => '1.1'],
        ];
    }

    /**
     * @dataProvider nonIntegerishValueDataProvider
     *
     * @param mixed $value
     */
    public function testValidationFailsForNonIntegerishValue($value): void
    {
        self::assertFalse($this->rule->passes($value));
    }

    public function testProcessTransformsValueToInteger(): void
    {
        $value = $this->faker->numberBetween(-1000, 1000);

        self::assertSame($value, $this->rule->process((string) $value));
    }
}
