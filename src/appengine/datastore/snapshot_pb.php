<?php
/**
 * Copyright 2007 Google Inc.
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
# Generated by the protocol buffer compiler. DO NOT EDIT!
# source: google/appengine/datastore/snapshot.proto

namespace dummy {
  if (!defined('GOOGLE_APPENGINE_CLASSLOADER')) {
    require_once 'src/appengine/runtime/proto/ProtocolMessage.php';
  }
}
namespace storage_onestore_v3\Snapshot {
  class Status {
    const INACTIVE = 0;
    const ACTIVE = 1;
  }
}
namespace storage_onestore_v3 {
  class Snapshot extends \google\net\ProtocolMessage {
    public function getTs() {
      if (!isset($this->ts)) {
        return "0";
      }
      return $this->ts;
    }
    public function setTs($val) {
      if (is_double($val)) {
        $this->ts = sprintf('%0.0F', $val);
      } else {
        $this->ts = $val;
      }
      return $this;
    }
    public function clearTs() {
      unset($this->ts);
      return $this;
    }
    public function hasTs() {
      return isset($this->ts);
    }
    public function clear() {
      $this->clearTs();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->ts)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ts);
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->ts)) {
        $out->putVarInt32(8);
        $out->putVarInt64($this->ts);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 8:
            $this->setTs($d->getVarInt64());
            break;
          case 0:
            throw new \google\net\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->ts)) return 'ts';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasTs()) {
        $this->setTs($x->getTs());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->ts) !== isset($x->ts)) return false;
      if (isset($this->ts) && !$this->integerEquals($this->ts, $x->ts)) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->ts)) {
        $res .= $prefix . "ts: " . $this->debugFormatInt64($this->ts) . "\n";
      }
      return $res;
    }
  }
}
