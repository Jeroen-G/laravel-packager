<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff;
use Symplify\CodingStandard\Fixer\Strict\BlankLineAfterStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $parameters = $containerConfigurator->parameters();

    $services->set(AssignmentInConditionSniff::class);
    $services->set(BlankLineAfterNamespaceFixer::class);
    $services->set(BlankLineAfterOpeningTagFixer::class);
    $services->set(BlankLineAfterStrictTypesFixer::class);
    $services->set(ClassAttributesSeparationFixer::class);
    $services->set(DeclareStrictTypesFixer::class);
    $services->set(MbStrFunctionsFixer::class);
    $services->set(OrderedClassElementsFixer::class);
    $services->set(ReturnTypeDeclarationFixer::class);
    $services->set(StrictComparisonFixer::class);
    $services->set(StrictParamFixer::class);

    $parameters->set('sets', ['clean-code', 'psr12']);
    $parameters->set('exclude_files', ['node_modules/*', 'vendor/*', 'docs/*', 'testbench/*']);
};
