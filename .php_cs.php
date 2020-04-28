<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src')
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'test');

return Config::create()
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.build/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => false,

        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],

        'native_function_invocation' => [
            'strict' => true,
        ],
        'native_constant_invocation' => [
            'scope' => 'all',
        ],

        'phpdoc_order' => true,
        'phpdoc_summary' => false,
    ]);
