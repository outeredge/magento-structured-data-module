<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->paths([__DIR__]);

    $rectorConfig->skip([__DIR__ . '/vendor']);

    $parameters = $rectorConfig->parameters();

    $parameters->set(Option::FILE_EXTENSIONS, [
        'php',
        'phtml'
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        //SetList::CODE_QUALITY
    ]);
};
