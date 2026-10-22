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

  private static $cloudTasksClientFactory = null;
  private static $cloudTasksClient = null;
  private static $cloudTasksClientClass = null;

  public static function setCloudTasksClientFactory($factory) {
    if (self::$cloudTasksClient !== null) {
      if (method_exists(self::$cloudTasksClient, 'close')) {
        self::$cloudTasksClient->close();
      }
      self::$cloudTasksClient = null;
      self::$cloudTasksClientClass = null;
    }
    self::$cloudTasksClientFactory = $factory;
  }

  private static function getCloudTasksClient($clientClass) {
    if (self::$cloudTasksClient === null || self::$cloudTasksClientClass !== $clientClass) {
      if (self::$cloudTasksClient !== null && method_exists(self::$cloudTasksClient, 'close')) {
        self::$cloudTasksClient->close();
      }
      if (self::$cloudTasksClientFactory !== null) {
        self::$cloudTasksClient = call_user_func(self::$cloudTasksClientFactory, $clientClass);
      } else {
        self::$cloudTasksClient = new $clientClass();
      }
      self::$cloudTasksClientClass = $clientClass;
    }
    return self::$cloudTasksClient;
  }

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
    $tasks = array_values($tasks);
    if (count($tasks) > self::MAX_TASKS_PER_ADD) {
      throw new \InvalidArgumentException(
          '$tasks must contain at most ' . self::MAX_TASKS_PER_ADD .
          ' tasks. Actual size: ' . count($tasks));
    }
    foreach ($tasks as $task) {
      if (!($task instanceof PushTask)) {
        throw new \InvalidArgumentException(
            'All values in $tasks must be instances of PushTask. ' .
            'Actual type: ' . gettype($task));
      }
    }

    $useCloudTasks =
        strtolower((string) getenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE')) === 'true' ||
        getenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE') === '1';
    if ($useCloudTasks) {
        return $this->addTasksCloudTasks($tasks);
    }

    $req = new TaskQueueBulkAddRequest();
    $resp = new TaskQueueBulkAddResponse();

    $names = [];
    $current_time = microtime(true);
    foreach ($tasks as $task) {
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

  private static function normalizeRegion($region) {
    $legacyMap = [
      'us-central' => 'us-central1',
      'europe-west' => 'europe-west1',
    ];
    return isset($legacyMap[$region]) ? $legacyMap[$region] : $region;
  }

  private static function getRegion() {
    $envRegion = getenv('LOCATION_ID') ?: getenv('GAE_LOCATION') ?: getenv('GAE_REGION') ?: getenv('REGION_ID');
    if ($envRegion) {
      return self::normalizeRegion($envRegion);
    }
    static $region = null;
    if ($region === null) {
        $regionPath = self::getMetadataValue('instance/region');
        if ($regionPath) {
            $parts = explode('/', $regionPath);
            $region = end($parts) ?: null;
        }
    }
    if ($region !== null) {
      return self::normalizeRegion($region);
    }
    // The fallback is not cached, so a failed metadata server request (for
    // example a timeout during a cold start) is retried on the next call.
    return self::normalizeRegion(getenv('LOCAL_GCP_REGION') ?: 'us-central1');
  }

  private static function getProjectId() {
    $envProject = getenv('GOOGLE_CLOUD_PROJECT');
    if ($envProject) {
      return $envProject;
    }
    static $projectId = null;
    if ($projectId === null) {
        $projectId = self::getMetadataValue('project/project-id') ?: null;
    }
    if ($projectId !== null) {
      return $projectId;
    }
    // Not cached, so a failed metadata server request is retried next time.
    $appId = ApiProxy::getCurrentAppId();
    if (($pos = strpos($appId, '~')) !== false) {
      return substr($appId, $pos + 1);
    }
    return $appId;
  }

  /**
   * Returns the task's headers for a Cloud Tasks AppEngineHttpRequest.
   *
   * Cloud Tasks sets Host, X-Google-* and X-AppEngine-* headers itself (for
   * example X-AppEngine-QueueName and X-AppEngine-TaskName) and ignores
   * client supplied values, so they are not sent.
   */
  private static function buildCloudTaskHeaders($task) {
    $headers = [];
    $hasContentType = false;
    foreach ($task->getHeaders() as $header) {
      $pair = explode(':', $header, 2);
      $key = trim($pair[0]);
      $val = trim($pair[1]);
      if (strcasecmp($key, 'Host') === 0 ||
          stripos($key, 'X-Google-') === 0 ||
          stripos($key, 'X-AppEngine-') === 0) {
        continue;
      }
      if (strcasecmp($key, 'Content-Type') === 0) {
        $hasContentType = true;
        $key = 'Content-Type';
      }
      $headers[$key] = $val;
    }
    if (!$hasContentType) {
      $headers['Content-Type'] = 'application/octet-stream';
    }
    return $headers;
  }

  /**
   * Returns the App Engine routing of a task as [service, version, instance],
   * with null for parts that are not set.
   *
   * Like the legacy service, a Host header that is a hostname of the app,
   * such as v1.worker.my-app.appspot.com or
   * v1-dot-worker-dot-my-app.uc.r.appspot.com, selects
   * [[instance.]version.]service. Otherwise the task runs on the current
   * service and version, as legacy push tasks without a target do. A queue's
   * routing override, set from its queue.yaml target, takes precedence.
   */
  private static function buildCloudTaskRouting($task) {
    foreach ($task->getHeaders() as $header) {
      $pair = explode(':', $header, 2);
      if (strcasecmp(trim($pair[0]), 'Host') !== 0) {
        continue;
      }
      $host = preg_replace('/:\d+$/', '', strtolower(trim($pair[1])));
      $suffix = '.appspot.com';
      if (substr($host, -strlen($suffix)) !== $suffix) {
        break;
      }
      $host = substr($host, 0, -strlen($suffix));
      if (substr($host, -2) === '.r') {
        // Regional hostname: <target>-dot-<app>.<region code>.r.appspot.com.
        $host = substr($host, 0, -2);
        $host = substr($host, 0, (int) strrpos($host, '.'));
      }
      $projectId = strtolower((string) self::getProjectId());
      if (($colonPos = strpos($projectId, ':')) !== false) {
        // Domain-scoped project ID: example.com:my-app -> my-app.example.com
        $appHost = substr($projectId, $colonPos + 1) . '.' . substr($projectId, 0, $colonPos);
      } else {
        $appHost = $projectId;
      }
      if ($appHost === '') {
        break;
      }
      $normalizedHost = str_replace('-dot-', '.', $host);
      if ($normalizedHost === $appHost) {
        return ['default', null, null];
      }
      $appSuffix = '.' . $appHost;
      if (substr($normalizedHost, -strlen($appSuffix)) !== $appSuffix) {
        break;
      }
      $targetPrefix = substr($normalizedHost, 0, -strlen($appSuffix));
      $parts = explode('.', $targetPrefix);
      $n = count($parts);
      return [$parts[$n - 1], $n > 1 ? $parts[$n - 2] : null,
              $n > 2 ? $parts[$n - 3] : null];
    }
    return [getenv('GAE_SERVICE') ?: null, getenv('GAE_VERSION') ?: null, null];
  }

  /**
   * Returns an AppEngineRouting of $routingClass for the task, or null if no
   * routing applies.
   */
  private static function newCloudTaskRouting($routingClass, $task) {
    list($service, $version, $instance) = self::buildCloudTaskRouting($task);
    if ($service === null && $version === null && $instance === null) {
      return null;
    }
    $routing = new $routingClass();
    if ($service !== null) {
      $routing->setService($service);
    }
    if ($version !== null) {
      $routing->setVersion($version);
    }
    if ($instance !== null) {
      $routing->setInstance($instance);
    }
    return $routing;
  }

  /**
   * Maps a Cloud Tasks ApiException to the TaskQueue exception the legacy
   * service would raise.
   */
  private static function apiExceptionToTaskQueueException($e, $operation) {
    $status = method_exists($e, 'getStatus') ? $e->getStatus() : '';
    $code = $e->getCode();
    $msg = $e->getMessage();
    if ($status === 'ALREADY_EXISTS' || self::isAlreadyExistsError($code, $msg)) {
      return new TaskAlreadyExistsException('Task exists already: ' . $msg);
    }
    if (self::isTransientError($status, $code)) {
      return new TransientTaskQueueException(
          'Temporary error, please re-try: ' . $msg);
    }
    return new TaskQueueException(
        'Cloud Tasks Client SDK ' . $operation . ' failed: ' . $msg);
  }

  /**
   * Returns true for gRPC statuses that the legacy service reports as
   * TRANSIENT_ERROR: DEADLINE_EXCEEDED (4), RESOURCE_EXHAUSTED (8),
   * ABORTED (10) and UNAVAILABLE (14).
   */
  private static function isTransientError($status, $code) {
    return in_array($status, ['DEADLINE_EXCEEDED', 'RESOURCE_EXHAUSTED',
                              'ABORTED', 'UNAVAILABLE'], true) ||
        in_array($code, [4, 8, 10, 14], true);
  }

  private function buildCloudTaskObjV2($task, $fullQueueName) {
    $headers = self::buildCloudTaskHeaders($task);
    $taskName = $task->getName();

    $methodMap = [
      'POST' => 1,
      'GET' => 2,
      'HEAD' => 3,
      'PUT' => 4,
      'DELETE' => 5,
      'PATCH' => 6,
      'OPTIONS' => 7,
    ];
    $httpMethod = isset($methodMap[$task->getMethod()]) ? $methodMap[$task->getMethod()] : 1;

    $appEngineReq = new \Google\Cloud\Tasks\V2\AppEngineHttpRequest();
    $appEngineReq->setRelativeUri($task->getUrl() ?: '/');
    $appEngineReq->setHttpMethod($httpMethod);
    $routing = self::newCloudTaskRouting('\Google\Cloud\Tasks\V2\AppEngineRouting', $task);
    if ($routing !== null) {
      $appEngineReq->setAppEngineRouting($routing);
    }

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

    $taskObj = new \Google\Cloud\Tasks\V2\Task();
    if ($taskName) {
      $fullTaskName = $fullQueueName . "/tasks/" . $taskName;
      $taskObj->setName($fullTaskName);
    }
    $taskObj->setAppEngineHttpRequest($appEngineReq);

    if ($task->getDelaySeconds() > 0) {
      $taskObj->setScheduleTime(self::buildScheduleTime($task->getDelaySeconds()));
    }

    return $taskObj;
  }

  /**
   * Builds a Timestamp for now + $delaySeconds, preserving fractional seconds.
   */
  private static function buildScheduleTime($delaySeconds) {
    $scheduleTime = microtime(true) + $delaySeconds;
    $seconds = (int) floor($scheduleTime);
    $nanos = (int) round(($scheduleTime - $seconds) * 1e9);
    if ($nanos >= 1000000000) {
      $seconds += 1;
      $nanos -= 1000000000;
    }
    $ts = new \Google\Protobuf\Timestamp();
    $ts->setSeconds($seconds);
    $ts->setNanos($nanos);
    return $ts;
  }

  /**
   * Returns [clientClass, usesRequestObjects] for the Cloud Tasks V2 client
   * installed, or null if no Cloud Tasks V2 client is available.
   */
  private static function singleTaskClientSpec() {
    $candidates = [
      ['\Google\Cloud\Tasks\V2\Client\CloudTasksClient', true],
      ['\Google\Cloud\Tasks\V2\CloudTasksClient', false],
    ];
    foreach ($candidates as $candidate) {
      if (class_exists($candidate[0])) {
        return $candidate;
      }
    }
    return null;
  }

  /**
   * Creates a single task using the shared Cloud Tasks V2 client (or $client
   * when provided).
   */
  private function createSingleTaskCloudTasks($task, $fullQueueName, $client = null) {
    $spec = self::singleTaskClientSpec();
    if ($spec === null) {
      throw new TaskQueueException('Cloud Tasks Client SDK is not available.');
    }
    list($clientClass, $usesRequestObjects) = $spec;

    $taskObj = $this->buildCloudTaskObjV2($task, $fullQueueName);
    $requestClass = '\Google\Cloud\Tasks\V2\CreateTaskRequest';

    if ($client === null) {
      $client = self::getCloudTasksClient($clientClass);
    }
    try {
      if ($usesRequestObjects) {
        $createTaskReq = (new $requestClass())
            ->setParent($fullQueueName)
            ->setTask($taskObj);
        $response = $client->createTask($createTaskReq);
      } else {
        $response = $client->createTask($fullQueueName, $taskObj);
      }
      $parts = explode('/', $response->getName());
      return [end($parts)];
    } catch (\Google\ApiCore\ApiException $e) {
      throw self::apiExceptionToTaskQueueException($e, 'createTask');
    }
  }

  private static function processBatchCreateResponse($response, $tasks, &$names) {
    if (method_exists($response, 'isDone') && !$response->isDone()) {
      throw new TaskQueueException('Cloud Tasks batch create operation returned done=false');
    }

    $resObj = method_exists($response, 'getResult') ? $response->getResult() :
        (method_exists($response, 'getResponse') ? $response->getResponse() : $response);
    if ($resObj && method_exists($resObj, 'getTasks')) {
      foreach ($resObj->getTasks() as $resTask) {
        $parts = explode('/', $resTask->getName());
        $names[] = end($parts);
      }
    }

    $metadata = method_exists($response, 'getMetadata') ? $response->getMetadata() : null;
    $failedRequests = ($metadata && method_exists($metadata, 'getFailedRequests')) ? $metadata->getFailedRequests() : null;
    $exception = null;
    foreach ($tasks as $idx => $task) {
      $hasFailure = $failedRequests &&
          ((is_array($failedRequests) && isset($failedRequests[$idx])) ||
           (is_object($failedRequests) && method_exists($failedRequests, 'offsetExists') && $failedRequests->offsetExists($idx)));
      if (!$hasFailure) {
        continue;
      }
      $errStatus = is_array($failedRequests) ? $failedRequests[$idx] : $failedRequests->offsetGet($idx);
      $code = ($errStatus && method_exists($errStatus, 'getCode')) ? $errStatus->getCode() : 0;
      if ($code !== 0) {
        $msg = ($errStatus && method_exists($errStatus, 'getMessage')) ? $errStatus->getMessage() : '';
        if (self::isAlreadyExistsError($code, $msg)) {
          $exception = new TaskAlreadyExistsException('Task exists already: ' . $msg);
        } elseif (stripos($msg, 'queue does not exist') !== false || stripos($msg, 'queue no longer exists') !== false) {
          throw new TaskQueueException('Unknown queue: ' . $msg);
        } elseif (self::isTransientError('', $code)) {
          throw new TransientTaskQueueException('Temporary error, please re-try: ' . $msg);
        } else {
          throw new TaskQueueException('Task creation failed: ' . $msg);
        }
      }
    }
    if ($exception !== null) {
      throw $exception;
    }
  }

  /**
   * Returns true if $client->$methodName() accepts a Request object rather
   * than positional arguments ($parent, ...).
   */
  private static function methodAcceptsRequestObject($client, $methodName) {
    $ref = new \ReflectionMethod($client, $methodName);
    $params = $ref->getParameters();
    if (empty($params)) {
      return true;
    }
    $firstParam = $params[0];
    if (method_exists($firstParam, 'getType') && $firstParam->getType() !== null) {
      $type = $firstParam->getType();
      $typeName = method_exists($type, 'getName') ? $type->getName() : (string) $type;
      return $typeName !== 'string';
    }
    $paramName = strtolower($firstParam->getName());
    if ($paramName === 'parent' || $paramName === 'formattedparent') {
      return false;
    }
    return $ref->getNumberOfRequiredParameters() < 2;
  }

  private function addTasksCloudTasks($tasks) {
    $tasks = array_values($tasks);
    $projectId = self::getProjectId();
    $region = self::getRegion();
    $fullQueueName = "projects/" . $projectId . "/locations/" . $region . "/queues/" . $this->name;

    if (count($tasks) === 1) {
      return $this->createSingleTaskCloudTasks($tasks[0], $fullQueueName);
    }

    // addTasks() allows at most MAX_TASKS_PER_ADD (100) tasks, which is also
    // the BatchCreateTasks limit, so every call is a single batch request.
    $names = [];

    $v2ClientClass = null;
    if (class_exists('\Google\Cloud\Tasks\V2\Client\CloudTasksClient') && method_exists('\Google\Cloud\Tasks\V2\Client\CloudTasksClient', 'batchCreateTasks')) {
      $v2ClientClass = '\Google\Cloud\Tasks\V2\Client\CloudTasksClient';
    } elseif (class_exists('\Google\Cloud\Tasks\V2\CloudTasksClient') && method_exists('\Google\Cloud\Tasks\V2\CloudTasksClient', 'batchCreateTasks')) {
      $v2ClientClass = '\Google\Cloud\Tasks\V2\CloudTasksClient';
    }

    if ($v2ClientClass !== null) {
      $client = self::getCloudTasksClient($v2ClientClass);
      $createTaskRequests = [];
      foreach ($tasks as $task) {
        $taskObj = $this->buildCloudTaskObjV2($task, $fullQueueName);
        $createTaskReq = (new \Google\Cloud\Tasks\V2\CreateTaskRequest())
            ->setParent($fullQueueName)
            ->setTask($taskObj);
        $createTaskRequests[] = $createTaskReq;
      }

      try {
        if (class_exists('\Google\Cloud\Tasks\V2\BatchCreateTasksRequest') &&
            self::methodAcceptsRequestObject($client, 'batchCreateTasks')) {
          $batchReq = (new \Google\Cloud\Tasks\V2\BatchCreateTasksRequest())
              ->setParent($fullQueueName)
              ->setRequests($createTaskRequests);
          $response = $client->batchCreateTasks($batchReq);
        } else {
          $response = $client->batchCreateTasks($fullQueueName, $createTaskRequests);
        }

        self::processBatchCreateResponse($response, $tasks, $names);
      } catch (\Google\ApiCore\ApiException $e) {
        throw self::apiExceptionToTaskQueueException($e, 'batchCreate');
      }
      return $names;
    }

    // Fallback: If native batchCreateTasks is not available in the installed Cloud Tasks V2 SDK,
    // enqueue tasks individually using createTask over the shared client.
    $spec = self::singleTaskClientSpec();
    if ($spec === null) {
      throw new TaskQueueException('Cloud Tasks Client SDK is not available.');
    }
    $client = self::getCloudTasksClient($spec[0]);
    return $this->addTasksIndividually($tasks, $fullQueueName, $client);
  }

  /**
   * Creates tasks one at a time. Like BulkAdd, every task is attempted, and
   * TaskAlreadyExistsException is only thrown if no task failed for another
   * reason, so that it still means every task has been added.
   */
  private function addTasksIndividually($tasks, $fullQueueName, $client) {
    syslog(
        LOG_WARNING,
        'Cloud Tasks batchCreateTasks is not available in the installed ' .
        'google/cloud-tasks package; falling back to sequential createTask ' .
        'calls. Upgrade google/cloud-tasks to avoid request timeouts on ' .
        'large batches.'
    );
    $names = [];
    $alreadyExists = null;
    $error = null;
    foreach ($tasks as $task) {
      try {
        $res = $this->createSingleTaskCloudTasks($task, $fullQueueName, $client);
        $names[] = $res[0];
      } catch (TaskAlreadyExistsException $e) {
        $alreadyExists = $alreadyExists ?: $e;
      } catch (TaskQueueException $e) {
        $error = $error ?: $e;
      }
    }
    if ($error !== null) {
      throw $error;
    }
    if ($alreadyExists !== null) {
      throw $alreadyExists;
    }
    return $names;
  }

  private static function isAlreadyExistsError($errCode, $errMsg) {
    // Cloud Tasks reports duplicate and tombstoned task names as ALREADY_EXISTS.
    // NOT_FOUND is intentionally not mapped here so that missing queues,
    // projects or locations surface as TaskQueueException.
    return $errCode === 6 || $errCode === 409 ||
        stripos($errMsg, 'already exists') !== false ||
        stripos($errMsg, 'existed too recently') !== false ||
        stripos($errMsg, 'tombstoned') !== false;
  }
}
