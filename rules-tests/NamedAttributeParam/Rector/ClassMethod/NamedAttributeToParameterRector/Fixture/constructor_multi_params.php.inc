<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorMultiParams
{
    #[Named('a=foo,b=bar')]
    public function __construct(int $a, int $b)
    {
    }
}

?>
-----
<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorMultiParams
{
    public function __construct(
        #[\Ray\Di\Di\Named('foo')]
        int $a,
        #[\Ray\Di\Di\Named('bar')]
        int $b
    )
    {
    }
}

?>
