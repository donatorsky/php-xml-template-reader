<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader;

use Assert\Assertion;
use Donatorsky\XmlTemplate\Reader\Events\CDataRead;
use Donatorsky\XmlTemplate\Reader\Events\TagClosed;
use Donatorsky\XmlTemplate\Reader\Events\TagOpened;
use Donatorsky\XmlTemplate\Reader\Exceptions\RuleValidationFailedException;
use Donatorsky\XmlTemplate\Reader\Exceptions\UnexpectedMultipleNodeReadException;
use Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Rules\Callback;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\ContextAwareRuleInterface;
use JetBrains\PhpStorm\Language;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class XmlTemplateReader
{
    public const CONFIGURATION_REQUIRED_TRUE = 'true';

    public const CONFIGURATION_REQUIRED_FALSE = 'false';

    public const CONFIGURATION_CONTENTS_NONE = 'none';

    public const CONFIGURATION_CONTENTS_RAW = 'raw';

    public const CONFIGURATION_CONTENTS_TRIMMED = 'trimmed';

    public const CONFIGURATION_TYPE_SINGLE = 'single';

    public const CONFIGURATION_TYPE_COLLECTION = 'collection';

    public const CONFIGURATION_COLLECT_ATTRIBUTES_ALL = 'all';

    public const CONFIGURATION_COLLECT_ATTRIBUTES_VALIDATED = 'validated';

    private string $namespace;

    private EventDispatcherInterface $eventDispatcher;

    private array $path = [];

    private array $pathForHash = [];

    private array $pathForObject = [];

    private array $counter = [];

    /**
     * @var resource|\XmlParser|null
     */
    private $xmlParser;

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        #[Language('XML')]
        string $template,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $simpleXMLElement = new SimpleXMLElement(
            $template,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET,
        );

        $namespaces = $simpleXMLElement->getNamespaces();

        Assertion::count($namespaces, 1, 'You need to specify exactly one template namespace, %2$d provided');

        $this->namespace = (string) \key($namespaces);
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();

        $this->addListenersFromTemplate($simpleXMLElement);
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function open(): self
    {
        Assertion::false($this->isOpened(), 'Reading is already in progress');

        $this->initializeParser();

        $this->pathForObject[] = new Node('');

        return $this;
    }

    public function isOpened(): bool
    {
        return null !== $this->xmlParser;
    }

    public function update(
        #[Language('XML')]
        string $xml
    ): self {
        try {
            $result = \xml_parse($this->xmlParser, $xml);

            //TODO: Do something with invalid result
        } finally {
            $this->deinitializeParser();
        }

        return $this;
    }

    /**
     * @throws \Assert\AssertionFailedException When XML reading is not finished yet and there are still open nodes
     */
    public function close(): Node
    {
        $this->deinitializeParser();

        Assertion::count($this->pathForObject, 1);

        $this->counter = [];

        /**
         * @var \Donatorsky\XmlTemplate\Reader\Models\Node $wrapperObject
         */
        $wrapperObject = \array_pop($this->pathForObject);

        $nodeValueObjects = $wrapperObject->getRelations();

        return \reset($nodeValueObjects)->setParent(null);
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function read(
        #[Language('XML')]
        string $xml
    ): Node {
        $this->open();

        $this->update($xml);

        return $this->close();
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function readFile(
        string $path,
        int $chunkSize = 4096
    ): ?object {
        return $this->readStream(\fopen($path, 'rb'), $chunkSize);
    }

    /**
     * @param resource $stream
     *
     * @throws \Assert\AssertionFailedException
     */
    public function readStream(
        $stream,
        int $chunkSize = 4096
    ): ?object {
        Assertion::greaterThan($chunkSize, 0, 'The read chunk size must be greater than 0');

        $this->open();

        try {
            while (!\feof($stream)) {
                $this->update(\fread($stream, $chunkSize));
            }
        } finally {
            \fclose($stream);
        }

        return $this->close();
    }

    private function initializeParser(): void
    {
        $this->xmlParser = \xml_parser_create('UTF-8');

        \xml_set_object($this->xmlParser, $this);
        \xml_set_element_handler($this->xmlParser, 'onTagOpenRead', 'onTagCloseRead');
        \xml_set_character_data_handler($this->xmlParser, 'onCDataRead');
        \xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
        \xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, true);
    }

    private function deinitializeParser(): void
    {
        if (null !== $this->xmlParser) {
            \xml_parser_free($this->xmlParser);

            $this->xmlParser = null;
        }
    }

    /**
     * @param resource|\XmlParser $xmlParser
     */
    private function onTagOpenRead($xmlParser, string $nodeName, array $attributes): void
    {
        $this->path[] = $nodeName;

        $parentNodeValueObject = \end($this->pathForObject);

        $this->eventDispatcher->dispatch(
            new TagOpened(
                $parentNodeValueObject,
                $nodeName,
                $attributes,
                \md5(\implode("\0", $this->pathForHash)),
            ),
            \sprintf('open@%s', \implode('/', $this->path)),
        );

        $this->pathForHash[] = \sprintf('%s %.6f', $nodeName, \microtime(true));
    }

    /**
     * @param resource|\XmlParser $xmlParser
     */
    private function onCDataRead($xmlParser, string $contents): void
    {
        $this->eventDispatcher->dispatch(
            new CDataRead(
                \end($this->pathForObject),
                $contents,
            ),
            \sprintf('cdata@%s', \implode('/', $this->path)),
        );
    }

    /**
     * @param resource|\XmlParser $xmlParser
     */
    private function onTagCloseRead($xmlParser, string $nodeName): void
    {
        \array_pop($this->pathForHash);

        $this->eventDispatcher->dispatch(
            new TagClosed(
                \end($this->pathForObject),
                $nodeName,
            ),
            \sprintf('close@%s', \implode('/', $this->path)),
        );

        \array_pop($this->path);
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\RuleValidationFailedException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\UnexpectedMultipleNodeReadException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException
     */
    private function addListenersFromTemplate(SimpleXMLElement $simpleXMLElement, array $path = []): void
    {
        $children = $simpleXMLElement->children();

        if (null === $children) {
            return;
        }

        $currentPath = $path;

        foreach ($children as $child) {
            $currentPath[] = $child->getName();

            $configurationAttributes = $child->attributes($this->namespace, true);
            $currentPathString = \implode('/', $currentPath);

            // Read parsing configuration
            $configuration = [
                'required' => \filter_var(
                    (string) ($configurationAttributes['required'] ?? self::CONFIGURATION_REQUIRED_TRUE),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),

                'attributesRules'   => [],
                'contents'          => (string) ($configurationAttributes['contents'] ?? self::CONFIGURATION_CONTENTS_NONE),
                'type'              => (string) ($configurationAttributes['type'] ?? self::CONFIGURATION_TYPE_SINGLE),
                'collectAttributes' => (string) ($configurationAttributes['collectAttributes'] ?? self::CONFIGURATION_COLLECT_ATTRIBUTES_ALL),
                'castTo'            => (string) ($configurationAttributes['castTo'] ?? Node::class),
            ];

            Assertion::notNull($configuration['required'], \sprintf(
                'The "%s" node\'s %s:required attribute value "%s" is invalid, true or false was expected',
                $currentPathString,
                $this->namespace,
                $configurationAttributes['required'],
            ));

            Assertion::choice($configuration['contents'], [
                self::CONFIGURATION_CONTENTS_NONE,
                self::CONFIGURATION_CONTENTS_RAW,
                self::CONFIGURATION_CONTENTS_TRIMMED,
            ], \sprintf(
                'The "%s" node\'s %s:contents attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::choice($configuration['type'], [
                self::CONFIGURATION_TYPE_SINGLE,
                self::CONFIGURATION_TYPE_COLLECTION,
            ], \sprintf(
                'The "%s" node\'s %s:type attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::choice($configuration['collectAttributes'], [
                self::CONFIGURATION_COLLECT_ATTRIBUTES_ALL,
                self::CONFIGURATION_COLLECT_ATTRIBUTES_VALIDATED,
            ], \sprintf(
                'The "%s" node\'s %s:collectAttributes attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::true(\class_exists($configuration['castTo']), \sprintf(
                'The "%s" node\'s %s:castTo attribute value "%s" refers to non-existent class FQN',
                $currentPathString,
                $this->namespace,
                $configurationAttributes['castTo'],
            ));

            // Attributes rules
            foreach ($child->attributes() as $name => $rulesDefinition) {
                $rules = [];

                if (false === \preg_match_all('/(?P<rule>\w+)(?:\s*:\s*(?P<parameters>[^|]+)\s*)?/m', (string) $rulesDefinition, $matches, PREG_SET_ORDER)) {
                    throw new RuntimeException('Unexpected PRCE2 error');
                }

                foreach ($matches as $match) {
                    $parameters = isset($match['parameters']) ?
                        \preg_split('/\s*,\s*/', \trim($match['parameters'])) :
                        [];

                    $validateRuleMethodExists = \method_exists(
                        $configuration['castTo'],
                        $validateRuleMethod = \sprintf('validate%sRule', \ucfirst($match['rule'])),
                    );

                    $processRuleMethodExists = \method_exists(
                        $configuration['castTo'],
                        $processRuleMethod = \sprintf('process%sRule', \ucfirst($match['rule'])),
                    );

                    if ($validateRuleMethodExists || $processRuleMethodExists) {
                        $rules[] = new Callback(
                            $validateRuleMethodExists ?
                                $validateRuleMethod :
                                'noopValidateRule',
                            $processRuleMethodExists ?
                                $processRuleMethod :
                                'noopProcessRule',
                            $parameters
                        );

                        continue;
                    }

                    $ruleClass = \sprintf('\\Donatorsky\XmlTemplate\Reader\\Rules\\%s', \ucfirst($match['rule']));

                    if (!\class_exists($ruleClass)) {
                        throw new UnknownRuleException($match['rule']);
                    }

                    $rules[] = new $ruleClass(...$parameters);
                }

                $configuration['attributesRules'][$name] = $rules;
            }

            // Tag Open Listener
            $this->eventDispatcher->addListener(
                \sprintf('open@%s', $currentPathString),
                function (TagOpened $event) use (&$configuration, &$currentPathString): void {
                    $parentNodeHash = $event->getParentNodeHash();
                    $currentNodeName = $event->getNodeName();

                    $this->counter[$parentNodeHash][$currentNodeName] ??= 0;

                    if (self::CONFIGURATION_TYPE_SINGLE === $configuration['type'] && ++$this->counter[$parentNodeHash][$currentNodeName] > 1) {
                        throw new UnexpectedMultipleNodeReadException($currentPathString);
                    }

                    $parentNodeValueObject = $event->getParentNodeValueObject();
                    $attributes = $event->getAttributes();

                    /**
                     * @var \Donatorsky\XmlTemplate\Reader\Models\Node $currentNodeValueObject
                     */
                    $currentNodeValueObject = new $configuration['castTo']($currentNodeName, $parentNodeValueObject);

                    foreach ($attributes as $name => $value) {
                        if (!isset($configuration['attributesRules'][$name])) {
                            if (self::CONFIGURATION_COLLECT_ATTRIBUTES_ALL === $configuration['collectAttributes']) {
                                $currentNodeValueObject->setAttribute($name, $value);
                            }

                            continue;
                        }

                        foreach ($configuration['attributesRules'][$name] as $rule) {
                            if ($rule instanceof ContextAwareRuleInterface) {
                                $rule->withContext($currentNodeValueObject);
                            }

                            if (!$rule->passes($value)) {
                                throw new RuleValidationFailedException($name, $value, $currentPathString, $rule);
                            }

                            $value = $rule->process($value);
                        }

                        $currentNodeValueObject->setAttribute($name, $value);
                    }

                    switch ($configuration['type']) {
                        case self::CONFIGURATION_TYPE_SINGLE:
                            $parentNodeValueObject->addRelation($currentNodeName, $currentNodeValueObject);

                        break;

                        case self::CONFIGURATION_TYPE_COLLECTION:
                            $parentNodeValueObject->addChild($currentNodeName, $currentNodeValueObject);

                        break;
                    }

                    $this->pathForObject[] = $currentNodeValueObject;
                }
            );

            // Tag CData listener
            $this->eventDispatcher->addListener(
                \sprintf('cdata@%s', $currentPathString),
                static function (CDataRead $event) use (&$configuration): void {
                    if (self::CONFIGURATION_CONTENTS_NONE === $configuration['contents']) {
                        return;
                    }

                    $contents = $event->getContents();

                    if (null !== $contents && self::CONFIGURATION_CONTENTS_TRIMMED === $configuration['contents']) {
                        $contents = \trim($contents);
                    }

                    $event->getCurrentNodeValueObject()
                        ->setContents($contents);
                }
            );

            // Tag Close Listener
            $this->eventDispatcher->addListener(
                \sprintf('close@%s', $currentPathString),
                function (TagClosed $event): void {
                    \array_pop($this->pathForObject);
                }
            );

            // Parse children
            $this->addListenersFromTemplate($child, $currentPath);

            \array_pop($currentPath);
        }
    }
}
