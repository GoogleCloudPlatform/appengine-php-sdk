<?php

require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/ConstraintInterface.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/MatchAllConstraint.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/MatchNoneConstraint.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/MultiConstraint.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Comparator.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/Constraint.php';
require_once __DIR__ . '/../../../vendor/composer/semver/src/Constraint/Bound.php';


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

echo "Comparator result: " . print_r(Comparator::greaterThanOrEqualTo('2foos', '2.0'));
$versions = GitVersionCollection::create($projectRoot)
    ->addFromTags(function($tag) {
        $val = false; 
        if(is_numeric(substr($tag, 0, 1))) {
            $val = Comparator::greaterThanOrEqualTo($tag, '2.0');
        }
        return $val;
    })
    ->add('master', 'master branch');

return new Sami($iterator, [
    'title' => 'Appengine PHP SDK for PHP API Reference',
    'build_dir' => $projectRoot . '/.docs/%version%',
    'cache_dir' => $projectRoot . '/.cache/%version%',
    'remote_repository' => new GitHubRemoteRepository('GoogleCloudPlatform/appengine-php-sdk', $projectRoot),
    'versions' => $versions
]);
