<?php

/**
 * MemcacheOptIn.php creates aliases for the classes to match the 
 * original behavior used in the Memcache php55 sdk. This is for the
 * convenience of migration to php7+ sdk. To opt in, please add 
 * this file to the autoload::files section of the 
 * composer.json. 
 */

$classMap = [
    'Memcache' => 'Google\AppEngine\Api\Memcache\Memcache',
    'Memcached' => 'Google\AppEngine\Api\Memcache\Memcached'
];

foreach ($classMap as $alias => $class) {
    class_alias($class, $alias);
}
