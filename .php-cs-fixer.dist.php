<?php

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())->in([__DIR__ . '/src', __DIR__ . '/tests'])
    );
