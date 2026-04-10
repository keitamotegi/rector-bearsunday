<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorPartialNamed
{
    #[Named('a=foo')]
    public function __construct(int $a, int $b)
    {
    }
}

?>
-----
<?php

namespace Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\Fixture;

use Ray\Di\Di\Named;

class ConstructorPartialNamed
{
    public function __construct(
        #[\Ray\Di\Di\Named('foo')]
        int $a,
        int $b
    )
    {
    }
}

?>
