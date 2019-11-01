<?PHP
/***********************************************
	Database Tables Configuration
	- rename to "tables.php" to use
***********************************************/
$tables = array(
	$this->Database->db['default'] => array(
		'table_name' => array(
			'name' => 'table_name',
			'version' => 1910110246,
			'backup' => array(				// Backup options								(optional)
				'enabled' => true,			// Whether or not backup is enabled				(optional, default: true)
			//	'include' => array(),		// Columns to only include in the backup		(optional, empty: includes nothing)
			//	'exclude' => array()		// Columns to exclude from the backup			(optional, empty: excludes nothing)
			),
			'restore' => array(				// Restore options								(optional)
				'enabled' => true,			// Whether or not restore is enabled			(optional, default: true)
			//	'insert' => true,			// Insert row if missing						(optional, default: true)
			//	'include' => array(),		// Columns to only include in the restore		(optional, empty: includes nothing)
			//	'exclude' => array()		// Columns to exclude from the restore			(optional, empty: excludes nothing)
			),
			'columns' => array(
				'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
				'title' => array('VARCHAR(50)', 'NOT NULL', 'UNIQUE')
			),
			'options' => array(
				'ENGINE' => 'InnoDB',
				'DEFAULT CHARSET' => 'utf8',
				'AUTO_INCREMENT' => 1
			),
			'rows' => array(
				array('id' => 1, 'title' => 'Neato'),
				array('title' => 'Burrito')
			)
		)
	)
);
?>