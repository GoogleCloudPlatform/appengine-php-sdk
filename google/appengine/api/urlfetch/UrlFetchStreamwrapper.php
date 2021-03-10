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
 */


namespace google\appengine\api\urlfetch;

require_once __DIR__ . '/UrlFetch.php';

use google\appengine\api\urlfetch\UrlFetch;
use google\appengine\runtime\ApplicationError;
use google\appengine\testing\ApiProxyTestBase;
use google\appengine\URLFetchServiceError\ErrorCode;
use google\appengine\URLFetchRequest;
use google\appengine\URLFetchRequest\RequestMethod;
use google\appengine\URLFetchResponse;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\CachingStream;

class UrlFetchStreamwrapper {

  const DOMAIN_SEPARATOR = ": ";
  const NEWLINE_SEPARATOR = "\r\n";

  public $context;
  private $stream;
  private $url_fetch = null;
  private $url_fetch_response = null;
  

  //Context options parameters
  private $headers = null;
  private $content = null;
  private $timeout = null;
  private $method = null;


  /**
   * Constructs a new stream wrapper.
   */
  public function __construct() {}

  /**
   * Destructs an existing stream wrapper.
   */
  public function __destruct() {}

  /**
   * Maps Error Code to Exception Type. Contains proto error types.
   */
  private static function errorCodeToException($error) {
    switch($error) {
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
  private function getContextOptions($context_key, $context_value) {
    switch($context_key) {
      case 'method':
        $this->getMethod($context_value);
        break;
      case 'header':
        $this->getHeaders($context_value);
        break;
      case 'user_agent':
        print("Got UserAgent\n");
        break;
      case 'content':
        $this->getContent($context_value);
        break;
      case 'proxy':
        //Not Supported.
        print("Got proxy\n");
        break;
      case 'request_fulluri':
        //Not Supported.
        print("Got full uri\n");
        break;
      case 'max_redirects':
        //Not Supported.
        break;
      case 'protocol_version':
        //Not Supported.
        print("Got protocol version\n");
        break;
      case 'timeout':
        $this->getTimeout($context_value);
        print("Got Timeout\n");
        break;
      case 'ignore_errors':
        //Not Supported.
        break;
      default:
        throw new Exception('Invalid $context_value value' . $context_key); 
    }
  }

  /**
   * Save Method.
   * 
   * @param string $context_value: 
   *    Input must be one of 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'. 
   * 
   * @return void. 
   *
   */
  private function getMethod($context_value) {
    $checkVars = array('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH');
    if (!is_string($context_value) || !in_array($context_value, $checkVars)) {
      throw new Exception('Method value is illegal'); 
    }
    $this->method = $context_value;
  }

  /**
   * Parse and save all the headers.
   *
   * @param (string or array) $context_value: Contains header to be parsed. 
   * 
   * @return void. 
   *
   */
  private function getHeaders($context_value) {
    if($this->headers == null) {
      $this->headers = array();
    }
    if (is_string($context_value)) {
      
      $headers_array = explode(self::NEWLINE_SEPARATOR, $context_value); 
      foreach ($headers_array as $header) {
        $h_array = explode(self::DOMAIN_SEPARATOR, $header);
        
        //Empty value check for cases when there are excessive \r\n values.
        if(empty($h_array[0])) {
          continue;
        }  
        array_push($this->headers, $h_array);
      }
    } elseif (is_array($context_value)) {
      $this->headers = $this->headers + $context_value;
    } else {
      throw new Exception('Header value must be string or array'); 
    }
  }

  /**
   * Save all the content.
   *
   * @param string $context_value: URL-encoded query string, 
   *    typically generated from http_build_query(). 
   *
   * @return void. 
   *
   */
  private function getContent($context_value) {
    if (!is_string($context_value)) {
      throw new Exception('Content value must of type string string'); 
    }
    $this->content = $context_value;
  }

    /**
   * Save the timeout.
   *
   * @param float $context_value: Timeout for URL request in seconds. 
   *
   * @return void. 
   *
   */
  private function getTimeout($context_value) {
    if (!is_numeric($context_value)) {
      throw new Exception('Content value must of type string string'); 
    }
    $this->timeout = $context_value;
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

  public function stream_open($url, $mode, $options_stream, &$opened_path) {
    
    if($this->context == null) {
      throw new Exception('ERROR: Context not set for stream wrapper.'); 
    }
    $options = stream_context_get_options($this->context);

    foreach($options as $section1=>$items1){      
      $web_protocol = $section1;
      print("Web Protocol: ");
      var_dump($web_protocol);
      foreach($items1 as $context_key=>$context_value){
        $this->getContextOptions($context_key, $context_value);
      }
    }

    try {
      $urlfetch = new UrlFetch();
      $resp = 
          $urlfetch->fetch(
            $url, 
            $this->method, 
            $this->headers, 
            $this->content, 
            null, 
            null, 
            $this->timeout);

      $this->url_fetch_response = $resp;

      $this->stream = Stream::factory($resp->getContent());
      $this->stream = new CachingStream($this->stream);
    } catch(ApplicationError $e) {
      $this->errorHandler($e);
      throw errorCodeToException($e->getApplicationError());
    }
    if($resp->getStatuscode() >= 400) {
      return false;
    }
    return true;
  }

  public function stream_close() {}

  /**
   * Rename the URL path.
   *
   * @return bool Should return true if the read/write position is at the end of the stream and if no more data is available to be read, or false otherwise.
   *
   */
  public function stream_eof() {
    return $this->stream->eof();
  }

 /**
   * Flushes the output, Unused.
   *
   * @return bool Should return true if the cached data was successfully stored (or if there was no data to store), or false if the data could not be stored.
   *
   */
  public function stream_flush() {}
  
  
 /**
   * Returns URL Stats.
   *
   * @return void
   *
   */
  public function stream_stat() {
    print("STREAM STAT: ");
    return $this->url_fetch_response;
  }

  /**
   * Read from Stream.
   *
   * @param int $count How many bytes of data from the current position should be returned.
   *
   * @throws \InvalidArgumentException If $count is not an int
   *
   * @return If there are less than count bytes available, return as many as are available. If no more data is available, return either false or an empty string.
   *
   */
  public function stream_read($count) {
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
  public function stream_seek($offset, $whence) {
    print("STREAM SEEK: ");
    switch($whence) {
      case 0: //'SEEK_SET'
        $stream->seek($offset);
        break;
      case 1: // 'SEEK_CUR'
        $cur_position = $stream->tell();
        $stream->seek($cur_position + $offset);
        break;
      case 2: // 'SEEK_END'
        $cur_position = $stream->tell();
        $eof_offset = 0;
        while(!$stream->eof()) {
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
   * @return int Should return the current position of the stream.
   *
   */
  public function stream_tell () {
    return $stream->tell();
  }

  /**
   * Retrieve Stack Trace information.
   *
   */
  private function errorHandler($e) {
    $trace = $e->getTrace();
    echo "\nTEST LOG: ".$e->getMessage()."\n";
    echo 'ERROR occuring in: '.$e->getFile().' on line '.$e->getLine();
    echo ' called from '.$trace[0]['file'].' on line '.$trace[0]['line']."\n";
    echo "\nStack Trace Pretty Print:\n".$e->getTraceAsString()."\n";
    echo "\nFull Stack Trace Array:\n";
    print_r($trace);
  }
}