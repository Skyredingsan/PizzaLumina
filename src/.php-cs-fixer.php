<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/database')
    ->in(__DIR__ . '/tests')
    ->exclude('migrations')
    ->exclude('seeders');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'modifier_keywords' => true,
        'no_extra_blank_lines' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    ])
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setFinder($finder)
    ->setRiskyAllowed(true);
