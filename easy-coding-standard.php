<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff;
use Symplify\CodingStandard\Fixer\Strict\BlankLineAfterStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/config'])
    ->withSkip(['node_modules/*', 'vendor/*', 'docs/*', 'testbench/*'])
    ->withPreparedSets(psr12: true, cleanCode: true)
    ->withRules([
        AssignmentInConditionSniff::class,
        BlankLineAfterNamespaceFixer::class,
        BlankLineAfterOpeningTagFixer::class,
        BlankLineAfterStrictTypesFixer::class,
        ClassAttributesSeparationFixer::class,
        DeclareStrictTypesFixer::class,
        MbStrFunctionsFixer::class,
        OrderedClassElementsFixer::class,
        ReturnTypeDeclarationFixer::class,
        StrictComparisonFixer::class,
        StrictParamFixer::class,
    ])
    ->withConfiguredRule(ConcatSpaceFixer::class, ['spacing' => 'none']);
