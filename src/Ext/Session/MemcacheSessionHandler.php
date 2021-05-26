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
 * A session handler interface using the GAE Memcache API.
 */
class MemcacheSessionHandler implements \SessionHandlerInterface {

  const SESSION_PREFIX = '_ah_sess_';

  /**
   * The number of seconds before session objects expire.
   */
  private $expire = null;

  /**
   * The memcacheContainer to use for storing and retrieving session data.
   */
  private $memcacheContainer = null;

  /**
   * Constructs the session handler instance.
   * @param MemcacheContainer $memcacheContainer Optional, for mocking in tests
   */
  public function __construct($memcacheContainer = null) {
    if(isset($memcacheContainer)) {
      $this->memcacheContainer = $memcacheContainer;
    }
    else {
      $this->memcacheContainer = new MemcacheContainer();
    }

    // Get session max lifetime to leverage Memcache expire functionality.
    $this->expire = ini_get("session.gc_maxlifetime");
  }

  /**
   * Opens the session handler.
   * @param string $savePath Not used
   * @param string $sessionName Not ued
   * @return bool true if successful, false otherwise
   */
  public function open($savePath, $sessionName) {
    return true;
  }

  /**
   * Closes the session handler.
   * @return bool true if successful, false otherwise
   */
  public function close() {
    return $this->memcacheContainer->close();
  }

  /**
   * Read an element from Memcache with the given ID.
   * @param string $id Session ID associated with the data to be retrieved
   * @return string data associated with that ID or bool false on failure
   */
  public function read($id) {
    $data = $this->memcacheContainer->get(self::SESSION_PREFIX . $id);
    return  empty($data) ? '' : $data;
  }

  /**
   * Write an element to Memcache with the given ID and data.
   * @param string $id Session ID associated with the data to be stored
   * @param string $data Data to be stored
   * @return bool true if successful, false otherwise
   */
  public function write($id, $data) {
    return $this->memcacheContainer->set(
        self::SESSION_PREFIX . $id, $data, $this->expire);
  }

  /**
   * Destroy the data associated with a particular session ID.
   * @param string $id Session ID associated with the data to be destroyed
   * @return bool true if successful, false otherwise
   */
  public function destroy($id) {
    return $this->memcacheContainer->delete(
        self::SESSION_PREFIX . $id);
  }

  /**
   * Garbage collection method - always returns true as this is handled by the
   * Memcache expire function.
   * @param int $maxlifetime Not used
   * @return bool true if successful, false otherwise
   */
  public function gc($maxlifetime) {
    // Handled by "expire" in Memcache.
    return true;
  }

  /**
   * Configure the session handler to use the Memcache API.
   * @param MemcacheContainer $memcacheContainer Optional, for mocking in tests
   */
  public static function configure($memcacheContainer = null) {
    $handler = new MemcacheSessionHandler($memcacheContainer);

    session_set_save_handler($handler, true);
  }
}
