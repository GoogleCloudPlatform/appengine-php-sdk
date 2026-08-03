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
 * The PushQueue class, which is part of the Task Queue API.
 *
 */

namespace Google\AppEngine\Api\TaskQueue;

use Google\AppEngine\Runtime\ApiProxy;
use Google\AppEngine\Runtime\ApplicationError;
use google\appengine\TaskQueueAddRequest;
use google\appengine\TaskQueueAddRequest\RequestMethod;
use google\appengine\TaskQueueAddResponse;
use google\appengine\TaskQueueBulkAddRequest;
use google\appengine\TaskQueueBulkAddResponse;
use google\appengine\TaskQueueServiceError\ErrorCode;

/**
 * A PushQueue executes PushTasks by sending the task back to the application
 * in the form of an HTTP request to one of the application's handlers.
 */
final class PushQueue {
  /**
   * The maximum number of tasks in a single call addTasks.
   */
  const MAX_TASKS_PER_ADD = 100;

  private $name;

  private static $methods = [
    'POST' => RequestMethod::POST,
    'GET' => RequestMethod::GET,
    'HEAD' => RequestMethod::HEAD,
    'PUT' => RequestMethod::PUT,
    'DELETE' => RequestMethod::DELETE
  ];

  /**
   * Construct a PushQueue
   *
   * @param string $name The name of the queue.
   */
  public function __construct($name = 'default') {
    if (!is_string($name)) {
      throw new \InvalidArgumentException(
          '$name must be a string. Actual type: ' . gettype($name));
    }
    # TODO: validate queue name length and regex.
    $this->name = $name;
  }

  /**
   * Return the queue's name.
   *
   * @return string The queue's name.
   */
  public function getName() {
    return $this->name;
  }

  private static function errorCodeToException($error) {
    switch($error) {
      case ErrorCode::UNKNOWN_QUEUE:
        return new TaskQueueException('Unknown queue');
      case ErrorCode::TRANSIENT_ERROR:
        return new TransientTaskQueueException(
            'Temporary error, please re-try');
      case ErrorCode::INTERNAL_ERROR:
        return new TaskQueueException('Internal error');
      case ErrorCode::TASK_TOO_LARGE:
        return new TaskQueueException('Task too large');
      case ErrorCode::INVALID_TASK_NAME:
        return new TaskQueueException('Invalid task name');
      case ErrorCode::INVALID_QUEUE_NAME:
      case ErrorCode::TOMBSTONED_QUEUE:
        return new TaskQueueException('Invalid queue name');
      case ErrorCode::INVALID_URL:
        return new TaskQueueException('Invalid URL');
      case ErrorCode::PERMISSION_DENIED:
        return new TaskQueueException('Permission Denied');

      // Both TASK_ALREADY_EXISTS and TOMBSTONED_TASK are translated into the
      // same exception. This is in keeping with the Java API but different to
      // the Python API. Knowing that the task is tombstoned isn't particularly
      // interesting: the main point is that it has already been added.
      case ErrorCode::TASK_ALREADY_EXISTS:
      case ErrorCode::TOMBSTONED_TASK:
        return new TaskAlreadyExistsException(
            'Task with the same name exists already');
      case ErrorCode::INVALID_ETA:
        return new TaskQueueException('Invalid delay_seconds');
      case ErrorCode::INVALID_REQUEST:
        return new TaskQueueException('Invalid request');
      case ErrorCode::DUPLICATE_TASK_NAME:
        return new TaskQueueException(
            'Duplicate task names in addTasks request.');
      case ErrorCode::TOO_MANY_TASKS:
        return new TaskQueueException('Too many tasks in request.');
      case ErrorCode::INVALID_QUEUE_MODE:
        return new TaskQueueException('Cannot add a PushTask to a pull queue.');
      default:
        return new TaskQueueException('Error Code: ' . $error);
    }
  }

  /**
   * Add tasks to the queue.
   *
   * @param PushTask[] $tasks The tasks to be added to the queue.
   *
   * @return An array containing the name of each task added, with the same
   * ordering as $tasks.
   *
   * @throws TaskAlreadyExistsException if a task of the same name already
   * exists in the queue.
   * If this exception is raised, the caller can be guaranteed that all tasks
   * were successfully added either by this call or a previous call. Another way
   * to express it is that, if any task failed to be added for a different
   * reason, a different exception will be thrown.
   * @throws TaskQueueException if there was a problem using the service.
   */
  public function addTasks($tasks) {
    if (!is_array($tasks)) {
      throw new \InvalidArgumentException(
          '$tasks must be an array. Actual type: ' . gettype($tasks));
    }
    if (empty($tasks)) {
      return [];
    }
    if (count($tasks) > self::MAX_TASKS_PER_ADD) {
      throw new \InvalidArgumentException(
          '$tasks must contain at most ' . self::MAX_TASKS_PER_ADD .
          ' tasks. Actual size: ' . count($tasks));
    }

    if (getenv('GAE_PUSHQUEUE_BACKEND') === 'CLOUD_TASK') {
        return $this->addTasksCloudTasks($tasks);
    }

    $req = new TaskQueueBulkAddRequest();
    $resp = new TaskQueueBulkAddResponse();

    $names = [];
    $current_time = microtime(true);
    foreach ($tasks as $task) {
      if (!($task instanceof PushTask)) {
        throw new \InvalidArgumentException(
            'All values in $tasks must be instances of PushTask. ' .
            'Actual type: ' . gettype($task));
      }
      $names[] = $task->getName();
      $add = $req->addAddRequest();
      $add->setQueueName($this->name);
      $add->setTaskName($task->getName());
      $add->setEtaUsec(($current_time + $task->getDelaySeconds()) * 1e6);
      $add->setMethod(self::$methods[$task->getMethod()]);
      $add->setUrl($task->getUrl());
      foreach ($task->getHeaders() as $header) {
        $pair = explode(':', $header, 2);
        $header_pb = $add->addHeader();
        $header_pb->setKey(trim($pair[0]));
        $header_pb->setValue(trim($pair[1]));
      }
      // TODO: Replace getQueryData() with getBody() and simplify the following
      // block.
      if ($task->getMethod() == 'POST' || $task->getMethod() == 'PUT') {
        if ($task->getQueryData()) {
          $add->setBody(http_build_query($task->getQueryData()));
        }
      }
      if ($add->byteSizePartial() > PushTask::MAX_TASK_SIZE_BYTES) {
        throw new TaskQueueException('Task greater than maximum size of ' .
            PushTask::MAX_TASK_SIZE_BYTES . '. size: ' .
            $add->byteSizePartial());
      }
    }

    try {
      ApiProxy::makeSyncCall('taskqueue', 'BulkAdd', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }

    // Update $names with any generated task names. Also, check if there are any
    // error responses.
    $results = $resp->getTaskResultList();
    $exception = null;
    foreach ($results as $index => $task_result) {
      if ($task_result->hasChosenTaskName()) {
        $names[$index] = $task_result->getChosenTaskName();
      }
      if ($task_result->getResult() != ErrorCode::OK) {
        $exception = self::errorCodeToException($task_result->getResult());
        // Other exceptions take precedence over TaskAlreadyExistsException.
        if (!($exception instanceof TaskAlreadyExistsException)) {
          throw $exception;
        }
      }
    }
    if (isset($exception)) {
      throw $exception;
    }
    return $names;
  }

  private static function getMetadataValue($path) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'Metadata-Flavor: Google',
            'timeout' => 1.0
        ]
    ];
    $context = stream_context_create($opts);
    $url = 'http://metadata.google.internal/computeMetadata/v1/' . $path;
    $result = @file_get_contents($url, false, $context);
    return $result;
  }

  private static function getRegion() {
    static $region = null;
    if ($region === null) {
        $region = getenv('LOCATION_ID') ?: getenv('GAE_LOCATION') ?: getenv('GAE_REGION') ?: getenv('REGION_ID');
        if (!$region) {
            $regionPath = self::getMetadataValue('instance/region');
            if ($regionPath) {
                $parts = explode('/', $regionPath);
                $region = end($parts);
            }
        }
        if (!$region) {
            $region = getenv('LOCAL_GCP_REGION') ?: 'us-central1';
        }
    }
    return $region;
  }

  private static function getProjectId() {
    static $projectId = null;
    if ($projectId === null) {
        $projectId = getenv('GOOGLE_CLOUD_PROJECT');
        if (!$projectId) {
            $projectId = self::getMetadataValue('project/project-id');
        }
        if (!$projectId) {
            $appId = ApiProxy::getCurrentAppId();
            if (($pos = strpos($appId, '~')) !== false) {
                $projectId = substr($appId, $pos + 1);
            } else {
                $projectId = $appId;
            }
        }
    }
    return $projectId;
  }

  private static function getCloudPlatformToken() {
      try {
          $res = \Google\AppEngine\Api\AppIdentity\AppIdentityService::getAccessToken('https://www.googleapis.com/auth/cloud-platform');
          if (isset($res['access_token'])) {
              return $res['access_token'];
          }
      } catch (\Exception $e) {
          // Fallback to metadata server if AppIdentityService fails
      }
      $json = self::getMetadataValue('instance/service-accounts/default/token');
      if ($json) {
          $data = json_decode($json, true);
          if (isset($data['access_token'])) {
              return $data['access_token'];
          }
      }
      throw new TaskQueueException('Failed to obtain OAuth access token for Cloud Tasks');
  }

  private function addTasksCloudTasks($tasks) {
    $projectId = self::getProjectId();
    $region = self::getRegion();
    $token = self::getCloudPlatformToken();
    $fullQueueName = "projects/" . $projectId . "/locations/" . $region . "/queues/" . $this->name;

    $names = [];
    $chunks = array_chunk($tasks, 100);

    foreach ($chunks as $chunk) {
      $createTaskRequests = [];

      foreach ($chunk as $task) {
        $headers = [];
        $hasContentType = false;
        foreach ($task->getHeaders() as $header) {
          $pair = explode(':', $header, 2);
          $key = trim($pair[0]);
          $val = trim($pair[1]);
          if (strcasecmp($key, 'Content-Type') === 0) {
            $hasContentType = true;
            $key = 'Content-Type';
          } elseif (strcasecmp($key, 'X-AppEngine-QueueName') === 0) {
            $key = 'X-AppEngine-QueueName';
          } elseif (strcasecmp($key, 'X-AppEngine-TaskName') === 0) {
            $key = 'X-AppEngine-TaskName';
          }
          $headers[$key] = $val;
        }
        if (!$hasContentType) {
          $headers['Content-Type'] = 'application/octet-stream';
        }
        if (!isset($headers['X-AppEngine-QueueName'])) {
          $headers['X-AppEngine-QueueName'] = $this->name;
        }

        $taskName = $task->getName();
        if ($taskName && !isset($headers['X-AppEngine-TaskName'])) {
          $headers['X-AppEngine-TaskName'] = $taskName;
        }

        $methodMap = [
          'POST' => \Google\Cloud\Tasks\V2beta3\HttpMethod::POST,
          'GET' => \Google\Cloud\Tasks\V2beta3\HttpMethod::GET,
          'PUT' => \Google\Cloud\Tasks\V2beta3\HttpMethod::PUT,
          'DELETE' => \Google\Cloud\Tasks\V2beta3\HttpMethod::DELETE,
          'HEAD' => \Google\Cloud\Tasks\V2beta3\HttpMethod::HEAD,
        ];
        $httpMethod = isset($methodMap[$task->getMethod()]) ? $methodMap[$task->getMethod()] : \Google\Cloud\Tasks\V2beta3\HttpMethod::POST;

        $appEngineReq = new \Google\Cloud\Tasks\V2beta3\AppEngineHttpRequest();
        $appEngineReq->setRelativeUri($task->getUrl() ?: '/');
        $appEngineReq->setHttpMethod($httpMethod);

        foreach ($headers as $k => $v) {
          $appEngineReq->getHeaders()[$k] = $v;
        }

        if ($task->getMethod() === 'POST' || $task->getMethod() === 'PUT') {
          if ($task->getQueryData()) {
            $body = http_build_query($task->getQueryData());
            if (strlen($body) > PushTask::MAX_TASK_SIZE_BYTES) {
              throw new TaskQueueException('Task greater than maximum size of ' .
                  PushTask::MAX_TASK_SIZE_BYTES . '. size: ' . strlen($body));
            }
            $appEngineReq->setBody($body);
          }
        }

        $taskObj = new \Google\Cloud\Tasks\V2beta3\Task();
        if ($taskName) {
          $fullTaskName = $fullQueueName . "/tasks/" . $taskName;
          $taskObj->setName($fullTaskName);
        }
        $taskObj->setAppEngineHttpRequest($appEngineReq);

        if ($task->getDelaySeconds() > 0) {
          $ts = new \Google\Protobuf\Timestamp();
          $ts->setSeconds(time() + $task->getDelaySeconds());
          $taskObj->setScheduleTime($ts);
        }

        $createTaskReq = new \Google\Cloud\Tasks\V2beta3\CreateTaskRequest();
        $createTaskReq->setParent($fullQueueName);
        $createTaskReq->setTask($taskObj);
        $createTaskRequests[] = $createTaskReq;
      }

      $client = new \Google\Cloud\Tasks\V2beta3\CloudTasksClient();
      try {
        $response = $client->batchCreateTasks($fullQueueName, $createTaskRequests);
        foreach ($response->getTasks() as $resTask) {
          $parts = explode('/', $resTask->getName());
          $names[] = end($parts);
        }
      } catch (\Google\ApiCore\ApiException $e) {
        if ($e->getStatus() === 'ALREADY_EXISTS' || $e->getCode() === 409) {
          throw new TaskAlreadyExistsException('Task exists already: ' . $e->getMessage());
        }
        throw new TaskQueueException('Cloud Tasks Client SDK batchCreate failed: ' . $e->getMessage());
      } finally {
        $client->close();
      }
    }

    return $names;
  }

  private static function isAlreadyExistsError($errCode, $errMsg) {
    if ($errCode === 6 || $errCode === 409 || stripos($errMsg, 'already exists') !== false) {
      return true;
    }
    if (($errCode === 5 || $errCode === 404) && stripos($errMsg, 'Requested entity was not found') !== false) {
      return true;
    }
    return false;
  }
}
