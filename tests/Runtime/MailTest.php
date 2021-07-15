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
 * Tests for Mail API on App Engine.
 *
 */

require_once __DIR__ . "/../../src/Runtime/Mail.php";

use google\appengine\base\VoidProto;
use Google\AppEngine\Api\Mail\Message;
use google\appengine\MailMessage;
use google\appengine\MailServiceError\ErrorCode;
use Google\AppEngine\Runtime\ApplicationError;
use Google\AppEngine\Testing\ApiProxyTestBase;

class MailTest extends ApiProxyTestBase {
  public function setUp(): void {
    parent::setUp();
  }

  public function testSendSimpleMail() {
    // Mocking mailparse functions in the global namespace
    function mailparse_msg_create() {}
    function mailparse_msg_parse($mime, $raw_mail) {
      return true;
    }
    function mailparse_msg_get_structure($mime) {
      return [1];
    }

    function mailparse_msg_get_part($mimemail, $mimesection) {
      return [];
    }
    function mailparse_msg_get_part_data($mime) {
      $array = array();
      $array['headers']['from'] = 'foo@foo.com';
      $array['headers']['to'] = 'bar@bar.com';
      $array['headers']['subject'] = 'subject';
      $array['content-type'] = 'text/plain';
      $array['starting-pos-body'] = 78;
      $array['ending-pos-body'] = 84;
      return $array;
    }

    $to = 'bar@bar.com';
    $from = 'foo@foo.com';
    $subject = 'subject';
    $message = 'text';
    $headers = "From: {$from}\r" .
           "Content-Type: text/plain\r";

    $message_proto = new MailMessage();
    $message_proto->setSender($from);
    $message_proto->addTo($to);
    $message_proto->setSubject($subject);
    $message_proto->setTextBody($message);
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);
    
    $raw_mail = "To: {$to}\rSubject: {$subject}\r";
    $raw_mail .= $headers;
    $raw_mail .= "\r\n{$message}";
    mailRun($raw_mail);
    $this->apiProxyMock->verify();
  }

}
