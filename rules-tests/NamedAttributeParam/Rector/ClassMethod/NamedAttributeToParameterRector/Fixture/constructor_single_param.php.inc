<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorSingleParam
{
    #[Named('original')]
    public function __construct(
        private readonly \Twig\Environment $twig,
    )
    {
    }
}

?>
-----
<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorSingleParam
{
    public function __construct(
        #[\Ray\Di\Di\Named('original')]
        private readonly \Twig\Environment $twig
    )
    {
    }
}

?>
