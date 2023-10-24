<?php

declare(strict_types=1);

/*
 * This file is part of the Neo4j PHP Client and Driver package.
 *
 * (c) Nagels <https://nagels.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixerCustomFixers\Fixer\ConstructorEmptyBracesFixer;
use PhpCsFixerCustomFixers\Fixer\IssetToArrayKeyExistsFixer;
use PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer;
use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamOrderFixer;
use PhpCsFixerCustomFixers\Fixer\PhpUnitAssertArgumentsOrderFixer;
use PhpCsFixerCustomFixers\Fixer\StringableInterfaceFixer;

try {
    $finder = PhpCsFixer\Finder::create()
        ->in(__DIR__.'/src')
        ->in(__DIR__.'/config')
        ->in(__DIR__.'/tests');
} catch (Throwable $e) {
    echo $e->getMessage()."\n";

    exit(1);
}

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
