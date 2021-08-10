<?php

require_once __DIR__ . '/vendor/autoload.php';

use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;
use Composer\Semver\Comparator;

$projectRoot = realpath(__DIR__ . '/../../..');

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('vendor')
    ->exclude('tests')
    ->in($projectRoot);

echo "Comparator Result: " . print_r(Comparator::greaterThanOrEqualTo($tag, '2.0.0'));
$versions = GitVersionCollection::create($projectRoot)
    ->addFromTags(function($tag) {
        return Comparator::greaterThanOrEqualTo($tag, '2.0.0');
    })
    ->add('master', 'master branch');

return new Sami($iterator, [
    'title' => 'Appengine PHP SDK for PHP API Reference',
    'build_dir' => $projectRoot . '/.docs/%version%',
    'cache_dir' => $projectRoot . '/.cache/%version%',
    'remote_repository' => new GitHubRemoteRepository('GoogleCloudPlatform/appengine-php-sdk', $projectRoot),
    'versions' => $versions
]);
