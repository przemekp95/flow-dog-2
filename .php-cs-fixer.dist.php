<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('legacy')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::sequential())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => false,
    ])
    ->setUnsupportedPhpVersionAllowed(true)
    ->setFinder($finder)
;
