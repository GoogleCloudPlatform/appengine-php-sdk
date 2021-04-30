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
namespace Google\AppEngine\Testing;

use Google\AppEngine\Runtime\ApiProxy;
use Google\AppEngine\Runtime\ApiProxyBase;

class ApiProxyMock extends ApiProxyBase {
  public function init($testcase) {
    $this->expected = array();
    $this->testcase = $testcase;
    $this->verified = false;
    ApiProxy::setApiProxy($this);
  }

  public function expectCall($package, $call_name, $req, $resp) {
    $call = new ApiCallArguments($package, $call_name, $req, $resp);
    $this->expected[] = $call;
  }

  public function makeSyncCall(
      $package, $call_name, $req, $resp, $deadline = null) {
    $call = new ApiCallArguments($package, $call_name, $req, $resp);
    $expectedCall = array_shift($this->expected);

    if (is_null($expectedCall)) {
      $this->testcase->fail("Unexpected API Call: " . $call->toString());
    }
    if (!$call->isInputEqual($expectedCall)) {
      $this->testcase->fail("Expected: " . $expectedCall->toString() . "\n"
          . "Was: " . $call->toString());
    }
    if ($expectedCall->resp instanceof \Exception) {
      throw $expectedCall->resp;
    } else {
      $this->copyFrom($resp, $expectedCall->resp);

    }
  }

  public function verify() {
    $this->testcase->assertSame(array(), $this->expected);
  }

  /**
   * Copies data from another protocol buffer pb2 to pb1.
   */
  private function copyFrom($pb1, $pb2) {
    if ($pb1 === $pb2) {
      return;
    }
    $pb1->clear();
    $pb1->mergeFrom($pb2);
  }
}

