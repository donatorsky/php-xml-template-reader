<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber
 */
class IntegerNumberTest extends TestCase
{
    private IntegerNumber $rule;

    protected function setUp(): void
    {
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

    public function validNumberDataProvider(): iterable
    {
        yield 'Scientific notation string' => [
            'value'    => '1.23e4',
            'expected' => 12300,
        ];

        yield 'Negative integer' => [
            'value'    => -123,
            'expected' => -123,
        ];

        yield 'Zero integer' => [
            'value'    => 0,
            'expected' => 0,
        ];

        yield 'Positive integer' => [
            'value'    => 123,
            'expected' => 123,
        ];

        yield 'Negative integer string' => [
            'value'    => '-123',
            'expected' => -123,
        ];

        yield 'Zero integer string' => [
            'value'    => '0',
            'expected' => 0,
        ];

        yield 'Positive integer string' => [
            'value'    => '123',
            'expected' => 123,
        ];
    }

    /**
     * @dataProvider validNumberDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testProcessTransformsValueToInteger($value, $expected): void
    {
        self::assertSame($expected, $this->rule->process($value));
    }
}
