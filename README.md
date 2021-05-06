# PHP XML Template Reader

The PHP XML Reader where you show how to read the XML, and it does the rest for you.

[![GitHub license](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/donatorsky/php-xml-template-reader/blob/main/LICENSE)
[![Build](https://github.com/donatorsky/php-xml-template-reader/workflows/CI/badge.svg?branch=main)](https://github.com/donatorsky/php-xml-template-reader/actions?query=branch%3Amain)
[![Coverage Status](https://coveralls.io/repos/github/donatorsky/php-xml-template-reader/badge.svg?branch=main)](https://coveralls.io/github/donatorsky/php-xml-template-reader?branch=main)

## How it works

The PHP XML Template Reader helps You to parse given XML file and create an object from it. The parser uses given template as a schema and tries to match it to input XML, optionally validating it with defined rules.

To start, simply create new `\Donatorsky\XmlTemplate\Reader\XmlTemplateReader` object, pass it the template and read using one of available reading modes.

### Example 1

Assuming the following XML:

```xml
<books>
    <book ISBN="1234567890"
          category="adventures">
        <title>Lorem ipsum adventures</title>
        ...
    </book>
    ...
</books>
```

You can already see a pattern, so You can define the template as follows:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <books>
        <book tpl:type="collection"
              ISBN="required | integer"
              category="">
            <title tpl:contents="raw" />
            ...
        </book>
        ...
    </books>
</template>
```

As the output You will see an object of type `\Donatorsky\XmlTemplate\Reader\Models\Node` (by default, can be changed) with processed data:

```php
Node {
    private $nodeName = 'books';
    
    private $children = Map [
        // Because of tpl:type="collection", book element is expected to occur more than 1 times
        'book' => Collection [
            0 => Node {
                    private $nodeName = 'book';
                    
                    private $attributes = Map [
                        // You can define set of parsing rules in the template. In this case:
                        // required | integer
                        // Means, that value cannot be empty and has to be a valid number. It is also converted to the integer.
                        'ISBN' => 1234567890,
                        
                        // No filters defined means the value is read "as is"
                        'category' => 'adventures',
                    ];
                    
                    // By default, tpl:type="single", so title is expected to occur at most 1 time
                    private $relations = Map [
                        'title' => Node {
                                        private $nodeName = 'title';
                                        
                                        private $contents = 'Lorem ipsum adventures';
                                    },
                        ...
                    ];
                },
            1 => ...
        ]
    ];
}
```

Please note, that only nodes defined in the template are present in the output Node. When XML changes, You need to update the template.

### Example 2

The Reader supports namespaced nodes and attributes. In case suggested template's `tpl` namespace conflicts with Yours, feel free to change it to any other XML valid value:

```xml

<tpl:books xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance">
    <tpl:book tpl:ISBN="1234567890"
              tpl:category="adventures">
        <tpl:title>Lorem ipsum adventures</tpl:title>
        ...
    </tpl:book>
    ...
</tpl:books>
```

You can already see a pattern, so You can define the template as follows:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:my-namespace="http://www.w3.org/2001/XMLSchema-instance"
          my-namespace:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <tpl:books>
        <tpl:book my-namespace:type="collection"
                  tpl:ISBN="required | integer"
                  tpl:category="">
            <tpl:title my-namespace:contents="raw" />
            ...
        </tpl:book>
        ...
    </tpl:books>
</template>
```

## Reading modes

Multiple reading modes are available. Given the following example code:

```php
$xmlTemplateReader = new \Donatorsky\XmlTemplate\Reader\XmlTemplateReader(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    // ...
</template>
XML
);
```

### `read`: read XML from string

You can provide XML contents and parse it using `read` method:

```php
$node = $xmlTemplateReader->read(<<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
// ...
XML
);
```

### `readFile`: read XML from file in given path

You can provide a path to the XML file and parse it using `readFile` method:

```php
$node = $xmlTemplateReader->readFile('/path/to/file.xml');
```

### `readStream`: read XML from already opened resource

You can provide a resource with the XML contents and parse it using `readStream` method:

```php
$handler = fopen('/path/to/file.xml', 'rb+');

$node = $xmlTemplateReader->readStream($handler);
```

### `open`, `update` and `close`: custom stream XML reading

You can read the XML chunk by chunk using You own implementation with `open`, `update` and `close` methods:

```php
$handler = fopen('/path/to/file.xml', 'rb+');

$xmlTemplateReader->open();

while (!\feof($handler)) {
    $this->update(\fread($handler, 1024));
}

$node = $xmlTemplateReader->close();
```

## Parsing modifiers

You can use various parsing modifiers to define some behaviours. Examples below use `tpl` namespace.

### tpl:castTo

Accepted values: class' FQN, must implement `\Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface`.

By default, when node is parsed, it creates new `\Donatorsky\XmlTemplate\Reader\Models\Node` instance with parsed data. However, You can use Your own class. This class must implement `\Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface` interface.

#### Example

Define node classes:

```php
namespace Some\Name\Space;

class BooksNode implements \Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface {
    // ...
}

// You can also extend \Donatorsky\XmlTemplate\Reader\Models\Node class
class SingleBookNode extends \Donatorsky\XmlTemplate\Reader\Models\Node {
    // ...
    
    public function getIsbn(): int {
        return $this->attributes->get('ISBN');
    }
    
    public function getCategory(): int {
        return $this->attributes->get('category');
    }
}
```

Use them in the template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <books tpl:castTo="\Some\Name\Space\BooksNode">
        <book tpl:type="collection"
              tpl:castTo="\Some\Name\Space\SingleBookNode"
              ISBN="required | integer"
              category="">
            <title tpl:contents="raw" />
            ...
        </book>
        ...
    </books>
</template>
```

The output:

```php
$booksNode = Some\Name\Space\BooksNode {
    private $nodeName = 'books';
    
    private $children = \Donatorsky\XmlTemplate\Reader\Models\Map [
        // Because of tpl:type="collection", book element is expected to occur more than 1 times
        'book' => \Donatorsky\XmlTemplate\Reader\Models\Collection [
            0 => Some\Name\Space\SingleBookNode {
                    private $nodeName = 'book';
                    
                    private $attributes = \Donatorsky\XmlTemplate\Reader\Models\Map [
                        // You can define set of parsing rules in the template. In this case:
                        // required | integer
                        // Means, that value cannot be empty and has to be a valid number. It is also converted to the integer.
                        'ISBN' => 1234567890,
                        
                        // No filters defined means the value is read "as is"
                        'category' => 'adventures',
                    ];
                    
                    // By default, tpl:type="single", so title is expected to occur at most 1 time
                    private $relations = \Donatorsky\XmlTemplate\Reader\Models\Map [
                        'title' => \Donatorsky\XmlTemplate\Reader\Models\Node {
                                        private $nodeName = 'title';
                                        
                                        private $contents = 'Lorem ipsum adventures';
                                    },
                        ...
                    ];
                },
            1 => ...
        ]
    ];
}

// ...

/**
 * @var \Some\Name\Space\SingleBookNode $book
 */
foreach ($booksNode->getChildren('book') as $book){
    var_dump($book->getIsbn(), $book->getCategory());
}
```

### tpl:collectAttributes

Accepted values: `all`, `validated` (default).

By default, only validated nodes' attributes are collected. This means, that only attributes that are defined in the template are collected. However, You can change it if You also want to collect other attributes.

Given the input XML:

```xml

<books ISBN="1234567890"
       category="adventures">
    ...
</books>
```

#### Example 1

With the following template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <books tpl:collectAttributes="all"
           ISBN="">
        ...
    </books>
</template>
```

You will get:

```php
Node {
    private $nodeName = 'books';
    
    private $attributes = Map [
        'ISBN'     => '1234567890',
        'category' => 'adventures',
    ];
}
```

#### Example 2

With the following template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <books tpl:collectAttributes="validated"
           ISBN="">
        ...
    </books>
</template>
```

You will get:

```php
Node {
    private $nodeName = 'books';
    
    private $attributes = \Donatorsky\XmlTemplate\Reader\Models\Map [
        'ISBN' => '1234567890',
        // 'category' is missing as it is not "validated"
    ];
}
```

### tpl:contents

Accepted values: `none` (default when `tpl:type` = collection), `raw` (default when `tpl:type` = single), `trimmed`.

By default, no node's contents is collected (`none`). This is especially useful for nodes containing other nodes, thus the contents is only a bunch of whitespaces (when XML if pretty-printed). You can change this behaviour and collect raw, unchanged contents (`raw`) of the node or additionally trim whitespaces (`trimmed`).

#### Example

Given the input XML:

```xml

<book>
    <title>...</title>

    <description>
        ...
        ...
    </description>

    <authors>
        // ...
    </authors>
</book>
```

With the following template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <book>
        <title tpl:contents="raw" />
        <description tpl:contents="trimmed" />
        <authors tpl:contents="none" />
    </book>
</template>
```

You will get:

```php
Node {
    private $nodeName = 'books';
    
    private $relations = Map [
        'title' => Node {
                        private $nodeName = 'title';
                        
                        private $contents = '...';
                    },
        'description' => Node {
                        private $nodeName = 'title';
                        
                        private $contents = '...
        ...';
                    },
        'authors' => Node {
                        private $nodeName = 'title';
                        
                        private $contents = null;
                    },
    ];
}
```

### tpl:type

Accepted values: `single` (default), `collection`.

By default, each node defined in the template is considered to be a single (`single`). However, if You expect multiple elements of the same type, You can change it (`collection`).

#### Example

Given the input XML:

```xml

<book>
    <title>...</title>

    <authors>
        <author>...</author>
        <author>...</author>
        <author>...</author>
    </authors>
</book>
```

With the following template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <book>
        <title tpl:type="single" />
        <authors>
            <author tpl:type="collection">
                // ...
            </author>
        </authors>
    </book>
</template>
```

You will get:

```php
Node {
    private $nodeName = 'books';
    
    private $relations = Map [
        'title' => Node {
                        private $nodeName = 'title';
                    }
    ];
    
    private $children = Map [
        'author' => Collection [
            0 => Node {
                    private $nodeName = 'author';
                },
            1 => Node {
                    private $nodeName = 'author';
                },
            2 => Node {
                    private $nodeName = 'author';
                },
        ]
    ];
}
```

## Rules

Rules are simple validators and transformers that can be chained. You can use rules to define attributes constraints and transform them to expected value. Rules may be aliased. Rules can accept additional attributes. Names and aliases are case-insensitive. You can examine built-in rules in `src/Rules` directory, or You can create one by implementing `\Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface` interface.

### Built-in rules

Rule name | Aliases | Validation | Transformation
--------- | ------- | ---------- | --------------
callback | | Custom validation rule | Custom transformation
float | | [Numeric](https://www.php.net/manual/en/function.is-numeric.php) value | Cast to float type
greaterThan:threshold | | [Numeric](https://www.php.net/manual/en/function.is-numeric.php) value greater than _threshold_ | Value unchanged
int | integer | [Numeric](https://www.php.net/manual/en/function.is-numeric.php) value | Cast to int type
numeric | | [Numeric](https://www.php.net/manual/en/function.is-numeric.php) value | Cast to int or float type
required | | Value must not be [empty](https://www.php.net/manual/en/function.empty) | Value unchanged
trim | | String value | Value is [trimmed](https://www.php.net/manual/en/function.trim)

### Custom rules

To define custom rule You need to first create a class that implements RuleInterface:

```php
namespace Some\Name\Space;

class RegexpRule implements \Donatorsky\XmlTemplate\Reader\Rules\Contracts\RuleInterface {

    private string $pattern;
    
    public function __construct(string $pattern) {
        $this->pattern = $pattern;
    }

    public function passes($value) : bool {
        // Validate $value against pattern
        return (bool) preg_match($this->pattern, $value);
    }
    
    public function process($value) {
        // Do not modify value
        return $value;
    }
}
```

Then, You need to register rule class:

```php
$xmlTemplateReader->registerRuleFilter(
    'regexp', // name
    \Some\Name\Space\RegexpRule::class, // Rule class' FQN
    [
        'matches',
    ] // optionalAliases
);
```

And use it in the template:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<template xmlns:tpl="http://www.w3.org/2001/XMLSchema-instance"
          tpl:noNamespaceSchemaLocation="./vendor/donatorsky/php-xml-template-reader/xml-template-reader.xsd">
    <book ISBN="regexp:/^\d{13}$/"
          category="matches:/^\w+$/i">
        // ...
    </book>
</template>
```
