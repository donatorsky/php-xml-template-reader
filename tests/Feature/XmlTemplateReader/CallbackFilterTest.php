<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Feature\XmlTemplateReader;

use Donatorsky\XmlTemplate\Reader\Exceptions\RuleValidationFailedException;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Rules\Callback;
use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class CallbackFilterTest extends AbstractXmlTemplateReaderTest
{
    private XmlTemplateReader $xmlTemplateReader;

    protected function setUp(): void
    {
        $this->xmlTemplateReader = new XmlTemplateReader(self::getTemplateXml('filters-callback'));
    }

    public function testCallbackFailsValidation(): void
    {
        $this->expectException(RuleValidationFailedException::class);
        $this->expectExceptionMessage(sprintf('Value "incorrect value" of attribute "callback" in node "root" does not pass %s rule', Callback::class));

        $this->xmlTemplateReader->read(
            <<<'XML'
<root callback=" incorrect value ">
</root>
XML
        );
    }

    public function testFiltersPass(): void
    {
        $node = $this->xmlTemplateReader->read(
            <<<'XML'
<root callback=" correct value ">
</root>
XML
        );

        $attributesMap = $node->getAttributes();

        self::assertTrue($attributesMap->has('callback'));
        self::assertSame('additional parameter CORRECT VALUE another parameter', $attributesMap->get('callback'));
    }
}

class MyNode extends Node
{
    public function myValidate(string $value, string $parameter1, string $parameter2): bool
    {
        return 'additional parameter' === $parameter1 &&
            'another parameter' === $parameter2 &&
            'correct value' === $value;
    }

    public function myProcess(string $value, string $parameter1, string $parameter2): string
    {
        return sprintf('%s %s %s', $parameter1, strtoupper($value), $parameter2);
    }
}
