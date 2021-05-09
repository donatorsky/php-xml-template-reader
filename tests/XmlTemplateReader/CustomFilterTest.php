<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use Assert\InvalidArgumentException;
use Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException;
use Donatorsky\XmlTemplate\Reader\Models\Map;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CustomFilterTest extends AbstractXmlTemplateReaderTest
{
    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml('filters-custom'));
    }

    public function testCustomRuleWasNotRegistered(): void
    {
        $this->expectException(UnknownRuleException::class);
        $this->expectExceptionMessage('The rule "myRule" is unknown');

        $this->xmlTemplateReader->preloadTemplate();
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testCustomFilterPass(): void
    {
        $node = $this->xmlTemplateReader->registerRuleFilter('myRule', MyRule::class, ['myRuleAlias'])
            ->read(self::getDataXml('filters-custom'));

        $attributesMap = $node->getAttributes();

        self::assertMapContains($attributesMap, 'custom', 'foo SOME VALUE 1A 123');
        self::assertMapContains($attributesMap, 'customSpaced', 'foo SOME VALUE 1B 123');
        self::assertMapContains($attributesMap, 'customAliased', 'bar SOME VALUE 2A 987');
        self::assertMapContains($attributesMap, 'customAliasedSpaced', 'bar SOME VALUE 2B 987');
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "my rule" name of the rule is invalid.');

        $this->xmlTemplateReader->registerRuleFilter('my rule', MyRule::class);
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithInvalidAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "my rule alias" alias name of the rule is invalid.');

        $this->xmlTemplateReader->registerRuleFilter('myRule', MyRule::class, ['my rule alias']);
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithRuleClassNotImplementingRuleInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Class "stdClass" was expected to be subclass of "%s".', RuleInterface::class));

        $this->xmlTemplateReader->registerRuleFilter('myRule', stdClass::class);
    }

    private static function assertMapContains(Map $attributesMap, string $name, string $value): void
    {
        self::assertTrue($attributesMap->has($name));
        self::assertSame($value, $attributesMap->get($name));
    }
}

class MyRule implements RuleInterface
{
    private string $arg1;

    private string $arg2;

    public function __construct(string $arg1, string $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }

    public function passes($value): bool
    {
        return (0 === strpos($value, 'some value 1') && 'foo' === $this->arg1 && '123' === $this->arg2) ||
            (0 === strpos($value, 'some value 2') && 'bar' === $this->arg1 && '987' === $this->arg2);
    }

    public function process($value)
    {
        return sprintf('%s %s %s', $this->arg1, strtoupper($value), $this->arg2);
    }
}
