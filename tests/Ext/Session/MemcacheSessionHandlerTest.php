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
/**
 * Tests for the Memcache Session Handler. Roughly based on Cloud SQL Session
 * Handler tests by slangley.
 *
 */

namespace Google\AppEngine\Ext\Session;

use PHPUnit\Framework\TestCase;

/** 
* @runTestsInSeparateProcesses 
*/
class MemcacheSessionHandlerTest extends TestCase {
  
  public function testSession() {

    $stub = $this->getMockBuilder(\Google\AppEngine\Ext\Session\MemcacheContainer::class)
                     ->setMethods(['close', 'get', 'set', 'delete'])
                     ->getMock();

    MemcacheSessionHandler::configure($stub);

    $sessionId = 'my_session_id';
    $mySessionId = '_ah_sess_' . $sessionId;

    $stub->expects($this->at(0))
        ->method('get')
        ->with($this->equalTo($mySessionId))
        ->will($this->returnValue(false));

    // Expectations for writing & closing the session
    $escapedAccess = 'escaped_access';
    $escapedData = 'Foo|s:3:"Bar";';
    $expires = ini_get("session.gc_maxlifetime");

    $stub->expects($this->at(1))
        ->method('set')
        ->with($this->equalTo($mySessionId),
            $this->equalTo($escapedData),
            $this->equalTo($expires))
        ->will($this->returnValue(true));

    $stub->expects($this->at(2))
      ->method('close')
      ->will($this->returnValue(true));

    session_id($sessionId);
    session_start();
    $_SESSION['Foo'] = 'Bar';
    $this->assertEquals('Bar', $_SESSION['Foo'], 'Session data does not match expected value.');
    session_write_close();
  }
  public function testConstant() {
    $this->assertEquals(1, MEMCACHE_HAVE_SESSION);
  }
}