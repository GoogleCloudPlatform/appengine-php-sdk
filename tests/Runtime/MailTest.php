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


use Google\AppEngine\Runtime;
use google\appengine\base\VoidProto;
use Google\AppEngine\Api\Mail\Message;
use google\appengine\MailMessage;
use google\appengine\MailServiceError\ErrorCode;
use Google\AppEngine\Runtime\ApplicationError;
use Google\AppEngine\Testing\ApiProxyTestBase;

class MailTest extends ApiProxyTestBase {

  // This use declaration must be defined inside the test class. 
  // Please see https://github.com/php-mock/php-mock-phpunit 
  use \phpmock\phpunit\PHPMock;

  public function setUp(): void {
    parent::setUp();
  }
  
  public function testSendTextMail() {
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_create");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_parse");
    $mailparse_mock->expects($this->once())->willReturn(true);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_structure");
    $mailparse_mock->expects($this->once())->willReturn([1]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $array = array();
    $array['headers']['from'] = 'foo@foo.com';
    $array['headers']['to'] = 'bar@bar.com';
    $array['headers']['subject'] = 'subject';
    $array['content-type'] = 'text/plain';
    $array['starting-pos-body'] = 78;
    $array['ending-pos-body'] = 84;
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part_data");
    $mailparse_mock->expects($this->any())->willReturn($array);

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
    Runtime\mail($raw_mail);
    $this->apiProxyMock->verify();
  }

  public function testSendHtmlMail() {
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_create");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_parse");
    $mailparse_mock->expects($this->once())->willReturn(true);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_structure");
    $mailparse_mock->expects($this->once())->willReturn([1]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $array = array();
    $array['headers']['from'] = 'foo@foo.com';
    $array['headers']['to'] = 'bar@bar.com';
    $array['headers']['subject'] = 'subject';
    $array['content-type'] = 'text/html';
    $array['starting-pos-body'] = 77;
    $array['ending-pos-body'] = 88;
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part_data");
    $mailparse_mock->expects($this->any())->willReturn($array);

    $to = 'bar@bar.com';
    $from = 'foo@foo.com';
    $subject = 'subject';
    $message = "<b>html</b>";
    $headers = "From: {$from}\r" .
               "Content-Type: text/html\r";

    $message_proto = new MailMessage();
    $message_proto->setSender($from);
    $message_proto->addTo($to);
    $message_proto->setSubject($subject);
    $message_proto->setHtmlBody($message);
    $response = new VoidProto();
    $this->apiProxyMock->expectCall('mail', 'Send', $message_proto, $response);
    
    $raw_mail = "To: {$to}\rSubject: {$subject}\r";
    $raw_mail .= $headers;
    $raw_mail .= "\r\n{$message}";
    Runtime\mail($raw_mail);
    $this->apiProxyMock->verify();
  }

  public function testSendMailWithAttachment() {

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_create");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_parse");
    $mailparse_mock->expects($this->once())->willReturn(true);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_structure");
    $mailparse_mock->expects($this->once())->willReturn([1]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part");
    $mailparse_mock->expects($this->once())->willReturn([]);
    
    $image_content_id = '<image-content-id>';
    $attachment_data = 'image data';
    $filename = 'image.png';

    $array = array();
    $array['headers']['from'] = 'foo@foo.com';
    $array['headers']['to'] = 'bar@bar.com';
    $array['headers']['subject'] = 'subject';
    $array['content-type'] = 'text/plain';
    $array['content-disposition'] = 'inline';
    $array['disposition-filename'] = $filename;
    $array['content-id'] = $image_content_id;
    $array['starting-pos-body'] = 78;
    $array['ending-pos-body'] = 84;
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part_data");
    $mailparse_mock->expects($this->any())->willReturn($array);

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

    // Warning for setting 'content-disposition' but not having a separate body
    $this->expectException('PHPUnit\Framework\Error\Warning');
    
    $raw_mail = "To: {$to}\rSubject: {$subject}\r";
    $raw_mail .= $headers;
    $raw_mail .= "\r\n{$message}";
    Runtime\mail($raw_mail);
  }

  public function testSendMultipartMailWithUnknownContentType() {
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_create");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_parse");
    $mailparse_mock->expects($this->once())->willReturn(true);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_structure");
    $mailparse_mock->expects($this->once())->willReturn([1]);

    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part");
    $mailparse_mock->expects($this->once())->willReturn([]);

    $array = array();
    $array['headers']['from'] = 'foo@foo.com';
    $array['headers']['to'] = 'bar@bar.com';
    $array['headers']['subject'] = 'subject';
    $array['content-type'] = 'fake_value';
    $array['starting-pos-body'] = 77;
    $array['ending-pos-body'] = 88;
    $mailparse_mock = $this->getFunctionMock('Google\AppEngine\Runtime', "mailparse_msg_get_part_data");
    $mailparse_mock->expects($this->any())->willReturn($array);

    $to = 'bar@bar.com';
    $from = 'foo@foo.com';
    $subject = 'subject';
    $message = "<b>html</b>";
    $headers = "From: {$from}\r" .
           "Content-Type: fake_value\r";

    $message_proto = new MailMessage();
    $message_proto->setSender($from);
    $message_proto->addTo($to);
    $message_proto->setSubject($subject);
    $message_proto->setHtmlBody($message);
    $response = new VoidProto();

    // Warning issued for incorrect content-type field.
    $this->expectException('PHPUnit\Framework\Error\Warning');
    
    $raw_mail = "To: {$to}\rSubject: {$subject}\r";
    $raw_mail .= $headers;
    $raw_mail .= "\r\n{$message}";
    Runtime\mail($raw_mail);
    $this->apiProxyMock->verify();
  }
}
