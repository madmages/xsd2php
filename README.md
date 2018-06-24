xsd2php
=======
Convert XSD/WSDL into PHP classes.

XSD2PHP can also generate [JMS Serializer](http://jmsyst.com/libs/serializer) compatible metadata that can be used to serialize/unserialize the object instances.

**Fork of [goetas-webservices/xsd2php](https://github.com/goetas-webservices/xsd2php)**

## Installation

There is one recommended way to install xsd2php via [Composer](https://getcomposer.org/):


* adding the dependency to your ``composer.json`` file:

```js
  "require": {
      ..
      "goetas-webservices/xsd2php-runtime":"^0.2.2",
      ..
  },
  "require-dev": {
      ..
      "madmages/xsd2php":"^1.1",
      ..
  },
```

## Usage example

```php
use Madmages\Xsd\XsdToPhp\App;
use Madmages\Xsd\XsdToPhp\Config;

include 'vendor/autoload.php';

$config = (new Config)
    ->addNamespace('http://zakupki.gov.ru/223fz/types/1', 'ZGR\\Types', 'classes', 'jms')
    ->handleGeneratedClass(function ($class) {
        return $class;
    })
    ->handleGeneratedMethod(function ($method) {
        return $method;
    });

App::run(['xsd/Types.xsd'], $config);
```


Serialize / Unserialize
-----------------------

XSD2PHP can also generate for you [JMS Serializer](http://jmsyst.com/libs/serializer) metadata 
that you can use to serialize/unserialize the generated PHP class instances.

The parameter `aliases` in the configuration file, will instruct XSD2PHP to not generate any metadata information or
PHP class for the `{http://www.example.org/test/}MyCustomXSDType` type.
All reference to this type are replaced with the `MyCustomMappedPHPType` name.

You have to provide a [custom serializer](http://jmsyst.com/libs/serializer/master/handlers#subscribing-handlers) 
for this type/alis.


Here is an example on how to configure JMS serializer to handle custom types

```php
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistryInterface;

use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;

$serializerBuilder = SerializerBuilder::create();
$serializerBuilder->addMetadataDir('metadata dir', 'TestNs');
$serializerBuilder->configureHandlers(function (HandlerRegistryInterface $handler) use ($serializerBuilder) {
    $serializerBuilder->addDefaultHandlers();
    $handler->registerSubscribingHandler(new BaseTypesHandler()); // XMLSchema List handling
    $handler->registerSubscribingHandler(new XmlSchemaDateHandler()); // XMLSchema date handling

    // $handler->registerSubscribingHandler(new YourhandlerHere());
});

$serializer = $serializerBuilder->build();

// deserialize the XML into Demo\MyObject object
$object = $serializer->deserialize('<some xml/>', 'TestNs\MyObject', 'xml');

// some code ....

// serialize the Demo\MyObject back into XML
$newXml = $serializer->serialize($object, 'xml');

```

Dealing with `xsd:anyType` or `xsd:anySimpleType`
-------------------------------------------------

If your XSD contains `xsd:anyType` or `xsd:anySimpleType` types you have to specify a handler for this.

When you generate the JMS metadata you have to specify a custom handler:

```php
use Madmages\Xsd\XsdToPhp\App;
use Madmages\Xsd\XsdToPhp\Config;

include 'vendor/autoload.php';

// aliases xsd_type => php_type
$aliases = [
    'anyType'       => 'MyCustomAnyTypeHandler'
    'anySimpleType' => 'MyCustomAnySimpleTypeHandler'
];

$config = (new Config)
        ->addNamespace('http://zakupki.gov.ru/223fz/types/1', 'ZGR\\Types', 'classes', 'jms', $aliases)

App::run(['xsd/Types.xsd'], $config);
      
```

Now you have to create a custom serialization handler:

```php
use JMS\Serializer\XmlSerializationVisitor;
use JMS\Serializer\XmlDeserializationVisitor;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Context;

class MyHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'MyCustomAnyTypeHandler',
                'method' => 'deserializeAnyType'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'MyCustomAnyTypeHandler',
                'method' => 'serializeAnyType'
            )
        );
    }

    public function serializeAnyType(XmlSerializationVisitor $visitor, $data, array $type, Context $context)
    {
        // serialize your object here
    }

    public function deserializeAnyType(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        // deserialize your object here
    }
}
```

Naming Strategy
---------------

There are two types of naming strategies: `short` and `long`. The default is `short`, this naming strategy can however generate naming conflicts.

The `long` naming strategy will suffix elements with `Element` and types with `Type`.

* `MyNamesapce\User` will become `MyNamesapce\UserElement`
* `MyNamesapce\UserType` will become `MyNamesapce\UserTypeType`

An XSD for instance with a type named `User`, a type named `UserType`, a root element named `User` and `UserElement`, will only work when using the `long` naming strategy.

* If you don't have naming conflicts and you want to have short and descriptive class names, use the `short` option.
* If you have naming conflicts use the `long` option.
* If you want to be safe, use the `long` option.
