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
 * SessionHandler using App Engine Memcache API.
 *
 * Nb. Does not yet implement session locking, as not available until
 * MemcacheD is available for PHP runtime.
 *
 */

namespace Google\AppEngine\Ext\Session;

/**
 * Remove direct interaction with Memcache object for ease of mocking in tests.
 */
class MemcacheContainer {

  /**
   * The memcache object for storing sessions.
   */
  private $memcache = null;

  /**
   * Initialises a Memcache instance
   */
  public function __construct() {
    $this->memcache = new \Google\AppEngine\Api\Memcache\Memcache();
  }

  /**
   * Closes the Memcache instance.
   * @return bool true if successful, false otherwise
   */
  public function close() {
    return $this->memcache->close();
  }

  /**
   * Finds the value associated with input key, from Memcache.
   * @param string $key Input key from which to find value
   * @return string value associated with input key
   */
  public function get($key) {
    return $this->memcache->get($key, null);
  }

  /**
   * Inserts a key value pair, with expiry time, into Memcache.
   * @param string $key Input key to associate with the value
   * @param string $value Input value to be stored
   * @param int $expire Time until the pair can be garbage collected
   * @return bool true if successful, false otherwise
   */
  public function set($key, $value, $expire) {
    return $this->memcache->set($key, $value, null, $expire);
  }

  /**
   * Removes the key value pair, keyed with the input variable.
   * @param string $key Input key to remove key value pair
   * @return bool true if successful, false otherwise
   */
  public function delete($key) {
    return $this->memcache->delete($key);
  }
}