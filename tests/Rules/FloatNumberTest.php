<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Rules\FloatNumber;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\FloatNumber
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\FloatNumber
 */
class FloatNumberTest extends TestCase
{
    use WithFaker {
        setUp as setUpFaker;
    }

    private FloatNumber $rule;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->rule = new FloatNumber();
    }

    public function testProcessTransformsValueToFloat(): void
    {
        $value = $this->faker->randomFloat(6, -1000.0, 1000.0);

        self::assertSame($value, $this->rule->process((string) $value));
        self::assertSame(12300.0, $this->rule->process('1.23e4'));
    }
}
