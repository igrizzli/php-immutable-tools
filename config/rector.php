<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Vlx\Immutable\src\ImmutableModelsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ImmutableModelsRector::class);
};