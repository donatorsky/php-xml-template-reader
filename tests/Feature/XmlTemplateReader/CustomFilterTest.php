<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CustomFilterTest extends AbstractXmlTemplateReaderTest
{
    private const XML_DATA = <<<'XML'
<root custom="some value 1" customAliased="some value 2">
</root>
XML;

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
            ->preloadTemplate()
            ->read(self::XML_DATA);

        $attributesMap = $node->getAttributes();

        self::assertTrue($attributesMap->has('custom'));
        self::assertSame('foo SOME VALUE 1 123', $attributesMap->get('custom'));
        self::assertTrue($attributesMap->has('customAliased'));
        self::assertSame('bar SOME VALUE 2 987', $attributesMap->get('customAliased'));
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithInvalidName(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "my rule" name of the rule is invalid.');

        $this->xmlTemplateReader->registerRuleFilter('my rule', MyRule::class);
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithInvalidAlias(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "my rule alias" alias name of the rule is invalid.');

        $this->xmlTemplateReader->registerRuleFilter('myRule', MyRule::class, ['my rule alias']);
    }

    /**
     * @depends testCustomRuleWasNotRegistered
     */
    public function testFailToRegisterCustomRuleWithRuleClassNotImplementingRuleInterface(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Class "stdClass" was expected to be subclass of "%s".', RuleInterface::class));

        $this->xmlTemplateReader->registerRuleFilter('myRule', stdClass::class);
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
        return ('some value 1' === $value && 'foo' === $this->arg1 && '123' === $this->arg2) ||
            ('some value 2' === $value && 'bar' === $this->arg1 && '987' === $this->arg2);
    }

    public function process($value)
    {
        return \sprintf('%s %s %s', $this->arg1, \strtoupper($value), $this->arg2);
    }
}
