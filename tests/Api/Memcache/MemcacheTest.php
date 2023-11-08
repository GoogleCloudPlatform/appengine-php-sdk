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
 * Unit tests for the emulated "memcache" PHP extension.
 *
 */

namespace Google\AppEngine\Api\Memcache;

use google\appengine\MemcacheDeleteRequest;
use google\appengine\MemcacheDeleteResponse;
use google\appengine\MemcacheDeleteResponse\DeleteStatusCode;
use google\appengine\MemcacheFlushRequest;
use google\appengine\MemcacheFlushResponse;
use google\appengine\MemcacheGetRequest;
use google\appengine\MemcacheGetResponse;
use google\appengine\MemcacheIncrementRequest;
use google\appengine\MemcacheIncrementResponse;
use google\appengine\MemcacheSetRequest;
use google\appengine\MemcacheSetRequest\SetPolicy;
use google\appengine\MemcacheSetResponse;
use google\appengine\MemcacheSetResponse\SetStatusCode;
use Google\AppEngine\Testing\ApiProxyTestBase;

class MemcacheTest extends ApiProxyTestBase {

  public function setUp(): void {
    parent::setUp();
    $this->_SERVER = $_SERVER;
  }

  public function tearDown(): void {
    $_SERVER = $this->_SERVER;
    parent::tearDown();
  }

  public function testAddSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("2");
    $item->setFlags(6);  // float
    $item->setSetPolicy(SetPolicy::ADD);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertTrue($memcache->add("float", 2.0, null, 30));
    $this->apiProxyMock->verify();
  }

  public function testAddAlreadyThere() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("2");
    $item->setFlags(6);   // float
    $item->setSetPolicy(SetPolicy::ADD);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::NOT_STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->add("float", 2.0, null, 30));
    $this->apiProxyMock->verify();
  }

  public function testDeleteSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheDeleteRequest();
    $item = $request->addItem();
    $item->setKey("delete_key");

    $response = new MemcacheDeleteResponse();
    $response->addDeleteStatus(DeleteStatusCode::DELETED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Delete',
                                    $request,
                                    $response);
    $this->assertTrue($memcache->delete("delete_key"));
    $this->apiProxyMock->verify();
  }

  public function testDeleteNotThere() {
    $memcache = new Memcache();

    $request = new MemcacheDeleteRequest();
    $item = $request->addItem();
    $item->setKey("delete_key");

    $response = new MemcacheDeleteResponse();
    $response->addDeleteStatus(DeleteStatusCode::NOT_FOUND);

    $this->apiProxyMock->expectCall('memcache',
                                    'Delete',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->delete("delete_key"));
    $this->apiProxyMock->verify();
  }

  public function testFlush() {
    $req = new MemcacheFlushRequest();
    $resp = new MemcacheFlushResponse();
    $memcache = new Memcache();

    $this->apiProxyMock->expectCall('memcache',
                                    'FlushAll',
                                    $req,
                                    $resp);
    $memcache->flush();
    $this->apiProxyMock->verify();
  }

  public function testGetStringSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue("value");
    $item->setFlags(0);  // String.

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals("value", $memcache->get("key"));
    $this->apiProxyMock->verify();
  }

  public function testGetUnicodeSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue("value");
    $item->setFlags(1);  // Unicode.

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals("value", $memcache->get("key"));
    $this->apiProxyMock->verify();
  }

  public function testGetMissing() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");

    $response = new MemcacheGetResponse();

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->get("key"));
    $this->apiProxyMock->verify();
  }

  public function testGetUnexpectedValue() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue("value");
    $item->setFlags(2);  // Python's picked type.

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->get("key"));
    $this->apiProxyMock->verify();
  }

  public function testGetMany() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key1");
    $request->addKey("key2");
    $request->addKey("key3");

    $response = new MemcacheGetResponse();
    $item3 = $response->addItem();
    $item3->setKey("key3");
    $item3->setValue("value3");
    $item3->setFlags(0);  // string.
    $item1 = $response->addItem();
    $item1->setKey("key1");
    $item1->setValue("value1");
    $item1->setFlags(0);  // string.

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals(array("key1" => "value1", "key3" => "value3"),
                        $memcache->get(array("key1", "key2", "key3")));
    $this->apiProxyMock->verify();
  }

  public function testGetManyAllMissing() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key1");
    $request->addKey("key2");
    $request->addKey("key3");

    $response = new MemcacheGetResponse();

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->get(array("key1", "key2", "key3")));
    $this->apiProxyMock->verify();
  }

  public function testPeekString() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");
    $request->setForPeek(true);

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue(3);
    $item->setFlags(0);  // string.
    $timestamps = $item->mutableTimestamps();
    $timestamps->setExpirationTimeSec(123);
    $timestamps->setLastAccessTimeSec(456);
    $timestamps->setDeleteLockTimeSec(789);

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals(new ItemWithTimestamps("3", 123, 456, 789),
                        memcache_peek($memcache, "key"));
    $this->apiProxyMock->verify();
  }

  public function testPeekInt() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");
    $request->setForPeek(true);

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue(3);
    $item->setFlags(3);  // int.
    $timestamps = $item->mutableTimestamps();
    $timestamps->setExpirationTimeSec(123);
    $timestamps->setLastAccessTimeSec(456);
    $timestamps->setDeleteLockTimeSec(789);

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals(new ItemWithTimestamps(3, 123, 456, 789),
                        memcache_peek($memcache, "key"));
    $this->apiProxyMock->verify();
  }

  public function testPeekMany() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key1");
    $request->addKey("key2");
    $request->addKey("key3");
    $request->setForPeek(true);

    $response = new MemcacheGetResponse();
    $item1 = $response->addItem();
    $item1->setKey("key1");
    $item1->setValue("value1");
    $item1->setFlags(0);  // string.
    $timestamps = $item1->mutableTimestamps();
    $timestamps->setExpirationTimeSec(123);
    $timestamps->setLastAccessTimeSec(456);
    $timestamps->setDeleteLockTimeSec(789);
    $item3 = $response->addItem();
    $item3->setKey("key3");
    $item3->setValue(3);
    $item3->setFlags(3);
    $timestamps = $item3->mutableTimestamps();
    $timestamps->setExpirationTimeSec(987);
    $timestamps->setLastAccessTimeSec(654);
    // Omit deleteLockTimeSec to test a zero is returned when missing

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertEquals(["key1" => new ItemWithTimestamps("value1", 123, 456, 789),
                         "key3" => new ItemWithTimestamps(3, 987, 654, 0)],
                        memcache_peek($memcache, ["key1","key2","key3"]));
    $this->apiProxyMock->verify();
  }

  public function testPeekUnexpectedValue() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");
    $request->setForPeek(true);

    $response = new MemcacheGetResponse();
    $item = $response->addItem();
    $item->setKey("key");
    $item->setValue("value");
    $item->setFlags(2);  // Python's picked type - unsupported.
    $timestamps = $item->mutableTimestamps();
    $timestamps->setExpirationTimeSec(123);
    $timestamps->setLastAccessTimeSec(456);
    $timestamps->setDeleteLockTimeSec(789);

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertFalse(memcache_peek($memcache, "key"));
    $this->apiProxyMock->verify();
  }


  public function testPeekMissing() {
    $memcache = new Memcache();

    $request = new MemcacheGetRequest();
    $request->addKey("key");
    $request->setForPeek(true);

    $response = new MemcacheGetResponse();

    $this->apiProxyMock->expectCall('memcache',
                                    'Get',
                                    $request,
                                    $response);
    $this->assertFalse(memcache_peek($memcache, "key"));
    $this->apiProxyMock->verify();
  }

  public function testIncrementSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheIncrementRequest();
    $request->setKey("key");
    $request->setDelta(5);

    $response = new MemcacheIncrementResponse();
    $response->setNewValue(7);

    $this->apiProxyMock->expectCall('memcache',
                                    'Increment',
                                    $request,
                                    $response);
    $this->assertEquals(7, $memcache->increment("key", 5));
    $this->apiProxyMock->verify();
  }

  public function testIncrementNonExistingValue() {
    $memcache = new Memcache();

    $request = new MemcacheIncrementRequest();
    $request->setKey("key");
    $request->setDelta(5);

    $response = new MemcacheIncrementResponse();

    $this->apiProxyMock->expectCall('memcache',
                                    'Increment',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->increment("key", 5));
    $this->apiProxyMock->verify();
  }

  public function testDecrementSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheIncrementRequest();
    $request->setKey("key");
    $request->setDelta(4);
    $request->setDirection(MemcacheIncrementRequest\Direction::DECREMENT);

    $response = new MemcacheIncrementResponse();
    $response->setNewValue(8);

    $this->apiProxyMock->expectCall('memcache',
                                    'Increment',
                                    $request,
                                    $response);
    $this->assertEquals(8, $memcache->decrement("key", 4));
    $this->apiProxyMock->verify();
  }


  public function testReplaceSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("2");
    $item->setFlags(6);  // float
    $item->setSetPolicy(SetPolicy::REPLACE);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertTrue($memcache->replace("float", 2.0, null, 30));
    $this->apiProxyMock->verify();
  }

  public function testReplaceNotThere() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("2");
    $item->setFlags(6);  // float
    $item->setSetPolicy(SetPolicy::REPLACE);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::NOT_STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertFalse($memcache->replace("float", 2.0, null, 30));
    $this->apiProxyMock->verify();
  }

  public function testSetSuccess() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("2");
    $item->setFlags(6);  // float
    $item->setSetPolicy(SetPolicy::SET);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertTrue($memcache->set("float", 2.0, null, 30));
    $this->apiProxyMock->verify();
  }

  public function testSetSuccessCompressed() {
    $memcache = new Memcache();

    $request = new MemcacheSetRequest();
    $item = $request->addItem();
    $item->setKey("float");
    $item->setValue("3");
    $item->setFlags(6);  // float
    $item->setSetPolicy(SetPolicy::SET);
    $item->setExpirationTime(30);

    $response = new MemcacheSetResponse();
    $response->addSetStatus(SetStatusCode::STORED);

    $this->apiProxyMock->expectCall('memcache',
                                    'Set',
                                    $request,
                                    $response);
    $this->assertTrue($memcache->set("float", 3.0, 0, 30));
    $this->apiProxyMock->verify();
  }
}
