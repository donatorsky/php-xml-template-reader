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
use Donatorsky\XmlTemplate\Reader\Exceptions\XmlParsingFailedException;
use Donatorsky\XmlTemplate\Reader\Models\Collection;
use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Models\Node;
use Donatorsky\XmlTemplate\Reader\Rules\Contracts\ContextAwareRuleInterface;
use JetBrains\PhpStorm\Language;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use function Safe\fopen;
use function Safe\fread;
use function Safe\xml_parser_create;
use function Safe\xml_set_object;

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

    private ?string $template = null;

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
     * @var array<NodeInterface>
     */
    private array $pathForObject = [];

    /**
     * @var array<string,array<string,int>>
     */
    private array $counter = [];

    /**
     * @var array<string,class-string<Rules\Contracts\RuleInterface>>
     */
    private array $rulesClassmap = [
        'callback'    => Rules\Callback::class,
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

    public function __construct(
        #[Language('XML')]
        string $template,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->template = $template;
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
    }

    /**
     * @throws \Assert\AssertionFailedException                               When template namespace configuration is incorrect
     * @throws \Donatorsky\XmlTemplate\Reader\Exceptions\UnknownRuleException When template contains {@see registerRuleFilter unregistered} custom rules
     * @throws \Exception                                                     If the XML data could not be parsed. See {@see \SimpleXMLElement::__construct} for more details.
     */
    public function preloadTemplate(): self
    {
        // Check if template is already loaded
        if (null === $this->template) {
            return $this;
        }

        // If not, then parse it
        $simpleXMLElement = new SimpleXMLElement(
            $this->template,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET,
        );

        /** @var non-empty-array<non-empty-string,string> $namespaces */
        $namespaces = $simpleXMLElement->getNamespaces();

        Assertion::count($namespaces, 1, 'You need to specify exactly one template namespace, %2$d provided');

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->namespace = key($namespaces);

        $this->addListenersFromTemplate($simpleXMLElement);

        $this->template = null;

        return $this;
    }

    public function isPreloaded(): bool
    {
        return null === $this->template;
    }

    /**
     * Returns the template namespace.
     * Should not be called before a template is loaded.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Registers new rule class that can be used for validating and transforming parameter's data.
     * Both name and aliases become case-insensitive.
     *
     * @param string                                      $name         The name of the rule. It must not be empty and consist of letters, numbers and _ only.
     * @param class-string<Rules\Contracts\RuleInterface> $ruleClassFqn
     * @param string[]                                    $aliases
     *
     * @throws \Assert\AssertionFailedException When $name is invalid
     * @throws \Assert\AssertionFailedException When $ruleClassFqn does not implement RuleInterface
     */
    public function registerRuleFilter(string $name, string $ruleClassFqn, array $aliases = []): self
    {
        Assertion::regex($name, '/^\w+$/', 'The "%1$s" name of the rule is invalid.');
        Assertion::subclassOf($ruleClassFqn, Rules\Contracts\RuleInterface::class);

        $aliases[] = $name;

        foreach ($aliases as $alias) {
            Assertion::regex($alias, '/^\w+$/', 'The "%1$s" alias name of the rule is invalid.');

            $this->rulesClassmap[strtolower($alias)] = $ruleClassFqn;
        }

        return $this;
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws \Safe\Exceptions\XmlException
     */
    public function open(): self
    {
        Assertion::false($this->isOpened(), 'Reading is already in progress');

        $this->initializeParser();
        $this->preloadTemplate();

        $this->pathForObject = [new Node('')];

        return $this;
    }

    public function isOpened(): bool
    {
        return null !== $this->xmlParser;
    }

    /**
     * @throws \Assert\AssertionFailedException When calling update without calling open first
     * @throws \Throwable                       When there was an error during parsing
     */
    public function update(
        #[Language('XML')]
        string $xml
    ): self {
        Assertion::true($this->isOpened(), 'Streamed reading has not been started yet, ::open() it first.');

        $exception = null;

        try {
            if (!xml_parse($this->xmlParser, $xml)) {
                throw new XmlParsingFailedException(
                    $errorCode = xml_get_error_code($this->xmlParser),
                    xml_error_string($errorCode) ?? 'Unknown parsing error',
                    xml_get_current_line_number($this->xmlParser),
                    xml_get_current_column_number($this->xmlParser),
                    xml_get_current_byte_index($this->xmlParser),
                );
            }
        } catch (Throwable $exception) {
        } finally {
            if (null !== $exception) {
                $this->deinitializeParser();

                throw $exception;
            }
        }

        return $this;
    }

    /**
     * @throws \Assert\AssertionFailedException When XML reading has not been started yet
     * @throws \Assert\AssertionFailedException When XML reading is not finished yet and there are still open nodes
     */
    public function close(): NodeInterface
    {
        Assertion::true($this->isOpened(), 'Streamed reading has not been started yet, ::open() it first.');

        $this->deinitializeParser();

        Assertion::count($this->path, 0, 'Streamed reading has not been finished yet, there are still %2$s node(s) opened.');

        $this->counter = [];

        /** @var NodeInterface $wrapperObject */
        $wrapperObject = array_pop($this->pathForObject);

        /** @var NodeInterface $nodeValueObject */
        $nodeValueObject = $wrapperObject->getRelations()->first();

        return $nodeValueObject->setParent(null);
    }

    /**
     * @throws \Throwable When there was an error during parsing
     */
    public function read(
        #[Language('XML')]
        string $xml
    ): NodeInterface {
        return $this->open()
            ->update($xml)
            ->close();
    }

    /**
     * @throws \Assert\AssertionFailedException When file could not be opened
     * @throws \Assert\AssertionFailedException When $chunkSize parameter is less than 1
     * @throws \Throwable                       When there was an error during parsing
     */
    public function readFile(
        string $path,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): ?NodeInterface {
        return $this->readStream(fopen($path, 'rb'), $chunkSize);
    }

    /**
     * @param resource $stream
     *
     * @throws \Assert\AssertionFailedException When $chunkSize parameter is less than 1
     * @throws \Throwable                       When there was an error during parsing
     */
    public function readStream(
        $stream,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): ?NodeInterface {
        Assertion::greaterThan($chunkSize, 0, 'Provided read chunk size %1$s must be greater than 0.');

        $this->open();

        try {
            while (!feof($stream)) {
                $this->update(fread($stream, $chunkSize));
            }
        } finally {
            fclose($stream);
        }

        return $this->close();
    }

    /**
     * @throws \Safe\Exceptions\XmlException
     */
    private function initializeParser(): void
    {
        $this->xmlParser = xml_parser_create('UTF-8');

        xml_set_object($this->xmlParser, $this);
        xml_set_element_handler(
            $this->xmlParser,
            fn (...$arguments) => $this->onTagOpenRead(...$arguments),
            fn (...$arguments) => $this->onTagCloseRead(...$arguments),
        );
        xml_set_character_data_handler(
            $this->xmlParser,
            fn (...$arguments) => $this->onCDataRead(...$arguments),
        );
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 1);
    }

    private function deinitializeParser(): void
    {
        if (null !== $this->xmlParser) {
            xml_parse($this->xmlParser, '', true);
            xml_parser_free($this->xmlParser);

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
            $this->dispatchCDataReadEvent();

            $this->inCData = false;
            $this->cData = '';
        }

        $this->path[] = $nodeName;

        /** @var NodeInterface $parentNodeValueObject */
        $parentNodeValueObject = end($this->pathForObject);

        $this->eventDispatcher->dispatch(
            new TagOpened(
                $parentNodeValueObject,
                $nodeName,
                $attributes,
                md5(implode("\0", $this->pathForHash)),
            ),
            sprintf('open@%s', implode('/', $this->path)),
        );

        $this->pathForHash[] = sprintf('%s %.6f', $nodeName, microtime(true));
    }

    /**
     * @param resource|\XMLParser $xmlParser
     */
    private function onCDataRead($xmlParser, string $contents): void
    {
        $this->inCData = true;
        $this->cData .= $contents;
    }

    private function dispatchCDataReadEvent(): void
    {
        /** @var NodeInterface $currentNodeValueObject */
        $currentNodeValueObject = end($this->pathForObject);

        $this->eventDispatcher->dispatch(
            new CDataRead(
                $currentNodeValueObject,
                $this->cData,
            ),
            sprintf('cdata@%s', implode('/', $this->path)),
        );
    }

    /**
     * @param resource|\XMLParser $xmlParser
     */
    private function onTagCloseRead($xmlParser, string $nodeName): void
    {
        if ($this->inCData) {
            $this->dispatchCDataReadEvent();

            $this->inCData = false;
            $this->cData = '';
        }

        array_pop($this->pathForHash);

        /** @var NodeInterface $currentNodeValueObject */
        $currentNodeValueObject = end($this->pathForObject);

        $this->eventDispatcher->dispatch(
            new TagClosed(
                $currentNodeValueObject,
                $nodeName,
            ),
            sprintf('close@%s', implode('/', $this->path)),
        );

        array_pop($this->path);
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

        if ($children->count() < 1) {
            return;
        }

        $currentPath = $path;

        foreach ($children as $child) {
            $currentPath[] = $child->getName();

            /** @var SimpleXMLElement $configurationAttributes */
            $configurationAttributes = $child->attributes($this->namespace, true);
            $currentPathString = implode('/', $currentPath);

            // Read parsing configuration
            $configuration = [
                'required' => filter_var(
                    (string) ($configurationAttributes['required'] ?? self::CONFIGURATION_REQUIRED_TRUE),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),

                'attributesRules'   => [],
                'contents'          => '',
                'type'              => (string) ($configurationAttributes['type'] ?? self::CONFIGURATION_TYPE_SINGLE),
                'collectAttributes' => (string) ($configurationAttributes['collectAttributes'] ?? self::CONFIGURATION_COLLECT_ATTRIBUTES_VALIDATED),
                'castTo'            => (string) ($configurationAttributes['castTo'] ?? Node::class),
            ];

            if (isset($configurationAttributes['contents'])) {
                $configuration['contents'] = (string) $configurationAttributes['contents'];
            } else {
                $configuration['contents'] = self::CONFIGURATION_TYPE_SINGLE === $configuration['type'] ?
                    self::CONFIGURATION_CONTENTS_NONE :
                    self::CONFIGURATION_CONTENTS_RAW;
            }

            Assertion::notNull($configuration['required'], sprintf(
                'The "%s" node\'s %s:required attribute value "%s" is invalid, true or false was expected',
                $currentPathString,
                $this->namespace,
                $configurationAttributes['required'],
            ));

            Assertion::choice($configuration['contents'], [
                self::CONFIGURATION_CONTENTS_NONE,
                self::CONFIGURATION_CONTENTS_RAW,
                self::CONFIGURATION_CONTENTS_TRIMMED,
            ], sprintf(
                'The "%s" node\'s %s:contents attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::choice($configuration['type'], [
                self::CONFIGURATION_TYPE_SINGLE,
                self::CONFIGURATION_TYPE_COLLECTION,
            ], sprintf(
                'The "%s" node\'s %s:type attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::choice($configuration['collectAttributes'], [
                self::CONFIGURATION_COLLECT_ATTRIBUTES_ALL,
                self::CONFIGURATION_COLLECT_ATTRIBUTES_VALIDATED,
            ], sprintf(
                'The "%s" node\'s %s:collectAttributes attribute value "%%1$s" is invalid, expecting one of: %%2$s',
                $currentPathString,
                $this->namespace,
            ));

            Assertion::classExists($configuration['castTo'], sprintf(
                'The "%s" node\'s %s:castTo attribute value "%s" refers to non-existent class FQN',
                $currentPathString,
                $this->namespace,
                $configurationAttributes['castTo'],
            ));
            Assertion::subclassOf($configuration['castTo'], NodeInterface::class, sprintf(
                'The "%s" node\'s %s:castTo attribute value "%s" refers to a class that does not implement "%s" interface',
                $currentPathString,
                $this->namespace,
                $configurationAttributes['castTo'],
                NodeInterface::class,
            ));

            // Attributes rules
            foreach ($child->attributes() as $name => $rulesDefinition) {
                $rules = [];

                if (false === preg_match_all('/(?P<rule>\w+)(?:\s*:\s*(?P<parameters>[^|]+)\s*)?/m', (string) $rulesDefinition, $matches, PREG_SET_ORDER)) {
                    // @codeCoverageIgnoreStart
                    throw new RuntimeException('Unexpected PRCE2 error');
                    // @codeCoverageIgnoreEnd
                }

                foreach ($matches as $match) {
                    $ruleClass = $this->rulesClassmap[strtolower($match['rule'])] ?? null;

                    if (null === $ruleClass) {
                        throw new UnknownRuleException($match['rule']);
                    }

                    $parameters = isset($match['parameters']) ?
                        preg_split('/\s*,\s*/', trim($match['parameters'])) :
                        [];

                    $rules[] = new $ruleClass(...$parameters);
                }

                $configuration['attributesRules'][$name] = $rules;
            }

            // Tag Open Listener
            $this->eventDispatcher->addListener(
                sprintf('open@%s', $currentPathString),
                function (TagOpened $event) use ($configuration, $currentPathString): void {
                    $parentNodeHash = $event->getParentNodeHash();
                    $currentNodeName = $event->getNodeName();

                    $this->counter[$parentNodeHash][$currentNodeName] ??= 0;

                    if (self::CONFIGURATION_TYPE_SINGLE === $configuration['type'] && ++$this->counter[$parentNodeHash][$currentNodeName] > 1) {
                        throw new UnexpectedMultipleNodeReadException($currentPathString);
                    }

                    $parentNodeValueObject = $event->getParentNodeValueObject();
                    $attributes = $event->getAttributes();

                    /** @var NodeInterface $currentNodeValueObject */
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
                sprintf('cdata@%s', $currentPathString),
                static function (CDataRead $event) use ($configuration): void {
                    if (self::CONFIGURATION_CONTENTS_NONE === $configuration['contents']) {
                        return;
                    }

                    $contents = $event->getContents();

                    if (null !== $contents && self::CONFIGURATION_CONTENTS_TRIMMED === $configuration['contents']) {
                        $contents = trim($contents);
                    }

                    $event->getCurrentNodeValueObject()
                        ->setContents($contents);
                }
            );

            // Tag Close Listener
            $this->eventDispatcher->addListener(
                sprintf('close@%s', $currentPathString),
                function (): void {
                    array_pop($this->pathForObject);
                }
            );

            // Parse children
            $this->addListenersFromTemplate($child, $currentPath);

            array_pop($currentPath);
        }
    }
}
