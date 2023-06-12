<?php
declare(strict_types=1);

use Ctw\Qa\Rector\Config\RectorConfig\DefaultFileExtensions;

use Ctw\Qa\Rector\Config\RectorConfig\DefaultSets;
use Ctw\Qa\Rector\Config\RectorConfig\DefaultSkip;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    $fileExtensions = new DefaultFileExtensions();
    $sets           = new DefaultSets();
    $skip           = new DefaultSkip();

    $rectorConfig->fileExtensions($fileExtensions());

    $rectorConfig->paths(
        [
            sprintf('%s/src', __DIR__),
            sprintf('%s/test', __DIR__),
            sprintf('%s/ecs.php', __DIR__),
            sprintf('%s/rector.php', __DIR__),
        ]
    );

    $rectorConfig->sets($sets());

    $rectorConfig->skip([...$skip(), sprintf('%s/test/asset/*', __DIR__)]);
};
