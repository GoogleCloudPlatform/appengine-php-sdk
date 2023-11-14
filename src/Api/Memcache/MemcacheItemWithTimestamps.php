<?php
/**
 * A memcache item with a value and timestamps.
 */

namespace Google\AppEngine\Api\Memcache;

class MemcacheItemWithTimestamps {

  private $value = null;
  private $expiration_time_sec = null;
  private $last_access_time_sec = null;
  private $delete_lock_time_sec = null;

  /**
   * Constructs an instance of MemcacheItemWithTimestamps.
   * @param mixed $value The value of the item.
   * @param int $expirationTimeSec The absolute expiration time of the item.
   * @param int $lastAccessTimeSec The absolute last access time of the item.
   * @param int $deleteLockTimeSec The absolute delete lock time of the item.
   */
  public function __construct($value,
                              $expirationTimeSec,
                              $lastAccessTimeSec,
                              $deleteLockTimeSec) {
    $this->value = $value;
    $this->expiration_time_sec = $expirationTimeSec;
    $this->last_access_time_sec = $lastAccessTimeSec;
    $this->delete_lock_time_sec = $deleteLockTimeSec;
  }

  /**
  * @return mixed The value of the item is returned.
  */
  public function getValue() {
    return $this->value;
  }

  /**
  * @return int Absolute expiration timestamp of the item in unix epoch seconds.
  *             Returns 0 if this item has no expiration timestamp.
  */
  public function getExpirationTimeSec() {
    return $this->expiration_time_sec;
  }

  /**
  * @return int Absolute last accessed timestamp of the item in unix epoch
  *             seconds.
  */
  public function getLastAccessTimeSec() {
    return $this->last_access_time_sec;
  }

  /**
  * @return int Absolute delete_time timestamp of the item in unix epoch
  *             seconds. Returns 0 if this item has no expiration timestamp.
  */
  public function getDeleteLockTimeSec() {
    return $this->delete_lock_time_sec;
  }
}
