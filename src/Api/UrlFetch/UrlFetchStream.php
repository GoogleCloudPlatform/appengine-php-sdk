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

use Google\Appengine\Runtime\ApplicationError;
use google\appengine\URLFetchServiceError\ErrorCode;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\CachingStream;
use Exception;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class UrlFetchStream implements IteratorAggregate, ArrayAccess 
{
    private const DOMAIN_SEPARATOR = ": ";
    private const NEWLINE_SEPARATOR = "/\r\n|\n|\r/";
    private const HTTP_METHODS = array('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH');

    public $context;
    private $stream = null;
    private $urlFetchResponse = null;
  
    // Context options parameters
    private $headers = [];
    private $content = '';
    private $timeout = 0.0;
    private $method = 'GET';
    private $userAgent = '';
    private $verifyPeer = true;
    private $responseHeaders = null;
    
    /**
    * IteratorAggregate and ArrayAccess implements access to http response header data.
    * This data can be fetched by using stream_get_meta_data().
    */

    /* IteratorAggregate */
    public function getIterator(): Traversable 
    {
        return new ArrayIterator($this->responseHeaders);
    }
    /* ArrayAccess */
    public function offsetExists($offset): bool 
    { 
        return array_key_exists($offset, $this->responseHeaders); 
    }
    public function offsetGet($offset): mixed 
    { 
        return $this->responseHeaders[$offset]; 
    }
    public function offsetSet($offset, $value): void 
    { 
        $this->responseHeaders[$offset] = $value; 
    }
    public function offsetUnset($offset): void 
    { 
        unset($this->responseHeaders[$offset]); 
    }

    /**
     * HTTP and SSL Context Options.
     * See link for a lists of HTTP and SSL options, and their types:
     *    https://www.php.net/manual/en/context.http.php
     *    https://www.php.net/manual/en/context.ssl.php
     *
     * @param string $contextKey Specifies the context type.
     * @param string|int|bool|array $contextValue Specifies the context value to be set.
     *
     * @throws \Exception if illegal or unsupported context option given.
     *
     * @return void.
     */
    private function setContextOptions(string $contextKey, $contextValue): void
    {
        switch ($contextKey) {
            // HTTP Context Options.
            case 'method':
                $this->setMethod($contextValue);
                break;
            case 'header':
                if (is_string($contextValue)) {
                    $contextValue = $this->splitHeaderString($contextValue);
                }
                $this->setHeaders($contextValue);
                break;
            case 'content':
                $this->setContent($contextValue);
                break;
            case 'timeout':
                $this->setTimeout($contextValue);
                break;
            case 'user_agent':
                $this->setUserAgent($contextValue);
                break;
            case 'proxy':
            case 'request_fulluri':
            case 'max_redirects':
            case 'protocol_version':
            case 'ignore_errors':
                throw new Exception('URLFetch does not support HTTP stream context option ' . $contextKey);
                break;
            // SSL Context Options.
            case 'verify_peer':
                $this->setVerifyPeer($contextValue);
                break;
            case 'peer_name':
            case 'verify_peer_name':
            case 'local_pk':
            case 'disable_compression':
            case 'peer_fingerprint':
            case 'security_level':
            case 'allow_self_signed':
            case 'cafile':
            case 'capath':
            case 'local_cert':
            case 'passphrase':
            case 'verify_depth':
            case 'ciphers':
            case 'capture_peer_cert':
            case 'capture_peer_cert_chain':
            case 'SNI_enabled':
                throw new Exception('URLFetch does not support SSL stream context option ' . $contextKey);
            default:
                throw new Exception('Invalid $contextKey value ' . $contextKey);
        }
    }

    /**
     * Save method.
     *
     * @param string $method
     *    Input must be one of 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'.
     *
     * @return void.
     *
     */
    private function setMethod(string $method): void
    {
        if (!is_string($method) || !in_array($method, self::HTTP_METHODS)) {
            throw new Exception(sprintf( 
                'Method value: %s is illegal, please use one of: %s', 
                $method, print_r(self::HTTP_METHODS, true) 
            ));
        }
        $this->method = $method;
    }

    /**
     * Save headers.
     *
     * @param (string or array) $headers Contains header to be parsed.
     *
     * @return void.
     *
     */
    private function setHeaders(array $headers): void
    {
        $this->headers = $this->headers + $headers;
    }


    /**
     * Split header string to an array.
     *
     * @param string $headers Contains header(s) to be parsed.
     *
     * @throws Exception if $headers is of an illegal type.
     *
     * @return array.
     *
     */
    private function splitHeaderString(string $headers): array
    {
        $headersArray = [];
        $headersSplit = preg_split(self::NEWLINE_SEPARATOR, $headers);
        foreach ($headersSplit as $header) {
            $hArray = explode(self::DOMAIN_SEPARATOR, $header);
            if (!isset($hArray[1])) {
                $hArray[1] = null;
            }
            $hPair = [$hArray[0] => $hArray[1]];
            // Empty value check for cases when there are excessive \r\n values.
            if (empty($hArray[0])) {
                continue;
            }
            $headersArray = array_merge($headersArray, $hPair);
        }
        return $headersArray;
    }


    /**
     * Save content.
     *
     * @param string $content URL-encoded query string,
     *     typically generated from http_build_query().
     *
     * @return void.
     *
     */
    private function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Save timeout.
     *
     * @param float $timeout: Timeout for URL request in seconds.
     *
     * @return void.
     *
     */
    private function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Save User-Agent as header.
     *
     * @param string $userAgent 'User-Agent' header string.
     *
     * @return void.
     *
     */
    private function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Set requirement verification of SSL certificate.
     *
     * @param bool $verifyPeer
     *
     * @return void.
     */
    private function setVerifyPeer(bool $verifyPeer): void 
    {
        $this->verifyPeer = $verifyPeer;
    }

    /**
     * Opens URL Stream.
     *
     * @param string $url Specifies the URL that was passed to the original function.
     * @param string $mode UNUSED in the context of URLs.
     * @param int $optionsStream UNUSED in the context of URLs.
     * @param null $openedPath UNUSED in the context of URLs.
     *
     * @throws Exception if URLFetch request is nto successful.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function stream_open(string $url, string $mode, int $optionsStream, &$openedPath): bool
    {
        if ($this->context == null) {
            throw new Exception('ERROR: Context not set for stream wrapper.');
        }
        $options = stream_context_get_options($this->context);

        foreach ($options as $webProtocol => $contextArray) {
            foreach ($contextArray as $contextKey => $contextValue) {
                $this->setContextOptions($contextKey, $contextValue);
            }
        }

        // Check and store User-agent if specified separately from header.
        if (!in_array('User-Agent', $this->headers) && $this->userAgent != '') {
            $headerArr = ['User-Agent' => $this->userAgent];
            $this->headers = $this->headers + $headerArr;
        }

        try {
            $urlfetch = new UrlFetch();
            $resp =
                $urlfetch->fetch(
                    $url,
                    $this->method,
                    $this->headers,
                    $this->content,
                    true,
                    true,
                    $this->timeout,
                    $this->verifyPeer);
            $this->urlFetchResponse = $resp;
            $this->stream = new CachingStream(Stream::factory($resp->getContent()));
            $this->responseHeaders = $this->buildHeaderArray($resp->getStatuscode(), $resp->getHeaderList());
        } catch (Exception $e) {
            throw new Exception(sprintf("Caught UrlFetch Exception:  %s", $e->getMessage()));
        }

        if ($resp->getStatuscode() >= 400) {
            return false;
        }
        return true;
    }

    /**
    * Closes URL Stream.
    *
    * @return void.
    */
    public function stream_close(): void
    {
        $this->stream = null;
        $this->urlFetchResponse = null;
        $this->headers = [];
        $this->content = '';
        $this->timeout = 0.0;
        $this->method = 'GET';
        $this->userAgent = '';
        $this->verifyPeer = true;
    }

    /**
     * Return if end of file.
     *
     * @return bool Return true if the read/write position is at the end of the stream and if
     *     no more data is available to be read, or false otherwise.
     *
     */
    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    /**
     * Returns URL Stats, Unused. 
     * Must be implemented for stream wrapper.
     *
     * @return void.
     */
    public function stream_stat(): void
    {
    }

    /**
     * Read from stream.
     *
     * @param int $count How many bytes of data from the current position should be returned.
     *
     * @return string Return number of bytes. 
     *     If there are less than count bytes available, return as many as are available. 
     *     If no more data is available, return either false or an empty string.
     */
    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }

    /**
      * Seeks to specific location in a stream.
      *
      * @param int $offset The stream offset to seek to.
      *
      * @param int $whence
      *     SEEK_SET: - Set position equal to offset bytes.
      *     SEEK_CUR: - Set position to current location plus offset.
      *     SEEK_END: - Set position to end-of-file plus offset.
      *
      * @return bool Return true if the position was updated, false otherwise.
      *
      */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        if ($this->stream->isSeekable()) {
            $this->stream->seek($offset, $whence);
            return true;
        }
        return false;
    }

    /**
      * Retrieve the current position of a stream.
      *
      * @return the current position of the stream as int.
      *
      */
    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    /**
      * Build the UrlFetch response header array.
      *
      * @return Header array.
      *
      */
    private function buildHeaderArray(int $statusCode, array $headerList): array
    {
        $sRow = 'error';
        if ($statusCode === 200) {
            $sRow = sprintf('HTTP/1.1 %s OK', $statusCode);
        } 
        $headerArr = [$sRow];
        foreach($headerList as $header) {
            $row = sprintf('%s: %s', $header->getKey(), $header->getValue());
            array_push($headerArr, $row);
        }
        return $headerArr;
    }
}
