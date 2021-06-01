<?php
/**
 * Copyright 2021 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * MemcacheOptIn.php creates aliases for the classes to match the 
 * original behavior used in the Memcache php55 sdk. This is for the
 * convenience of migration to php7+ sdk. To opt in, please add 
 * this file to the autoload::files section of the 
 * composer.json. 
 *
 * Example: 
 * {
 *   "require": {
 *     "google/appengine-php-sdk": "^2.0"
 *   },
 *   "autoload": {
 *     "files": [
 *     "./vendor/google/appengine-php-sdk/src/Api/Memcache/MemcacheOptIn.php"
 *     ]
 *   }
 * }
 */

$classMap = [
    'Memcache' => 'Google\AppEngine\Api\Memcache\Memcache',
    'Memcached' => 'Google\AppEngine\Api\Memcache\Memcached'
];

foreach ($classMap as $alias => $class) {
    class_alias($class, $alias);
}

// Define constants for compatibility
if (!defined('MEMCACHE_COMPRESSED')) {
	define('MEMCACHE_COMPRESSED', 2);
}

if (!defined('MEMCACHE_HAVE_SESSION')) {
	define('MEMCACHE_HAVE_SESSION', 1); // See ext/session/MemcacheSessionHandler.
}