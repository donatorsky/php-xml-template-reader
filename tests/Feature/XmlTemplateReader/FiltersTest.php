<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Exceptions\RuleValidationFailedException;
use Donatorsky\XmlTemplate\Reader\Rules\GreaterThan;
use Donatorsky\XmlTemplate\Reader\Rules\IntegerNumber;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class FiltersTest extends AbstractXmlTemplateReaderTest
{
    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml('filters'));
    }

    public function testIntegerNumberRuleFailsBeforeGreaterThanRule(): void
    {
        $this->expectException(RuleValidationFailedException::class);
        $this->expectExceptionMessage(sprintf('Value "non-numeric value" of attribute "greaterThan5" in node "root" does not pass %s rule', IntegerNumber::class));

        $this->xmlTemplateReader->read(
            <<<'XML'
<root greaterThan5="non-numeric value">
</root>
XML
        );
    }

    public function testGreaterThanRuleFailsAfterValueWasTransformedToInteger(): void
    {
        $this->expectException(RuleValidationFailedException::class);
        $this->expectExceptionMessage(sprintf('Value "5" of attribute "greaterThan5" in node "root" does not pass %s rule', GreaterThan::class));

        $this->xmlTemplateReader->read(
            <<<'XML'
<root greaterThan5=" 5 ">
</root>
XML
        );
    }

    public function testFiltersPass(): void
    {
        $node = $this->xmlTemplateReader->read(
            <<<'XML'
<root greaterThan5="6">
</root>
XML
        );

        $attributesMap = $node->getAttributes();

        self::assertTrue($attributesMap->has('greaterThan5'));
        self::assertSame(6, $attributesMap->get('greaterThan5'));
    }
}
