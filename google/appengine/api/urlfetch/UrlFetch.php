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

namespace google\appengine\api\urlfetch;

use google\appengine\runtime\ApiProxy;
use google\appengine\runtime\ApplicationError;
use google\appengine\URLFetchRequest;
use google\appengine\URLFetchRequest\RequestMethod;
use google\appengine\URLFetchResponse;
use google\appengine\URLFetchServiceError\ErrorCode;

final class UrlFetch
{

  /**
   * Maps Error Code to Exception Type. Contains proto error types.
   */
    private static function errorCodeToException($error)
    {
        switch ($error) {
      case ErrorCode::OK:
        return new Exception('Module Return OK.');
      case ErrorCode::INVALID_URL:
        return new Exception('Invalid URL.');
      case ErrorCode::FETCH_ERROR:
        return new Exception('FETCH ERROR.');
      case ErrorCode::UNSPECIFIED_ERROR:
        return new Exception('Unexpected Error.');
      case ErrorCode::RESPONSE_TOO_LARGE:
        return new Exception('Response Too Large.');
      case ErrorCode::DEADLINE_EXCEEDED:
        return new Exception('Deadline Exceeded.');
      case ErrorCode::SSL_CERTIFICATE_ERROR:
        return new Exception('SSL Certificate Error.');
      case ErrorCode::DNS_ERROR:
        return new Exception('DNS Error.');
      case ErrorCode::CLOSED:
        return new Exception('Closed Error.');
      case ErrorCode::INTERNAL_TRANSIENT_ERROR:
        return new Exception('Internal Transient Error.');
      case ErrorCode::TOO_MANY_REDIRECTS:
        return new Exception('Too Many Redirects.');
      case ErrorCode::MALFORMED_REPLY:
        return new Exception('Malformed Reply.');
      case ErrorCode::CONNECTION_ERROR:
        return new Exception('Connection Error.');
      case ErrorCode::PAYLOAD_TOO_LARGE:
        return new Exception('Payload Too Large.');
      default:
        return new ModulesException('Error Code: ' . $error);
    }
    }

    /**
     * Maps Request method string to URLFetch Request type.
     *
     * @param string $request_method: Specifies the HTTP request type.
     *
     * @throws \Exception for invalid $request_method input strings.
     *
     * @return URLFetchRequest\RequestMethod type, equivalent
     */
    private function getRequestMethod($request_method)
    {
        switch ($request_method) {
      case 'GET':
        return RequestMethod::GET;
      case 'POST':
        return RequestMethod::POST;
      case 'HEAD':
        return RequestMethod::HEAD;
      case 'PUT':
        return RequestMethod::PUT;
      case 'DELETE':
        return RequestMethod::DELETE;
      case 'PATCH':
        return RequestMethod::PATCH;
      default:
        throw new Exception('Invalid Request Method Input: ' . $request_method);
    }
    }

    /**
     * Fetches a URL.
     *
     * @param string $url: Specifies the URL.
     * @param string $request_method: The HTTP method.
     *  URLs are fetched using one of the following HTTP methods:
     *   - GET
     *   - POST
     *   - HEAD
     *   - PUT
     *   - DELETE
     *   - PATCH
     * @param array_map $header: Optional {key, value} pair input as header.
     * @param string $payload: Optional, add a Payload to a URL Request for POST, PUT and PATCH requests.
     * @param bool $allow_truncated: Optional, specify if contetn is truncated.
     * @param bool $follow_redirects: Optional, If set to `True` (the default), redirects are
     *     transparently followed, and the response (if less than 5 redirects)
     *     contains the final destination's payload; the response status is 200.
     *     You lose, however, the redirect chain information. If set to `False`,
     *     you see the HTTP response yourself, including the 'Location' header, and
     *     redirects are not followed.
     * @param int $deadline: Optional, the timeout for the request in seconds. The default is a system-specific
     *    deadline (typically 5 seconds).
     * @param bool $validate_certificate: Optional,  If set to `True`, requests are not
     *     sent to the server unless the certificate is valid, signed by a trusted CA,
     *     and the host name matches the certificate. A value of `None` indicates that
     *     the behavior will be chosen by the underlying `urlfetch` implementation.
     *
     * @throws \InvalidArgumentException If UrlFetchRequest has a failure.
     *
     * @return URLFetchResponse Returns URLFetchResponse object upon success, else throws application error.
     *
     */
    public function fetch(
    string $url,
    string $request_method = 'GET',
    array $headers = null,
    string $payload = null,
    bool $allow_truncated = null,
    bool $follow_redirects = null,
    float $deadline = null,
    bool $validate_certificate = null
  ) {
        if (strncmp($url,'http://', 7) != 0 && strncmp($url,'https://', 8)!= 0) {
            throw new Exception('stream_open: URL input must use http:// or https://');
        }
    
        $URLFetchRequest = new URLFetchRequest();
        $URLFetchResponse = new URLFetchResponse();

        // Request Method and URL.
        $URLFetchRequest->setUrl($url);
        $req_method = $this->getRequestMethod($request_method);
        $URLFetchRequest->setMethod($req_method);
    
        // Headers.
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $header = new URLFetchRequest\Header();
                $header->setKey($key);
                $header->setValue($value);
                $URLFetchRequest->addHeader($header);
            }
        }
    
        // Payload.
        if (!empty($payload) && ($request_method == 'POST' || $request_method == 'PUT' || $request_method == 'PATCH')) {
            $URLFetchRequest->setPayload($payload);
        }

        //Deadline.
        if (!empty($deadline)) {
            $URLFetchRequest->setDeadline($deadline);
        }
    
        $URLFetchRequest->setFollowredirects($follow_redirects);
        $URLFetchRequest->setMustvalidateservercertificate($validate_certificate);

        try {
            ApiProxy::makeSyncCall(
          'urlfetch', 'Fetch', $URLFetchRequest, $URLFetchResponse);
        } catch (ApplicationError $e) {
            http_response_code(500);
            $this->errorHandler($e);
            throw errorCodeToException($e->getApplicationError());
        }

        //Allow Truncated.
        if ($URLFetchResponse->getContentwastruncated() == true && !$allow_truncated) {
            throw new Exception('Error: Output was truncated and allow_truncated option is not enabled!');
        }

        return $URLFetchResponse;
    }
  
    private function errorHandler($e)
    {
        $trace = $e->getTrace();
        echo "\nMessage LOG: " . $e->getMessage() . "\n";
        echo 'ERROR occuring in: ' . $e->getFile() . ' on line ' . $e->getLine();
        echo ' called from ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'] . "\n";
        echo "\nStack Trace Pretty Print:\n" . $e->getTraceAsString() . "\n";
        echo "\nFull Stack Trace Array:\n";
        print_r($trace);
    }
}
