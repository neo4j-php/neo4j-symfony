<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
}

$finder = Finder::create()
    ->in(__DIR__.'/')
    ->exclude(__DIR__.'/vendor')
;

return (new Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
