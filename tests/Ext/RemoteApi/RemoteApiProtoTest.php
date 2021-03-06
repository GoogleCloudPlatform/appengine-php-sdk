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
namespace google\appengine\ext\remote_api;

use google\appengine\ext\remote_api\Request;
use \PHPUnit\Framework\TestCase;

# TODO: delete this test when we finalize the layout of the SDK

class RemoteApiProtoTest extends TestCase {
  public function testRequestInstantiation() {
    $req = new Request();
    $this->assertEquals("", $req->serializePartialToString());
  }
}

