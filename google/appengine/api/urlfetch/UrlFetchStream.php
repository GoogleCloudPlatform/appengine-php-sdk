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

use google\appengine\runtime\ApplicationError;
use google\appengine\URLFetchServiceError\ErrorCode;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\CachingStream;

class UrlFetchStream
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
    private $method = null;
    private $user_agent = '';

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
     * HTTP Context Options
     * See link for a list of options and their types:
     *    https://www.php.net/manual/en/context.http.php
     *s
     * @param string $context_key: Specifies the context type.
     * @param string $context_value: Specifies the context value.
     *
     * @throws \Exception If Illegal context option given.
     *
     * @return void.
     */
    private function setContextOptions($context_key, $context_value)
    {
        switch ($context_key) {
          case 'method':
            $this->setMethod($context_value);
            break;
          case 'header':
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
            throw new Exception('URLFetch does not support stream context option ' . $context_key);
            break;
          default:
            throw new Exception('Invalid $context_key value' . $context_key);
        }
    }

    /**
     * Save Method.
     *
     * @param string $method:
     *    Input must be one of 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'.
     *
     * @return void.
     *
     */
    private function setMethod($method)
    {
        if (!is_string($method) || !in_array($method, self::HTTP_METHODS)) {
            throw new Exception('Method value is illegal');
        }
        $this->method = $method;
    }

    /**
     * Parse and save all the headers.
     *
     * @param (string or array) $headers: Contains header to be parsed.
     *
     * @return void.
     *
     */
    private function setHeaders($headers)
    {
        if (is_string($headers)) {
            $headers_array = preg_split(self::NEWLINE_SEPARATOR, $headers);
            foreach ($headers_array as $header) {
                $h_array = explode(self::DOMAIN_SEPARATOR, $header);
        
                // Empty value check for cases when there are excessive \r\n values.
                if (empty($h_array[0])) {
                    continue;
                }
                array_push($this->headers, $h_array);
            }
        } elseif (is_array($headers)) {
            $this->headers = $this->headers + $headers;
        } else {
            throw new Exception('Header value must be string or array');
        }
    }

    /**
     * Save all the content.
     *
     * @param string $content: URL-encoded query string,
     *    typically generated from http_build_query().
     *
     * @return void.
     *
     */
    private function setContent($content)
    {
        if (!is_string($content)) {
            throw new Exception('Content value must of type string');
        }
        $this->content = $content;
    }

    /**
     * Save the timeout.
     *
     * @param float $timeout: Timeout for URL request in seconds.
     *
     * @return void.
     *
     */
    private function setTimeout($timeout)
    {
        if (!is_numeric($timeout)) {
            throw new Exception('Content value must of type string');
        }
        $this->timeout = $timeout;
    }

    /**
     * Save the user-agent as header.
     *
     * @param string $user_agent: User-Agent header string.
     *
     * @return void.
     *
     */
    private function setUserAgent($user_agent)
    {
        if (!is_string($user_agent)) {
            throw new Exception('User Agent value must of type string');
        }
        $this->user_agent = $user_agent;
    }

    /**
     * Opens  URL Stream.
     *
     * @param string $url: Specifies the URL that was passed to the original function.
     * @param string $mode: UNUSED in the context of URLs.
     * @param int $options: UNUSED in the context of URLs.
     * @param string $opened_path: UNUSED in the context of URLs.
     *
     * @throws \Exception If URLFetch call has exception.
     *
     * @return bool Returns true on success or false on failure.
     *
     */
    public function stream_open($url, $mode, $options_stream, &$opened_path)
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
            $this->timeout);

            $this->url_fetch_response = $resp;
            $this->stream = new CachingStream(Stream::factory($resp->getContent()));
        } catch (ApplicationError $e) {
            throw errorCodeToException($e->getApplicationError());
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
    public function stream_close()
    {
        $this->stream = null;
        $this->url_fetch_response = null;
        $this->headers = [];
        $this->content = '';
        $this->timeout = 0.0;
        $this->method = null;
        $this->user_agent = '';
    }

    /**
     * Return if end of file.
     *
     * @return bool Return true if the read/write position is at the end of the stream and if
     *     no more data is available to be read, or false otherwise.
     *
     */
    public function stream_eof()
    {
        return $this->stream->eof();
    }

    /**
      * Flushes the output, Unused.
      *
      * @return bool Return true if the cached data was successfully stored (or if there was no data to store),
      *     or false if the data could not be stored.
      *
      */
    public function stream_flush()
    {
    }
  
    /**
      * Returns URL Stats.
      *
      * @return void
      *
      */
    public function stream_stat()
    {
        return $this->url_fetch_response;
    }

    /**
     * Read from Stream.
     *
     * @param int $count How many bytes of data from the current position should be returned.
     *
     * @return If there are less than count bytes available, return as many as are available. If no more data is available, return either false or an empty string.
     *
     */
    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    /**
      * Seeks to specific location in a stream.
      *
      * @param int $offset The stream offset to seek to.
      *
      * @param int $whence
      *    Possible Valiues:
      *     SEEK_SET: 0 - Set position equal to offset bytes.
      *     SEEK_CUR: 1 - Set position to current location plus offset.
      *     SEEK_END: 2 - Set position to end-of-file plus offset.
      *
      * @return bool Return true if the position was updated, false otherwise.
      *
      */
    public function stream_seek($offset, $whence)
    {
        switch ($whence) {
          case 0: // 'SEEK_SET'
            $stream->seek($offset);
            break;
          case 1: // 'SEEK_CUR'
            $cur_position = $stream->tell();
            $stream->seek($cur_position + $offset);
            break;
          case 2: // 'SEEK_END'
            $cur_position = $stream->tell();
            $eof_offset = 0;
            while (!$stream->eof()) {
                ++$eof_offset;
                $stream->seek($cur_position + $eof_offset);
            }
            $stream->seek($cur_position + $eof_offset + $offset);
            break;
          default:
            return false;
        }
        return true;
    }

    /**
      * Retrieve the current position of a stream.
      *
      * @return int Return the current position of the stream.
      *
      */
    public function stream_tell()
    {
        return $stream->tell();
    }
}
