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
	  if (self::$classmap === null) {
	    self::$classmap = [
	 		'Memcache' => __DIR__ . 'src/Api/Memcache/Memcache.php'
	    ];
	    $base_dir = dirname(__FILE__);
	    self::$sdk_root = dirname(dirname(dirname($base_dir))) .
	                      DIRECTORY_SEPARATOR;
	  }
	  $class_name = strtolower($class_name);
	  if (array_key_exists($class_name, self::$classmap)) {
	    $target_file = self::$classmap[$class_name];
	    $full_path = self::$sdk_root . $target_file;
	    if (file_exists($full_path)) {
	      require $full_path;
	    } else {
	      require $target_file;
	    }
	  }
	}
}

 spl_autoload_register(__NAMESPACE__ . '\ClassLoader::loadClass');

