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


// Mocking mailparse functions in the global namespaace
function mailparse_msg_create() {}
function mailparse_msg_parse($mime, $raw_mail) {
  return true;
}
function mailparse_msg_get_structure($mime) {
  return [];
}

function mailparse_msg_get_part_data($mime) {
  $array = array();
  $array['headers']['from'] = 'foo@foo.com';
  $array['headers']['to'] = 'bar@bar.com';
  $array['headers']['subject'] = 'subject';
  
  if ($GLOBALS['htmlBodyFlag']) {
    $array['content-type'] = 'text/html';
  } else {
    $array['content-type'] = 'text/plain';
  }
  return $array;
}

class MailTest extends ApiProxyTestBase {
  public function setUp(): void {
    parent::setUp();
    ini_set('sendmail_from', '');
    putenv('APPLICATION_ID=');
  }

  public function testSetSenderUsingIniSetting() {
    ini_set('sendmail_from', 'foo@foo.com');

    $message_proto = new MailMessage();
    $message_proto->setSender('foo@foo.com');
    $message_proto->addTo('bar@bar.com');
    $message_proto->setSubject('subject');
    $message_proto->setTextBody('text');
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);
    $this->assertTrue(sendmail('bar@bar.com', 'subject', 'text'));
    $this->apiProxyMock->verify();
  }

  public function testSendSimpleMail() {
    $headers = "From: foo@foo.com\r\n";
    $message_proto = new MailMessage();
    $message_proto->setSender('foo@foo.com');
    $message_proto->addTo('bar@bar.com');
    $message_proto->setSubject('subject');
    $message_proto->setTextBody('text');
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);

    $ret = sendmail('bar@bar.com', 'subject', 'text', $headers);
    $this->assertTrue($ret);
    $this->apiProxyMock->verify();
  }

  public function testSendMailUsingHeadersWithoutTrailingLinebreak() {
    $headers = "From: foo@foo.com";
    $message_proto = new MailMessage();
    $message_proto->setSender('foo@foo.com');
    $message_proto->addTo('bar@bar.com');
    $message_proto->setSubject('subject');
    $message_proto->setTextBody('text');
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);

    $ret = sendmail('bar@bar.com', 'subject', 'text', $headers);
    $this->assertTrue($ret);
    $this->apiProxyMock->verify();
  }

  public function testSendHtmlMail() {
    $GLOBALS['htmlBodyFlag'] = 1;
    $html = "<b>html</b>";
    $headers = "From: foo@foo.com\r\n" .
               "Content-Type: text/html\r\n";
    $message_proto = new MailMessage();
    $message_proto->setSender('foo@foo.com');
    $message_proto->addTo('bar@bar.com');
    $message_proto->setSubject('subject');
    $message_proto->setHtmlBody($html);
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);

    $ret = sendmail('bar@bar.com', 'subject', $html, $headers);
    $this->assertTrue($ret);
    $this->apiProxyMock->verify();
    $GLOBALS['htmlBodyFlag'] = 0;
  }

}
