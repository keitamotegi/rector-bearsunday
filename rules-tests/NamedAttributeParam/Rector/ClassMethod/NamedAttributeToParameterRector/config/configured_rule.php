<?php

declare(strict_types=1);

use Rector\BearSunday\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        NamedAttributeToParameterRector::class,
    ]);
