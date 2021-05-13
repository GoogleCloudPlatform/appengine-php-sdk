<?php

/**
 * MemcacheOptIn.php creates aliases for the classes to match the 
 * original behavior used in the Memcache php55 sdk. This is for the
 * convenience of migration to php7+ sdk. To opt in, please add 
 * "src/Api/Memcache/MemcacheOptIn.php" to the autoload::files section of the 
 * composer.json. 
 */

$classMap = [
    'Google\AppEngine\Api\Memcache\Memcache' => 'Memcache',
    'Google\AppEngine\Api\Memcache\Memcached' => 'Memcached'
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}
