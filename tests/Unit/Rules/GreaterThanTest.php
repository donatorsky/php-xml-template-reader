<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\Rules;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Rules\GreaterThan;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\GreaterThan
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\GreaterThan
 */
class GreaterThanTest extends TestCase
{
    use WithFaker;

    private const FLOAT_EPSILON = 0.000001;

    public function testRuleCannotBeCreatedWithNonNumericThreshold(): void
    {
        $threshold = $this->fakeNonNumericValue();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Value "%s" is not numeric.', $threshold));

        new GreaterThan($threshold);
    }

    public function testRuleCanBeCreatedWithIntegerThreshold(): GreaterThan
    {
        $fakeNumber = $this->fakeNumber();
        $rule = new GreaterThan((string) $fakeNumber);

        self::assertSame((float) $fakeNumber, $rule->getThreshold());

        return $rule;
    }

    public function testRuleCanBeCreatedWithFloatThreshold(): GreaterThan
    {
        $fakeFloat = $this->fakeFloat();
        $rule = new GreaterThan((string) $fakeFloat);

        self::assertSame($fakeFloat, $rule->getThreshold());

        return $rule;
    }

    /**
     * @depends testRuleCanBeCreatedWithIntegerThreshold
     * @depends testRuleCanBeCreatedWithFloatThreshold
     */
    public function testValidationFailsForNonNumericValue(
        GreaterThan $greaterThanIntegerRule,
        GreaterThan $greaterThanFloatRule
    ): void {
        self::assertFalse($greaterThanIntegerRule->passes($this->fakeNonNumericValue()));
        self::assertFalse($greaterThanFloatRule->passes($this->fakeNonNumericValue()));
    }

    /**
     * @depends testRuleCanBeCreatedWithIntegerThreshold
     * @depends testRuleCanBeCreatedWithFloatThreshold
     */
    public function testValidationFailsForValueLessThanThreshold(
        GreaterThan $greaterThanIntegerRule,
        GreaterThan $greaterThanFloatRule
    ): void {
        self::assertFalse($greaterThanIntegerRule->passes($greaterThanIntegerRule->getThreshold() - 1));
        self::assertFalse($greaterThanFloatRule->passes($greaterThanFloatRule->getThreshold() - self::FLOAT_EPSILON));
    }

    /**
     * @depends testRuleCanBeCreatedWithIntegerThreshold
     * @depends testRuleCanBeCreatedWithFloatThreshold
     */
    public function testValidationFailsForValueSameAsThreshold(
        GreaterThan $greaterThanIntegerRule,
        GreaterThan $greaterThanFloatRule
    ): void {
        self::assertFalse($greaterThanIntegerRule->passes($greaterThanIntegerRule->getThreshold()));
        self::assertFalse($greaterThanFloatRule->passes($greaterThanFloatRule->getThreshold()));
    }

    /**
     * @depends testRuleCanBeCreatedWithIntegerThreshold
     * @depends testRuleCanBeCreatedWithFloatThreshold
     */
    public function testValidationPassesForValueGreaterThanThreshold(
        GreaterThan $greaterThanIntegerRule,
        GreaterThan $greaterThanFloatRule
    ): void {
        self::assertTrue($greaterThanIntegerRule->passes($greaterThanIntegerRule->getThreshold() + 1));
        self::assertTrue($greaterThanFloatRule->passes($greaterThanFloatRule->getThreshold() + self::FLOAT_EPSILON));
    }

    /**
     * @depends testRuleCanBeCreatedWithIntegerThreshold
     * @depends testRuleCanBeCreatedWithFloatThreshold
     */
    public function testProcessReturnsTheSameNumericValue(
        GreaterThan $greaterThanIntegerRule,
        GreaterThan $greaterThanFloatRule
    ): void {
        $fakeNumber = $this->fakeNumber();
        self::assertSame($fakeNumber, $greaterThanIntegerRule->process($fakeNumber));

        $fakeFloat = $this->fakeFloat();
        self::assertSame($fakeFloat, $greaterThanFloatRule->process($fakeFloat));
    }

    private function fakeNonNumericValue(): string
    {
        return $this->faker->asciify('foo********bar');
    }

    private function fakeNumber(): int
    {
        return $this->faker->numberBetween(-1000, 1000);
    }

    private function fakeFloat(): float
    {
        return $this->faker->randomFloat(6, -1000.0, 1000.0);
    }
}
