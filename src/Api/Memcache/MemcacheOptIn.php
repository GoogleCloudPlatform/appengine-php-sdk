<?php

public function loadClass($class_name) {
	$classmap = [
		'Memcache' => __DIR__ . '/Memcache.php',
		'Memcached' => __DIR__ . '/Memcached.php'
	];
	if (array_key_exists($class_name, self::$classmap)) {
		$target_file = self::$classmap[$class_name];
	  	require_once $target_file;
	}
}

spl_autoload_register('loadClass');

