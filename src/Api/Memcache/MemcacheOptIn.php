<?php


// return array(
// 	'Memcache' => __DIR__ . 'src/Api/Memcache/Memcache.php',
// );

// use 'Google\AppEngine\Api\Memcache\Memcache';

// 'Memcache' => __DIR__ . 'Memcache.php';
// 'Memcached' => __DIR__ . 'Memcached.php';

// 'google/appengine/runtime/Memcached.php',
// 'Memcache' => 'Google\AppEngine\Api\Memcache\Memcache',




final class ClassLoader {
	private static $classmap = null;
	private static $sdk_root = null;

	public static function loadClass($class_name) {
	    self::$classmap = [
	 		'Memcache' => __DIR__ . '/Memcache.php',
	 		'Memcached' => __DIR__ . '/Memcached.php'
	    ];
	  if (array_key_exists($class_name, self::$classmap)) {
	    	$target_file = self::$classmap[$class_name];
	  		require  $target_file;
	  }

	}
}

 spl_autoload_register('\ClassLoader::loadClass');

