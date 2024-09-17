<?php

namespace Vlx\Immutable;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\BetterPhpDocParser\ValueObject\Type\ShortenedIdentifierTypeNode;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ImmutableModelsRector extends AbstractRector
{
    public function __construct(
        readonly private PhpDocInfoFactory $phpDocInfoFactory,
        readonly private PhpDocTagRemover $phpDocTagRemover,
        readonly private DocBlockUpdater $docBlockUpdater,
        readonly private NodeNameResolver $nameResolver,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Adds @method with signature from __construct method for classes using ImmutableData trait',
            []
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        // Check if the class uses the ImmutableData trait
        if (!$this->usesImmutableDataTrait($node)) {
            return null;
        }

        return $this->addOrUpdateWithMethodPhpdoc($node);
    }

    private function usesImmutableDataTrait(Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if (!$trait instanceof FullyQualified) {
                        continue;
                    }
                    if ($this->getName($trait) === ImmutableData::class) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function addOrUpdateWithMethodPhpdoc(Class_ $class): ?Class_
    {
        $constructorMethod = $class->getMethod('__construct');
        if ($constructorMethod === null) {
            return null;
        }

        $parameters = [];
        foreach ($constructorMethod->params as $param) {
            $paramName = $this->getName($param);
            $type = $param->type;
            $nullable = false;
            if ($type instanceof NullableType) {
                $nullable = true;
                $type = $type->type;
            }

            $paramType = $this->nameResolver->getShortName($type);
            $defaultNull = ' = null';
            $parameters[] = sprintf(
                '%s%s $%s%s',
                $nullable ? '?' : '',
                $paramType,
                $paramName,
                $defaultNull,
            );
        }

        $phpDoc = $this->phpDocInfoFactory->createFromNodeOrEmpty($class);
        $tags = $phpDoc->getTagsByName('method');
        foreach ($tags as $tag) {
            $tagValue = $tag->value;
            if (!$tagValue instanceof MethodTagValueNode) {
                continue;
            }

            if ($tagValue->methodName === 'with') {
                $this->phpDocTagRemover->removeTagValueFromNode($phpDoc, $tag);
            }
        }
        $phpDoc->addPhpDocTagNode(new PhpDocTagNode('@method', new MethodTagValueNode(
            false,
            new ShortenedIdentifierTypeNode($class->name),
            'with',
            $parameters, // @phpstan-ignore-line
            ''
        )));

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($class);

        return $class;
    }
}