<?php

declare(strict_types=1);

namespace Rector\BearSunday\NamedAttributeParam\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Ray\Di\Di\Named;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function array_merge;
use function assert;
use function count;
use function explode;
use function is_string;
use function substr;
use function trim;

/**
 * メソッドレベルの #[Named] 属性をパラメータレベルに移動する
 *
 * ray/di 2.19 以降、メソッドレベルの #[Named] がパラメータに自動伝播しなくなったため、
 * パラメータレベルに明示的に移動する必要がある。
 *
 * @see \Rector\Tests\NamedAttributeParam\Rector\ClassMethod\NamedAttributeToParameterRector\NamedAttributeToParameterRectorTest
 */
final class NamedAttributeToParameterRector extends AbstractRector
{
    private const NAMED_CLASS = 'Ray\\Di\\Di\\Named';
    private const NAMED_SHORT = 'Named';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Move method-level #[Named] attribute to parameter-level', [
            new CodeSample(
                <<<'CODE_SAMPLE'
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class SomeClass
{
    #[Inject]
    #[Named('json')]
    public function setRenderer(RenderInterface $renderer): void
    {
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class SomeClass
{
    #[Inject]
    public function setRenderer(#[Named('json')] RenderInterface $renderer): void
    {
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        assert($node instanceof ClassMethod);

        $namedAttrIndex = $this->findNamedAttributeGroupIndex($node);
        if ($namedAttrIndex === null) {
            return null;
        }

        $namedValue = $this->extractNamedValue($node->attrGroups[$namedAttrIndex]);
        if ($namedValue === null) {
            return null;
        }

        $names = $this->parseName($namedValue);
        $hasChanged = false;

        // 単一パラメータ: #[Named('foo')] のように key=value 形式でない場合
        if ($names === [] && count($node->params) === 1) {
            $param = $node->params[0];
            $newAttrGroup = $this->createNamedAttributeGroup(trim($namedValue));
            $param->attrGroups = array_merge($param->attrGroups, [$newAttrGroup]);
            $hasChanged = true;
        } else {
            // 複数パラメータ: #[Named('a=foo,b=bar')] 形式
            foreach ($node->params as $param) {
                $varName = $param->var->name;
                if (! isset($names[$varName])) {
                    continue;
                }
                $newAttrGroup = $this->createNamedAttributeGroup($names[$varName]);
                $param->attrGroups = array_merge($param->attrGroups, [$newAttrGroup]);
                $hasChanged = true;
            }
        }

        if (! $hasChanged) {
            return null;
        }

        // メソッドレベルの #[Named] 属性を削除
        $this->removeNamedAttributeGroup($node, $namedAttrIndex);

        return $node;
    }

    /**
     * メソッドの attrGroups から #[Named] 属性のインデックスを探す
     */
    private function findNamedAttributeGroupIndex(ClassMethod $node): ?int
    {
        foreach ($node->attrGroups as $index => $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                if ($attrName === self::NAMED_CLASS || $attrName === self::NAMED_SHORT) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * #[Named('...')] 属性から値を抽出する
     */
    private function extractNamedValue(AttributeGroup $attrGroup): ?string
    {
        foreach ($attrGroup->attrs as $attr) {
            $attrName = $attr->name->toString();
            if ($attrName !== self::NAMED_CLASS && $attrName !== self::NAMED_SHORT) {
                continue;
            }

            if ($attr->args === []) {
                return null;
            }

            $firstArg = $attr->args[0];
            if ($firstArg->value instanceof String_) {
                return $firstArg->value->value;
            }
        }

        return null;
    }

    /**
     * #[Named('value')] の AttributeGroup を生成する
     */
    private function createNamedAttributeGroup(string $value): AttributeGroup
    {
        $attr = new Attribute(
            new Node\Name\FullyQualified(self::NAMED_CLASS),
            [new Arg(new String_($value))]
        );

        return new AttributeGroup([$attr]);
    }

    /**
     * メソッドレベルの #[Named] 属性を削除する
     *
     * AttributeGroup 内に Named 以外の属性もある場合は Named のみ削除し、
     * Named だけの場合は AttributeGroup ごと削除する
     */
    private function removeNamedAttributeGroup(ClassMethod $node, int $index): void
    {
        $attrGroup = $node->attrGroups[$index];

        if (count($attrGroup->attrs) === 1) {
            // Named だけなので AttributeGroup ごと削除
            array_splice($node->attrGroups, $index, 1);
        } else {
            // Named 以外の属性も含まれている場合、Named のみ除去
            $attrGroup->attrs = array_values(array_filter(
                $attrGroup->attrs,
                function (Attribute $attr): bool {
                    $name = $attr->name->toString();
                    return $name !== self::NAMED_CLASS && $name !== self::NAMED_SHORT;
                }
            ));
        }
    }

    /**
     * "a=foo,b=bar" 形式の文字列をパースする
     *
     * @return array<string, string>
     */
    private function parseName(string $name): array
    {
        $names = [];
        $keyValues = explode(',', $name);
        foreach ($keyValues as $keyValue) {
            $exploded = explode('=', $keyValue);
            if (isset($exploded[1])) {
                [$key, $value] = $exploded;
                assert(is_string($key));
                if (isset($key[0]) && $key[0] === '$') {
                    $key = substr($key, 1);
                }

                $names[trim($key)] = trim($value);
            }
        }

        return $names;
    }
}
