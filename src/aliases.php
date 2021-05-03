<?php

$classMap = [
    'Google\AppEngine\Api\AppIdentity\AppIdentityService' => 'google\appengine\api\appidentity\AppIdentityService'
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}