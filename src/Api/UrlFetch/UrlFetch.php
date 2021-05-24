<?php

declare(strict_types=1);

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

namespace Google\AppEngine\Api\UrlFetch;

use Google\Appengine\Runtime\ApiProxy;
use Google\Appengine\Runtime\ApplicationError;
use google\appengine\URLFetchRequest;
use google\appengine\URLFetchRequest\RequestMethod;
use google\appengine\URLFetchResponse;
use google\appengine\URLFetchServiceError\ErrorCode;
use Exception;

final class UrlFetch
{
    /**
     * Maps UrlFetch error codes.
     *
     * @param ApplicationError UrlFetch application error.
     *
     * @return Exception with error information.
     */
    private static function errorCodeToException(ApplicationError $error): Exception
    {
        $errorCodeMap = [
            ErrorCode::OK => 'Module Return OK',
            ErrorCode::INVALID_URL => 'Invalid URL',
            ErrorCode::FETCH_ERROR => 'Fetch Error',
            ErrorCode::UNSPECIFIED_ERROR => 'Unexpected Error',
            ErrorCode::RESPONSE_TOO_LARGE => 'Response Too Large',
            ErrorCode::DEADLINE_EXCEEDED => 'Deadline Exceeded',
            ErrorCode::SSL_CERTIFICATE_ERROR => 'Ssl Certificate Error',
            ErrorCode::DNS_ERROR => 'Dns Error',
            ErrorCode::CLOSED => 'Closed Error',
            ErrorCode::INTERNAL_TRANSIENT_ERROR => 'Internal Transient Error',
            ErrorCode::TOO_MANY_REDIRECTS => 'Too Many Redirects',
            ErrorCode::MALFORMED_REPLY => 'Malformed Reply',
            ErrorCode::CONNECTION_ERROR => 'Connection Error',
            ErrorCode::PAYLOAD_TOO_LARGE => 'Payload Too Large',
        ];

        $errorCode = $errorCodeMap[$error->getApplicationError()] ?? (string) $error;

        return new Exception(sprintf(
            'UrlFetch Exception with Error Code: %s with message: %s',
            $errorCode,
            $error->getMessage()
        ) . PHP_EOL);
    }

    /**
     * Maps Request method string to URLFetch Request type.
     *
     * @param string $requestMethod Specifies the HTTP request type.
     *
     * @throws Exception for invalid $requestMethod input strings.
     *
     * @return RequestMethod
     */
    private function getRequestMethod(string $requestMethod): int
    {
        switch ($requestMethod) {
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
                throw new Exception('Invalid Request Method Input: ' . $requestMethod);
        }
    }

    /**
     * Fetches a URL.
     *
     * @param string $url Specifies the URL.
     * @param string $requestMethod Optional, The HTTP method.
     *     URLs are fetched using one of the following HTTP methods:
     *     - GET
     *     - POST
     *     - HEAD
     *     - PUT
     *     - DELETE
     *     - PATCH
     * @param array $headers Optional, array containing values in the format of {key => value} pairs.
     * @param string $payload Optional, payload for a URL Request when using POST, PUT, and PATCH requests.
     * @param bool $allowTruncated Optional, specify if content is truncated.
     * @param bool $followRedirects Optional, specify if redirects are followed.
     * @param int $deadline Optional, the timeout for the request in seconds.
     * @param bool $validateCertificate Optional, If set to `true`, requests are not
     *     sent to the server unless the certificate is valid and signed by a trusted CA.
     *
     * @throws Exception If UrlFetchRequest has an application failure, if illegal web protocol used,
     *     or if content is illegally truncated.
     *
     * @return URLFetchResponse Returns URLFetchResponse object upon success, else throws application error.
     *
     */
    public function fetch(
        string $url,
        string $requestMethod = 'GET',
        array $headers = [],
        string $payload = '',
        bool $allowTruncated = true,
        bool $followRedirects = true,
        float $deadline = 0.0,
        bool $validateCertificate = false
    ): URLFetchResponse {
        if (strncmp($url, 'http://', 7) !== 0 && strncmp($url, 'https://', 8) !== 0) {
            throw new Exception('URL input must use http:// or https://');
        }

        // Only allow validate certificate for https requests.
        if (strncmp($url, 'http://', 7) === 0) {
            $validateCertificate = false;
        }
    
        $req = new URLFetchRequest();
        $resp = new URLFetchResponse();

        // Request Method and URL.
        $req->setUrl($url);
        $reqMethod = $this->getRequestMethod($requestMethod);
        $req->setMethod($reqMethod);
    
        // Headers.
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $header = $req->addHeader();
                $header->setKey($key);
                $header->setValue($value);
            }
        }

        // Payload.
        if (!empty($payload) && ($reqMethod == RequestMethod::POST || $reqMethod == RequestMethod::PUT 
                || $reqMethod == RequestMethod::PATCH)) {
            $req->setPayload($payload);
        }

        // Deadline.
        if ($deadline > 0.0) {
            $req->setDeadline($deadline);
        }
    
        $req->setFollowredirects($followRedirects);
        $req->setMustvalidateservercertificate($validateCertificate);

        try {
            ApiProxy::makeSyncCall(
                'urlfetch', 'Fetch', $req, $resp);
        } catch (ApplicationError $e) {
            throw self::errorCodeToException($e);
        }

        // Allow Truncated.
        if ($resp->getContentwastruncated() == true && !$allowTruncated) {
            throw new Exception('Output was truncated and allowTruncated option is not enabled.');
        }

        return $resp;
    }
}
