<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests;

use Donatorsky\XmlTemplate\Reader\XmlTemplateReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\XmlTemplateReader
 */
class XmlTemplateReaderTest extends TestCase
{
    private const TEMPLATE = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./app/xml-template-reader.xsd">
    <root tpl:required="true"
          tpl:type="single">
        <actors tpl:required="false"
                tpl:type="single">
            <actor tpl:required="true"
                   tpl:type="collection"
                   tpl:contents="raw"
                   id="required | integer | greaterThan:0" />
        </actors>
        <foo:singers tpl:required="false"
                     tpl:type="single">
            <foo:singer tpl:required="true"
                        tpl:type="collection"
                        tpl:collectAttributes="all"
                        tpl:contents="trimmed"
                        bar:id="integer" />
        </foo:singers>
    </root>
</template>
XML;

    private const FILE = __DIR__ . DIRECTORY_SEPARATOR . 'example.xml';

    public function testDebug(): void
    {
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch(),
        );

        $xmlTemplateReader = new XmlTemplateReader(self::TEMPLATE, $eventDispatcher);

        /** @noinspection ForgottenDebugOutputInspection */
        dd(
            $xmlTemplateReader,

            $nodeValueObject = $xmlTemplateReader->read(\file_get_contents(self::FILE)),
            $nodeValueObject->toArray(),

            //$eventDispatcher->getCalledListeners(),
            //$eventDispatcher->getOrphanedEvents(),
            //$eventDispatcher->getNotCalledListeners(),
            //$eventDispatcher->getListeners(),

            $xmlTemplateReader->readFile(self::FILE),
            $xmlTemplateReader->readStream(\fopen(self::FILE, 'r')),

            $xmlTemplateReader,
        );
    }
}
