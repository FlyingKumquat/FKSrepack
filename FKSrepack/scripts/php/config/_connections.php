<?PHP
/***********************************************
	Database Connections to load
	- rename to "connections.php" to use
***********************************************/
$db = array(
	'persist' => false,					// Default PDO::ATTR_PERSISTENT value
	'default' => 'connection_name',
	'connection_name' => array(
		'type' => 'mysql', 				// Database Type
		'host' => '127.0.0.1', 			// Host Address
		'user' => 'user_name', 			// User's Name
		'pass' => 'password', 			// User's Password
		'port' => '3306', 				// Connection Port
		'name' => 'db_name', 			// Database Name
		'charset' => 'utf8' 			// Character Set
	)
);
?>