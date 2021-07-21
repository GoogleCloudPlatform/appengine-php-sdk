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
 * SendMail Executable File 
 * Include this file in the php.ini to override the mail() function with App Engine mail APIs.
 * To enable, please add the following to the php.ini file:
 *  extension = mailparse.so
 *  sendmail_path = "php ./vendor/google/appengine-php-sdk/src/Runtime/SendMail.php -t -i"
 *    
 * @see http://php.net/mail
 * @see https://www.php.net/manual/en/mail.configuration.php#ini.sendmail-path
 */

require_once __DIR__ . "/Mail.php";

use function Google\AppEngine\Runtime\sendRawToMailApi;

$raw_mail = file_get_contents('php://stdin');
return sendRawToMailApi($raw_mail);
