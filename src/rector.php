<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;
use SavinMikhail\AddNamedArgumentsRector\AddNamedArgumentsRector;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ]);

    $config->skip([
        __DIR__ . '/database/migrations',
        __DIR__ . '/database/seeders',
        __DIR__ . '/bootstrap/cache',
    ]);

    $config->importNames();
    $config->importShortClasses();
    $config->removeUnusedImports();

    $config->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
    ]);

    $config->ruleWithConfiguration(AddNamedArgumentsRector::class, [
        AddNamedArgumentsRector::ALLOW_NAMED_VARIADIC_ARGUMENTS => false,
    ]);
};
