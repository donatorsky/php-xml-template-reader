<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Unit\XmlTemplateReader;

use PHPUnit\Framework\TestCase;

abstract class AbstractXmlTemplateReaderTest extends TestCase
{
    protected const DUMMY_TEMPLATE = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="../../../src/xml-template-reader.xsd">
    <root foo="" />
</template>
XML;

    protected const DUMMY_XML_CONTENTS = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<root foo="bar">Lorem ipsum</root>
XML;

    protected const DUMMY_XML_FILE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.xml';
}
