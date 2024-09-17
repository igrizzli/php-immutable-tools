To make it possible to create a modified copy of an immutable object use trait, and call method `with`.
```php
readonly class SomeData
{
    use ImmutableData;

    public function __construct(
        public int $field,
        public AnotherData $objectField,
        public bool $flag = false,
    ) {
    }
}

$object = new SomeData(field: 1, objectField: new AnotherData(), flag: true);
$newObject = $object->with(field: 2, flag: false);
```

You can also use rector for automatic generation phpDoc for `with` method in each class which use `ImmutableData`

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Vlx\Immutable\ImmutableModelsRector;

return RectorConfig::configure()
    ...
    ->withRules([ImmutableModelsRector::class]);
```