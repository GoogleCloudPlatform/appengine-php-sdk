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
 * An API for fetching information about and controlling App Engine Modules.
 *
 */

namespace Google\AppEngine\Api\Modules;

use Google\AppEngine\Runtime\ApiProxy;
use Google\AppEngine\Runtime\ApplicationError;
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

final class ModulesService {
  private static $adminService = null;

  /** @internal */
  public static function setAdminServiceForTesting($service) {
    self::$adminService = $service;
  }
  
  private static function errorCodeToException($error) {
    switch($error) {
      case ErrorCode::INVALID_MODULE:
        return new ModulesException('Invalid module.');
      case ErrorCode::INVALID_VERSION:
        return new ModulesException('Invalid version.');
      case ErrorCode::INVALID_INSTANCES:
        return new ModulesException('Invalid instances.');
      case ErrorCode::TRANSIENT_ERROR:
        return new TransientModulesException('Temporary error, please re-try');
      case ErrorCode::UNEXPECTED_STATE:
        return new InvalidModuleStateException('Module in an unexpected state');
      default:
        return new ModulesException('Error Code: ' . $error);
    }
  }

  /**
   * Gets the name of the currently running module.
   *
   * @return string The name of the current module. For example, if this is
   * version "v1" of module "module5" for app "my-app", this function
   * will return "module5".
   */
  public static function getCurrentModuleName() {
    return $_SERVER['GAE_SERVICE'];
  }

  /**
   * Gets the version of the currently running module.
   *
   * @return string The name of the current module. For example, if this is
   * version "v1" of module "module5" for app "my-app", this function
   * will return "v1".
   */
  public static function getCurrentVersionName() {
    return explode('.', $_SERVER['GAE_VERSION'])[0];
  }

  /**
   * Gets the id of the currently running instance.
   *
   * @return string The name of the current module. For example, if this is
   * instance 2 of version "v1" of module "module5" for app "my-app", this
   * function will return "2". For automatically-scaled modules, this function
   * will return a unique hex string for the instance (e.g.
   * "00c61b117c7f7fd0ce9e1325a04b8f0df30deaaf").
   */
  public static function getCurrentInstanceId() {
    return $_SERVER['GAE_INSTANCE'];
  }
  
  private static function useAdminApi() {
    return strtolower(getenv('MODULES_USE_ADMIN_API')) === 'true';
  }

  private static function getAdminService() {
    if (self::$adminService !== null) {
      return self::$adminService;
    }
    static $service = null;
    if ($service === null) {
      $client = new \Google_Client();
      $client->useApplicationDefaultCredentials();
      $client->addScope('https://www.googleapis.com/auth/cloud-platform');
      $service = new \Google_Service_Appengine($client);
    }
    return $service;
  }
  
  /**
 * Returns the project ID for the current application.
 * 
 * @return string|null The project ID or null if not found.
 */
    private static function getProjectId() {
    // Check $_SERVER first to support the SDK's testing pattern
    $projectId = isset($_SERVER['GOOGLE_CLOUD_PROJECT']) ? $_SERVER['GOOGLE_CLOUD_PROJECT'] : null;
    
    if (!$projectId) {
      $projectId = getenv('GAE_PROJECT') ?: getenv('GOOGLE_CLOUD_PROJECT');
    }

    if (!$projectId) {
      $appId = getenv('GAE_APPLICATION') ?: (isset($_SERVER['GAE_APPLICATION']) ? $_SERVER['GAE_APPLICATION'] : null);
      if ($appId) {
          $parts = explode('~', $appId, 2);
          // In App Engine, GAE_APPLICATION is often prefixed (e.g., 's~project-id').
          $projectId = isset($parts[1]) ? $parts[1] : $parts[0];
      }
    }

    return $projectId;
  }


  /**
   * Gets an array of all the modules for the application.
   *
   * @return string[] An array of string containing the names of the modules
   * associated with the application. The 'default' module will be included if
   * it exists, as will the name of the module that is associated with the
   * instance that calls this function.
   */
   
    public static function getModules() {
    if (!self::useAdminApi()) {
      return self::getModulesLegacy();
    }
    try {
      $service = self::getAdminService();
      $response = $service->apps_services->listAppsServices(self::getProjectId());
      $modules = [];
      $services = $response->getServices();
      if ($services !== null) { // Add null check
        foreach ($services as $s) {
          $modules[] = $s->getId();
        }
      }
      return $modules;
    } catch (\Throwable $e) { // Catch Throwable to include Errors
      throw new ModulesException($e->getMessage());
    }
  }

  
  private static function getModulesLegacy() {
    $req = new GetModulesRequest();
    $resp = new GetModulesResponse();

    ApiProxy::makeSyncCall('modules', 'GetModules', $req, $resp);
    return $resp->getModuleList();
  }

  /**
   * Get an array of all versions associated with a module.
   *
   * @param string $module The name of the module to retrieve the versions for.
   * If null then the versions for the current module will be retrieved.
   *
   * @return string[] An array of strings containing the names of versions
   * associated with the module. The current version will also be included in
   * this list.
   *
   * @throws \InvalidArgumentException If $module is not a string.
   * @throws ModulesException If the given $module isn't valid.
   * @throws TransientModulesException if there is an issue fetching the
   * information.
   */
   
  public static function getVersions($module = null) {
    if (!self::useAdminApi()) {
      return self::getVersionsLegacy($module);
    }
    $module = $module ?: self::getCurrentModuleName();
    try {
      $service = self::getAdminService();
      $response = $service->apps_services_versions->listAppsServicesVersions(
          self::getProjectId(), $module);
      $versions = [];
      $versionList = $response->getVersions();
      if ($versionList !== null) { // Add null check
        foreach ($versionList as $v) {
          $versions[] = $v->getId();
        }
      }
      return $versions;
    } catch (\Throwable $e) { // Catch Throwable to include Errors
      throw new ModulesException($e->getMessage());
    }
  }

  
  private static function getVersionsLegacy($module = null) {
    $req = new GetVersionsRequest();
    $resp = new GetVersionsResponse();

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'GetVersions', $req, $resp);
    } catch (ApplicationError $e) {
      throw errorCodeToException($e->getApplicationError());
    }
    return $resp->getVersionList();
  }

  /**
   * Get the default version of a module.
   *
   * @param string $module The name of the module to retrieve the default
   * versions for. If null then the default versions for the current module
   * will be retrieved.
   *
   * @return string The default version of the module.
   *
   * @throws \InvalidArgumentException If $module is not a string.
   * @throws ModulesException If the given $module is invalid or if no default
   * version could be found.
   */
    public static function getDefaultVersion($module = null) {
    if (!self::useAdminApi()) {
      return self::getDefaultVersionLegacy($module);
    }
    
    $module = $module ?: self::getCurrentModuleName();
    try {
      $service = self::getAdminService();
      $serviceConfig = $service->apps_services->get(self::getProjectId(), $module);
      
      $split = $serviceConfig->getSplit();
      $allocations = $split ? $split->getAllocations() : [];
      
      $maxAlloc = -1.0;
      $retVersion = null;

      // Iterate through allocations to find the version with the highest traffic
      foreach ($allocations as $version => $allocation) {
        if ($allocation == 1.0) {
          $retVersion = $version;
          break;
        }

        if ($allocation > $maxAlloc) {
          $retVersion = $version;
          $maxAlloc = $allocation;
        } elseif ($allocation == $maxAlloc) {
          // Tie-breaker: Lexicographically smaller version ID
          if ($version < $retVersion) {
            $retVersion = $version;
          }
        }
      }

      // If no version could be determined (e.g. empty allocations), throw the exception
      if ($retVersion === null) {
        throw new ModulesException("Could not determine default version for module '$module'.");
      }
      
      return $retVersion;
    } catch (\Exception $e) {
      // Avoid wrapping ModulesException if it was already thrown inside the try block
      if ($e instanceof ModulesException) {
        throw $e;
      }
      throw new ModulesException($e->getMessage());
    }
  }

  
  private static function getDefaultVersionLegacy($module = null) {
    $req = new GetDefaultVersionRequest();
    $resp = new GetDefaultVersionResponse();

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'GetDefaultVersion', $req, $resp);
    } catch (ApplicationError $e) {
      throw errorCodeToException($e->getApplicationError());
    }
    return $resp->getVersion();
  }

  /**
   * Get the number of instances set for a version of a module.
   *
   * This function does not work on automatically-scaled modules.
   *
   * @param string $module The name of the module to retrieve the count for. If
   * null then the count for the current module will be retrieved.
   *
   * @param string $version The version of the module to retrieve the count for.
   * If null then the count for the version of the current instance will be
   * retrieved.
   *
   * @return integer The number of instances set for the current module
   * version.
   *
   * @throws \InvalidArgumentException If $module or $version is not a string.
   * @throws ModulesException if the given combination of $module and $version
   * is invalid.
   */
   
  public static function getNumInstances($module = null, $version = null) {
    if (!self::useAdminApi()) {
      return self::getNumInstancesLegacy($module, $version);
    }
    $module = $module ?: self::getCurrentModuleName();
    $version = $version ?: self::getCurrentVersionName();
    try {
      $service = self::getAdminService();
      $v = $service->apps_services_versions->get(self::getProjectId(), $module, $version);
      return $v->getManualScaling()->getInstances();
    } catch (\Exception $e) {
      throw new ModulesException($e->getMessage());
    }
  }
  
  private static function getNumInstancesLegacy($module = null, $version = null) {
    $req = new GetNumInstancesRequest();
    $resp = new GetNumInstancesResponse();

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    if ($version !== null) {
      if (!is_string($version)) {
        throw new \InvalidArgumentException(
            '$version must be a string. Actual type: ' . gettype($version));
      }
      $req->setVersion($version);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'GetNumInstances', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }
    return (int) $resp->getInstances();
  }

  /**
   * Set the number of instances for a version of a module.
   *
   * This function does not work on automatically-scaled modules.
   *
   * @param string $module The name of the module to set the instance count for.
   * If null then the instance count for the current module will be set.
   *
   * @param string $version The version of the module to set the instance count
   * for. If null then the count for the version of the current instance will
   * be set.
   *
   * @throws \InvalidArgumentException If $instances is not an integer or if
   * $module or $version is not a string.
   * @throws ModulesException if the given combination of $module and $version
   * is invalid.
   * @throws TransientModulesException if there is an issue setting the
   * instance count.
   */
  public static function setNumInstances($instances,
                                         $module = null,
                                         $version = null) {
    if (!self::useAdminApi()) {
      return self::setNumInstancesLegacy($instances, $module, $version);
    }
    try {
      $module = $module ?: self::getCurrentModuleName();
      $version = $version ?: self::getCurrentVersionName();
      $service = self::getAdminService();
      $v = new \Google_Service_Appengine_Version();
      $manualScaling = new \Google_Service_Appengine_ManualScaling();
      $manualScaling->setInstances($instances);
      $v->setManualScaling($manualScaling);
      $service->apps_services_versions->patch(
        self::getProjectId(), $module, $version, $v,
        ['updateMask' => 'manualScaling.instances']);
      return;
    } catch (\Exception $e) {
      throw new ModulesException($e->getMessage());
    }
  } 
  
  private static function setNumInstancesLegacy($instances,
                                         $module = null,
                                         $version = null) {
    $req = new SetNumInstancesRequest();
    $resp = new SetNumInstancesResponse();

    if (!is_int($instances)) {
      throw new \InvalidArgumentException(
          '$instances must be an integer. Actual type: ' . gettype($instances));
    }
    $req->setInstances($instances);

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    if ($version !== null) {
      if (!is_string($version)) {
        throw new \InvalidArgumentException(
            '$version must be a string. Actual type: ' . gettype($version));
      }
      $req->setVersion($version);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'SetNumInstances', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }
  }

  /**
   * Starts all instances of the given version of a module.
   * *
   * @param string $module The name of the module to start.
   *
   * @param string $version The version of the module to start.
   *
   * @throws \InvalidArgumentException If $module or $version is not a string.
   * @throws ModulesException if the given combination of $module and $version
   * is invalid.
   * @throws InvalidModuleStateException if the given $version is already
   * started or cannot be started.
   * @throws TransientModulesException if there is an issue starting the module
   * version.
   */
  public static function startVersion($module, $version) {
    if (!self::useAdminApi()) {
      return self::startVersionLegacy($module, $version);
    }
    $module = $module ?: self::getCurrentModuleName();
    $version = $version ?: self::getCurrentVersionName();
    try {
      $service = self::getAdminService();
      $v = new \Google_Service_Appengine_Version();
      $v->setServingStatus('SERVING');
      $service->apps_services_versions->patch(
        self::getProjectId(), $module, $version, $v,
        ['updateMask' => 'servingStatus']);
      return;
    } catch (\Exception $e) {
      throw new ModulesException($e->getMessage());
    }
  }
  
  private static function startVersionLegacy($module, $version) {
    $req = new StartModuleRequest();
    $resp = new StartModuleResponse();

    if (!is_string($module)) {
      throw new \InvalidArgumentException(
          '$module must be a string. Actual type: ' . gettype($module));
    }
    $req->setModule($module);

    if (!is_string($version)) {
      throw new \InvalidArgumentException(
          '$version must be a string. Actual type: ' . gettype($version));
    }
    $req->setVersion($version);

    try {
      ApiProxy::makeSyncCall('modules', 'StartModule', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }
  }

  /**
   * Stops all instances of the given version of a module.
   * *
   * @param string $module The name of the module to stop. If null then the
   * current module will be stopped.
   *
   * @param string $version The version of the module to stop. If null then the
   * current version will be stopped.
   *
   * @throws \InvalidArgumentException If $module or $version is not a string.
   * @throws ModulesException if the given combination of $module and $version
   * instance is invalid.
   * @throws InvalidModuleStateException if the given $version is already
   * stopped or cannot be stopped.
   * @throws TransientModulesException if there is an issue stopping the module
   * version.
   */
  public static function stopVersion($module = null, $version = null) {
    if (!self::useAdminApi()) {
      return self::stopVersionLegacy($module, $version);
    }
    $module = $module ?: self::getCurrentModuleName();
    $version = $version ?: self::getCurrentVersionName();
    try {
      $service = self::getAdminService();
      $v = new \Google_Service_Appengine_Version();
      $v->setServingStatus('STOPPED');
      $service->apps_services_versions->patch(
        self::getProjectId(), $module, $version, $v,
        ['updateMask' => 'servingStatus']);
      return;
    } catch (\Exception $e) {
      throw new ModulesException($e->getMessage());
    }
  }
  
  private static function stopVersionLegacy($module = null, $version = null) {
    $req = new StopModuleRequest();
    $resp = new StopModuleResponse();

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    if ($version !== null) {
      if (!is_string($version)) {
        throw new \InvalidArgumentException(
            '$version must be a string. Actual type: ' . gettype($version));
      }
      $req->setVersion($version);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'StopModule', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }
  }


  private static function constructHostname(...$parts) {
    return implode('.', $parts);
  }

  /**
   * Returns the hostname to use when contacting a module.
   * *
   * @param string $module The name of the module whose hostname should be
   * returned. If null then the hostname of the current module will be returned.
   *
   * @param string $version The version of the module whose hostname should be
   * returned. If null then the hostname for the version of the current
   * instance will be returned.
   *
   * @param string $instance The instance whose hostname should be returned. If
   * null then the load balanced hostname for the module will be returned. If
   * the module is not a fixed module then the instance parameter is ignored.
   *
   * @return string The valid canonical hostname that can be used to communicate
   * with the given module/version/instance e.g.
   * "0.version1.module5.myapp.appspot.com".

   * @throws \InvalidArgumentException If $module or $version is not a string
   * or if $instance is not a string or integer.
   * @throws ModulesException if the given combination of $module and $instance
   * is invalid.
   */
  public static function getHostname($module = null,
                                     $version = null,
                                     $instance = null) {
  if (!self::useAdminApi()) {
    return self::getHostnameLegacy($module, $version, $instance);
  }
  if ($instance !== null) {
      $instanceId = (int) $instance;
      if ($instanceId < 0) {
        throw new ModulesException("Instance must be a non-negative integer.");
      }
    }

    $projectId = self::getProjectId();
    $reqModule = $module ?: self::getCurrentModuleName();
    $reqVersion = $version ?: self::getCurrentVersionName();

    try {
      $services = self::getModules();
      $service = self::getAdminService();
        
      // Fetch application details to get the default hostname
      $app = $service->apps->get($projectId);
      $defaultHostname = $app->getDefaultHostname();
    } catch (\Exception $e) {
      throw new ModulesException($e->getMessage());
    }

    // Handle Legacy Applications (Single 'default' module)
    if (count($services) === 1 && $services[0] === 'default') {
      if ($reqModule !== 'default') {
        throw new ModulesException("Module '$reqModule' not found.");
      }
      return $instance !== null 
          ? self::constructHostname($instance, $reqVersion, $defaultHostname)
          : self::constructHostname($reqVersion, $defaultHostname);
    }

    // Handle instance-specific hostname requests
    if ($instance !== null) {
      try {
        $vDetails = $service->apps_services_versions->get($projectId, $reqModule, $reqVersion, ['view' => 'FULL']);
        
        if (!$vDetails->getManualScaling()) {
          throw new ModulesException("Instance-specific hostnames are only available for manually scaled services.");
        }

        $numInstances = $vDetails->getManualScaling()->getInstances();
        if ((int) $instance >= $numInstances) {
          throw new ModulesException("The specified instance does not exist for this module/version.");
        }

        return self::constructHostname($instance, $reqVersion, $reqModule, $defaultHostname);
      } catch (\Google_Service_Exception $e) {
        if ($e->getCode() == 404) {
          throw new ModulesException("Module '$reqModule' or version '$reqVersion' not found.");
        }
        throw new ModulesException($e->getMessage());
      }
    }

    // Handle requests with no explicit version and no instance
    if ($version === null) {
      try {
        $versionsList = self::getVersions($reqModule);
        if (in_array($reqVersion, $versionsList)) {
          return self::constructHostname($reqVersion, $reqModule, $defaultHostname);
        } else {
          // Return hostname without version if current version doesn't exist in target module
          return self::constructHostname($reqModule, $defaultHostname);
        }
      } catch (\Google_Service_Exception $e) {
        if ($e->getCode() == 404) {
          throw new ModulesException("Module '$reqModule' not found.");
        }
        throw new ModulesException($e->getMessage());
      }
    }

    // Request with a version but no instance
    return self::constructHostname($version, $reqModule, $defaultHostname);
  }
  
  private static function getHostnameLegacy($module = null,
                                     $version = null,
                                     $instance = null) {
    $req = new GetHostnameRequest();
    $resp = new GetHostnameResponse();

    if ($module !== null) {
      if (!is_string($module)) {
        throw new \InvalidArgumentException(
            '$module must be a string. Actual type: ' . gettype($module));
      }
      $req->setModule($module);
    }

    if ($version !== null) {
      if (!is_string($version)) {
        throw new \InvalidArgumentException(
            '$version must be a string. Actual type: ' . gettype($version));
      }
      $req->setVersion($version);
    }

    if ($instance !== null) {
      if (!is_int($instance) && !is_string($instance)) {
        throw new \InvalidArgumentException(
            '$instance must be an integer or string. Actual type: ' .
            gettype($instance));
      }
      $req->setInstance((string) $instance);
    }

    try {
      ApiProxy::makeSyncCall('modules', 'GetHostname', $req, $resp);
    } catch (ApplicationError $e) {
      throw self::errorCodeToException($e->getApplicationError());
    }

    return $resp->getHostname();
  }
}
