<?PHP
/*##############################################
	Database Simplistic PDO Wrapper
	Version: 1.15.20191030
	Updated: 10/30/2019
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class Database {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $r;
	public $pdo;
	public $type;
	public $db;
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($args = null) {
		// Load "connections.php" if it exists otherwise set default settings
		if(is_file(__DIR__ . '/../config/connections.php')) {
			include(__DIR__ . '/../config/connections.php');
			foreach($db as $k => $v) {
				$this->db[$k] = $v;
			}
		} else {
            // Note: These settings should not be changed!
            // Add your own connection to config/connections.php
            $this->db = array(
                'persist' => false,
                'default' => 'my_connection',
                'my_connection' => array(
                    'type' => 'mysql',
                    'host' => '127.0.0.1',
                    'user' => 'user_name',
                    'pass' => 'awesome-password',
                    'port' => '3306',
                    'name' => 'db_name',
                    'charset' => 'utf8'
                )
            );
        }
		
		if(isset($args['db'])) { $this->db['default'] = $args['db']; }
		else { $args['db'] = $this->db['default']; }
		$this->type = strtolower($this->db[$args['db']]['type']);
		$this->db['persist'] = isset($args['persist']) ? $args['persist'] : $this->db['persist'];
		if($this->db['persist']) {
			$this->con($args);
		}
	}
/*----------------------------------------------
	Destruct
----------------------------------------------*/
	public function __destruct() {
		$this->pdo = null;
	}
/*----------------------------------------------
	Connect
----------------------------------------------*/
	public function con($args = null) {
		if(!isset($args['db'])) { $args['db'] = $this->db['default']; }
		try {
			$this->type = strtolower($this->db[$args['db']]['type']);
			if($this->type == 'mysql') {
			// MySQL
				$this->pdo = new PDO(
					'mysql'
					. ':host=' . $this->db[$args['db']]['host']
					. ';port=' . $this->db[$args['db']]['port']
					. ';dbname=' . $this->db[$args['db']]['name']
					. (isset($this->db[$args['db']]['charset']) ? ';charset=' . $this->db[$args['db']]['charset'] : ''),
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'sqlsrv' || $this->type == 'mssql') {
			// MSSQL
				$this->pdo = new PDO(
					'sqlsrv'
					. ':Server=' . $this->db[$args['db']]['host']
					. ',' . $this->db[$args['db']]['port']
					. ';Database=' . $this->db[$args['db']]['name']
					. (isset($this->db[$args['db']]['charset']) ? ';charset=' . $this->db[$args['db']]['charset'] : ''),
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'sqlite') {
			// SQLite
				$this->pdo = new PDO(
					'sqlite:' . $this->db[$args['db']]['file'],
					null,
					null,
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'pgsql') {
			// PostgreSQL
				$this->pdo = new PDO(
					'pgsql'
					. ':host=' . $this->db[$args['db']]['host']
					. ';port=' . $this->db[$args['db']]['port']
					. ';dbname=' . $this->db[$args['db']]['name'],
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else {
			// Unknown
				$this->r['error'] = sprintf('Database type \'%s\' is not supported.', $this->type);
				return false;
			}
		} catch(PDOException $e) {
			$this->r['error'] = $e->getMessage();
			return false;
		}
		return true;
	}
/*----------------------------------------------
	Query Function
----------------------------------------------*/
	/*
		$Database->Q(array(
			//'assoc' => 'col_1',
			//'assoc' => array('col_2', 'col_1'),
			'assoc' => array(
				'columns' => array('col_2', 'col_1'),
				'separator' => '_',
			),
			'limit' => 10,
			'order' => array('col_1' => 'ASC', 'col_2' => 'DESC'),
			'query' => 'SELECT * FROM table_name'
		));
	*/
	public function Q($args) {
	// Unset 'last_id' and 'row_count'
		unset($this->r['last_id']);
		unset($this->r['row_count']);
		
	// Adjust for straight query
		if(!is_array($args)) { $args = array('query' => $args); }
		
	// Adjust args for missing parameters
		$args['db'] = isset($args['db']) ? $args['db'] : $this->db['default'];
		$args['params'] = isset($args['params']) ? $args['params'] : array();
		$args['assoc'] = isset($args['assoc']) ? $args['assoc'] : false;
		$args['fetchAll'] = isset($args['fetchAll']) && is_long($args['fetchAll']) ? $args['fetchAll'] : PDO::FETCH_ASSOC;
		
	// Set the database type
		$this->type = strtolower($this->db[$args['db']]['type']);
	
	// Add order to query if it exists
		if(isset($args['order'])) {
			$order = '';
			foreach($args['order'] as $k => $v) {
				$v = strtoupper($v);
				if($v == 'ASC' || $v == 'DESC') {
					$order .= (empty($order) ? ' ORDER BY ' : ', ') . $k . ' ' . $v;
				}
			}
			$args['query'] .= $order;
		}
		
	// Add limit to query if it exists
		if(isset($args['limit'])) {
			if($this->type == 'sqlsrv' || $this->type == 'mssql') {
				$args['query'] = preg_replace('/SELECT/mi', 'SELECT TOP ' . $args['limit'], $args['query'], 1);
			} else {
				$args['query'] .= ' LIMIT ' . $args['limit'];
			}
		}
	
	// Clean up query string for friendly appearance
		$args['query'] = trim(preg_replace("/\s\s+/", " ", $args['query']));
	
	// Clear result for new data
		$this->r = array('args' => $args);
	
	// Open connection if not open/persistent
		if(!$this->db['persist']) {
			if(!$this->con($args)) { return false; }
		}
		
	// Reconnect if the persistent connection has gone away
		if($this->db['persist'] && !$this->ping()) {
			if(!$this->con($args)) { return false; }
		}

	// Bind the params and run the query
		try {
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $this->pdo->prepare($args['query']);
			$stmt->execute($args['params']);
		} catch(PDOException $e) {
			$this->r['error'] = $e->getMessage();
			return false;
		}
		
	// Explode query to find type
		$type = explode(' ', str_replace('(', '', $args['query']));
		switch($type[0]) {
			case 'SELECT':
			case 'SHOW':
			// Grab all data from query if SELECT
				$rows = $stmt->fetchAll($args['fetchAll']);
				$this->r['found'] = count($rows);
				$this->r['rows'] = $rows;
				$this->r['row'] = ($this->r['found'] > 0 ? $rows[0] : array());
				if($args['fetchAll'] == PDO::FETCH_ASSOC && $args['assoc']) {
					$this->assoc($args['assoc']);
				}
				break;
				
			case 'INSERT':
			// Set the 'last_id' if INSERT
				$this->r['last_id'] = $this->pdo->lastInsertId();
				break;
				
			case 'CREATE':
				break;
			
			case 'UPDATE':
			case 'DELETE':
			// Set the 'row_count' if UPDATE or DELETE
				$this->r['row_count'] = $stmt->rowCount();
				break;
		}
	
	// Close connection if not persistent
		if(!$this->db['persist']) {
			$stmt = null;
			$this->pdo = null;
		}
	
	// At this point, everything was a success!
		return true;
	}
/*----------------------------------------------
	Associate Rows Function
----------------------------------------------*/
	public function assoc($args) {
		// Rows is not an array
		if(!is_array($this->r['rows'])) { return false; }
		
		// Set temp rows variable
		$rows = array();
		
		// Super-cool dig function
		$dig = function($r, $rows, $args) use(&$dig) {
			$column = $r[array_shift($args)];
			if(!isset($rows[$column])) { $rows[$column] = array(); }
			$rows[$column] = (empty($args) ? $r : $dig($r, $rows[$column], $args));
			return $rows;
		};
		
		if(is_array($args)) {
		// Arguments provided
			// Check for columns key
			if(!isset($args['columns'])) {
				// Make sure all columns exist
				foreach($args as $c) {
					if(!isset($this->r['rows'][0][$c])) { return false; }
				}
				
				// Loop through all rows and create associative array
				foreach($this->r['rows'] as $r) {
					$rows = $dig($r, $rows, $args);
				}
			} else {
				// Make sure all columns exist
				foreach($args['columns'] as $c) {
					if(!isset($this->r['rows'][0][$c])) { return false; }
				}
				
				// Set separator
				$separator = isset($args['separator']) ? $args['separator'] : ':';
				
				// Loop through all rows and create associative array
				foreach($this->r['rows'] as $r) {
					// Set build id array
					$_build_id = array();
					
					// Loop through columns and add to build id array
					foreach($args['columns'] as $c) {
						array_push($_build_id, $r[$c]);
					}
					
					// Set the row value
					$rows[implode($separator, $_build_id)] = $r;
				}
			}			
		} else {
		// Column name provided
			// Column does not exist
			if(!isset($this->r['rows'][0][$args])) { return false; }
			
			// Loop through all rows and create associative array
			foreach($this->r['rows'] as $r) {
				$rows[$r[$args]] = $r;
			}
		}
		
		// Set new rows
		$this->r['rows'] = $rows;
		
		return true;
    }
/*----------------------------------------------
	Ping Function
----------------------------------------------*/
	public function ping() {
		if(!isset($this->pdo)) { $this->con(); }
        try {
            return (bool) $this->pdo->query('SELECT 1+1');
        } catch(PDOException $e) {
            return false;
        }
    }
/*----------------------------------------------
	Get Primary Keys
----------------------------------------------*/
	public function primaryKeys($table) {
		$keys = array();
        switch($this->type) {
			case 'mysql':
				if($this->Q('SHOW INDEX FROM ' . $table . ' WHERE Key_name = "PRIMARY"')) {
					if($this->r['found'] > 0) {
						foreach($this->r['rows'] as $k => $v) {
							array_push($keys, $v['Column_name']);
						}
					}
				}
				break;
				
			case 'sqlsrv':
			case 'mssql':
				if($this->Q('
					SELECT
						COLUMN_NAME
						
					FROM
						INFORMATION_SCHEMA.KEY_COLUMN_USAGE
						
					WHERE
						OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + \'.\' + QUOTENAME(CONSTRAINT_NAME)), \'IsPrimaryKey\') = 1
							AND
						TABLE_NAME = \'' . $table . '\'
							AND
						TABLE_SCHEMA = \'' . $this->db[$this->db['default']]['name'] . '\'
				')) {
					if($this->r['found'] > 0) {
						foreach($this->r['rows'] as $k => $v) {
							array_push($keys, $v['COLUMN_NAME']);
						}
					}
				}
				break;
		}
		return $keys;
	}
/*----------------------------------------------
	Get Columns
----------------------------------------------*/
	public function columns($table) {
		$keys = array();
		switch($this->type) {
			case 'sqlsrv':
			case 'mssql':
			case 'mysql':
				if($this->Q('
					SELECT
						*
						
					FROM
						INFORMATION_SCHEMA.COLUMNS
						
					WHERE
						TABLE_NAME = \'' . $table . '\'
							AND
						TABLE_SCHEMA = \'' . $this->db[$this->db['default']]['name'] . '\'
						
					ORDER BY
						ORDINAL_POSITION ASC
				')) {
					if($this->r['found'] > 0) {
						foreach($this->r['rows'] as $k => $v) {
							$keys[$v['COLUMN_NAME']] = array(
								'position' => $v['ORDINAL_POSITION'],
								'table_name' => $v['TABLE_NAME'],
								'name' => $v['COLUMN_NAME'],
								'type' => $v['DATA_TYPE'],
								'max_length' => $v['CHARACTER_MAXIMUM_LENGTH'],
								'precision' => $v['NUMERIC_PRECISION'],
								'nullable' => $v['IS_NULLABLE'] == 'YES',
								'default' => $v['COLUMN_DEFAULT']
							);
						}
					}
				}
				break;
		}
		return $keys;
	}
/*----------------------------------------------
	Table Exists Function
----------------------------------------------*/
	public function tableExists($table) {
        if($this->Q(sprintf('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = \'%s\' AND TABLE_SCHEMA = \'%s\'', $table, $this->db[$this->db['default']]['name']))) {
			if($this->r['found'] > 0) {
				return true;
			}
		}
		return false;
	}
/*----------------------------------------------
	Table Create Function
----------------------------------------------*/
	/*
		$Database->tableCreate(array(
			'name' => 'table_name',
			'columns' => array(
				'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
				'title' => array('VARCHAR(50)', 'NOT NULL', 'UNIQUE')
			),
			'options' => array(
				'ENGINE' => 'InnoDB',
				'DEFAULT CHARSET' => 'utf8',
				'AUTO_INCREMENT' => 1
			)
		));
	*/
	public function tableCreate($table_info) {
		// Set temp variables
		$_columns = array();
		$_options = array();
		$_primary_keys = array();
		$_query = '';
		
		// Loop through columns
		foreach($table_info['columns'] as $name => $properties) {
			foreach($properties as $k => $v) {
				if(strtoupper($v) == 'PRIMARY KEY') {
					array_push($_primary_keys, $name);
					unset($properties[$k]);
				}
			}
			array_push($_columns, sprintf('%s %s', $name, implode(' ', $properties)));
		}
		
		// Add primary keys
		if(!empty($_primary_keys)) {
			array_push($_columns, sprintf('PRIMARY KEY (%s)', implode(', ', $_primary_keys)));
		}
		
		// Loop through options
		foreach($table_info['options'] as $k => $v) {
			array_push($_options, sprintf('%s = %s', $k, $v));
		}
		
		// Create query
		$_query = sprintf('CREATE TABLE %s (%s) %s;', $table_info['name'], implode(', ', $_columns), implode(' ', $_options));
		
		return $this->Q($_query);
	}
/*----------------------------------------------
	Table Drop Function
----------------------------------------------*/
	public function tableDrop($table_name) {
		// Create query
		$_query = sprintf('DROP TABLE IF EXISTS %s;', $table_name);
		
		return $this->Q($_query);
	}
/*----------------------------------------------
	Table Backup
----------------------------------------------*/
	public function tableBackup($table_info, $location = false) {
		if(!$location) { $location = sprintf('%s/../../../', __DIR__); }
		$location_backups_root = sprintf('%sbackups/', $location);
		$location_backups = sprintf('%s%s/', $location_backups_root, $this->db[$this->db['default']]['name']);
		$location_table = sprintf('%s%s/', $location_backups, $table_info['name']);
		$backup_file = sprintf('%s%s-%s.txt', $location_table, $table_info['name'], date('Y-m-d-H-i-s'));
		$columns = array_keys($table_info['columns']);
		$all = true;
		
		// Check backup settings
		if(array_key_exists('backup', $table_info)) {
			// See if backup is enabled
			if(array_key_exists('enabled', $table_info['backup']) && $table_info['backup']['enabled'] === false) {
				$this->r['error'] = sprintf('Backup is not enabled for table \'%s\'.', $table_info['name']);
				return false;
			}
			
			// Set columns if include is not empty
			if(array_key_exists('include', $table_info['backup'])) {
				$columns = $table_info['backup']['include'];
				$all = false;
			}
			
			// Remove columns if exclude is not empty
			if(array_key_exists('exclude', $table_info['backup'])) {
				$columns = array_diff($columns, $table_info['backup']['exclude']);
				$all = false;
			}
		}
		
		// Check for empty columns
		if(empty($columns)) {
			$this->r['error'] = sprintf('No columns to backup for table \'%s\'.', $table_info['name']);
			return false;
		}
		
		// Create directories if they don't exist
		if(!is_dir($location_table)) { mkdir($location_table, 0777, true); }
		
		// Create .htaccess file if missing
		if(!is_file($location_backups_root . '.htaccess')) { file_put_contents($location_backups_root . '.htaccess', 'Deny from all'); }
		
		// Grab all data from the table
		if(!$this->Q('SELECT * FROM ' . $table_info['name'])) { return false; }
		
		// See if we don't want to backup all
		if(!$all) {
			// Loop through rows and remove columns we don't want to backup
			foreach($this->r['rows'] as &$row) {
				// Loop through columns
				foreach($row as $column => $v) {
					// See if column is not what we want
					if(!in_array($column, $columns)) {
						// Unset column
						unset($row[$column]);
					}
				}
			}
		}
		
		// Save file
		if(file_put_contents($backup_file, json_encode($this->r['rows'])) === false) {
			$this->r['error'] = sprintf('Unable to save backup file for table \'%s\'.', $table_info['name']);
			return false;
		}
		
		return true;
	}
/*----------------------------------------------
	Table Backup Get Data
----------------------------------------------*/
	public function tableBackupGetData($table_info, $file_name = false, $location = false) {
		if(!$location) { $location = sprintf('%s/../../../', __DIR__); }
		$location_backups_root = sprintf('%sbackups/', $location);
		$location_backups = sprintf('%s%s/', $location_backups_root, $this->db[$this->db['default']]['name']);
		$location_table = sprintf('%s%s/', $location_backups, $table_info['name']);
		$backup_file = $file_name;
		
		// Check for file name
		if($file_name) {
			// File name provided, see if it is a file
			$file_name = is_file($location_table . $file_name) ? $location_table . $file_name : false;
		} else {
			// File name NOT provided, find the newest backup
			if(!is_dir($location_table)) {
				$this->r['error'] = sprintf('Directory does not exist \'%s\'.', $location_table);
				return false;
			}
			
			$_file = array();
			foreach(scandir($location_table) as $f) {
				if($f == '.' || $f == '..' || is_dir($f)) { continue; }
				$_time = filectime($location_table . $f);
				if(empty($_file) || $_time > $_file['time']) {
					$_file['name'] = $f;
					$_file['time'] = $_time;
				}
			}
			if(!empty($_file)) {
				$file_name = $location_table . $_file['name'];
			}
		}
		
		// No file found, return false
		if(!$file_name) {
			if($backup_file) {
				$this->r['error'] = sprintf('Unable to locate backup file \'%s\'.', $location_table . $backup_file);
			} else {
				$this->r['error'] = sprintf('Unable to locate backup file for table \'%s\'.', $table_info['name']);
			}
			return false;
		}
		
		// Get table data from file
		$table_data = file_get_contents($file_name);
		
		// Return false if unable to grab table data from file
		if($table_data === false) {
			$this->r['error'] = sprintf('Unable to get data from backup file \'%s\'.', $file_name);
			return false;
		}
		
		// Return json decoded data
		return json_decode($table_data, true);
	}
/*----------------------------------------------
	Table Fill
----------------------------------------------*/
	public function tableFill($table_info, $table_data, $restoring = false) {
		// Check for empty table data
		if(empty($table_data)) {
			$this->r['error'] = sprintf('No data to fill table \'%s\' with.', $table_info['name']);
			return false;
		}
		
		// Get all columns
		$restore_columns = array_keys($table_info['columns']);
		
		// See if we are trying to restore
		if($restoring) {
			// Check restore settings
			if(array_key_exists('restore', $table_info)) {
				// See if restore is enabled
				if(array_key_exists('enabled', $table_info['restore']) && $table_info['restore']['enabled'] === false) {
					$this->r['error'] = sprintf('Restore is not enabled for table \'%s\'.', $table_info['name']);
					return false;
				}
				
				// Set columns if include is not empty
				if(array_key_exists('include', $table_info['restore'])) {
					$restore_columns = $table_info['restore']['include'];
				}
				
				// Remove columns if exclude is not empty
				if(array_key_exists('exclude', $table_info['restore'])) {
					$restore_columns = array_diff($restore_columns, $table_info['restore']['exclude']);
				}
			}
		}
		
		// Get all table primary keys and columns
		$primary_keys = $this->primaryKeys($table_info['name']);
		$columns = $this->columns($table_info['name']);
		
		// Loop through backed up data rows
		foreach($table_data as $i => $row) {
			// Get backup row columns
			$backup_columns = array_keys($table_data[$i]);
			
			// Set variables
			$params = array();			// Array of parametrized values
			$query = array();			// Insert query
			$update = array();			// On dupe update query
			$modifications = array();	// Modifications to existing columns
			$additions = array();		// Additional columns to add

			// Loop through the new columns
			foreach($columns as $column) {
				// Check to see if the the column exists in the backup
				if(in_array($column['name'], $backup_columns)) {
					// Column exists, check to see if we need to set new data
					if(!is_null($column['default']) || $column['nullable']) { continue; }
					
					// Need to create a value
					$modifications[$column['name']] = 0;
				} else {
					// Column does not exist, do we need to add it?
					if(!is_null($column['default']) || $column['nullable']) { continue; }
					
					// Column needs to exist with a value
					$additions[$column['name']] = 0;
				}
			}

			// Loop through each col in this row
			foreach($row as $col => $val) {
				// Skip if backed up col no longer exists
				if(!array_key_exists($col, $columns)) { continue; }
				
				// Change value if in the modify list
				if(is_null($val) && array_key_exists($col, $modifications)) { $val = $modifications[$col]; }
				
				// Set params
				$params[':' . $col] = $val;
				array_push($query, $col . ' = :' . $col);
				
				// Check for primary key(s)
				if(!in_array($col, $primary_keys) && in_array($col, $restore_columns)) {
					array_push($update, $col . ' = :' . $col);
				}
			}
			
			// Loop through any additions that need to be added
			foreach($additions as $col => $val) {
				// Set params
				$params[':' . $col] = $val;
				array_push($query, $col . ' = :' . $col);
				
				// Check for primary key(s)
				if(!in_array($col, $primary_keys) && in_array($col, $restore_columns)) {
					array_push($update, $col . ' = :' . $col);
				}
			}
			
			// Check for empty columns
			if(empty($update)) {
				array_push($update, array_keys($columns)[0] . ' = ' . array_keys($columns)[0]);
			}
			
			// Check for missing data
			if(empty($params) || empty($query) || empty($update)) {
				$this->r['error'] = sprintf('Something went wrong, data is missing for table \'%s\'.', $table_info['name']);
				return false;
			}
			
			// Upsert row
			if(!$this->Q(array(
				'params' => $params,
				'query' => 'INSERT INTO ' . $table_info['name'] . ' SET ' . implode(', ', $query) . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update)
			))) {
				return false;
			}
		}
		
		return true;
	}
/*----------------------------------------------
	Table Reset
----------------------------------------------*/
	public function tableReset($table_info) {
		// Drop Table
		if(!$this->tableDrop($table_info['name'])) {
			return false;
		}
		
		// Create Table
		if(!$this->tableCreate($table_info)) {
			return false;
		}
		
		// Check for table rows
		if(array_key_exists('rows', $table_info) && !empty($table_info['rows'])) {
			// Fill Table with default values
			if(!$this->tableFill($table_info, $table_info['rows'])) {
				return false;
			}
		}
		
		return true;
	}
/*----------------------------------------------
	Table Restore
----------------------------------------------*/
	public function tableRestore($table_info, $options = array()) {
		// Set defaults
		$options = array_merge(array(
			'backup' => false,
			'restore' => false,
			'file_name' => false,
			'location' => false
		), $options);
		
		// See if backup is enabled
		if($options['backup']) {
			if(array_key_exists('backup', $table_info) && array_key_exists('enabled', $table_info['backup']) && $table_info['backup']['enabled'] === false) {
				$this->r['error'] = sprintf('Backup is not enabled for table \'%s\'.', $table_info['name']);
				return false;
			}
		}
		
		// See if restore is enabled
		if($options['restore']) {
			if(array_key_exists('restore', $table_info) && array_key_exists('enabled', $table_info['restore']) && $table_info['restore']['enabled'] === false) {
				$this->r['error'] = sprintf('Restore is not enabled for table \'%s\'.', $table_info['name']);
				return false;
			}
		}
		
		// Backup table data
		if($options['backup']) {
			if(!$this->tableBackup($table_info, $options['location'])) {
				return false;
			}
		}
		
		// Reset the table
		if(!$this->tableReset($table_info)) {
			return false;
		}
		
		// Restore backup data to table
		if($options['restore']) {
			// Get backup data from file
			$backup_data = $this->tableBackupGetData($table_info, $options['file_name'], $options['location']);
			
			// Check for failure
			if($backup_data === false) { return false; }
			
			// If data is empty, return true
			if(empty($backup_data)) { return true; }
			
			// Fill table with backup data
			if(!$this->tableFill($table_info, $backup_data, true)) {
				return false;
			}
		}
		
		return true;
	}
}
?>