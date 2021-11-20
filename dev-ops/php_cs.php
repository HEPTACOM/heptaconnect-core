<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(dirname(__DIR__) . '/src');

return (new Config())
    ->setFinder($finder)
    ->setCacheFile(dirname(__DIR__) . '/.build/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => false,
        'concat_space' => [
            'spacing' => 'one',
        ],

        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],

        'native_function_invocation' => [
            'include' => ['@all'],
            'strict' => true,
        ],
        'native_constant_invocation' => [
            'scope' => 'all',
        ],

        'phpdoc_order' => true,
        'phpdoc_summary' => false,
    ]);
