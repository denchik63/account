<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/migrations')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        'fopen_flags' => false
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/php-cs/.php-cs-fixer.cache');
