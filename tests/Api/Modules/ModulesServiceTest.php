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
 * Unit tests for the Modules API.
 *
 */

namespace Google\AppEngine\Api\Modules;

use Google\AppEngine\Runtime\ApplicationError;
use Google\AppEngine\Testing\ApiProxyTestBase;
use google\appengine\GetDefaultVersionRequest;
use google\appengine\GetDefaultVersionResponse;
use google\appengine\GetHostnameRequest;
use google\appengine\GetHostnameResponse;
use google\appengine\GetModulesRequest;
use google\appengine\GetModulesResponse;
use google\appengine\GetNumInstancesRequest;
use google\appengine\GetNumInstancesResponse;
use google\appengine\GetVersionsRequest;
use google\appengine\GetVersionsResponse;
use google\appengine\ModulesServiceError\ErrorCode;
use google\appengine\SetNumInstancesRequest;
use google\appengine\SetNumInstancesResponse;
use google\appengine\StartModuleRequest;
use google\appengine\StartModuleResponse;
use google\appengine\StopModuleRequest;
use google\appengine\StopModuleResponse;

class ModulesTest extends ApiProxyTestBase {

  public function setUp(): void {
    parent::setUp();
    $this->_SERVER = $_SERVER;
    $_SERVER['GOOGLE_CLOUD_PROJECT'] = 'test-project';
  }

  public function tearDown(): void {
    $_SERVER = $this->_SERVER;
    putenv('MODULES_USE_ADMIN_API');
    ModulesService::setAdminServiceForTesting(null);
    parent::tearDown();
  }

  public function testGetCurrentModuleNameWithDefaultModule() {
    $_SERVER['GAE_SERVICE'] = 'default';
    $_SERVER['GAE_VERSION'] = 'v1.123';
    $this->assertEquals('default', ModulesService::getCurrentModuleName());
  }

  public function testGetCurrentModuleNameWithNonDefaultModule() {
    $_SERVER['GAE_SERVICE'] = 'module1';
    $_SERVER['GAE_VERSION'] = 'v1.123';
    $this->assertEquals('module1', ModulesService::getCurrentModuleName());
  }

  public function testGetCurrentVersionName() {
    $_SERVER['GAE_VERSION'] = 'v1.123';
    $this->assertEquals('v1', ModulesService::getCurrentVersionName());
  }

  public function testGetCurrentInstanceId() {
    $_SERVER['GAE_INSTANCE'] = '123';
    $this->assertEquals('123', ModulesService::getCurrentInstanceId());
  }
  
  public function testGetModulesAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');

    // 1. Mock the Service objects
    $service1 = $this->createMock('Google_Service_Appengine_Service');
    $service1->method('getId')->willReturn('module1');
    
    $service2 = $this->createMock('Google_Service_Appengine_Service');
    $service2->method('getId')->willReturn('module2');

    // 2. Mock the ListServicesResponse
    $response = $this->createMock('Google_Service_Appengine_ListServicesResponse');
    $response->method('getServices')->willReturn([$service1, $service2]);

    // 3. Mock the AppsServices resource
    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('listAppsServices')
                 ->with('test-project')
                 ->willReturn($response);

    // 4. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    // Inject the mock
    ModulesService::setAdminServiceForTesting($adminService);

    // Execute and Verify
    $modules = ModulesService::getModules();
    $this->assertEquals(['module1', 'module2'], $modules);
  }

  /**
   * Tests that getModules throws a ModulesException if the Admin API call fails.
   */
  public function testGetModulesAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('listAppsServices')
                 ->willThrowException(new \Exception("Admin API Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Error");

    ModulesService::getModules();
  }

  public function testGetModulesLegacy() {
    $req = new GetModulesRequest();
    $resp = new GetModulesResponse();

    $resp->addModule('module1');
    $resp->addModule('module2');

    $this->apiProxyMock->expectCall('modules', 'GetModules', $req, $resp);

    $this->assertEquals(['module1', 'module2'], ModulesService::getModules());
    $this->apiProxyMock->verify();
  }
  
    /**
   * Tests that getVersions correctly lists versions for a module using the Admin API.
   */
  public function testGetVersionsAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');
    $targetModule = 'module1';

    // 1. Mock the Version objects
    $version1 = $this->createMock('Google_Service_Appengine_Version');
    $version1->method('getId')->willReturn('v1');
    
    $version2 = $this->createMock('Google_Service_Appengine_Version');
    $version2->method('getId')->willReturn('v2');

    // 2. Mock the ListVersionsResponse
    // Note: The specific class name for version list response is Google_Service_Appengine_ListVersionsResponse
    $response = $this->createMock('Google_Service_Appengine_ListVersionsResponse');
    $response->method('getVersions')->willReturn([$version1, $version2]);

    // 3. Mock the AppsServicesVersions resource
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('listAppsServicesVersions')
                     ->with('test-project', $targetModule)
                     ->willReturn($response);

    // 4. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    // Execute and Verify
    $versions = ModulesService::getVersions($targetModule);
    $this->assertEquals(['v1', 'v2'], $versions);
  }

  /**
   * Tests getVersions with Admin API when no module is specified (uses current).
   */
  public function testGetVersionsAdminApiDefaultModule() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default';

    $version = $this->createMock('Google_Service_Appengine_Version');
    $version->method('getId')->willReturn('v1');

    $response = $this->createMock('Google_Service_Appengine_ListVersionsResponse');
    $response->method('getVersions')->willReturn([$version]);

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('listAppsServicesVersions')
                     ->with('test-project', 'default')
                     ->willReturn($response);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $versions = ModulesService::getVersions(); // No module argument
    $this->assertEquals(['v1'], $versions);
  }

  /**
   * Tests that getVersions throws a ModulesException on Admin API failure.
   */
  public function testGetVersionsAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('listAppsServicesVersions')
                     ->willThrowException(new \Exception("Admin API list failure"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API list failure");

    ModulesService::getVersions('module1');
  }


  public function testGetVersionsLegacy() {
    $req = new GetVersionsRequest();
    $resp = new GetVersionsResponse();

    $resp->addVersion('v1');
    $resp->addVersion('v2');

    $this->apiProxyMock->expectCall('modules', 'GetVersions', $req, $resp);

    $this->assertEquals(['v1', 'v2'], ModulesService::getVersions());
    $this->apiProxyMock->verify();
  }

  public function testGetVersionsLegacyWithModule() {
    $req = new GetVersionsRequest();
    $resp = new GetVersionsResponse();

    $req->setModule('module1');
    $resp->addVersion('v1');
    $resp->addVersion('v2');

    $this->apiProxyMock->expectCall('modules', 'GetVersions', $req, $resp);

    $this->assertEquals(['v1', 'v2'], ModulesService::getVersions('module1'));
    $this->apiProxyMock->verify();
  }

  public function testGetVersionsLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::getVersions(5);
  }
  
    /**
   * Tests success when a single version has 100% (1.0) traffic allocation.
   */
  public function testGetDefaultVersionAdminApiSuccess100Percent() {
    putenv('MODULES_USE_ADMIN_API=true');
    $targetModule = 'module1';

    $trafficSplit = $this->createMock('Google_Service_Appengine_TrafficSplit');
    $trafficSplit->method('getAllocations')->willReturn(['v1' => 1.0]);

    $serviceConfig = $this->createMock('Google_Service_Appengine_Service');
    $serviceConfig->method('getSplit')->willReturn($trafficSplit);

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('get')
                 ->with('test-project', $targetModule)
                 ->willReturn($serviceConfig);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->assertEquals('v1', ModulesService::getDefaultVersion($targetModule));
  }

  /**
   * Tests success when traffic is split; the version with the highest allocation wins.
   */
  public function testGetDefaultVersionAdminApiSuccessSplit() {
    putenv('MODULES_USE_ADMIN_API=true');

    $trafficSplit = $this->createMock('Google_Service_Appengine_TrafficSplit');
    $trafficSplit->method('getAllocations')->willReturn([
        'v1' => 0.3,
        'v2' => 0.6,
        'v3' => 0.1
    ]);

    $serviceConfig = $this->createMock('Google_Service_Appengine_Service');
    $serviceConfig->method('getSplit')->willReturn($trafficSplit);

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('get')->willReturn($serviceConfig);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    // v2 has 0.6 allocation which is the maximum
    $this->assertEquals('v2', ModulesService::getDefaultVersion('module1'));
  }

  /**
   * Tests tie-breaking logic where the lexicographically smaller version ID wins.
   */
  public function testGetDefaultVersionAdminApiSuccessTieBreak() {
    putenv('MODULES_USE_ADMIN_API=true');

    $trafficSplit = $this->createMock('Google_Service_Appengine_TrafficSplit');
    $trafficSplit->method('getAllocations')->willReturn([
        'version-b' => 0.5,
        'version-a' => 0.5
    ]);

    $serviceConfig = $this->createMock('Google_Service_Appengine_Service');
    $serviceConfig->method('getSplit')->willReturn($trafficSplit);

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('get')->willReturn($serviceConfig);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    // Both have 0.5, 'version-a' is lexicographically smaller than 'version-b'
    $this->assertEquals('version-a', ModulesService::getDefaultVersion('module1'));
  }

  /**
   * Tests that a ModulesException is thrown if allocations are empty.
   */
  public function testGetDefaultVersionAdminApiNoAllocations() {
    putenv('MODULES_USE_ADMIN_API=true');

    $trafficSplit = $this->createMock('Google_Service_Appengine_TrafficSplit');
    $trafficSplit->method('getAllocations')->willReturn([]);

    $serviceConfig = $this->createMock('Google_Service_Appengine_Service');
    $serviceConfig->method('getSplit')->willReturn($trafficSplit);

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('get')->willReturn($serviceConfig);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Could not determine default version for module 'module1'.");
    
    ModulesService::getDefaultVersion('module1');
  }

  /**
   * Tests that API exceptions are correctly wrapped in ModulesException.
   */
  public function testGetDefaultVersionAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('get')
                 ->willThrowException(new \Exception("Admin API Get Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services = $appsServices;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Get Error");

    ModulesService::getDefaultVersion('module1');
  }
  
    /**
   * Tests that getNumInstances correctly retrieves instance count using the Admin API.
   */
  public function testGetNumInstancesAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');
    $targetModule = 'module1';
    $targetVersion = 'v1';

    // 1. Mock the ManualScaling and Version objects
    $manualScaling = $this->createMock('Google_Service_Appengine_ManualScaling');
    $manualScaling->method('getInstances')->willReturn(5);

    $version = $this->createMock('Google_Service_Appengine_Version');
    $version->method('getManualScaling')->willReturn($manualScaling);

    // 2. Mock the AppsServicesVersions resource
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('get')
                     ->with('test-project', $targetModule, $targetVersion)
                     ->willReturn($version);

    // 3. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    // Execute and Verify
    $instances = ModulesService::getNumInstances($targetModule, $targetVersion);
    $this->assertEquals(5, $instances);
  }

  /**
   * Tests getNumInstances using Admin API with default module/version from environment.
   */
  public function testGetNumInstancesAdminApiDefaults() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default-module';
    $_SERVER['GAE_VERSION'] = 'v2.12345';

    $manualScaling = $this->createMock('Google_Service_Appengine_ManualScaling');
    $manualScaling->method('getInstances')->willReturn(3);

    $version = $this->createMock('Google_Service_Appengine_Version');
    $version->method('getManualScaling')->willReturn($manualScaling);

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('get')
                     ->with('test-project', 'default-module', 'v2') // Expects parsed version 'v2'
                     ->willReturn($version);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $instances = ModulesService::getNumInstances(); // No arguments provided
    $this->assertEquals(3, $instances);
  }

  /**
   * Tests that getNumInstances throws a ModulesException if the Admin API call fails.
   */
  public function testGetNumInstancesAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('get')
                     ->willThrowException(new \Exception("Admin API Get Version Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Get Version Error");

    ModulesService::getNumInstances('module1', 'v1');
  }



  public function testGetNumInstancesLegacy() {
    $req = new GetNumInstancesRequest();
    $resp = new GetNumInstancesResponse();

    $resp->setInstances(3);

    $this->apiProxyMock->expectCall('modules', 'GetNumInstances', $req, $resp);

    $this->assertEquals(3, ModulesService::getNumInstances());
    $this->apiProxyMock->verify();
  }

  public function testGetNumInstancesLegacyWithModuleAndVersion() {
    $req = new GetNumInstancesRequest();
    $resp = new GetNumInstancesResponse();

    $req->setModule('module1');
    $req->setVersion('v1');
    $resp->setInstances(3);

    $this->apiProxyMock->expectCall('modules', 'GetNumInstances', $req, $resp);

    $this->assertEquals(3, ModulesService::getNumInstances('module1', 'v1'));
    $this->apiProxyMock->verify();
  }

  public function testGetNumInstancesLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::getNumInstances(5);
  }

  public function testGetNumInstancesLegacyWithIntegerVersion() {
    $this->expectException('\InvalidArgumentException',
      '$version must be a string. Actual type: integer');
    ModulesService::getNumInstances('module1', 5);
  }

  public function testGetNumInstancesLegacyInvalidModule() {
    $req = new GetNumInstancesRequest();
    $resp = new ApplicationError(ErrorCode::INVALID_MODULE, 'invalid module');

    $this->expectException(
        '\Google\AppEngine\Api\Modules\ModulesException');
    $this->apiProxyMock->expectCall('modules', 'GetNumInstances', $req, $resp);

    $this->assertEquals(3, ModulesService::getNumInstances());
    $this->apiProxyMock->verify();
  }
  
    /**
   * Tests that setNumInstances correctly patches the version using the Admin API.
   */
  public function testSetNumInstancesAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');
    $instances = 10;
    $targetModule = 'module1';
    $targetVersion = 'v1';

    // 1. Mock the AppsServicesVersions resource
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    
    // 2. Set up expectation for the patch call
    $versionsResource->expects($this->once())
                     ->method('patch')
                     ->with(
                         $this->equalTo('test-project'),
                         $this->equalTo($targetModule),
                         $this->equalTo($targetVersion),
                         $this->callback(function($v) use ($instances) {
                             // Verify the Version object has the correct ManualScaling instances set
                             return $v instanceof \Google_Service_Appengine_Version &&
                                    $v->getManualScaling()->getInstances() === $instances;
                         }),
                         $this->equalTo(['updateMask' => 'manualScaling.instances'])
                     );

    // 3. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    // Execute
    ModulesService::setNumInstances($instances, $targetModule, $targetVersion);
  }

  /**
   * Tests setNumInstances using Admin API with default module/version.
   */
  public function testSetNumInstancesAdminApiDefaults() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default-module';
    $_SERVER['GAE_VERSION'] = 'v2.98765';
    $instances = 3;

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->expects($this->once())
                     ->method('patch')
                     ->with(
                         'test-project',
                         'default-module',
                         'v2', // Expects parsed version
                         $this->anything(),
                         ['updateMask' => 'manualScaling.instances']
                     );

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    ModulesService::setNumInstances($instances);
  }

  /**
   * Tests that setNumInstances throws a ModulesException if the patch operation fails.
   */
  public function testSetNumInstancesAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('patch')
                     ->willThrowException(new \Exception("Admin API Patch Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Patch Error");

    ModulesService::setNumInstances(5, 'module1', 'v1');
  }


  public function testSetNumInstancesLegacy() {
    $req = new SetNumInstancesRequest();
    $resp = new SetNumInstancesResponse();

    $req->setInstances(3);

    $this->apiProxyMock->expectCall('modules', 'SetNumInstances', $req, $resp);

    ModulesService::setNumInstances(3);
    $this->apiProxyMock->verify();
  }

  public function testSetNumInstancesLegacyWithModuleAndVersion() {
    $req = new SetNumInstancesRequest();
    $resp = new SetNumInstancesResponse();

    $req->setInstances(3);

    $this->apiProxyMock->expectCall('modules', 'SetNumInstances', $req, $resp);

    ModulesService::setNumInstances(3);
    $this->apiProxyMock->verify();
  }

  public function testSetNumInstancesLegacyWithStringInstances() {
    $this->expectException('\InvalidArgumentException',
      '$instances must be an integer. Actual type: string');
    ModulesService::setNumInstances('hello');
  }

  public function testSetNumInstancesLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::setNumInstances(5, 10);
  }

  public function testSetNumInstancesLegacyWithIntegerVersion() {
    $this->expectException('\InvalidArgumentException',
      '$version must be a string. Actual type: integer');
    ModulesService::setNumInstances(5, 'module1', 5);
  }

  public function testSetNumInstancesLegacyInvalidVersion() {
    $req = new SetNumInstancesRequest();
    $resp = new ApplicationError(ErrorCode::INVALID_VERSION, 'invalid version');

    $req->setInstances(3);

    $this->expectException(
        '\Google\AppEngine\Api\Modules\ModulesException');
    $this->apiProxyMock->expectCall('modules', 'SetNumInstances', $req, $resp);

    ModulesService::setNumInstances(3);
    $this->apiProxyMock->verify();
  }
  
    /**
   * Tests that startVersion correctly patches the serving status to SERVING.
   */
  public function testStartVersionAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');
    $targetModule = 'module1';
    $targetVersion = 'v1';

    // 1. Mock the AppsServicesVersions resource
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    
    // 2. Set up expectation for the patch call
    $versionsResource->expects($this->once())
                     ->method('patch')
                     ->with(
                         $this->equalTo('test-project'),
                         $this->equalTo($targetModule),
                         $this->equalTo($targetVersion),
                         $this->callback(function($v) {
                             // Verify the Version object has servingStatus set to SERVING
                             return $v instanceof \Google_Service_Appengine_Version &&
                                    $v->getServingStatus() === 'SERVING';
                         }),
                         $this->equalTo(['updateMask' => 'servingStatus'])
                     );

    // 3. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    // Execute
    ModulesService::startVersion($targetModule, $targetVersion);
  }

  /**
   * Tests startVersion with Admin API when the patch operation fails.
   */
  public function testStartVersionAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('patch')
                     ->willThrowException(new \Exception("Admin API Patch Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Patch Error");

    ModulesService::startVersion('module1', 'v1');
  }


  public function testStartModuleLegacy() {
    $req = new StartModuleRequest();
    $resp = new StartModuleResponse();

    $req->setModule('module1');
    $req->setVersion('v1');

    $this->apiProxyMock->expectCall('modules', 'StartModule', $req, $resp);

    ModulesService::startVersion('module1', 'v1');
    $this->apiProxyMock->verify();
  }

  public function testStartModuleLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::startVersion(5, 'v1');
  }

  public function testStartModuleLegacyWithIntegerVersion() {
    $this->expectException('\InvalidArgumentException',
      '$version must be a string. Actual type: integer');
    ModulesService::startVersion('module1', 5);
  }

  public function testStartModuleLegacyWithTransientError() {
    $req = new StartModuleRequest();
    $resp = new ApplicationError(ErrorCode::TRANSIENT_ERROR,
                                 'invalid version');

    $req->setModule('module1');
    $req->setVersion('v1');

    $this->expectException(
        '\Google\AppEngine\Api\Modules\TransientModulesException');
    $this->apiProxyMock->expectCall('modules', 'StartModule', $req, $resp);

    ModulesService::startVersion('module1', 'v1');
    $this->apiProxyMock->verify();
  }
  
    /**
   * Tests that stopVersion correctly patches the serving status to STOPPED.
   */
  public function testStopVersionAdminApiSuccess() {
    putenv('MODULES_USE_ADMIN_API=true');
    $targetModule = 'module1';
    $targetVersion = 'v1';

    // 1. Mock the AppsServicesVersions resource
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    
    // 2. Set up expectation for the patch call
    $versionsResource->expects($this->once())
                     ->method('patch')
                     ->with(
                         $this->equalTo('test-project'),
                         $this->equalTo($targetModule),
                         $this->equalTo($targetVersion),
                         $this->callback(function($v) {
                             // Verify the Version object has servingStatus set to STOPPED
                             return $v instanceof \Google_Service_Appengine_Version &&
                                    $v->getServingStatus() === 'STOPPED';
                         }),
                         $this->equalTo(['updateMask' => 'servingStatus'])
                     );

    // 3. Mock the main App Engine Service client
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    // Execute
    ModulesService::stopVersion($targetModule, $targetVersion);
  }

  /**
   * Tests stopVersion using Admin API with default module/version.
   */
  public function testStopVersionAdminApiDefaults() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default-module';
    $_SERVER['GAE_VERSION'] = 'v2.123';

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->expects($this->once())
                     ->method('patch')
                     ->with(
                         'test-project',
                         'default-module',
                         'v2', // Expects parsed version
                         $this->anything(),
                         ['updateMask' => 'servingStatus']
                     );

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    ModulesService::stopVersion();
  }

  /**
   * Tests startVersion with Admin API when the patch operation fails.
   */
  public function testStopVersionAdminApiFailure() {
    putenv('MODULES_USE_ADMIN_API=true');

    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('patch')
                     ->willThrowException(new \Exception("Admin API Patch Error"));

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Admin API Patch Error");

    ModulesService::stopVersion('module1', 'v1');
  }


  public function testStopModuleLegacy() {
    $req = new StopModuleRequest();
    $resp = new StopModuleResponse();

    $this->apiProxyMock->expectCall('modules', 'StopModule', $req, $resp);

    ModulesService::stopVersion();
    $this->apiProxyMock->verify();
  }

  public function testStopModuleLegacyWithModuleAndVersion() {
    $req = new StopModuleRequest();
    $resp = new StopModuleResponse();

    $req->setModule('module1');
    $req->setVersion('v1');

    $this->apiProxyMock->expectCall('modules', 'StopModule', $req, $resp);

    ModulesService::stopVersion('module1', 'v1');
    $this->apiProxyMock->verify();
  }

  public function testStopModuleLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::stopVersion(5, 'v1');
  }

  public function testStopModuleLegacyWithIntegerVersion() {
    $this->expectException('\InvalidArgumentException',
      '$version must be a string. Actual type: integer');
    ModulesService::stopVersion('module1', 5);
  }

  public function testStopModuleLegacyWithTransientError() {
    $req = new StopModuleRequest();
    $resp = new ApplicationError(ErrorCode::TRANSIENT_ERROR,
                                 'invalid version');

    $req->setModule('module1');
    $req->setVersion('v1');

    $this->expectException(
        '\Google\AppEngine\Api\Modules\TransientModulesException');
    $this->apiProxyMock->expectCall('modules', 'StopModule', $req, $resp);

    ModulesService::stopVersion('module1', 'v1');
    $this->apiProxyMock->verify();
  }
  
    /**
   * Tests hostname construction for a legacy app with a single 'default' module.
   */
  public function testGetHostnameAdminApiLegacyApp() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default';
    $_SERVER['GAE_VERSION'] = 'v1.123';

    // Mock response for apps->get()
    $app = $this->createMock('Google_Service_Appengine_Application');
    $app->method('getDefaultHostname')->willReturn('myapp.appspot.com');
    $appsResource = $this->createMock('Google_Service_Appengine_Resource_Apps');
    $appsResource->method('get')->with('test-project')->willReturn($app);

    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps = $appsResource;

    // Mock getModules to return only 'default'
    // This requires a mock of the AppsServices resource as well
    $response = $this->createMock('Google_Service_Appengine_ListServicesResponse');
    $s = $this->createMock('Google_Service_Appengine_Service');
    $s->method('getId')->willReturn('default');
    $response->method('getServices')->willReturn([$s]);
    $adminService->apps_services = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $adminService->apps_services->method('listAppsServices')->willReturn($response);

    ModulesService::setAdminServiceForTesting($adminService);

    // 1. Load-balanced request
    $this->assertEquals('v1.myapp.appspot.com', ModulesService::getHostname());

    // 2. Instance-specific request
    $this->assertEquals('0.v1.myapp.appspot.com', ModulesService::getHostname(null, null, 0));
  }

  /**
   * Tests instance-specific hostname construction for a manually scaled service.
   */
  public function testGetHostnameAdminApiManualScaling() {
    putenv('MODULES_USE_ADMIN_API=true');
    $module = 'module1';
    $version = 'v1';
    $instance = 2;

    $app = $this->createMock('Google_Service_Appengine_Application');
    $app->method('getDefaultHostname')->willReturn('myapp.appspot.com');
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps = $this->createMock('Google_Service_Appengine_Resource_Apps');
    $adminService->apps->method('get')->willReturn($app);

    // Mock getModules to return multiple services (non-legacy)
    $res = $this->createMock('Google_Service_Appengine_ListServicesResponse');
    $s1 = $this->createMock('Google_Service_Appengine_Service'); $s1->method('getId')->willReturn('default');
    $s2 = $this->createMock('Google_Service_Appengine_Service'); $s2->method('getId')->willReturn('module1');
    $res->method('getServices')->willReturn([$s1, $s2]);
    $adminService->apps_services = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $adminService->apps_services->method('listAppsServices')->willReturn($res);

    // Mock the Version details for manual scaling check
    $ms = $this->createMock('Google_Service_Appengine_ManualScaling');
    $ms->method('getInstances')->willReturn(5);
    $v = $this->createMock('Google_Service_Appengine_Version');
    $v->method('getManualScaling')->willReturn($ms);
    
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('get')
                     ->with('test-project', $module, $version, ['view' => 'FULL'])
                     ->willReturn($v);
    $adminService->apps_services_versions = $versionsResource;

    ModulesService::setAdminServiceForTesting($adminService);

    $expected = '2.v1.module1.myapp.appspot.com';
    $this->assertEquals($expected, ModulesService::getHostname($module, $version, $instance));
  }

  /**
   * Tests fallback logic when no version is provided and current version doesn't exist in target module.
   */
  public function testGetHostnameAdminApiVersionFallback() {
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GAE_SERVICE'] = 'default';
    $_SERVER['GAE_VERSION'] = 'current-v.123';
    $targetModule = 'other-module';

    $app = $this->createMock('Google_Service_Appengine_Application');
    $app->method('getDefaultHostname')->willReturn('myapp.appspot.com');
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps = $this->createMock('Google_Service_Appengine_Resource_Apps');
    $adminService->apps->method('get')->willReturn($app);

    // Mock services list
    $res = $this->createMock('Google_Service_Appengine_ListServicesResponse');
    $s = $this->createMock('Google_Service_Appengine_Service'); $s->method('getId')->willReturn($targetModule);
    $res->method('getServices')->willReturn([$s]);
    $adminService->apps_services = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $adminService->apps_services->method('listAppsServices')->willReturn($res);

    // Mock target module versions (does NOT contain 'current-v')
    $vRes = $this->createMock('Google_Service_Appengine_ListVersionsResponse');
    $v = $this->createMock('Google_Service_Appengine_Version'); $v->method('getId')->willReturn('prod-v');
    $vRes->method('getVersions')->willReturn([$v]);
    $adminService->apps_services_versions = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $adminService->apps_services_versions->method('listAppsServicesVersions')->willReturn($vRes);

    ModulesService::setAdminServiceForTesting($adminService);

    // Since 'current-v' is not in 'other-module', it should return hostname without version
    $this->assertEquals('other-module.myapp.appspot.com', ModulesService::getHostname($targetModule));
  }

  /**
   * Tests that getHostname fails if an instance is requested for a non-manually scaled service.
   */
  public function testGetHostnameAdminApiInvalidScalingError() {
    // Enable the Admin API path
    putenv('MODULES_USE_ADMIN_API=true');
    $_SERVER['GOOGLE_CLOUD_PROJECT'] = 'test-project';
    
    // 1. Mock the App Engine Application (for default hostname retrieval)
    $app = $this->createMock('Google_Service_Appengine_Application');
    $app->method('getDefaultHostname')->willReturn('myapp.appspot.com');
    
    $appsResource = $this->createMock('Google_Service_Appengine_Resource_Apps');
    $appsResource->method('get')->with('test-project')->willReturn($app);

    // 2. Mock the Services List (to prevent foreach(null) in getModules)
    // The previous error occurred because this mock returned null by default.
    $listServicesResponse = $this->createMock('Google_Service_Appengine_ListServicesResponse');
    $listServicesResponse->method('getServices')->willReturn([]); // Return empty array
    
    $appsServices = $this->createMock('Google_Service_Appengine_Resource_AppsServices');
    $appsServices->method('listAppsServices')->willReturn($listServicesResponse);

    // 3. Mock a Version that is NOT manually scaled
    // This triggers the specific error we are testing for.
    $version = $this->createMock('Google_Service_Appengine_Version');
    $version->method('getManualScaling')->willReturn(null);
    
    $versionsResource = $this->createMock('Google_Service_Appengine_Resource_AppsServicesVersions');
    $versionsResource->method('get')
                     ->with('test-project', 'm1', 'v1', ['view' => 'FULL'])
                     ->willReturn($version);

    // 4. Assemble the main Admin Service mock
    $adminService = $this->createMock('Google_Service_Appengine');
    $adminService->apps = $appsResource;
    $adminService->apps_services = $appsServices;
    $adminService->apps_services_versions = $versionsResource;

    // Inject the mock into the service
    ModulesService::setAdminServiceForTesting($adminService);

    // 5. Assert that the specific ModulesException is thrown
    $this->expectException(ModulesException::class);
    $this->expectExceptionMessage("Instance-specific hostnames are only available for manually scaled services.");
    
    // Execute the call that should trigger the exception
    ModulesService::getHostname('m1', 'v1', 0);
  }

  public function testGetHostnameLegacy() {
    $req = new GetHostnameRequest();
    $resp = new GetHostnameResponse();

    $resp->setHostname('hostname');

    $this->apiProxyMock->expectCall('modules', 'GetHostname', $req, $resp);

    $this->assertEquals('hostname', ModulesService::getHostname());
    $this->apiProxyMock->verify();
  }

  public function testGetHostnameLegacyWithModuleVersionAndIntegerInstance() {
    $req = new GetHostnameRequest();
    $resp = new GetHostnameResponse();

    $req->setModule('module1');
    $req->setVersion('v1');
    $req->setInstance('73');
    $resp->setHostname('hostname');

    $this->apiProxyMock->expectCall('modules', 'GetHostname', $req, $resp);

    $this->assertEquals('hostname',
                        ModulesService::getHostname('module1', 'v1', 73));
    $this->apiProxyMock->verify();
  }

  public function testGetHostnameLegacyWithModuleVersionAndStringInstance() {
    $req = new GetHostnameRequest();
    $resp = new GetHostnameResponse();

    $req->setModule('module1');
    $req->setVersion('v1');
    $req->setInstance('73');
    $resp->setHostname('hostname');

    $this->apiProxyMock->expectCall('modules', 'GetHostname', $req, $resp);

    $this->assertEquals('hostname',
                        ModulesService::getHostname('module1', 'v1', '73'));
    $this->apiProxyMock->verify();
  }

  public function testGetHostnameLegacyWithIntegerModule() {
    $this->expectException('\InvalidArgumentException',
      '$module must be a string. Actual type: integer');
    ModulesService::getHostname(5);
  }

  public function testGetHostnameLegacyWithIntegerVersion() {
    $this->expectException('\InvalidArgumentException',
      '$version must be a string. Actual type: integer');
    ModulesService::getHostname('module1', 5);
  }

  public function testGetHostnameLegacyWithArrayInstance() {
    $this->expectException('\InvalidArgumentException',
      '$instance must be an integer or string. Actual type: array');
    ModulesService::getHostname('module1', 'v1', []);
  }

  public function testGetHostnameLegacyWithInvalidInstancesError() {
    $req = new GetHostnameRequest();
    $resp = new ApplicationError(ErrorCode::INVALID_INSTANCES,
                                 'invalid instances');

    $this->expectException(
        '\Google\AppEngine\Api\Modules\ModulesException');
    $this->apiProxyMock->expectCall('modules', 'GetHostname', $req, $resp);

    $this->assertEquals('hostname', ModulesService::getHostname());
    $this->apiProxyMock->verify();
  }
}
