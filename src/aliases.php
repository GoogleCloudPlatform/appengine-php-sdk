<?php

/**
 * Alias.php creates aliases for the classes to match the 
 * original names used in the php55 sdk. This is for the
 * convenience of migration to php7+ sdk. Alias.php is 
 * included in the autoloader inside the composer.json.
 */

$classMap = [
    'Google\AppEngine\Api\AppIdentity\AppIdentityService' => 'google\appengine\api\app_identity\AppIdentityService',
    'Google\AppEngine\Api\TaskQueue\PushTask' => 'google\appengine\api\taskqueue\PushTask',
	'Google\AppEngine\Api\TaskQueue\PushQueue' => 'google\appengine\api\taskqueue\PushQueue',
	'Google\AppEngine\Api\Users\User' => 'google\appengine\api\users\User',
	'Google\AppEngine\Api\Users\UserService' => 'google\appengine\api\users\UserService',
	'Google\AppEngine\Api\Users\UserServiceUtil' => 'google\appengine\api\users\UserServiceUtil',
	'Google\AppEngine\Api\Users\UsersException' =>'google\appengine\api\users\UsersException'
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}
