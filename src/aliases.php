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
 * Alias.php creates aliases for the classes to match the 
 * original names used in the php55 sdk. This is for the
 * convenience of migration to php7+ sdk. Alias.php is 
 * included in the autoloader inside the composer.json.
 */

$classMap = [
    'Google\AppEngine\Api\AppIdentity\AppIdentityService' => 'google\appengine\api\app_identity\AppIdentityService',
    'Google\AppEngine\Api\Modules\InvalidModuleStateException' => 'google\appengine\api\modules\InvalidModuleStateException',
    'Google\AppEngine\Api\Modules\ModulesException' => 'google\appengine\api\modules\ModulesException',
    'Google\AppEngine\Api\Modules\ModulesService' => 'google\appengine\api\modules\ModulesService',
    'Google\AppEngine\Api\Modules\TransientModulesException' => 'google\appengine\api\modules\TransientModulesException',
    'Google\AppEngine\Api\TaskQueue\PushTask' => 'google\appengine\api\taskqueue\PushTask',
	'Google\AppEngine\Api\TaskQueue\PushQueue' => 'google\appengine\api\taskqueue\PushQueue',
	'Google\AppEngine\Api\Users\User' => 'google\appengine\api\users\User',
	'Google\AppEngine\Api\Users\UserService' => 'google\appengine\api\users\UserService',
	'Google\AppEngine\Api\Users\UserServiceUtil' => 'google\appengine\api\users\UserServiceUtil',
	'Google\AppEngine\Api\Users\UsersException' =>'google\appengine\api\users\UsersException'
];

foreach ($classMap as $class => $alias) {
    @class_alias($class, $alias);
}
