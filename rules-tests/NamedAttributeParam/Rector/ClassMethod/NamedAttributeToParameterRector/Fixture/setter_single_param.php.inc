<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class SetterSingleParam
{
    #[Inject]
    #[Named('json')]
    public function setRenderer(RenderInterface $renderer): void
    {
    }
}

?>
-----
<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class SetterSingleParam
{
    #[Inject]
    public function setRenderer(#[\Ray\Di\Di\Named('json')]
    RenderInterface $renderer): void
    {
    }
}

?>
