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

namespace Google\AppEngine\Runtime;

use google\appengine\ext\remote_api\Request;
use google\appengine\ext\remote_api\Response;
use google\appengine\ext\remote_api\RpcError\ErrorCode;
use google\appengine\SignForAppRequest;
use google\appengine\SignForAppResponse;
use Google\AppEngine\Runtime\ApiProxy;
use Google\AppEngine\Runtime\ApplicationError;
use \PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;

class VmAPiProxyTest extends TestCase {
  const PACKAGE_NAME = 'app_identity_service';
  const CALL_NAME = "TestCall";

  // The default options used when configuring the expected RPC.
  private static $rpc_default_options = [
    'host' => VmApiProxy::SERVICE_BRIDGE_HOST,
    'port' => VmApiProxy::API_PORT,
    'proxy_path' => VmApiProxy::PROXY_PATH,
    'package_name' => self::PACKAGE_NAME,
    'call_name' => self::CALL_NAME,
    'ticket' => 'SomeTicketValue',
    'timeout' => VmApiProxy::DEFAULT_TIMEOUT_SEC,
    'http_headers' => [
      VmApiProxy::SERVICE_ENDPOINT_HEADER => VmApiProxy::SERVICE_ENDPOINT_NAME,
      VmApiProxy::SERVICE_METHOD_HEADER => VmApiProxy::APIHOST_METHOD,
      'Content-Type' => VmApiProxy::RPC_CONTENT_TYPE,
    ],
    'context' => [
      'http' => [
        'method' => 'POST',
      ],
    ],
  ];

  protected function setUp(): void {
    ApiProxy::setApiProxy(new VmApiProxy());

    // Standard environment variables
    putenv(VmApiProxy::TICKET_HEADER . '=' .
           self::$rpc_default_options['ticket']);
  }

  protected function tearDown(): void {

    // Clear the environment
    putenv(VmApiProxy::TICKET_HEADER);
  }

  protected function expectRpc($request,
                               $response,
                               $call_options = []) {
    $stream_call_data = [];

    $options = array_merge(self::$rpc_default_options, $call_options);

    // Open call will supply the address and the RPC request.
    $address = sprintf('http://%s:%s%s',
                       $options['host'],
                       $options['port'],
                       $options['proxy_path']);

    $remote_request = new Request();
    $remote_request->setServiceName($options['package_name']);
    $remote_request->setMethod($options['call_name']);
    $remote_request->setRequestId($options['ticket']);
    $remote_request->setRequest($request->serializeToString());

    $options['context']['http']['content'] =
        $remote_request->serializeToString();

    $options['context']['http']["timeout"] = $options['timeout'] +
                                             VmApiProxy::DEADLINE_DELTA_SECONDS;

    $options['http_headers'][VmApiProxy::SERVICE_DEADLINE_HEADER] =
        $options['timeout'];

    // Form the header string - sort by key as we do a string compare to check
    // for a match.
    ksort($options['http_headers']);
    $header_str = "";
    foreach($options['http_headers'] as $k => $v) {
      $header_str .= sprintf("%s: %s\r\n", $k, $v);
    }
    $options['context']['http']['header'] = $header_str;

    $stream_call_data['stream_open'] = [
      'address' => $address,
      'mode' => 'rb',
      'context' => $options['context'],
    ];

    if (isset($options['http_open_failure'])) {
      $stream_call_data['stream_open']['http_open_failure'] = true;
    }

    $remote_response = new Response();
    if (isset($options['rpc_exception'])) {
      $error = $remote_response->mutableRpcError();
      $error->setCode($options['rpc_exception']);
    } else if (isset($options['application_error'])) {
      $error = $remote_response->mutableApplicationError();
      $error->setCode($options['application_error']['code']);
      $error->setDetail($options['application_error']['detail']);
    } else if (isset($options['generic_exception'])) {
      $remote_response->setException(true);
    } else {
      $remote_response->setResponse($response->serializeToString());
    }
    $serialized_remote_response = $remote_response->serializeToString();

    $stream_call_data['stream_stat'] = [
      'size' => strlen($serialized_remote_response),
    ];

    $stream_call_data['stream_read'] = [
      'bytes' => $serialized_remote_response,
    ];
  }

  public function testBasicRpc() {
    $expected_request = new SignForAppRequest();
    $expected_response = new SignForAppResponse();
    $expected_request->setBytesToSign("SomeBytes");
    $expected_response->setKeyName("TheKeyName");
    
    $remote_response = new Response();
    $remote_response->setResponse($expected_response->serializeToString());
    $string = $remote_response->serializeToString();
    $mock = new MockHandler([
        new Psr7\Response(200, ['Content-Type' => 'text/plain'], $string)
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $vmApiProxy = new VmApiProxy(null, $client);

    $this->expectRpc($expected_request, $expected_response);

    $response = new SignForAppResponse();
    $vmApiProxy->makeSyncCall(self::PACKAGE_NAME,
                           self::CALL_NAME,
                           $expected_request,
                           $response);

    $this->assertEquals($response->getKeyName(), "TheKeyName");
  }

  /**
   * @dataProvider rpcExceptionProvider
   */
  public function testRpcException($error_code, $exception) {
    $expected_request = new SignForAppRequest();
    $expected_response = new SignForAppResponse();
    $expected_request->setBytesToSign("SomeBytes");
    $remote_response = new Response();
    $remote_response->setResponse($expected_response->serializeToString());
    $error = $remote_response->mutableRpcError();
    $error->setCode($error_code);
    $string = $remote_response->serializeToString();

    $mock = new MockHandler([
        new Psr7\Response(200, ['Content-Type' => 'text/plain'], $string)
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $vmApiProxy = new VmApiProxy(null, $client);
    $response = new SignForAppResponse();
    

    $options = [
      'rpc_exception' => $error_code,
    ];

    $this->expectRpc($expected_request, $expected_response, $options);

    $this->expectException('Google\AppEngine\Runtime\\' . $exception);
    $vmApiProxy->makeSyncCall(self::PACKAGE_NAME,
                           self::CALL_NAME,
                           $expected_request,
                           $response);
  }

  public function rpcExceptionProvider() {
    return [
      [ErrorCode::UNKNOWN, 'RPCFailedError'],
      [ErrorCode::CALL_NOT_FOUND, 'CallNotFoundError'],
      [ErrorCode::PARSE_ERROR, 'ArgumentError'],
      [ErrorCode::SECURITY_VIOLATION, 'RPCFailedError'],
      [ErrorCode::OVER_QUOTA, 'OverQuotaError'],
      [ErrorCode::REQUEST_TOO_LARGE, 'RequestTooLargeError'],
      [ErrorCode::CAPABILITY_DISABLED, 'CapabilityDisabledError'],
      [ErrorCode::FEATURE_DISABLED, 'FeatureNotEnabledError'],
      [ErrorCode::BAD_REQUEST, 'RPCFailedError'],
      [ErrorCode::RESPONSE_TOO_LARGE, 'ResponseTooLargeError'],
      [ErrorCode::CANCELLED, 'CancelledError'],
      [ErrorCode::REPLAY_ERROR, 'RPCFailedError'],
      [ErrorCode::DEADLINE_EXCEEDED, 'DeadlineExceededError'],
    ];
  }

  public function testApplicationError() {
    $expected_request = new SignForAppRequest();
    $expected_response = new SignForAppResponse();
    $expected_request->setBytesToSign("SomeBytes");
    $remote_response = new Response();
    $remote_response->setResponse($expected_response->serializeToString());
    $error = $remote_response->mutableRpcError();
    $error->setCode(666);
    $error->setDetail('foo');
    $string = $remote_response->serializeToString();

    $mock = new MockHandler([
        new Psr7\Response(200, ['Content-Type' => 'text/plain'], $string)
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $vmApiProxy = new VmApiProxy(null, $client);
    $response = new SignForAppResponse();

    $options = [
      'application_error' => [
        'code' => 666,
        'detail' => 'foo',
      ],
    ];

    $this->expectRpc($expected_request, $expected_response, $options);
    
    $this->expectException('Google\AppEngine\Runtime\RPCFailedError');
    $vmApiProxy->makeSyncCall(self::PACKAGE_NAME,
                           self::CALL_NAME,
                           $expected_request,
                           $response);

  }

  public function testRpcDeadline() {
    $expected_request = new SignForAppRequest();
    $expected_response = new SignForAppResponse();
    $expected_request->setBytesToSign("SomeBytes");
    $expected_response->setKeyName("TheKeyName");
    $timeout = 666;

    $remote_response = new Response();
    $remote_response->setResponse($expected_response->serializeToString());
    $string = $remote_response->serializeToString();
    $mock = new MockHandler([
        new Psr7\Response(200, ['Content-Type' => 'text/plain'], $string)
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $vmApiProxy = new VmApiProxy(null, $client);

    $options = [
      'timeout' => $timeout,
    ];
    $this->expectRpc($expected_request, $expected_response, $options);

    $response = new SignForAppResponse();
    $vmApiProxy->makeSyncCall(self::PACKAGE_NAME,
                           self::CALL_NAME,
                           $expected_request,
                           $response, 
                           $timeout);

    $this->assertEquals($response->getKeyName(), "TheKeyName");
  }

  public function testRpcDevTicket() {
    $expected_request = new SignForAppRequest();
    $expected_response = new SignForAppResponse();
    $expected_request->setBytesToSign("SomeBytes");
    $expected_response->setKeyName("TheKeyName");
    
    $ticket = 'TheDevTicket';
    putenv(VmApiProxy::TICKET_HEADER);
    putenv(VmApiProxy::DEV_TICKET_HEADER . "=$ticket");

    $remote_response = new Response();
    $remote_response->setResponse($expected_response->serializeToString());
    $string = $remote_response->serializeToString();
    $mock = new MockHandler([
        new Psr7\Response(200, ['Content-Type' => 'text/plain'], $string)
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $vmApiProxy = new VmApiProxy(null, $client);
  
    $options = [
      'ticket' => $ticket,
    ];

    $this->expectRpc($expected_request, $expected_response, $options);

    $response = new SignForAppResponse();
    $vmApiProxy->makeSyncCall(self::PACKAGE_NAME,
                           self::CALL_NAME,
                           $expected_request,
                           $response);

    $this->assertEquals($response->getKeyName(), "TheKeyName");
    putenv(VmApiProxy::DEV_TICKET_HEADER);

  }
}

