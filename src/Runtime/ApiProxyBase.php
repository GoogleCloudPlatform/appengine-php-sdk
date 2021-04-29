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
namespace Google\Appengine\Runtime;

abstract class ApiProxyBase {
  const OK                  =  0;
  const RPC_FAILED          =  1;
  const CALL_NOT_FOUND      =  2;
  const ARGUMENT_ERROR      =  3;
  const DEADLINE_EXCEEDED   =  4;
  const CANCELLED           =  5;
  const APPLICATION_ERROR   =  6;
  const OTHER_ERROR         =  7;
  const OVER_QUOTA          =  8;
  const REQUEST_TOO_LARGE   =  9;
  const CAPABILITY_DISABLED = 10;
  const FEATURE_DISABLED    = 11;
  const RESPONSE_TOO_LARGE  = 12;

  protected static $exceptionLookupTable = array(
    self::RPC_FAILED => array(
      '\Google\Appengine\Runtime\RPCFailedError',
      'The remote RPC to the application server failed for the call %s.%s().'),
    self::CALL_NOT_FOUND => array(
      '\Google\Appengine\Runtime\CallNotFoundError',
      "The API package '%s' or call '%s()' was not found."),
    self::ARGUMENT_ERROR => array(
      '\Google\Appengine\Runtime\ArgumentError',
      'An error occurred parsing (locally or remotely) the arguments to %s.%s().'
    ),
    self::DEADLINE_EXCEEDED => array(
      '\Google\Appengine\Runtime\DeadlineExceededError',
      'The API call %s.%s() took too long to respond and was cancelled.'),
    self::CANCELLED => array(
      '\Google\Appengine\Runtime\CancelledError',
      'The API call %s.%s() was explicitly cancelled.'),
    self::OTHER_ERROR => array(
      '\Google\Appengine\Runtime\Error',
      'An error occurred for the API request %s.%s().'),
    self::OVER_QUOTA => array(
      '\Google\Appengine\Runtime\OverQuotaError',
      'The API call %s.%s() required more quota than is available.'),
    self::REQUEST_TOO_LARGE => array(
      '\Google\Appengine\Runtime\RequestTooLargeError',
      'The request to API call %s.%s() was too large.'),
    self::RESPONSE_TOO_LARGE => array(
      '\Google\Appengine\Runtime\ResponseTooLargeError',
      'The response from API call %s.%s() was too large.'),

    # APPLICATION_ERROR is special-cased to create an ApplicationError
    # with the specified application_error and error_detail values.
    #
    # CAPABILITY_DISABLED is special-cased to create a
    # CapabilityDisabledError with the specified error_detail message.
    #
    # FEATURE_DISABLED is special-cased to create a FeatureNotEnabledError
    # with the specified error_detail message.
  );

  abstract public function makeSyncCall(
      $package,
      $call_name,
      $request,
      $response,
      $deadline = null);
}
