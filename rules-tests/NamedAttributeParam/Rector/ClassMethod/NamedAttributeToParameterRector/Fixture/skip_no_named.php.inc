<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Inject;

class SkipNoNamed
{
    #[Inject]
    public function setRenderer(RenderInterface $renderer): void
    {
    }
}

?>
