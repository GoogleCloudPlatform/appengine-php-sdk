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

namespace Google\AppEngine\Api\Urlfetch;

use Google\AppEngine\Testing\ApiProxyTestBase;
use google\appengine\URLFetchRequest;
use google\appengine\URLFetchRequest\RequestMethod;
use google\appengine\URLFetchResponse;

class UrlFetchStreamTest extends ApiProxyTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->_SERVER = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->_SERVER;
        parent::tearDown();
    }

    public function testStreamWithHeaderArray()
    {
        $urlfetch_stream = new UrlFetchStream();
        $url = "http://www.google.com";

        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::POST);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $header = $req->addHeader();
        $header->setKey('key');
        $header->setValue('value');
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        $header_arr = ['key' => 'value'];
        $opts = ['http' =>
            [
                'method' => 'POST',
                'header'  => $header_arr,
            ]
        ];
        $opts = stream_context_create($opts);
        $urlfetch_stream->context = $opts;
        $opened_path = '';
        $result = $urlfetch_stream->stream_open($url, 'a+', 0, $opened_path);
        $this->assertEquals(true, $result);
    }
  
    public function testStreamWithHeaderString()
    {
        $urlfetch_stream = new UrlFetchStream();
        $url = "http://www.google.com";

        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::POST);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $header = $req->addHeader();
        $header->setKey('key');
        $header->setValue('value');
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        $header_str = 'key: value';
        $opts = ['http' =>
            [
                'method' => 'POST',
                'header'  => $header_str,
            ]
        ];
        $opts = stream_context_create($opts);
        $urlfetch_stream->context = $opts;
        $opened_path = '';
        $result = $urlfetch_stream->stream_open($url, 'a+', 0, $opened_path);
        $this->assertEquals(true, $result);
    }

    public function testStreamWithMultiHeaderString()
    {
        $urlfetch_stream = new UrlFetchStream();
        $url = "http://www.google.com";

        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::POST);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $header = $req->addHeader();
        $header->setKey('Content-Type');
        $header->setValue('application/octet-stream');
        $header = $req->addHeader();
        $header->setKey('X-Google-RPC-Service-Deadline');
        $header->setValue('60');
         $header = $req->addHeader();
        $header->setKey('X-Google-RPC-Service-Endpoint');
        $header->setValue('app-engine-apis');
        $header = $req->addHeader();
        $header->setKey('X-Google-RPC-Service-Method');
        $header->setValue('/VMRemoteAPI.CallRemoteAPI');
        $header = $req->addHeader();
        $header->setKey('User-Agent');
        $header->setValue('some_user_agent_string');
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        $header_str = "Content-Type: application/octet-stream\r\n" .
            "X-Google-RPC-Service-Deadline: 60\n" . "X-Google-RPC-Service-Endpoint: app-engine-apis\r" .
            "X-Google-RPC-Service-Method: /VMRemoteAPI.CallRemoteAPI\n";

        $opts = ['http' =>
            [
                'method' => 'POST',
                'header'  => $header_str,
                'user_agent' => 'some_user_agent_string',
            ]
        ];
        $opts = stream_context_create($opts);
        $urlfetch_stream->context = $opts;
        $opened_path = '';
        $result = $urlfetch_stream->stream_open($url, 'a+', 0, $opened_path);
        $this->assertEquals(true, $result);
    }

    public function testGetFetchWithPayload()
    {
        $urlfetch_stream = new UrlFetchStream();
        $url = "http://www.google.com";
    
        $payload = http_build_query(
            [
                'var1' => 'some_content',
                'var2' => 'some_content2'
            ]
        );
        $opts = ['http' =>
            [
                'method' => 'POST',
                'content' => $payload
            ]
        ];
        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::POST);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $req->setPayload($payload);
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        $opts = stream_context_create($opts);
        $urlfetch_stream->context = $opts;
        $opened_path = '';
        $result = $urlfetch_stream->stream_open($url, 'a+', 0, $opened_path);
        $this->assertEquals(true, $result);
    }

    public function testGetFetchWithDeadline()
    {
        $urlfetch_stream = new UrlFetchStream();
        $url = "http://www.google.com";
        $deadline = 5.0;
        $opts = ['http' =>
            [
                'method' => 'POST',
                'timeout' => $deadline
            ]
        ];
        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::POST);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $req->setDeadline($deadline);
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        $opts = stream_context_create($opts);
        $urlfetch_stream->context = $opts;
        $opened_path = '';
        $result = $urlfetch_stream->stream_open($url, 'a+', 0, $opened_path);
        $this->assertEquals(true, $result);
    }

    public function testGetFetchWithFileGetContents()
    {
        $url = "http://www.google.com";
        $deadline = 5.0;
        $opts = ['http' =>
            [
                'method' => 'GET',
                'timeout' => $deadline
            ]
        ];

        // Mock behavior of result.
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();
        $req->setUrl($url);
        $req->setMethod(RequestMethod::GET);
        $req->setFollowredirects(true);
        $req->setMustvalidateservercertificate(false);
        $req->setDeadline($deadline);
        $this->apiProxyMock->expectCall('urlfetch', 'Fetch', $req, $resp);
        // Result.
        stream_wrapper_unregister("http");
        stream_wrapper_register("http", "Google\AppEngine\Api\Urlfetch\UrlFetchStream")
        or die("Failed to register http protocol for UrlFetchStream");
        $opts = stream_context_create($opts);
        $result = file_get_contents($url, false, $opts);
    }
}
