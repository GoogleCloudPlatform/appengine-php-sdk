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
# source: google/appengine/api/urlfetch_service.proto

namespace dummy {
  if (!defined('GOOGLE_APPENGINE_CLASSLOADER')) {
    require_once 'src/appengine/runtime/proto/ProtocolMessage.php';
  }
}
namespace google\appengine\URLFetchServiceError {
  class ErrorCode {
    const OK = 0;
    const INVALID_URL = 1;
    const FETCH_ERROR = 2;
    const UNSPECIFIED_ERROR = 3;
    const RESPONSE_TOO_LARGE = 4;
    const DEADLINE_EXCEEDED = 5;
    const SSL_CERTIFICATE_ERROR = 6;
    const DNS_ERROR = 7;
    const CLOSED = 8;
    const INTERNAL_TRANSIENT_ERROR = 9;
    const TOO_MANY_REDIRECTS = 10;
    const MALFORMED_REPLY = 11;
    const CONNECTION_ERROR = 12;
    const PAYLOAD_TOO_LARGE = 13;
  }
}
namespace google\appengine {
  class URLFetchServiceError extends \google\net\ProtocolMessage {
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
            throw new \google\net\ProtocolBufferDecodeError();
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
namespace google\appengine\URLFetchRequest {
  class RequestMethod {
    const GET = 1;
    const POST = 2;
    const HEAD = 3;
    const PUT = 4;
    const DELETE = 5;
    const PATCH = 6;
  }
}
namespace google\appengine\URLFetchRequest {
  class Header extends \google\net\ProtocolMessage {
    public function getKey() {
      if (!isset($this->Key)) {
        return '';
      }
      return $this->Key;
    }
    public function setKey($val) {
      $this->Key = $val;
      return $this;
    }
    public function clearKey() {
      unset($this->Key);
      return $this;
    }
    public function hasKey() {
      return isset($this->Key);
    }
    public function getValue() {
      if (!isset($this->Value)) {
        return '';
      }
      return $this->Value;
    }
    public function setValue($val) {
      $this->Value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->Value);
      return $this;
    }
    public function hasValue() {
      return isset($this->Value);
    }
    public function clear() {
      $this->clearKey();
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->Key)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Key));
      }
      if (isset($this->Value)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Value));
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->Key)) {
        $out->putVarInt32(34);
        $out->putPrefixedString($this->Key);
      }
      if (isset($this->Value)) {
        $out->putVarInt32(42);
        $out->putPrefixedString($this->Value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 28: return;
          case 34:
            $length = $d->getVarInt32();
            $this->setKey(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 42:
            $length = $d->getVarInt32();
            $this->setValue(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
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
      if (!isset($this->Key)) return 'Key';
      if (!isset($this->Value)) return 'Value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasKey()) {
        $this->setKey($x->getKey());
      }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->Key) !== isset($x->Key)) return false;
      if (isset($this->Key) && $this->Key !== $x->Key) return false;
      if (isset($this->Value) !== isset($x->Value)) return false;
      if (isset($this->Value) && $this->Value !== $x->Value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->Key)) {
        $res .= $prefix . "Key: " . $this->debugFormatString($this->Key) . "\n";
      }
      if (isset($this->Value)) {
        $res .= $prefix . "Value: " . $this->debugFormatString($this->Value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine {
  class URLFetchRequest extends \google\net\ProtocolMessage {
    private $header = array();
    public function getMethod() {
      if (!isset($this->Method)) {
        return 1;
      }
      return $this->Method;
    }
    public function setMethod($val) {
      $this->Method = $val;
      return $this;
    }
    public function clearMethod() {
      unset($this->Method);
      return $this;
    }
    public function hasMethod() {
      return isset($this->Method);
    }
    public function getUrl() {
      if (!isset($this->Url)) {
        return '';
      }
      return $this->Url;
    }
    public function setUrl($val) {
      $this->Url = $val;
      return $this;
    }
    public function clearUrl() {
      unset($this->Url);
      return $this;
    }
    public function hasUrl() {
      return isset($this->Url);
    }
    public function getHeaderSize() {
      return sizeof($this->header);
    }
    public function getHeaderList() {
      return $this->header;
    }
    public function mutableHeader($idx) {
      if (!isset($this->header[$idx])) {
        $val = new \google\appengine\URLFetchRequest\Header();
        $this->header[$idx] = $val;
        return $val;
      }
      return $this->header[$idx];
    }
    public function getHeader($idx) {
      if (isset($this->header[$idx])) {
        return $this->header[$idx];
      }
      if ($idx >= end(array_keys($this->header))) {
        throw new \OutOfRangeException('index out of range: ' + $idx);
      }
      return new \google\appengine\URLFetchRequest\Header();
    }
    public function addHeader() {
      $val = new \google\appengine\URLFetchRequest\Header();
      $this->header[] = $val;
      return $val;
    }
    public function clearHeader() {
      $this->header = array();
    }
    public function getPayload() {
      if (!isset($this->Payload)) {
        return '';
      }
      return $this->Payload;
    }
    public function setPayload($val) {
      $this->Payload = $val;
      return $this;
    }
    public function clearPayload() {
      unset($this->Payload);
      return $this;
    }
    public function hasPayload() {
      return isset($this->Payload);
    }
    public function getFollowredirects() {
      if (!isset($this->FollowRedirects)) {
        return true;
      }
      return $this->FollowRedirects;
    }
    public function setFollowredirects($val) {
      $this->FollowRedirects = $val;
      return $this;
    }
    public function clearFollowredirects() {
      unset($this->FollowRedirects);
      return $this;
    }
    public function hasFollowredirects() {
      return isset($this->FollowRedirects);
    }
    public function getDeadline() {
      if (!isset($this->Deadline)) {
        return 0.0;
      }
      return $this->Deadline;
    }
    public function setDeadline($val) {
      $this->Deadline = $val;
      return $this;
    }
    public function clearDeadline() {
      unset($this->Deadline);
      return $this;
    }
    public function hasDeadline() {
      return isset($this->Deadline);
    }
    public function getMustvalidateservercertificate() {
      if (!isset($this->MustValidateServerCertificate)) {
        return true;
      }
      return $this->MustValidateServerCertificate;
    }
    public function setMustvalidateservercertificate($val) {
      $this->MustValidateServerCertificate = $val;
      return $this;
    }
    public function clearMustvalidateservercertificate() {
      unset($this->MustValidateServerCertificate);
      return $this;
    }
    public function hasMustvalidateservercertificate() {
      return isset($this->MustValidateServerCertificate);
    }
    public function clear() {
      $this->clearMethod();
      $this->clearUrl();
      $this->clearHeader();
      $this->clearPayload();
      $this->clearFollowredirects();
      $this->clearDeadline();
      $this->clearMustvalidateservercertificate();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->Method)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->Method);
      }
      if (isset($this->Url)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Url));
      }
      $this->checkProtoArray($this->header);
      $res += 2 * sizeof($this->header);
      foreach ($this->header as $value) {
        $res += $value->byteSizePartial();
      }
      if (isset($this->Payload)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Payload));
      }
      if (isset($this->FollowRedirects)) {
        $res += 2;
      }
      if (isset($this->Deadline)) {
        $res += 9;
      }
      if (isset($this->MustValidateServerCertificate)) {
        $res += 2;
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->Method)) {
        $out->putVarInt32(8);
        $out->putVarInt32($this->Method);
      }
      if (isset($this->Url)) {
        $out->putVarInt32(18);
        $out->putPrefixedString($this->Url);
      }
      $this->checkProtoArray($this->header);
      foreach ($this->header as $value) {
        $out->putVarInt32(27);
        $value->outputPartial($out);
        $out->putVarInt32(28);
      }
      if (isset($this->Payload)) {
        $out->putVarInt32(50);
        $out->putPrefixedString($this->Payload);
      }
      if (isset($this->FollowRedirects)) {
        $out->putVarInt32(56);
        $out->putBoolean($this->FollowRedirects);
      }
      if (isset($this->Deadline)) {
        $out->putVarInt32(65);
        $out->putDouble($this->Deadline);
      }
      if (isset($this->MustValidateServerCertificate)) {
        $out->putVarInt32(72);
        $out->putBoolean($this->MustValidateServerCertificate);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 8:
            $this->setMethod($d->getVarInt32());
            break;
          case 18:
            $length = $d->getVarInt32();
            $this->setUrl(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 27:
            $this->addHeader()->tryMerge($d);
            break;
          case 50:
            $length = $d->getVarInt32();
            $this->setPayload(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 56:
            $this->setFollowredirects($d->getBoolean());
            break;
          case 65:
            $this->setDeadline($d->getDouble());
            break;
          case 72:
            $this->setMustvalidateservercertificate($d->getBoolean());
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
      if (!isset($this->Method)) return 'Method';
      if (!isset($this->Url)) return 'Url';
      foreach ($this->header as $value) {
        if (!$value->isInitialized()) return 'header';
      }
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasMethod()) {
        $this->setMethod($x->getMethod());
      }
      if ($x->hasUrl()) {
        $this->setUrl($x->getUrl());
      }
      foreach ($x->getHeaderList() as $v) {
        $this->addHeader()->copyFrom($v);
      }
      if ($x->hasPayload()) {
        $this->setPayload($x->getPayload());
      }
      if ($x->hasFollowredirects()) {
        $this->setFollowredirects($x->getFollowredirects());
      }
      if ($x->hasDeadline()) {
        $this->setDeadline($x->getDeadline());
      }
      if ($x->hasMustvalidateservercertificate()) {
        $this->setMustvalidateservercertificate($x->getMustvalidateservercertificate());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->Method) !== isset($x->Method)) return false;
      if (isset($this->Method) && $this->Method !== $x->Method) return false;
      if (isset($this->Url) !== isset($x->Url)) return false;
      if (isset($this->Url) && $this->Url !== $x->Url) return false;
      if (sizeof($this->header) !== sizeof($x->header)) return false;
      foreach (array_map(null, $this->header, $x->header) as $v) {
        if (!$v[0]->equals($v[1])) return false;
      }
      if (isset($this->Payload) !== isset($x->Payload)) return false;
      if (isset($this->Payload) && $this->Payload !== $x->Payload) return false;
      if (isset($this->FollowRedirects) !== isset($x->FollowRedirects)) return false;
      if (isset($this->FollowRedirects) && $this->FollowRedirects !== $x->FollowRedirects) return false;
      if (isset($this->Deadline) !== isset($x->Deadline)) return false;
      if (isset($this->Deadline) && $this->Deadline !== $x->Deadline) return false;
      if (isset($this->MustValidateServerCertificate) !== isset($x->MustValidateServerCertificate)) return false;
      if (isset($this->MustValidateServerCertificate) && $this->MustValidateServerCertificate !== $x->MustValidateServerCertificate) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->Method)) {
        $res .= $prefix . "Method: " . ($this->Method) . "\n";
      }
      if (isset($this->Url)) {
        $res .= $prefix . "Url: " . $this->debugFormatString($this->Url) . "\n";
      }
      foreach ($this->header as $value) {
        $res .= $prefix . "Header {\n" . $value->shortDebugString($prefix . "  ") . $prefix . "}\n";
      }
      if (isset($this->Payload)) {
        $res .= $prefix . "Payload: " . $this->debugFormatString($this->Payload) . "\n";
      }
      if (isset($this->FollowRedirects)) {
        $res .= $prefix . "FollowRedirects: " . $this->debugFormatBool($this->FollowRedirects) . "\n";
      }
      if (isset($this->Deadline)) {
        $res .= $prefix . "Deadline: " . $this->debugFormatDouble($this->Deadline) . "\n";
      }
      if (isset($this->MustValidateServerCertificate)) {
        $res .= $prefix . "MustValidateServerCertificate: " . $this->debugFormatBool($this->MustValidateServerCertificate) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine\URLFetchResponse {
  class Header extends \google\net\ProtocolMessage {
    public function getKey() {
      if (!isset($this->Key)) {
        return '';
      }
      return $this->Key;
    }
    public function setKey($val) {
      $this->Key = $val;
      return $this;
    }
    public function clearKey() {
      unset($this->Key);
      return $this;
    }
    public function hasKey() {
      return isset($this->Key);
    }
    public function getValue() {
      if (!isset($this->Value)) {
        return '';
      }
      return $this->Value;
    }
    public function setValue($val) {
      $this->Value = $val;
      return $this;
    }
    public function clearValue() {
      unset($this->Value);
      return $this;
    }
    public function hasValue() {
      return isset($this->Value);
    }
    public function clear() {
      $this->clearKey();
      $this->clearValue();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->Key)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Key));
      }
      if (isset($this->Value)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Value));
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->Key)) {
        $out->putVarInt32(34);
        $out->putPrefixedString($this->Key);
      }
      if (isset($this->Value)) {
        $out->putVarInt32(42);
        $out->putPrefixedString($this->Value);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 28: return;
          case 34:
            $length = $d->getVarInt32();
            $this->setKey(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 42:
            $length = $d->getVarInt32();
            $this->setValue(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
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
      if (!isset($this->Key)) return 'Key';
      if (!isset($this->Value)) return 'Value';
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasKey()) {
        $this->setKey($x->getKey());
      }
      if ($x->hasValue()) {
        $this->setValue($x->getValue());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->Key) !== isset($x->Key)) return false;
      if (isset($this->Key) && $this->Key !== $x->Key) return false;
      if (isset($this->Value) !== isset($x->Value)) return false;
      if (isset($this->Value) && $this->Value !== $x->Value) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->Key)) {
        $res .= $prefix . "Key: " . $this->debugFormatString($this->Key) . "\n";
      }
      if (isset($this->Value)) {
        $res .= $prefix . "Value: " . $this->debugFormatString($this->Value) . "\n";
      }
      return $res;
    }
  }
}
namespace google\appengine {
  class URLFetchResponse extends \google\net\ProtocolMessage {
    private $header = array();
    public function getContent() {
      if (!isset($this->Content)) {
        return '';
      }
      return $this->Content;
    }
    public function setContent($val) {
      $this->Content = $val;
      return $this;
    }
    public function clearContent() {
      unset($this->Content);
      return $this;
    }
    public function hasContent() {
      return isset($this->Content);
    }
    public function getStatuscode() {
      if (!isset($this->StatusCode)) {
        return 0;
      }
      return $this->StatusCode;
    }
    public function setStatuscode($val) {
      $this->StatusCode = $val;
      return $this;
    }
    public function clearStatuscode() {
      unset($this->StatusCode);
      return $this;
    }
    public function hasStatuscode() {
      return isset($this->StatusCode);
    }
    public function getHeaderSize() {
      return sizeof($this->header);
    }
    public function getHeaderList() {
      return $this->header;
    }
    public function mutableHeader($idx) {
      if (!isset($this->header[$idx])) {
        $val = new \google\appengine\URLFetchResponse\Header();
        $this->header[$idx] = $val;
        return $val;
      }
      return $this->header[$idx];
    }
    public function getHeader($idx) {
      if (isset($this->header[$idx])) {
        return $this->header[$idx];
      }
      if ($idx >= end(array_keys($this->header))) {
        throw new \OutOfRangeException('index out of range: ' + $idx);
      }
      return new \google\appengine\URLFetchResponse\Header();
    }
    public function addHeader() {
      $val = new \google\appengine\URLFetchResponse\Header();
      $this->header[] = $val;
      return $val;
    }
    public function clearHeader() {
      $this->header = array();
    }
    public function getContentwastruncated() {
      if (!isset($this->ContentWasTruncated)) {
        return false;
      }
      return $this->ContentWasTruncated;
    }
    public function setContentwastruncated($val) {
      $this->ContentWasTruncated = $val;
      return $this;
    }
    public function clearContentwastruncated() {
      unset($this->ContentWasTruncated);
      return $this;
    }
    public function hasContentwastruncated() {
      return isset($this->ContentWasTruncated);
    }
    public function getExternalbytessent() {
      if (!isset($this->ExternalBytesSent)) {
        return "0";
      }
      return $this->ExternalBytesSent;
    }
    public function setExternalbytessent($val) {
      if (is_double($val)) {
        $this->ExternalBytesSent = sprintf('%0.0F', $val);
      } else {
        $this->ExternalBytesSent = $val;
      }
      return $this;
    }
    public function clearExternalbytessent() {
      unset($this->ExternalBytesSent);
      return $this;
    }
    public function hasExternalbytessent() {
      return isset($this->ExternalBytesSent);
    }
    public function getExternalbytesreceived() {
      if (!isset($this->ExternalBytesReceived)) {
        return "0";
      }
      return $this->ExternalBytesReceived;
    }
    public function setExternalbytesreceived($val) {
      if (is_double($val)) {
        $this->ExternalBytesReceived = sprintf('%0.0F', $val);
      } else {
        $this->ExternalBytesReceived = $val;
      }
      return $this;
    }
    public function clearExternalbytesreceived() {
      unset($this->ExternalBytesReceived);
      return $this;
    }
    public function hasExternalbytesreceived() {
      return isset($this->ExternalBytesReceived);
    }
    public function getFinalurl() {
      if (!isset($this->FinalUrl)) {
        return '';
      }
      return $this->FinalUrl;
    }
    public function setFinalurl($val) {
      $this->FinalUrl = $val;
      return $this;
    }
    public function clearFinalurl() {
      unset($this->FinalUrl);
      return $this;
    }
    public function hasFinalurl() {
      return isset($this->FinalUrl);
    }
    public function getApicpumilliseconds() {
      if (!isset($this->ApiCpuMilliseconds)) {
        return '0';
      }
      return $this->ApiCpuMilliseconds;
    }
    public function setApicpumilliseconds($val) {
      if (is_double($val)) {
        $this->ApiCpuMilliseconds = sprintf('%0.0F', $val);
      } else {
        $this->ApiCpuMilliseconds = $val;
      }
      return $this;
    }
    public function clearApicpumilliseconds() {
      unset($this->ApiCpuMilliseconds);
      return $this;
    }
    public function hasApicpumilliseconds() {
      return isset($this->ApiCpuMilliseconds);
    }
    public function getApibytessent() {
      if (!isset($this->ApiBytesSent)) {
        return '0';
      }
      return $this->ApiBytesSent;
    }
    public function setApibytessent($val) {
      if (is_double($val)) {
        $this->ApiBytesSent = sprintf('%0.0F', $val);
      } else {
        $this->ApiBytesSent = $val;
      }
      return $this;
    }
    public function clearApibytessent() {
      unset($this->ApiBytesSent);
      return $this;
    }
    public function hasApibytessent() {
      return isset($this->ApiBytesSent);
    }
    public function getApibytesreceived() {
      if (!isset($this->ApiBytesReceived)) {
        return '0';
      }
      return $this->ApiBytesReceived;
    }
    public function setApibytesreceived($val) {
      if (is_double($val)) {
        $this->ApiBytesReceived = sprintf('%0.0F', $val);
      } else {
        $this->ApiBytesReceived = $val;
      }
      return $this;
    }
    public function clearApibytesreceived() {
      unset($this->ApiBytesReceived);
      return $this;
    }
    public function hasApibytesreceived() {
      return isset($this->ApiBytesReceived);
    }
    public function clear() {
      $this->clearContent();
      $this->clearStatuscode();
      $this->clearHeader();
      $this->clearContentwastruncated();
      $this->clearExternalbytessent();
      $this->clearExternalbytesreceived();
      $this->clearFinalurl();
      $this->clearApicpumilliseconds();
      $this->clearApibytessent();
      $this->clearApibytesreceived();
    }
    public function byteSizePartial() {
      $res = 0;
      if (isset($this->Content)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->Content));
      }
      if (isset($this->StatusCode)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->StatusCode);
      }
      $this->checkProtoArray($this->header);
      $res += 2 * sizeof($this->header);
      foreach ($this->header as $value) {
        $res += $value->byteSizePartial();
      }
      if (isset($this->ContentWasTruncated)) {
        $res += 2;
      }
      if (isset($this->ExternalBytesSent)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ExternalBytesSent);
      }
      if (isset($this->ExternalBytesReceived)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ExternalBytesReceived);
      }
      if (isset($this->FinalUrl)) {
        $res += 1;
        $res += $this->lengthString(strlen($this->FinalUrl));
      }
      if (isset($this->ApiCpuMilliseconds)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ApiCpuMilliseconds);
      }
      if (isset($this->ApiBytesSent)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ApiBytesSent);
      }
      if (isset($this->ApiBytesReceived)) {
        $res += 1;
        $res += $this->lengthVarInt64($this->ApiBytesReceived);
      }
      return $res;
    }
    public function outputPartial($out) {
      if (isset($this->Content)) {
        $out->putVarInt32(10);
        $out->putPrefixedString($this->Content);
      }
      if (isset($this->StatusCode)) {
        $out->putVarInt32(16);
        $out->putVarInt32($this->StatusCode);
      }
      $this->checkProtoArray($this->header);
      foreach ($this->header as $value) {
        $out->putVarInt32(27);
        $value->outputPartial($out);
        $out->putVarInt32(28);
      }
      if (isset($this->ContentWasTruncated)) {
        $out->putVarInt32(48);
        $out->putBoolean($this->ContentWasTruncated);
      }
      if (isset($this->ExternalBytesSent)) {
        $out->putVarInt32(56);
        $out->putVarInt64($this->ExternalBytesSent);
      }
      if (isset($this->ExternalBytesReceived)) {
        $out->putVarInt32(64);
        $out->putVarInt64($this->ExternalBytesReceived);
      }
      if (isset($this->FinalUrl)) {
        $out->putVarInt32(74);
        $out->putPrefixedString($this->FinalUrl);
      }
      if (isset($this->ApiCpuMilliseconds)) {
        $out->putVarInt32(80);
        $out->putVarInt64($this->ApiCpuMilliseconds);
      }
      if (isset($this->ApiBytesSent)) {
        $out->putVarInt32(88);
        $out->putVarInt64($this->ApiBytesSent);
      }
      if (isset($this->ApiBytesReceived)) {
        $out->putVarInt32(96);
        $out->putVarInt64($this->ApiBytesReceived);
      }
    }
    public function tryMerge($d) {
      while($d->avail() > 0) {
        $tt = $d->getVarInt32();
        switch ($tt) {
          case 10:
            $length = $d->getVarInt32();
            $this->setContent(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 16:
            $this->setStatuscode($d->getVarInt32());
            break;
          case 27:
            $this->addHeader()->tryMerge($d);
            break;
          case 48:
            $this->setContentwastruncated($d->getBoolean());
            break;
          case 56:
            $this->setExternalbytessent($d->getVarInt64());
            break;
          case 64:
            $this->setExternalbytesreceived($d->getVarInt64());
            break;
          case 74:
            $length = $d->getVarInt32();
            $this->setFinalurl(substr($d->buffer(), $d->pos(), $length));
            $d->skip($length);
            break;
          case 80:
            $this->setApicpumilliseconds($d->getVarInt64());
            break;
          case 88:
            $this->setApibytessent($d->getVarInt64());
            break;
          case 96:
            $this->setApibytesreceived($d->getVarInt64());
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
      if (!isset($this->StatusCode)) return 'StatusCode';
      foreach ($this->header as $value) {
        if (!$value->isInitialized()) return 'header';
      }
      return null;
    }
    public function mergeFrom($x) {
      if ($x === $this) { throw new \IllegalArgumentException('Cannot copy message to itself'); }
      if ($x->hasContent()) {
        $this->setContent($x->getContent());
      }
      if ($x->hasStatuscode()) {
        $this->setStatuscode($x->getStatuscode());
      }
      foreach ($x->getHeaderList() as $v) {
        $this->addHeader()->copyFrom($v);
      }
      if ($x->hasContentwastruncated()) {
        $this->setContentwastruncated($x->getContentwastruncated());
      }
      if ($x->hasExternalbytessent()) {
        $this->setExternalbytessent($x->getExternalbytessent());
      }
      if ($x->hasExternalbytesreceived()) {
        $this->setExternalbytesreceived($x->getExternalbytesreceived());
      }
      if ($x->hasFinalurl()) {
        $this->setFinalurl($x->getFinalurl());
      }
      if ($x->hasApicpumilliseconds()) {
        $this->setApicpumilliseconds($x->getApicpumilliseconds());
      }
      if ($x->hasApibytessent()) {
        $this->setApibytessent($x->getApibytessent());
      }
      if ($x->hasApibytesreceived()) {
        $this->setApibytesreceived($x->getApibytesreceived());
      }
    }
    public function equals($x) {
      if ($x === $this) { return true; }
      if (isset($this->Content) !== isset($x->Content)) return false;
      if (isset($this->Content) && $this->Content !== $x->Content) return false;
      if (isset($this->StatusCode) !== isset($x->StatusCode)) return false;
      if (isset($this->StatusCode) && !$this->integerEquals($this->StatusCode, $x->StatusCode)) return false;
      if (sizeof($this->header) !== sizeof($x->header)) return false;
      foreach (array_map(null, $this->header, $x->header) as $v) {
        if (!$v[0]->equals($v[1])) return false;
      }
      if (isset($this->ContentWasTruncated) !== isset($x->ContentWasTruncated)) return false;
      if (isset($this->ContentWasTruncated) && $this->ContentWasTruncated !== $x->ContentWasTruncated) return false;
      if (isset($this->ExternalBytesSent) !== isset($x->ExternalBytesSent)) return false;
      if (isset($this->ExternalBytesSent) && !$this->integerEquals($this->ExternalBytesSent, $x->ExternalBytesSent)) return false;
      if (isset($this->ExternalBytesReceived) !== isset($x->ExternalBytesReceived)) return false;
      if (isset($this->ExternalBytesReceived) && !$this->integerEquals($this->ExternalBytesReceived, $x->ExternalBytesReceived)) return false;
      if (isset($this->FinalUrl) !== isset($x->FinalUrl)) return false;
      if (isset($this->FinalUrl) && $this->FinalUrl !== $x->FinalUrl) return false;
      if (isset($this->ApiCpuMilliseconds) !== isset($x->ApiCpuMilliseconds)) return false;
      if (isset($this->ApiCpuMilliseconds) && !$this->integerEquals($this->ApiCpuMilliseconds, $x->ApiCpuMilliseconds)) return false;
      if (isset($this->ApiBytesSent) !== isset($x->ApiBytesSent)) return false;
      if (isset($this->ApiBytesSent) && !$this->integerEquals($this->ApiBytesSent, $x->ApiBytesSent)) return false;
      if (isset($this->ApiBytesReceived) !== isset($x->ApiBytesReceived)) return false;
      if (isset($this->ApiBytesReceived) && !$this->integerEquals($this->ApiBytesReceived, $x->ApiBytesReceived)) return false;
      return true;
    }
    public function shortDebugString($prefix = "") {
      $res = '';
      if (isset($this->Content)) {
        $res .= $prefix . "Content: " . $this->debugFormatString($this->Content) . "\n";
      }
      if (isset($this->StatusCode)) {
        $res .= $prefix . "StatusCode: " . $this->debugFormatInt32($this->StatusCode) . "\n";
      }
      foreach ($this->header as $value) {
        $res .= $prefix . "Header {\n" . $value->shortDebugString($prefix . "  ") . $prefix . "}\n";
      }
      if (isset($this->ContentWasTruncated)) {
        $res .= $prefix . "ContentWasTruncated: " . $this->debugFormatBool($this->ContentWasTruncated) . "\n";
      }
      if (isset($this->ExternalBytesSent)) {
        $res .= $prefix . "ExternalBytesSent: " . $this->debugFormatInt64($this->ExternalBytesSent) . "\n";
      }
      if (isset($this->ExternalBytesReceived)) {
        $res .= $prefix . "ExternalBytesReceived: " . $this->debugFormatInt64($this->ExternalBytesReceived) . "\n";
      }
      if (isset($this->FinalUrl)) {
        $res .= $prefix . "FinalUrl: " . $this->debugFormatString($this->FinalUrl) . "\n";
      }
      if (isset($this->ApiCpuMilliseconds)) {
        $res .= $prefix . "ApiCpuMilliseconds: " . $this->debugFormatInt64($this->ApiCpuMilliseconds) . "\n";
      }
      if (isset($this->ApiBytesSent)) {
        $res .= $prefix . "ApiBytesSent: " . $this->debugFormatInt64($this->ApiBytesSent) . "\n";
      }
      if (isset($this->ApiBytesReceived)) {
        $res .= $prefix . "ApiBytesReceived: " . $this->debugFormatInt64($this->ApiBytesReceived) . "\n";
      }
      return $res;
    }
  }
}
