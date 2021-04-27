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
# source: google/appengine/api/api_base.proto



// namespace dummy {
//   if (!defined('GOOGLE_APPENGINE_CLASSLOADER')) {
//     require_once 'google/appengine/runtime/proto/ProtocolMessage.php';
//   }
// }
namespace google\appengine\api {
  class StringProto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return '';
      }
      return $this->value;
    }
    public function setValue($val) {
      $this->value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->value));
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(10);
        $out->putPrefixedString($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 10:
            $length = $d->getVarInt32();
            $this->setValue(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && $this->value !== $x->value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatString($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class Integer32Proto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return 0;
      }
      return $this->value;
    }
    public function setValue($val) {
      $this->value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->value);
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(8);
        $out->putVarInt32($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 8:
            $this->setValue($d->getVarInt32());
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && !$this->integerEquals($this->value, $x->value)) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatInt32($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class Integer64Proto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return "0";
      }
      return $this->value;
    }
    public function setValue($val) {
      if (is_double($val)) {
        $this->value = sprintf('%0.0F', $val);
      } else {
        $this->value = $val;
      }
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->value);
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(8);
        $out->putVarInt64($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 8:
            $this->setValue($d->getVarInt64());
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && !$this->integerEquals($this->value, $x->value)) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatInt64($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class BoolProto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return false;
      }
      return $this->value;
    }
    public function setValue($val) {
      $this->value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 2;
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(8);
        $out->putBoolean($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 8:
            $this->setValue($d->getBoolean());
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && $this->value !== $x->value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatBool($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class DoubleProto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return 0.0;
      }
      return $this->value;
    }
    public function setValue($val) {
      $this->value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 9;
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(9);
        $out->putDouble($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 9:
            $this->setValue($d->getDouble());
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && $this->value !== $x->value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatDouble($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class BytesProto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function getValue() {
      if (!isset($this->value)) {
        return '';
      }
      return $this->value;
    }
    public function setValue($val) {
      $this->value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->value);
      return $this;
    }
    public function hasValue() {
      return isset($this->value);
    }
    public function clear() {
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->value)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->value));
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->value)) {
        $out->putVarInt32(10);
        $out->putPrefixedString($this->value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 10:
            $length = $d->getVarInt32();
            $this->setValue(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      if (!isset($this->value)) return 'value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->value) !== isset($x->value)) return false;
      if (isset($this->value) && $this->value !== $x->value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->value)) {
        $res .= $prefix . "value: " . $this->debugFormatString($this->value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\api {
  class VoidProto extends \google\appengine\runtime\proto\ProtocolMessage {
    public function clear() {
    }
    public function byteSizePartial() {
      $res = 0;
      return $res;
    }
    public function outputPartial($out) {
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 0:
            throw new \google\appengine\runtime\proto\ProtocolBufferDecodeError();
            break;
          default:
            $d->skipData($tt);
        }
      };
    }
    public function checkInitialized() {
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      return $res;
    }
  }
}
