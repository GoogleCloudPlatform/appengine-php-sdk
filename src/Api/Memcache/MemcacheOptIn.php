<?php

function loadClass($class_name) {
	$classmap = [
		'memcached' => __DIR__ . '/Memcached.php',
		'memcache' => __DIR__ . '/Memcache.php'
	];
	$class_name = strtolower($class_name);
	if (array_key_exists($class_name, $classmap)) {
		$target_file = $classmap[$class_name];
	  	require_once $target_file;
	}
}

spl_autoload_register('loadClass');

