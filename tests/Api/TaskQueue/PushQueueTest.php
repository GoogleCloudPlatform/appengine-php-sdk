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
 * Unit tests for the PushQueue class.
 *
 */

namespace Google\AppEngine\Api\TaskQueue;

use Google\AppEngine\Api\TaskQueue\PushTask;
use Google\AppEngine\Testing\ApiProxyTestBase;
use google\appengine\TaskQueueAddRequest\RequestMethod;
use google\appengine\TaskQueueBulkAddRequest;
use google\appengine\TaskQueueBulkAddResponse;
use google\appengine\TaskQueueServiceError\ErrorCode;

class PushQueueTest extends ApiProxyTestBase {

  // Used to mock file_get_contents() for metadata server requests.
  // See https://github.com/php-mock/php-mock-phpunit
  use \phpmock\phpunit\PHPMock;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    // Defined up front so the mock works even if file_get_contents() was
    // already called in this namespace.
    self::defineFunctionMock(__NAMESPACE__, 'file_get_contents');
  }

  public function setUp(): void {
    parent::setUp();
    $this->_SERVER = $_SERVER;
    // Mock out any microtime() calls.
    MockMicrotime::reset();
  }

  public function tearDown(): void {
    $_SERVER = $this->_SERVER;
    parent::tearDown();
  }

  private static function buildBulkAddRequest($queue_name = 'default') {
    $req = new TaskQueueBulkAddRequest();
    $task = $req->addAddRequest();
    $task->setQueueName($queue_name);
    $task->setTaskName('');
    $task->setUrl('/someUrl');
    $time = 12345.6;
    MockMicrotime::expect($time);
    $task->setEtaUsec($time * 1e6);
    $task->setMethod(RequestMethod::POST);
    return $req;
  }

  private static function buildBulkAddRequestWithTwoTasks(
      $queue_name = 'default') {
    $req = self::buildBulkAddRequest($queue_name);

    $task = $req->addAddRequest();
    $task->setQueueName($queue_name);
    $task->setTaskName('');
    $task->setUrl('/someOtherUrl');
    $time = 12345.6;
    MockMicrotime::expect($time);
    $task->setEtaUsec($time * 1e6);
    $task->setMethod(RequestMethod::POST);

    return $req;
  }

  public function testConstructorNameWrongType() {
    $this->expectException('\InvalidArgumentException',
        '$name must be a string. Actual type: integer');
    $queue = new PushQueue(54321);
  }

  public function testGetName() {
    $queue = new PushQueue();
    $this->assertEquals('default', $queue->getName());
    $queue = new PushQueue('fast-queue');
    $this->assertEquals('fast-queue', $queue->getName());
  }

  public function testAddTaskTooBig() {
    $this->expectException(
        '\Google\AppEngine\Api\TaskQueue\TaskQueueException',
        'Task greater than maximum size of ' . PushTask::MAX_TASK_SIZE_BYTES);
    // Althought 102400 is the max size, it's for the serialized proto which
    // includes the URL etc.
    $task = new PushTask('/someUrl', ['field' => str_repeat('a', 102395)]);
    (new PushQueue())->addTasks([$task]);
  }

  public function testPushQueueAddTasksWrongType() {
    $this->expectException('\InvalidArgumentException',
        '$tasks must be an array. Actual type: string');
    $queue = new PushQueue();
    $task_names = $queue->addTasks('not an array');
  }

  public function testPushQueueAddTasksWrongValueType() {
    $this->expectException('\InvalidArgumentException',
        'All values in $tasks must be instances of PushTask. ' .
        'Actual type: double');
    $queue = new PushQueue();
    $task_names = $queue->addTasks([1.0]);
  }

  public function testPushQueueAddTasksTooMany() {
    $this->expectException('\InvalidArgumentException',
        '$tasks must contain at most 100 tasks. Actual size: 101');
    $tasks = [];
    for ($i = 0; $i < 101; $i++) {
      $tasks[] = new PushTask('/a-url');
    }
    $queue = new PushQueue();
    $queue->addTasks($tasks);
  }

  public function testPushQueueAddTasksEmptyArray() {
    $queue = new PushQueue();
    $task_names = $queue->addTasks([]);
    $this->assertEquals([], $task_names);
    $this->apiProxyMock->verify();
  }

  public function testPushQueueSimplestAddTasks() {
    $req = self::buildBulkAddRequest();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');

    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task = new PushTask('/someUrl');
    $queue = new PushQueue();
    $task_names = $queue->addTasks([$task]);
    $this->assertEquals(['fred'], $task_names);
    $this->apiProxyMock->verify();
  }

  public function testGaePushQueueBackendEnvDoesNotEnableCloudTasks() {
    putenv('GAE_PUSHQUEUE_BACKEND=CLOUD_TASK');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        throw new \LogicException('Cloud Tasks must not be used');
      });
      $req = self::buildBulkAddRequest();
      $resp = new TaskQueueBulkAddResponse();
      $task_result = $resp->addTaskResult();
      $task_result->setResult(ErrorCode::OK);
      $task_result->setChosenTaskName('fred');
      $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

      $task_names = (new PushQueue())->addTasks([new PushTask('/someUrl')]);
      $this->assertEquals(['fred'], $task_names);
      $this->apiProxyMock->verify();
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('GAE_PUSHQUEUE_BACKEND');
    }
  }

  public function testPushQueueAddTwoTasks() {
    $req = self::buildBulkAddRequestWithTwoTasks();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('bob');

    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue();
    $task_names = $queue->addTasks([$task1, $task2]);
    $this->assertEquals(['fred', 'bob'], $task_names);
    $this->apiProxyMock->verify();
  }

  public function testPushQueueAddTwoTasksNonDefaultQueue() {
    $req = self::buildBulkAddRequestWithTwoTasks('superQ');

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('bob');

    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue('superQ');
    $task_names = $queue->addTasks([$task1, $task2]);
    $this->assertEquals(['fred', 'bob'], $task_names);
    $this->apiProxyMock->verify();
  }

  public function testPushQueueTaskAlreadyExistsError() {
    $req = self::buildBulkAddRequestWithTwoTasks();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::TOMBSTONED_TASK);
    $task_result->setChosenTaskName('bob');

    $this->expectException(
        '\Google\AppEngine\Api\TaskQueue\TaskAlreadyExistsException');
    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue();
    $queue->addTasks([$task1, $task2]);
    $this->apiProxyMock->verify();
  }

  public function testPushQueueUnknownQueueError() {
    $req = self::buildBulkAddRequestWithTwoTasks();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::UNKNOWN_QUEUE);
    $task_result->setChosenTaskName('bob');

    $this->expectException(
        '\Google\AppEngine\Api\TaskQueue\TaskQueueException',
        'Unknown queue');
    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue();
    $queue->addTasks([$task1, $task2]);
    $this->apiProxyMock->verify();
  }

  // UNKNOWN_QUEUE should take precedence over TOMBSTONED_TASK.
  public function testPushQueueTwoErrors() {
    $req = self::buildBulkAddRequestWithTwoTasks();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::TOMBSTONED_TASK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::UNKNOWN_QUEUE);
    $task_result->setChosenTaskName('bob');

    $this->expectException(
        '\Google\AppEngine\Api\TaskQueue\TaskQueueException',
        'Unknown queue');
    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue();
    $queue->addTasks([$task1, $task2]);
    $this->apiProxyMock->verify();
  }

  public function testPushQueueTooManyTasksError() {
    $req = self::buildBulkAddRequestWithTwoTasks();

    $resp = new TaskQueueBulkAddResponse();
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::OK);
    $task_result->setChosenTaskName('fred');
    $task_result = $resp->addTaskResult();
    $task_result->setResult(ErrorCode::TOO_MANY_TASKS);
    $task_result->setChosenTaskName('bob');

    $this->expectException(
        '\Google\AppEngine\Api\TaskQueue\TaskQueueException',
        'Too many tasks in request.');
    $this->apiProxyMock->expectCall('taskqueue', 'BulkAdd', $req, $resp);

    $task1 = new PushTask('/someUrl');
    $task2 = new PushTask('/someOtherUrl');
    $queue = new PushQueue();
    $queue->addTasks([$task1, $task2]);
    $this->apiProxyMock->verify();
  }

  private static function ensureCloudTasksV2StubsLoaded() {
    if (!class_exists('\Google\Protobuf\Timestamp')) {
      eval('
        namespace Google\Protobuf;
        class Timestamp {
          private $seconds = 0;
          private $nanos = 0;
          public function setSeconds($s) { $this->seconds = $s; return $this; }
          public function getSeconds() { return $this->seconds; }
          public function setNanos($n) { $this->nanos = $n; return $this; }
          public function getNanos() { return $this->nanos; }
        }
      ');
    }
    if (!class_exists('\Google\Rpc\Status')) {
      eval('
        namespace Google\Rpc;
        class Status {
          private $code = 0;
          private $message = "";
          public function setCode($c) { $this->code = $c; return $this; }
          public function getCode() { return $this->code; }
          public function setMessage($m) { $this->message = $m; return $this; }
          public function getMessage() { return $this->message; }
        }
      ');
    }
    if (!class_exists('\Google\ApiCore\ApiException')) {
      eval('
        namespace Google\ApiCore;
        class ApiException extends \Exception {
          private $status;
          public function __construct($message, $code, $status = "") {
            parent::__construct($message, $code);
            $this->status = $status;
          }
          public function getStatus() { return $this->status; }
        }
      ');
    }
    if (!class_exists('\Google\Cloud\Tasks\V2\Task')) {
      eval('
        namespace Google\Cloud\Tasks\V2;
        class AppEngineHttpRequest {
          private $relativeUri = "/";
          private $httpMethod = 1;
          private $headers;
          private $body = "";
          private $appEngineRouting = null;
          public function __construct() { $this->headers = new \ArrayObject(); }
          public function setAppEngineRouting($r) { $this->appEngineRouting = $r; return $this; }
          public function getAppEngineRouting() { return $this->appEngineRouting; }
          public function setRelativeUri($u) { $this->relativeUri = $u; return $this; }
          public function getRelativeUri() { return $this->relativeUri; }
          public function setHttpMethod($m) { $this->httpMethod = $m; return $this; }
          public function getHttpMethod() { return $this->httpMethod; }
          public function getHeaders() { return $this->headers; }
          public function setBody($b) { $this->body = $b; return $this; }
          public function getBody() { return $this->body; }
        }
        class AppEngineRouting {
          private $service = "";
          private $version = "";
          private $instance = "";
          public function setService($s) { $this->service = $s; return $this; }
          public function getService() { return $this->service; }
          public function setVersion($v) { $this->version = $v; return $this; }
          public function getVersion() { return $this->version; }
          public function setInstance($i) { $this->instance = $i; return $this; }
          public function getInstance() { return $this->instance; }
        }
        class Task {
          private $name = "";
          private $appEngineHttpRequest = null;
          private $scheduleTime = null;
          public function setName($n) { $this->name = $n; return $this; }
          public function getName() { return $this->name; }
          public function setAppEngineHttpRequest($r) { $this->appEngineHttpRequest = $r; return $this; }
          public function getAppEngineHttpRequest() { return $this->appEngineHttpRequest; }
          public function setScheduleTime($t) { $this->scheduleTime = $t; return $this; }
          public function getScheduleTime() { return $this->scheduleTime; }
        }
        class CreateTaskRequest {
          private $parent = "";
          private $task = null;
          public function setParent($p) { $this->parent = $p; return $this; }
          public function getParent() { return $this->parent; }
          public function setTask($t) { $this->task = $t; return $this; }
          public function getTask() { return $this->task; }
        }
        class BatchCreateTasksRequest {
          private $parent = "";
          private $requests = [];
          public function setParent($p) { $this->parent = $p; return $this; }
          public function getParent() { return $this->parent; }
          public function setRequests($r) { $this->requests = $r; return $this; }
          public function getRequests() { return $this->requests; }
        }
        class BatchCreateTasksResponse {
          private $tasks = [];
          public function setTasks($t) { $this->tasks = $t; return $this; }
          public function getTasks() { return $this->tasks; }
        }
        class BatchCreateTasksMetadata {
          private $failedRequests = [];
          public function setFailedRequests($f) { $this->failedRequests = $f; return $this; }
          public function getFailedRequests() { return $this->failedRequests; }
        }
      ');
    }
    if (!class_exists('\Google\Cloud\Tasks\V2\Client\CloudTasksClient')) {
      eval('
        namespace Google\Cloud\Tasks\V2\Client;
        class CloudTasksClient {
          public function createTask($req) {}
          public function batchCreateTasks($req) {}
          public function close() {}
        }
      ');
    }
  }

  public function testCloudTasksV2SingleCreateTask() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      $capturedReq = null;
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$capturedReq) {
        return new class($capturedReq) {
          private $capturedReqRef;
          public function __construct(&$ref) { $this->capturedReqRef = &$ref; }
          public function createTask($req) {
            $this->capturedReqRef = $req;
            return (new \Google\Cloud\Tasks\V2\Task())
                ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/single-1');
          }
          public function close() {}
        };
      });

      $task = new PushTask('/worker/push', ['foo' => 'bar'], [
        'name' => 'single-1',
        'delay_seconds' => 30,
      ]);
      $queue = new PushQueue('default');
      $names = $queue->addTasks([$task]);
      $this->assertEquals(['single-1'], $names);
      $this->assertNotNull($capturedReq);
      $this->assertEquals(
          'projects/test-proj/locations/us-central1/queues/default',
          $capturedReq->getParent());
      $ctTask = $capturedReq->getTask();
      $this->assertEquals(
          'projects/test-proj/locations/us-central1/queues/default/tasks/single-1',
          $ctTask->getName());
      $this->assertEquals('/worker/push', $ctTask->getAppEngineHttpRequest()->getRelativeUri());
      $this->assertEquals('foo=bar', $ctTask->getAppEngineHttpRequest()->getBody());
      $this->assertNotNull($ctTask->getScheduleTime());
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2SingleCreateTaskAlreadyExists() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function createTask($req) {
            throw new \Google\ApiCore\ApiException('Task already exists', 409, 'ALREADY_EXISTS');
          }
          public function close() {}
        };
      });

      $this->expectException('\Google\AppEngine\Api\TaskQueue\TaskAlreadyExistsException');
      $task = new PushTask('/worker/push', [], ['name' => 'dup-single']);
      $queue = new PushQueue('default');
      $queue->addTasks([$task]);
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2BatchCreateTasksViaGetResult() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function batchCreateTasks($req) {
            return new class {
              public function isDone() { return true; }
              public function operationFailed() { return false; }
              public function getMetadata() {
                return new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata();
              }
              public function getResult() {
                $t1 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/task-1');
                $t2 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/task-2');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse())
                    ->setTasks([$t1, $t2]);
              }
            };
          }
          public function close() {}
        };
      });

      $task1 = new PushTask('/url1', ['k' => 'v1'], ['name' => 'task-1']);
      $task2 = new PushTask('/url2', ['k' => 'v2'], ['name' => 'task-2']);
      $queue = new PushQueue('default');
      $names = $queue->addTasks([$task1, $task2]);
      $this->assertEquals(['task-1', 'task-2'], $names);
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2BatchCreateTasksFailedRequestsAlreadyExists() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function batchCreateTasks($req) {
            return new class {
              public function isDone() { return true; }
              public function operationFailed() { return false; }
              public function getMetadata() {
                $st = (new \Google\Rpc\Status())
                    ->setCode(6)
                    ->setMessage('The task cannot be created because a task with this name existed too recently');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata())
                    ->setFailedRequests([1 => $st]);
              }
              public function getResult() {
                $t1 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/task-1');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse())
                    ->setTasks([$t1]);
              }
            };
          }
          public function close() {}
        };
      });

      $this->expectException('\Google\AppEngine\Api\TaskQueue\TaskAlreadyExistsException');
      $task1 = new PushTask('/url1');
      $task2 = new PushTask('/url2', [], ['name' => 'dup-task']);
      $queue = new PushQueue('default');
      $queue->addTasks([$task1, $task2]);
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2BatchCreateTasksFailedRequestsUnknownQueue() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function batchCreateTasks($req) {
            return new class {
              public function isDone() { return true; }
              public function operationFailed() { return false; }
              public function getMetadata() {
                $st = (new \Google\Rpc\Status())
                    ->setCode(5)
                    ->setMessage('Queue does not exist');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata())
                    ->setFailedRequests([0 => $st]);
              }
              public function getResult() {
                return new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse();
              }
            };
          }
          public function close() {}
        };
      });

      $this->expectException('\Google\AppEngine\Api\TaskQueue\TaskQueueException');
      $this->expectExceptionMessage('Unknown queue');
      $task1 = new PushTask('/url1');
      $task2 = new PushTask('/url2');
      $queue = new PushQueue('missing-queue');
      $queue->addTasks([$task1, $task2]);
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksRoutingFromHostHeader() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    putenv('GAE_SERVICE=current');
    putenv('GAE_VERSION=cv');
    try {
      $captured = [];
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$captured) {
        return new class($captured) {
          private $capturedRef;
          public function __construct(&$ref) { $this->capturedRef = &$ref; }
          public function createTask($req) {
            $this->capturedRef[] = $req;
            return (new \Google\Cloud\Tasks\V2\Task())
                ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/t');
          }
          public function close() {}
        };
      });

      $cases = [
        // [Host header, service, version, instance]
        ['Host: 1.v2.worker.test-proj.appspot.com', 'worker', 'v2', '1'],
        ['host: v2-dot-worker-dot-test-proj.uc.r.appspot.com:443', 'worker', 'v2', ''],
        ['Host: worker-dot-test-proj.appspot.com', 'worker', '', ''],
        ['Host: test-proj.appspot.com', 'default', '', ''],
        // Different project ID or not a hostname of the app, and no Host
        // header: the current service and version.
        ['Host: worker.other-proj.appspot.com', 'current', 'cv', ''],
        ['Host: other-proj.appspot.com', 'current', 'cv', ''],
        ['Host: example.com', 'current', 'cv', ''],
        ['', 'current', 'cv', ''],
      ];
      $queue = new PushQueue('default');
      foreach ($cases as $case) {
        $queue->addTasks([new PushTask('/w', [], ['header' => $case[0]])]);
        $req = end($captured)->getTask()->getAppEngineHttpRequest();
        $routing = $req->getAppEngineRouting();
        $this->assertNotNull($routing, $case[0]);
        $this->assertEquals(
            [$case[1], $case[2], $case[3]],
            [$routing->getService(), $routing->getVersion(), $routing->getInstance()],
            $case[0]);
        $this->assertFalse(isset($req->getHeaders()['Host']), $case[0]);
        $this->assertFalse(isset($req->getHeaders()['host']), $case[0]);
      }

      // Domain-scoped project ID: example.com:my-app -> my-app.example.com.appspot.com
      putenv('GOOGLE_CLOUD_PROJECT=example.com:my-app');
      $domainCases = [
        ['Host: my-app.example.com.appspot.com', 'default', '', ''],
        ['Host: worker.my-app.example.com.appspot.com', 'worker', '', ''],
        ['Host: v2-dot-worker-dot-my-app-dot-example.com.uc.r.appspot.com', 'worker', 'v2', ''],
      ];
      foreach ($domainCases as $case) {
        $queue->addTasks([new PushTask('/w', [], ['header' => $case[0]])]);
        $req = end($captured)->getTask()->getAppEngineHttpRequest();
        $routing = $req->getAppEngineRouting();
        $this->assertNotNull($routing, $case[0]);
        $this->assertEquals(
            [$case[1], $case[2], $case[3]],
            [$routing->getService(), $routing->getVersion(), $routing->getInstance()],
            $case[0]);
      }
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
      putenv('GAE_SERVICE');
      putenv('GAE_VERSION');
    }
  }

  public function testCloudTasksV2SingleCreateTaskFractionalDelay() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      $capturedReq = null;
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$capturedReq) {
        return new class($capturedReq) {
          private $capturedReqRef;
          public function __construct(&$ref) { $this->capturedReqRef = &$ref; }
          public function createTask($req) {
            $this->capturedReqRef = $req;
            return (new \Google\Cloud\Tasks\V2\Task())
                ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/frac-1');
          }
          public function close() {}
        };
      });

      MockMicrotime::expect(12345.25);
      $task = new PushTask('/worker/push', [], [
        'name' => 'frac-1',
        'delay_seconds' => 0.5,
      ]);
      $queue = new PushQueue('default');
      $queue->addTasks([$task]);

      $scheduleTime = $capturedReq->getTask()->getScheduleTime();
      $this->assertEquals(12345, $scheduleTime->getSeconds());
      $this->assertEquals(750000000, $scheduleTime->getNanos());
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2SingleCreateTaskNotFoundIsNotAlreadyExists() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function createTask($req) {
            throw new \Google\ApiCore\ApiException('Requested entity was not found.', 5, 'NOT_FOUND');
          }
          public function close() {}
        };
      });

      $task = new PushTask('/worker/push', [], ['name' => 'nf-1']);
      $queue = new PushQueue('default');
      try {
        $queue->addTasks([$task]);
        $this->fail('Expected TaskQueueException');
      } catch (TaskQueueException $e) {
        $this->assertNotInstanceOf(TaskAlreadyExistsException::class, $e);
        $this->assertStringContainsString('Requested entity was not found', $e->getMessage());
      }
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2BatchCreateTasksUsesSingleClient() {
    self::ensureCloudTasksV2StubsLoaded();
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
    try {
      $counts = ['created' => 0, 'closed' => 0];
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$counts) {
        $counts['created']++;
        return new class($counts) {
          private $countsRef;
          public function __construct(&$ref) { $this->countsRef = &$ref; }
          public function batchCreateTasks($req) {
            return new class {
              public function isDone() { return true; }
              public function operationFailed() { return false; }
              public function getMetadata() {
                return new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata();
              }
              public function getResult() {
                $t1 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/a');
                $t2 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/b');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse())
                    ->setTasks([$t1, $t2]);
              }
            };
          }
          public function close() { $this->countsRef['closed']++; }
        };
      });

      $queue = new PushQueue('default');
      $names = $queue->addTasks([
        new PushTask('/a', [], ['name' => 'a']),
        new PushTask('/b', [], ['name' => 'b']),
      ]);
      $this->assertEquals(['a', 'b'], $names);
      $this->assertEquals(1, $counts['created']);
      $this->assertEquals(0, $counts['closed']);
      // A second batch call reuses the same cached client.
      $queue->addTasks([
        new PushTask('/a', [], ['name' => 'a']),
        new PushTask('/b', [], ['name' => 'b']),
      ]);
      $this->assertEquals(1, $counts['created']);
      PushQueue::setCloudTasksClientFactory(null);
      $this->assertEquals(1, $counts['closed']);
    } finally {
      PushQueue::setCloudTasksClientFactory(null);
      putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
      putenv('GOOGLE_CLOUD_PROJECT');
      putenv('LOCATION_ID');
    }
  }

  public function testCloudTasksV2NonZeroIndexedTasksArray() {
    self::ensureCloudTasksV2StubsLoaded();
    self::setCloudTasksEnv();
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function createTask($req) {
            return (new \Google\Cloud\Tasks\V2\Task())
                ->setName($req->getTask()->getName() ?: 'projects/test-proj/locations/us-central1/queues/default/tasks/single-nz');
          }
          public function batchCreateTasks($req) {
            return new class {
              public function getMetadata() {
                $st = (new \Google\Rpc\Status())
                    ->setCode(6)
                    ->setMessage('Task already exists');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata())
                    ->setFailedRequests([1 => $st]);
              }
              public function getResult() {
                $t1 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/t1');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse())
                    ->setTasks([$t1]);
              }
            };
          }
          public function close() {}
        };
      });

      $queue = new PushQueue('default');
      $singleRes = $queue->addTasks([2 => new PushTask('/url1', [], ['name' => 'single-nz'])]);
      $this->assertEquals(['single-nz'], $singleRes);

      $this->expectException(TaskAlreadyExistsException::class);
      $queue->addTasks([
        5 => new PushTask('/url1', [], ['name' => 't1']),
        9 => new PushTask('/url2', [], ['name' => 't2']),
      ]);
    } finally {
      self::clearCloudTasksEnv();
    }
  }

  private static function setCloudTasksEnv() {
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE=true');
    putenv('GOOGLE_CLOUD_PROJECT=test-proj');
    putenv('LOCATION_ID=us-central1');
  }

  private static function clearCloudTasksEnv() {
    PushQueue::setCloudTasksClientFactory(null);
    putenv('APPENGINE_USE_CLOUDTASK_PUSH_QUEUE');
    putenv('GOOGLE_CLOUD_PROJECT');
    putenv('LOCATION_ID');
  }

  public function testCloudTasksV2DoesNotSendReservedHeaders() {
    self::ensureCloudTasksV2StubsLoaded();
    self::setCloudTasksEnv();
    try {
      $capturedReq = null;
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$capturedReq) {
        return new class($capturedReq) {
          private $capturedReqRef;
          public function __construct(&$ref) { $this->capturedReqRef = &$ref; }
          public function createTask($req) {
            $this->capturedReqRef = $req;
            return (new \Google\Cloud\Tasks\V2\Task())
                ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/h-1');
          }
          public function close() {}
        };
      });

      $task = new PushTask('/worker', ['a' => 'b'], [
        'name' => 'h-1',
        'header' => "X-AppEngine-QueueName: spoofed\r\nX-Google-Foo: x\r\n" .
            "Host: example.com\r\nX-Custom: ok",
      ]);
      (new PushQueue('default'))->addTasks([$task]);

      $headers = $capturedReq->getTask()->getAppEngineHttpRequest()->getHeaders();
      $this->assertEquals('ok', $headers['X-Custom']);
      $this->assertEquals('application/x-www-form-urlencoded', $headers['Content-Type']);
      foreach (['X-AppEngine-QueueName', 'X-AppEngine-TaskName', 'X-Google-Foo', 'Host'] as $reserved) {
        $this->assertFalse(isset($headers[$reserved]), $reserved . ' should not be sent');
      }
    } finally {
      self::clearCloudTasksEnv();
    }
  }

  public function testRegionMetadataFallbackIsNotCached() {
    $saved = [];
    foreach (['LOCATION_ID', 'GAE_LOCATION', 'GAE_REGION', 'REGION_ID', 'LOCAL_GCP_REGION'] as $var) {
      $saved[$var] = getenv($var);
      putenv($var);
    }
    try {
      $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
      $fileGetContents->expects($this->exactly(2))->willReturnOnConsecutiveCalls(
          false, 'projects/123/regions/europe-west1');
      $getRegion = new \ReflectionMethod(PushQueue::class, 'getRegion');
      $getRegion->setAccessible(true);

      // A failed metadata request falls back without caching the fallback...
      $this->assertEquals('us-central1', $getRegion->invoke(null));
      // ...so the next call asks the metadata server again and caches it.
      $this->assertEquals('europe-west1', $getRegion->invoke(null));
      $this->assertEquals('europe-west1', $getRegion->invoke(null));
    } finally {
      foreach ($saved as $var => $value) {
        putenv($value === false ? $var : "$var=$value");
      }
    }
  }

  public function testCloudTasksV2TransientErrorIsTransientException() {
    self::ensureCloudTasksV2StubsLoaded();
    self::setCloudTasksEnv();
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function createTask($req) {
            throw new \Google\ApiCore\ApiException('The service is unavailable.', 14, 'UNAVAILABLE');
          }
          public function close() {}
        };
      });

      $this->expectException(TransientTaskQueueException::class);
      (new PushQueue('default'))->addTasks([new PushTask('/worker')]);
    } finally {
      self::clearCloudTasksEnv();
    }
  }

  public function testAddTasksIndividuallyAttemptsEveryTask() {
    self::ensureCloudTasksV2StubsLoaded();
    $addTasksIndividually = new \ReflectionMethod(PushQueue::class, 'addTasksIndividually');
    $addTasksIndividually->setAccessible(true);
    $newClient = function ($errors) {
      return new class($errors) {
        public $calls = 0;
        private $errors;
        public function __construct($errors) { $this->errors = $errors; }
        public function createTask($req) {
          $error = $this->errors[$this->calls++];
          if ($error !== null) {
            throw $error;
          }
          return (new \Google\Cloud\Tasks\V2\Task())->setName($req->getTask()->getName());
        }
        public function close() {}
      };
    };
    $queueName = 'projects/test-proj/locations/us-central1/queues/default';
    $tasks = [
      new PushTask('/a', [], ['name' => 'a']),
      new PushTask('/b', [], ['name' => 'b']),
      new PushTask('/c', [], ['name' => 'c']),
    ];
    $alreadyExists = new \Google\ApiCore\ApiException('already exists', 6, 'ALREADY_EXISTS');
    $notFound = new \Google\ApiCore\ApiException('Queue does not exist.', 5, 'NOT_FOUND');

    // A duplicate does not stop the remaining tasks from being added.
    $client = $newClient([$alreadyExists, null, null]);
    try {
      $addTasksIndividually->invoke(new PushQueue('default'), $tasks, $queueName, $client);
      $this->fail('Expected TaskAlreadyExistsException');
    } catch (TaskAlreadyExistsException $e) {
      $this->assertEquals(3, $client->calls);
    }

    // Another error takes precedence over TaskAlreadyExistsException.
    $client = $newClient([$alreadyExists, $notFound, null]);
    try {
      $addTasksIndividually->invoke(new PushQueue('default'), $tasks, $queueName, $client);
      $this->fail('Expected TaskQueueException');
    } catch (TaskQueueException $e) {
      $this->assertNotInstanceOf(TaskAlreadyExistsException::class, $e);
      $this->assertEquals(3, $client->calls);
    }

    $client = $newClient([null, null, null]);
    $this->assertEquals(['a', 'b', 'c'], $addTasksIndividually->invoke(
        new PushQueue('default'), $tasks, $queueName, $client));
  }

  public function testCloudTasksV2BatchCreateTasksPositionalSignatureAndTypeErrorPropagation() {
    self::ensureCloudTasksV2StubsLoaded();
    self::setCloudTasksEnv();
    try {
      $capturedParent = null;
      PushQueue::setCloudTasksClientFactory(function ($className) use (&$capturedParent) {
        return new class($capturedParent) {
          private $parentRef;
          public function __construct(&$ref) { $this->parentRef = &$ref; }
          public function batchCreateTasks($parent, $requests) {
            $this->parentRef = $parent;
            return new class {
              public function getMetadata() {
                return new \Google\Cloud\Tasks\V2\BatchCreateTasksMetadata();
              }
              public function getResult() {
                $t1 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/p1');
                $t2 = (new \Google\Cloud\Tasks\V2\Task())
                    ->setName('projects/test-proj/locations/us-central1/queues/default/tasks/p2');
                return (new \Google\Cloud\Tasks\V2\BatchCreateTasksResponse())
                    ->setTasks([$t1, $t2]);
              }
            };
          }
          public function close() {}
        };
      });

      $queue = new PushQueue('default');
      $names = $queue->addTasks([new PushTask('/a'), new PushTask('/b')]);
      $this->assertEquals(['p1', 'p2'], $names);
      $this->assertEquals('projects/test-proj/locations/us-central1/queues/default', $capturedParent);
    } finally {
      self::clearCloudTasksEnv();
    }
  }

  public function testCloudTasksV2BatchCreateTasksNotDoneThrowsWithoutPolling() {
    self::ensureCloudTasksV2StubsLoaded();
    self::setCloudTasksEnv();
    try {
      PushQueue::setCloudTasksClientFactory(function ($className) {
        return new class {
          public function batchCreateTasks($req) {
            return new class {
              public function isDone() { return false; }
              public function pollUntilComplete() {
                throw new \RuntimeException('pollUntilComplete should never be called');
              }
            };
          }
          public function close() {}
        };
      });

      $this->expectException(TaskQueueException::class);
      $this->expectExceptionMessage('Cloud Tasks batch create operation returned done=false');
      $queue = new PushQueue('default');
      $queue->addTasks([new PushTask('/a'), new PushTask('/b')]);
    } finally {
      self::clearCloudTasksEnv();
    }
  }

}
