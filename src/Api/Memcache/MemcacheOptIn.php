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

// Define constants for compatibility, but they will be ignored.
if (!defined('MEMCACHE_COMPRESSED')) {
	define('MEMCACHE_COMPRESSED', 2);
}

if (!defined('MEMCACHE_HAVE_SESSION')) {
	define('MEMCACHE_HAVE_SESSION', 1); // See ext/session/MemcacheSessionHandler.
}

/**
 * Adds a new item to the cache. Will fail if the key is already present in the
 * cache.
 *
 * @param Memcache $memcache_obj The cache instance to add item to.
 *
 * @param string $key The key associated with the value added to the cache.
 *
 * @param mixed $value The value to add to the cache.
 *
 * @param int $flag This parameter is present only for compatibility and is
 *                  ignored.
 *
 * @param int $expire The delay before the item is removed from the cache. If
 *                    $expire <= 2592000 then it is interpreted as the number
 *                    of seconds from the time of the call to wait before
 *                    removing the item from the cache. If $expire > 2592000
 *                    then it is interpreted as the absolute Unix epoch time
 *                    when the value will expire.
 *
 * @return bool true if the item was successfully added to the cache, false
 *              otherwise.
 */

  function memcache_add(
      Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
      string $key, 
      $value, 
      int $flag = null, 
      int $expire = 0
  ): bool {
    return $memcache_obj->add($key, $value, $flag, $expire);
  }

/**
 * This function is present only for compatibility and does nothing.
 */
function memcache_add_server(Google\AppEngine\Api\Memcache\Memcache $memcache_obj, $host) {
  return $memcache_obj->addServer($host);
}

/**
 * This function is present only for compatibility and does nothing.
 */
function memcache_close(Google\AppEngine\Api\Memcache\Memcache $memcache_obj) {
  return $memcache_obj->close();
}

/**
 * This function is present only for compatibility and does nothing.
 */
function memcache_connect($host, $port = null, $timeout = 1) {
  $memcache_obj = new Memcache();
  if (!$memcache_obj->connect($host, $port, $timeout)) {
    return false;
  } else {
    return $memcache_obj;
  }
}

/**
 * Decrements a cached item's value. The value must be a int, float or string
 * representing an integer e.g. 5, 5.0 or "5" or the call with fail.
 *
 * @param Memcache $memcache_obj The cache instance to decrement the value in.
 *
 * @param string $key The key associated with the value to decrement.
 *
 * @param int $value The amount to decrement the value.
 *
 * @return mixed On success, the new value of the item is returned. On
 *               failure, false is returned.
 */
function memcache_decrement(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
    string $key, 
    int $value = 1
) {
  return $memcache_obj->decrement($key, $value);
}

/**
 * Deletes an item from the cache.
 *
 * @param Memcache $memcache_obj The cache instance to delete the item from.
 *
 * @param string $key The key associated with the item to delete.
 *
 * @return bool true if the item was successfully deleted from the cache,
 *              false otherwise. Note that this will return false if $key is
 *              not present in the cache.
 */
function memcache_delete(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
    string $key
): bool {
  return $memcache_obj->delete($key);
}

/**
 * Removes all items from cache.
 *
 * @param Memcache $memcache_obj The cache instance to flush.
 *
 * @return bool true if all items were removed, false otherwise.
 */
function memcache_flush(Google\AppEngine\Api\Memcache\Memcache $memcache_obj): bool {
  return $memcache_obj->flush();
}

/**
 * Fetches previously stored data from the cache.
 *
 * @param string|string[] $keys The key associated with the value to fetch, or
 *                              an array of keys if fetching multiple values.
 *
 * @param Memcache $memcache_obj The cache instance to get the item from.
 *
 * @param int $flags This parameter is present only for compatibility and is
 *                   ignored. It should return the stored flag value.
 *
 * @return mixed On success, the string associated with the key, or an array
 *               of key-value pairs when $keys is an array. On failure, false
 *               is returned.
 */
function memcache_get(
  Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
  $keys, 
  int $flags = null
) {
  return $memcache_obj->get($keys, $flags);
}

/**
 * Increments a cached item's value. The value must be a int, float or string
 * representing an integer e.g. 5, 5.0 or "5" or the call with fail.
 *
 * @param Memcache $memcache_obj The cache instance to increment the value in.
 *
 * @param string $key The key associated with the value to increment.
 *
 * @param int $value The amount to increment the value.
 *
 * @return mixed On success, the new value of the item is returned. On
 *               failure, false is returned.
 */
 
function memcache_increment(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
    string $key, 
    int $value = 1
) {
  return $memcache_obj->increment($key, $value);
}

/**
 * This function is present only for compatibility and does nothing.
 */
function memcache_pconnect($host, $port = null, $timeout = 1) {
  $memcache_obj = new Memcache();
  if (!$memcache_obj->connect($host, $port, $timeout)) {
    return false;
  } else {
    return $memcache_obj;
  }
}

/**
 * Gets an item from memcache along with timestamp metadata.
 *
 * @param Memcache $memcache_obj The cache instance to get the item from.
 *
 * @param string|string[] $keys The key associated with the value to fetch, or
 *                              an array of keys if fetching multiple values.
 *
 * @param int $flags This parameter is present only for compatibility and is
 *                   ignored. It should return the stored flag value.
 *
 * @return mixed On success, the MemcacheItemWithTimestamps associated with the
 *               key, or an array of key-ItemWithTimestamp pairs when $keys is
 *               an array. On failure, false is returned.
 */
function memcache_peek(
  Google\AppEngine\Api\Memcache\Memcache $memcache_obj,
  $keys,
  $flags = null
) {
  return $memcache_obj->peek($keys, $flags);
}

/**
 * Replaces an existing item in the cache. Will fail if the key is not already
 * present in the cache.
 *
 * @param Memcache $memcache_obj The cache instance to store the item in.
 *
 * @param string $key The key associated with the value that will be replaced in
 *                    the cache.
 *
 * @param mixed $value The new cache value.
 *
 * @param int $flag This parameter is present only for compatibility and is
 *                  ignored.
 *
 * @param int $expire The delay before the item is removed from the cache. If
 *                    $expire <= 2592000 then it is interpreted as the number
 *                    of seconds from the time of the call to wait before
 *                    removing the item from the cache. If $expire > 2592000
 *                    then it is interpreted as the absolute Unix epoch time
 *                    when the value will expire.
 *
 * @return bool true if the item was successfully replaced  in the cache,
 *              false otherwise.
 */
function memcache_replace(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj,
    string $key,
    $value,
    int $flag = null,
    int $expire = 0
): bool {
  return $memcache_obj->replace($key, $value, $flag, $expire);
}

/**
 * Sets the value of a key in the cache regardless of whether it is currently
 * present or not.
 *
 * @param Memcache $memcache_obj The cache instance to store the item in.
 *
 * @param string $key The key associated with the value that will be replaced in
 *                    the cache.
 *
 * @param mixed $value The new cache value.
 *
 * @param int $flag This parameter is present only for compatibility and is
 *                  ignored.
 *
 * @param int $expire The delay before the item is removed from the cache. If
 *                    $expire <= 2592000 then it is interpreted as the number
 *                    of seconds from the time of the call to wait before
 *                    removing the item from the cache. If $expire > 2592000
 *                    then it is interpreted as the absolute Unix epoch time
 *                    when the value will expire.
 *
 * @return bool true if the item was successfully replaced the cache, false
 *              otherwise.
 */
function memcache_set(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj, 
    string $key, 
    $value, 
    int $flag = null, 
    int $expire = 0
): bool {
  return $memcache_obj->set($key, $value, $flag, $expire);
}

/**
 * This function is present only for compatibility and does nothing.
 */
function memcache_set_compress_threshold(
    Google\AppEngine\Api\Memcache\Memcache $memcache_obj,
    $threshold,
    $min_savings = 0.2
) {
  $memcache_obj->setCompressThreshold($threshold, $min_savings);
}
