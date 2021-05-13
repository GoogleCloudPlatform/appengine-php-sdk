<?php

/**
 * Alias.php creates aliases for the classes to match the 
 * original names used in the php55 sdk. This is for the
 * convenience of migration to php7+ sdk. Alias.php is 
 * included in the autoloader inside the composer.json.
 */

$classMap = [
    'Google\AppEngine\Api\Memcache\Memcache' => 'Memcache',
    'Google\AppEngine\Api\Memcache\Memcached' => 'Memcached'
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}
