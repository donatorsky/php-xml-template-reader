<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\XmlTemplateReader;

use JetBrains\PhpStorm\ExpectedValues;
use PHPUnit\Framework\TestCase;

abstract class AbstractXmlTemplateReaderTest extends TestCase
{
    protected static function getXmlPath(
        string $name,
        #[ExpectedValues(values: ['data', 'template'])]
        string $type
    ): string {
        return sprintf('%1$s%2$sresources%2$s%3$s-%4$s.xml', __DIR__, DIRECTORY_SEPARATOR, $name, $type);
    }

    protected static function getDataXml(string $name): string
    {
        return file_get_contents(self::getXmlPath($name, 'data'));
    }

    protected static function getTemplateXml(string $name): string
    {
        return file_get_contents(self::getXmlPath($name, 'template'));
    }
}
