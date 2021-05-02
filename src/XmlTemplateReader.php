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
use Donatorsky\XmlTemplate\Reader\Models\Collection;
use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\ContextAwareRuleInterface;
use JetBrains\PhpStorm\Language;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class XmlTemplateReader
{
    public const DEFAULT_CHUNK_SIZE = 4096;

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

    /**
     * @var string[]
     */
    private array $path = [];

    /**
     * @var string[]
     */
    private array $pathForHash = [];

    /**
     * @var non-empty-array<\Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface>
     */
    private array $pathForObject;

    /**
     * @var array<string,array<string,int>>
     */
    private array $counter = [];

    /**
     * @var array<string,class-string<\Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface>>
     */
    private array $rulesClassmap = [
        'float'       => Rules\FloatNumber::class,
        'greaterthan' => Rules\GreaterThan::class,
        'int'         => Rules\IntegerNumber::class,
        'integer'     => Rules\IntegerNumber::class,
        'numeric'     => Rules\Numeric::class,
        'required'    => Rules\Required::class,
        'trim'        => Rules\Trim::class,
    ];

    /**
     * @var resource|\XMLParser|null
     */
    private $xmlParser;

    /**
     * Whether parser is currently reading tag's character data or not.
     */
    private bool $inCData = false;

    private string $cData = '';

    /**
     * @throws \Assert\AssertionFailedException
     * @throws \Exception                       If the XML data could not be parsed. See {@see \SimpleXMLElement::__construct} for more details.
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
     * Registers new rule class that can be used for validating and transforming parameter's data.
     * Both name and aliases become case-insensitive.
     *
     * @param class-string<\Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface> $ruleClassFqn
     * @param string[]                                                                   $aliases
     *
     * @throws \Assert\AssertionFailedException
     */
    public function registerRuleFilter(string $name, string $ruleClassFqn, array $aliases = []): self
    {
        Assertion::subclassOf($ruleClassFqn, Rules\Contracts\RuleInterface::class);

        $aliases[] = $name;

        foreach ($aliases as $alias) {
            $this->rulesClassmap[\strtolower($alias)] = $ruleClassFqn;
        }

        return $this;
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function open(): self
    {
        Assertion::false($this->isOpened(), 'Reading is already in progress');

        $this->initializeParser();

        $this->pathForObject = [new Node('')];

        return $this;
    }

    public function isOpened(): bool
    {
        return null !== $this->xmlParser;
    }

    /**
     * @throws \Assert\AssertionFailedException When calling update without calling open first
     */
    public function update(
        #[Language('XML')]
        string $xml
    ): self {
        Assertion::true($this->isOpened());

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
    public function close(): NodeInterface
    {
        $this->deinitializeParser();

        Assertion::count($this->pathForObject, 1);

        $this->counter = [];

        /**
         * @var \Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface $wrapperObject
         */
        $wrapperObject = \array_pop($this->pathForObject);

        /**
         * @var \Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface $nodeValueObject
         */
        $nodeValueObject = $wrapperObject->getRelations()->first();

        return $nodeValueObject->setParent(null);
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function read(
        #[Language('XML')]
        string $xml
    ): NodeInterface {
        $this->open();

        $this->update($xml);

        return $this->close();
    }

    /**
     * @throws \Assert\AssertionFailedException When file could not be opened
     * @throws \Assert\AssertionFailedException When $chunkSize parameter is less than 1
     */
    public function readFile(
        string $path,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): ?NodeInterface {
        $stream = \fopen($path, 'rb');

        Assertion::isResource($stream, 'Provided file could not be opened');

        /**
         * @var resource $stream
         */
        return $this->readStream($stream, $chunkSize);
    }

    /**
     * @param resource $stream
     *
     * @throws \Assert\AssertionFailedException
     */
    public function readStream(
        $stream,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): ?NodeInterface {
        Assertion::greaterThan($chunkSize, 0, 'The read chunk size must be greater than 0');

        $this->open();

        try {
            while (!\feof($stream)) {
                $xml = \fread($stream, $chunkSize);

                Assertion::string($xml, 'Could not read XML data');

                /**
                 * @var string $xml
                 */
                $this->update($xml);
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
        \xml_set_element_handler($this->xmlParser,
            [$this, 'onTagOpenRead'],
            [$this, 'onTagCloseRead'],
        );
        \xml_set_character_data_handler($this->xmlParser, [$this, 'onCDataRead']);
        \xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 0);
        \xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 1);
    }

    private function deinitializeParser(): void
    {
        if (null !== $this->xmlParser) {
            \xml_parser_free($this->xmlParser);

            $this->xmlParser = null;
        }
    }

    /**
     * @param resource|\XMLParser  $xmlParser
     * @param array<string,string> $attributes
     */
    private function onTagOpenRead($xmlParser, string $nodeName, array $attributes): void
    {
        if ($this->inCData) {
            $this->onCDATARead($xmlParser, $this->cData);

            $this->inCData = false;
            $this->cData = '';
        }

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
     * @param resource|\XMLParser $xmlParser
     */
    private function onCDataRead($xmlParser, string $contents): void
    {
        $this->inCData = true;

        $this->eventDispatcher->dispatch(
            new CDataRead(
                \end($this->pathForObject),
                $contents,
            ),
            \sprintf('cdata@%s', \implode('/', $this->path)),
        );
    }

    /**
     * @param resource|\XMLParser $xmlParser
     */
    private function onTagCloseRead($xmlParser, string $nodeName): void
    {
        if ($this->inCData) {
            $this->onCDATARead($xmlParser, $this->cData);

            $this->inCData = false;
            $this->cData = '';
        }

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
     * @param string[] $path
     *
     * @throws \Assert\AssertionFailedException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\RuleValidationFailedException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\UnexpectedMultipleNodeReadException
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException
     */
    private function addListenersFromTemplate(SimpleXMLElement $simpleXMLElement, array $path = []): void
    {
        $children = $simpleXMLElement->children();

        if (0 === $children->count()) {
            return;
        }

        $currentPath = $path;

        foreach ($children as $child) {
            $currentPath[] = $child->getName();

            /** @var \SimpleXMLElement $configurationAttributes */
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
                    $ruleClass = $this->rulesClassmap[\strtolower($match['rule'])] ?? null;

                    if (null === $ruleClass) {
                        throw new UnknownRuleException($match['rule']);
                    }

                    $parameters = isset($match['parameters']) ?
                        \preg_split('/\s*,\s*/', \trim($match['parameters'])) :
                        [];

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
                     * @var \Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface $currentNodeValueObject
                     */
                    $currentNodeValueObject = new $configuration['castTo']($currentNodeName, $parentNodeValueObject);
                    $currentNodeAttributesMap = $currentNodeValueObject->getAttributes();

                    foreach ($attributes as $name => $value) {
                        if (!isset($configuration['attributesRules'][$name])) {
                            if (self::CONFIGURATION_COLLECT_ATTRIBUTES_ALL === $configuration['collectAttributes']) {
                                $currentNodeAttributesMap->set($name, $value);
                            }

                            continue;
                        }

                        $newValue = $value;

                        foreach ($configuration['attributesRules'][$name] as $rule) {
                            if ($rule instanceof ContextAwareRuleInterface) {
                                $rule->withContext($currentNodeValueObject);
                            }

                            if (!$rule->passes($newValue)) {
                                throw new RuleValidationFailedException($name, $newValue, $value, $currentPathString, $rule);
                            }

                            $newValue = $rule->process($newValue);
                        }

                        $currentNodeAttributesMap->set($name, $newValue);
                    }

                    switch ($configuration['type']) {
                        case self::CONFIGURATION_TYPE_SINGLE:
                            $parentNodeValueObject->getRelations()
                                ->set($currentNodeName, $currentNodeValueObject);

                        break;

                        case self::CONFIGURATION_TYPE_COLLECTION:
                            $parentNodeChildrenMap = $parentNodeValueObject->getChildren();

                            if ($parentNodeChildrenMap->has($currentNodeName)) {
                                $collection = $parentNodeChildrenMap->get($currentNodeName);
                            } else {
                                $collection = new Collection();
                                $parentNodeChildrenMap->set($currentNodeName, $collection);
                            }

                            $collection->push($currentNodeValueObject);

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
                function (): void {
                    \array_pop($this->pathForObject);
                }
            );

            // Parse children
            $this->addListenersFromTemplate($child, $currentPath);

            \array_pop($currentPath);
        }
    }
}
