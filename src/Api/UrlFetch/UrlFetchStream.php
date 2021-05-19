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
    private $url_fetch_response= null;
  
    // Context options parameters
    private $headers = [];
    private $content = '';
    private $timeout = 0.0;
    private $method = 'GET';
    private $user_agent = '';
    private $verify_peer = true;
    protected $response_headers = null;
    
    /**
    * IteratorAggregate and ArrayAccess implements access to http response header data.
    * This data can be fetched by using stream_get_meta_data().
    */

    /* IteratorAggregate */
    public function getIterator(): Traversable 
    {
        return new ArrayIterator($this->response_headers);
    }
    /* ArrayAccess */
    public function offsetExists($offset): bool 
    { 
        return array_key_exists($offset, $this->response_headers); 
    }
    public function offsetGet($offset): mixed 
    { 
        return $this->response_headers[$offset]; 
    }
    public function offsetSet($offset, $value): void 
    { 
        $this->response_headers[$offset] = $value; 
    }
    public function offsetUnset($offset): void 
    { 
        unset($this->response_headers[$offset]); 
    }

    /**
     * HTTP and SSL Context Options.
     * See link for a lists of HTTP and SSL options, and their types:
     *    https://www.php.net/manual/en/context.http.php
     *    https://www.php.net/manual/en/context.ssl.php
     *
     * @param string $context_key: Specifies the context type.
     * @param string|int|bool|array $context_value: Specifies the context value to be set.
     *
     * @throws \Exception if illegal or unsupported context option given.
     *
     * @return void.
     */
    private function setContextOptions(string $context_key, $context_value): void
    {
        switch ($context_key) {
            // HTTP Context Options.
            case 'method':
                $this->setMethod($context_value);
                break;
            case 'header':
                if (is_string($context_value)) {
                    $context_value = $this->splitHeaderString($context_value);
                }
                $this->setHeaders($context_value);
                break;
            case 'content':
                $this->setContent($context_value);
                break;
            case 'timeout':
                $this->setTimeout($context_value);
                break;
            case 'user_agent':
                $this->setUserAgent($context_value);
                break;
            case 'proxy':
            case 'request_fulluri':
            case 'max_redirects':
            case 'protocol_version':
            case 'ignore_errors':
                throw new Exception('URLFetch does not support HTTP stream context option ' . $context_key);
                break;
            // SSL Context Options.
            case 'verify_peer':
                $this->setVerifyPeer($context_value);
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
                throw new Exception('URLFetch does not support SSL stream context option ' . $context_key);
                break;
            default:
                throw new Exception('Invalid $context_key value ' . $context_key);
        }
    }

    /**
     * Save method.
     *
     * @param string $method:
     *    Input must be one of 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'.
     *
     * @return void.
     *
     */
    private function setMethod(string $method): void
    {
        if (!is_string($method) || !in_array($method, self::HTTP_METHODS)) {
            throw new Exception('Method value: ' . $method . ' is illegal, ' .
                'please use one of:' . print_r(self::HTTP_METHODS, true));
        }
        $this->method = $method;
    }

    /**
     * Save headers.
     *
     * @param (string or array) $headers: Contains header to be parsed.
     *
     * @throws \Exception if $headers is of an illegal type.
     *
     * @return void.
     *
     */
    private function setHeaders(array $headers): void
    {
        if (!is_array($headers)) {
            throw new Exception('Header value must be array');
        }
        $this->headers = $this->headers + $headers;
    }


    /**
     * Split header string to an array.
     *
     * @param string $headers: Contains header(s) to be parsed.
     *
     * @throws \Exception if $headers is of an illegal type.
     *
     * @return array.
     *
     */
    private function splitHeaderString(string $headers): array
    {
        $headers_array = [];
        $headers_split = preg_split(self::NEWLINE_SEPARATOR, $headers);
        foreach ($headers_split as $header) {
            $h_array = explode(self::DOMAIN_SEPARATOR, $header);
            if (!isset($h_array[1])) {
                $h_array[1] = null;
            }
            $h_pair = [$h_array[0] => $h_array[1]];
            // Empty value check for cases when there are excessive \r\n values.
            if (empty($h_array[0])) {
                continue;
            }
            $headers_array = array_merge($headers_array, $h_pair);
        }
        return $headers_array;
    }


    /**
     * Save content.
     *
     * @param string $content: URL-encoded query string,
     *     typically generated from http_build_query().
     *
     * @throws \Exception if $content is not of string type.
     *
     * @return void.
     *
     */
    private function setContent(string $content): void
    {
        if (!is_string($content)) {
            throw new Exception('Content value must of type string');
        }
        $this->content = $content;
    }

    /**
     * Save timeout.
     *
     * @param float $timeout: Timeout for URL request in seconds.
     *
     * @throws \Exception if $timeout is not of float type.
     *
     * @return void.
     *
     */
    private function setTimeout(float $timeout): void
    {
        if (!is_float($timeout)) {
            throw new Exception('Content value must of type float');
        }
        $this->timeout = $timeout;
    }

    /**
     * Save User-Agent as header.
     *
     * @param string $user_agent: 'User-Agent' header string.
     *
     * @throws \Exception if $user_agent is not of string type.
     *
     * @return void.
     *
     */
    private function setUserAgent(string $user_agent): void
    {
        if (!is_string($user_agent)) {
            throw new Exception('User Agent value must of type string');
        }
        $this->user_agent = $user_agent;
    }

    /**
     * Set requirement verification of SSL certificate.
     *
     * @param bool $verify_peer
     *
     * @throws \Exception if $verify_peer is not of bool type.
     *
     * @return void.
     *
     */
    private function setVerifyPeer(bool $verify_peer): void 
    {
        if (!is_bool($verify_peer)) {
            throw new Exception('Verify Peer value must of type bool');
        }
        $this->verify_peer = $verify_peer;
    }

    /**
     * Opens URL Stream.
     *
     * @param string $url: Specifies the URL that was passed to the original function.
     * @param string $mode: UNUSED in the context of URLs.
     * @param int $options_stream: UNUSED in the context of URLs.
     * @param null $opened_path: UNUSED in the context of URLs.
     *
     * @throws \Exception if URLFetch request is nto successful.
     *
     * @return bool Returns true on success or false on failure.
     *
     */
    public function stream_open(string $url, string $mode, int $options_stream, &$opened_path): bool
    {
        if ($this->context == null) {
            throw new Exception('ERROR: Context not set for stream wrapper.');
        }
        $options = stream_context_get_options($this->context);

        foreach ($options as $web_protocol => $context_array) {
            foreach ($context_array as $context_key => $context_value) {
                $this->setContextOptions($context_key, $context_value);
            }
        }

        // Check and store User-agent if specified separately from header.
        if (!in_array('User-Agent', $this->headers) && $this->user_agent != '') {
            $header_arr = ['User-Agent' => $this->user_agent];
            $this->headers = $this->headers + $header_arr;
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
                    $this->verify_peer);
            $this->url_fetch_response = $resp;
            $this->stream = new CachingStream(Stream::factory($resp->getContent()));
            $this->response_headers = $this->buildHeaderArray($resp->getStatuscode(), $resp->getHeaderList());
        } catch (Exception $e) {
            echo "Caught Exception: " . $e->getMessage();
            exit;    
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
    *
    */
    public function stream_close(): void
    {
        $this->stream = null;
        $this->url_fetch_response = null;
        $this->headers = [];
        $this->content = '';
        $this->timeout = 0.0;
        $this->method = 'GET';
        $this->user_agent = '';
        $this->verify_peer = true;
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
     *
     */
    public function stream_stat(): void
    {
    }

    /**
     * Read from stream.
     *
     * @param int $count: How many bytes of data from the current position should be returned.
     *
     * @return string Return number of bytes. 
     *     If there are less than count bytes available, return as many as are available. 
     *     If no more data is available, return either false or an empty string.
     *
     */
    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }

    /**
      * Seeks to specific location in a stream.
      *
      * @param int $offset: The stream offset to seek to.
      *
      * @param int $whence:
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
    private function buildHeaderArray(int $status_code, array $header_list): array
    {
        $s_row = 'error';
        if ($status_code === 200) {
            $s_row = sprintf('HTTP/1.1 %s OK', $status_code);
        } 
        $header_arr = [$s_row];
        foreach($header_list as $header) {
            $row = sprintf('%s: %s', $header->getKey(), $header->getValue());
            array_push($header_arr, $row);
        }
        return $header_arr;
    }
}