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
 * Allow users to send mail using the App Engine mail APIs.
 *
 */


// Must import all dependencies for an independent executable.  
require_once __DIR__ . "/../Runtime/Proto/ProtocolMessage.php";
require_once __DIR__ . "/../Runtime/Proto/Encoder.php";
require_once __DIR__ . "/../Runtime/Proto/Decoder.php";
require_once __DIR__ . "/../Runtime/Proto/ProtocolBufferEncodeError.php";
require_once __DIR__ . "/../Runtime/Proto/ProtocolBufferDecodeError.php";
require_once __DIR__ . "/../Api/api_base_pb.php";
require_once __DIR__ . "/../Api/Mail/mail_service_pb.php";
require_once __DIR__ . "/../Api/Mail/BaseMessage.php";
require_once __DIR__ . "/../Api/Mail/Message.php";
require_once __DIR__ . "/../Runtime/ApiProxyBase.php";
require_once __DIR__ . "/../Runtime/Error.php";
require_once __DIR__ . "/../Runtime/ApplicationError.php";
require_once __DIR__ . "/../Runtime/VmApiProxy.php";
require_once __DIR__ . "/../Runtime/ApiProxy.php";
require_once __DIR__ . "/../Ext/RemoteApi/remote_api_pb.php";
require_once __DIR__ . "/../Util/ArrayUtil.php";
require_once __DIR__ . "/../Util/StringUtil.php";


use Google\AppEngine\Api\AppIdentity\AppIdentityService;
use Google\AppEngine\Api\Mail\Message;
use Google\AppEngine\Util\ArrayUtil;
use Google\AppEngine\Util\StringUtil;


echo "STDIN Zach ";
$f = fopen('php://stdin', 'r');

// print_r(stream_get_meta_data($f));


// Parse To
$line = fgets($f);
$line_arr = explode (":", $line);
if($line_arr[0] != 'To'){
  throw new Exception('No To field set in mail() call, value is: ' . $line);
}
$to = $line_arr[1];

// Parse Subject
$line = fgets($f);
$line_arr = explode (":", $line);
if($line_arr[0] != 'Subject'){
  throw new Exception('No Subject field set in mail() call, value is: ' . $line);
}
$subject = $line_arr[1];

// Parse Headers and Message
$headers = '';
$message = '';

$meta_data = stream_get_meta_data($f);
// $h = $meta_data['wrapper_data'];
echo "METADATA ZACH: ";
print_r($meta_data);

$header_section = true; 
while($line = fgets($f)) {
  if($header_section == true && strpos($line, ':') !== false) {
    $headers .=  $line . "\r";
  } else {
    $header_section = false;
    $message .=  $line . "\r\n";
  }
}


fclose($f);

echo "TO ZACH: " . $to;
echo "SUBJECT ZACH: " . $subject;
echo "MESSAGE ZACH: " . $message;
//URGENT ZACH: MAKE SURE ALL THE HEADERS GET PASSED TO HERE!!!!
// ESPECIALLY THE MIME ONES AND THE BOUNDARY ONES!!!!!
echo "HEADERS ZACH: " . $headers;
return Mail::sendMail($to, $subject, $message, $headers);


final class Mail {
  // The format string for the default sender address.
  const DEFAULT_SENDER_ADDRESS_FORMAT = 'mailer@%s.appspotmail.com';
  
  /**
   * Send an email.
   *
   * This is a re-implementation of PHP's mail() function using App Engine
   * mail API. The function relies on mailparse extension to parse emails.
   *
   * @param string $to Receiver, or receivers of the mail.
   * @param string $subject Subject of the email to be sent.
   * @param string $message Message to be sent.
   * @param string $additional_headers optional
   *   String to be inserted at the end of the email header.
   * @param string $additional_parameters optional
   *   Additional flags to be passed to the mail program. This arugment is
   *   added only to match the signature of PHP's mail() function. The value is
   *   always ignored.
   * @return bool
   *   TRUE if the message is sent successfully, otherwise return FALSE.
   *
   * @see http://php.net/mail
   */

  public static function sendMail($to,
                                  $subject,
                                  $message,
                                  $additional_headers = null,
                                  $additional_parameters = null) {
    $raw_mail = "To: {$to}\rSubject: {$subject}\r";
    if ($additional_headers != null) {
      $raw_mail .= trim($additional_headers);
    }
    $raw_mail .= "\r\n\r\n{$message}";

    $mime = mailparse_msg_create();
    mailparse_msg_parse($mime, $raw_mail);
    $root_part = mailparse_msg_get_part_data($mime);
    echo "Zach ROOT PART: ";
    print_r($root_part);

    // Set sender address based on the following order
    // 1. "From" header in $additional_headers
    // 2. "sendmail_from" ini setting
    // 3. Default address "mailer@<app-id>.appspotmail.com
    $from = ini_get('sendmail_from');
    if (isset($root_part['headers']['from'])) {
      $from = $root_part['headers']['from'];
    }
    if ($from === false || $from == "") {
      $appid_arr = explode('~', getenv('GAE_APPLICATION'));
      echo "PRINTING PHPINFO: ";
      print_r(phpinfo());
      $appid = $appid_arr[1];
      // $host_name = getenv('HTTP_X_APPENGINE_DEFAULT_VERSION_HOSTNAME');
      // $host_name_suffix = '.appspotmail.com';
      // $qa_suffix = 'prom-qa.sandbox.google.com';
      // $length = strlen($qa_suffix);
      // echo "HOST NAME: " . $host_name;
      // echo "HOST NAME - LENGTH: " . substr($host_name, - $length);
      // if(substr($host_name, - $length) == $qa_suffix) {
      //   $host_name_suffix = '.prommail-qa.corp.google.com';
      // }
      $from = sprintf(self::DEFAULT_SENDER_ADDRESS_FORMAT, $appid);
      syslog(LOG_WARNING,
             "mail(): Unable to determine sender's email address from the " .
             "'sendmail_from' directive in php.ini or from the 'From' " .
             "header. Falling back to the default $from.");
    }

    $email = new Message();
    try {
      $email->setSender($from);
      $email->addTo($to);
      if (isset($root_part['headers']['cc'])) {
        $email->AddCc($root_part['headers']['cc']);
      }
      if (isset($root_part['headers']['bcc'])) {
        $email->AddBcc($root_part['headers']['bcc']);
      }
      if (isset($root_part['headers']['reply-to'])) {
        $email->setReplyTo($root_part['headers']['reply-to']);
      }

      $email->setSubject($subject);
      $parts = mailparse_msg_get_structure($mime);
      echo "ZACH ABOVE PARTS CNT: ";
      print_r(count($parts));
      if (count($parts) > 1) {
        foreach ($parts as $part_id) {
          $part = mailparse_msg_get_part($mime, $part_id);
          self::parseMimePart($part, $raw_mail, $email);
        }
      } else if ($root_part['content-type'] == 'text/plain') {
        $email->setTextBody($message);
      }  else if ($root_part['content-type'] == 'text/html') {
        $email->setHtmlBody($message);
      }

    echo "ZACH HEADERS22: ";
    print_r($root_part['headers']);
      $extra_headers = array_diff_key($root_part['headers'], array_flip([
          'from', 'to', 'cc', 'bcc', 'reply-to', 'subject', 'content-type']));
      foreach ($extra_headers as $key => $value) {
        try {
          $email->addHeader($key, $value);
        } catch (\InvalidArgumentException $e) {
          syslog(LOG_WARNING, "mail:() Dropping disallowed email header $key");
        }
      }
      $email->send();
    } catch (\Exception $e) {
      trigger_error('mail(): ' . $e->getMessage(), E_USER_WARNING);
      return false;
    }

    return true;
  }

  /**
   * Parse a MIME part and set the Message object accordingly.
   *
   * @param resource $part A MIME part, returned from mailparse_msg_get_part,
   *    to be parse.
   * @param string $raw_mail The string holding the raw content of the email
   *    $part is extracted from.
   * @param Message& $email The Message object to be set.
   */
  private static function parseMimePart($part, $raw_mail, &$email) {
    $data = mailparse_msg_get_part_data($part);
    $type = ArrayUtil::findByKeyOrDefault($data, 'content-type', 'text/plain');
    echo "ZACH DATA PART: ";
    print_r($data);

    $start = $data['starting-pos-body'];
    $end = $data['ending-pos-body'];
    $encoding = ArrayUtil::findByKeyOrDefault($data, 'transfer-encoding', '');
    $content = self::decodeContent(substr($raw_mail, $start, $end - $start),
                                   $encoding);
    echo "ZACH DATA CONTENT: ";
    print_r($content);

    if (isset($data['content-disposition'])) {
      $filename = ArrayUtil::findByKeyOrDefault(
          $data, 'disposition-filename', uniqid());
      $content_id = ArrayUtil::findByKeyOrNull($data, 'content-id');
      if ($content_id != null) {
        $content_id = "<$content_id>";
      }
      $email->addAttachment($filename, $content, $content_id);
    } else if ($type == 'text/html') {
      $email->setHtmlBody($content);
    } else if ($type == 'text/plain') {
      $email->setTextBody($content);
    } else if (!StringUtil::startsWith($type, 'multipart/')) {
      trigger_error("Ignore MIME part with unknown Content-Type $type. " .
                    "Did you forget to specifcy Content-Disposition header?",
                    E_USER_WARNING);
    }
  }

  /**
   * Decoded content based on the encoding scheme.
   *
   * @param string $content The content to be decoded.
   * @param string $scheme The encoding shceme used. Currently only supports
   *    'base64' and 'quoted-printable'.
   * @return string The deocded content if the encoding scheme is supported,
   *    otherwise returns the original content.
   */
  private static function decodeContent($content, $encoding) {
    switch (strtolower($encoding)) {
      case 'base64':
        return base64_decode($content);
      case 'quoted-printable':
        return quoted_printable_decode($content);
      default:
        return $content;
    }
  }

  private static function parseStream($line) {
    $line_arr = explode (":", line);

  }
}
?>
