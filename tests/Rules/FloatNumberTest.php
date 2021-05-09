<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\FloatNumber;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\FloatNumber
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\FloatNumber
 */
class FloatNumberTest extends TestCase
{
    private FloatNumber $rule;

    protected function setUp(): void
    {
        $this->rule = new FloatNumber();
    }

    public function validNumberDataProvider(): iterable
    {
        yield 'Negative float' => [
            'value'    => -123.45,
            'expected' => -123.45,
        ];

        yield 'Zero float' => [
            'value'    => 0.0,
            'expected' => 0.0,
        ];

        yield 'Positive float' => [
            'value'    => 123.45,
            'expected' => 123.45,
        ];

        yield 'Negative float string' => [
            'value'    => '-123.45',
            'expected' => -123.45,
        ];

        yield 'Zero float string' => [
            'value'    => '0.0',
            'expected' => 0.0,
        ];

        yield 'Positive float string' => [
            'value'    => '123.45',
            'expected' => 123.45,
        ];

        yield 'Scientific notation string' => [
            'value'    => '1.23e4',
            'expected' => 12300.0,
        ];

        yield 'Negative integer' => [
            'value'    => -123,
            'expected' => -123.0,
        ];

        yield 'Zero integer' => [
            'value'    => 0,
            'expected' => 0.0,
        ];

        yield 'Positive integer' => [
            'value'    => 123,
            'expected' => 123.0,
        ];

        yield 'Negative integer string' => [
            'value'    => '-123',
            'expected' => -123.0,
        ];

        yield 'Zero integer string' => [
            'value'    => '0',
            'expected' => 0.0,
        ];

        yield 'Positive integer string' => [
            'value'    => '123',
            'expected' => 123.0,
        ];
    }

    /**
     * @dataProvider validNumberDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testProcessTransformsValueToFloat($value, $expected): void
    {
        self::assertSame($expected, $this->rule->process($value));
    }
}
