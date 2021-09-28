<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('bin')
    ->exclude('certs')
    ->exclude('docker')
    ->exclude('public')
    ->exclude('translations')
    ->exclude('var')
    ->exclude('vendor')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR12' => true,
    '@PSR12:risky' => true,
    '@PhpCsFixer' => true,
    '@Symfony' => true,
    '@PHP70Migration' => true,
    '@PHP71Migration' => true,
    '@PHP73Migration' => true,
    '@PHP74Migration' => true,
    '@DoctrineAnnotation' => true
])
    ->setFinder($finder);
