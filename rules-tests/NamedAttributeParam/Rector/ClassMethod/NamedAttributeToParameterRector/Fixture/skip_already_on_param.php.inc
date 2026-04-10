<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class SkipAlreadyOnParam
{
    #[Inject]
    public function setRenderer(#[Named('json')] RenderInterface $renderer): void
    {
    }
}

?>
