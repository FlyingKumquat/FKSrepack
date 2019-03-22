<?PHP
/*##############################################
	Data Handler
	Version: 1.2.20190301
	Updated: 03/01/2019
##############################################*/

class DataHandler {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $Database = null;
	private $Databases = array(
		'local' => null,
		'remote' => null
	);
	private $Utilities = null;
	private $DataTypes = array();
	private $tables = array();
	private $status = array(
		'result' => true,
		'error'	=> false
	);
	
	public $error = false;
	public $modified = array();
	public $tst = null;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
/*
	$DataHandler = new \DataHandler(array(
		'items' => array(
			'base' => 'items',									// Base table name
			'data' => 'item_data',								// Data table name
			'data_types' => 'item_data_types',					// Data type table name
			'base_column' => 'item_id',							// Column name (optional | data table link to base table | default: target_id)
			'data_types_column' => 'data_type_id',				// Column name (optional | data table link to data types table | default: data_type_id)
			'query' => array(									// Query alterations (optional)
				'assoc' => 'id',
				'select' => '*',
				'where' => 'active = 1 AND deleted = 0'
			),
			'log_actions' => array(								// Log actions (required if using dif)
				'created' => \Enums\LogActions::ITEM_CREATED,
				'modified' => \Enums\LogActions::ITEM_MODIFIED
			)
		)
	));
*/
	public function __construct($tables, $database = false) {
		// Set tables
		$this->tables = $tables;		

		if($database) {
		// Setup database
			// Connect to single database
			$this->Database = new \Database(array('db' => $database, 'persist' => true));
			
			// Check single database status
			if(!$this->checkStatus()) {
				return false;
			}
			
			// Get datatypes
			$this->getDataTypes();
		} else {
		// Setup local & remote databases
			// Connect to local database
			$this->Databases['local'] = new \Database(array('persist' => true));
			
			// Check local database status
			if(!$this->checkStatus('local')) {
				return false;
			}
			
			// Get datatypes (local)
			$this->getDataTypes('local');
			
			// Set Utilities class
			$this->Utilities = new \Utilities;
			
			// Check for remote connection
			if($this->Utilities->siteSettings('REMOTE_SITE') == 'Secondary' && $remote = $this->Utilities->siteSettings('REMOTE_DATABASE')) {
				// Connect to remote database
				$this->Databases['remote'] = new \Database(array('db' => $remote, 'persist' => true));
				
				// Check remote database status
				if(!$this->checkStatus('remote')) {
					return false;
				}
				
				// Get datatypes (remote)
				$this->getDataTypes('remote');
			} else {
				// Set remote database to same as local
				$this->Databases['remote'] = $this->Databases['local'];
				
				// Set remote data types to same as local
				foreach($this->tables as $k => $v) {
					$this->DataTypes[$k]['remote'] = $this->DataTypes[$k]['local'];
				}
			}
		}
	}
	
/*----------------------------------------------
	Check Status
----------------------------------------------*/
	private function checkStatus($type = false) {
		// Check for database type
		if(!$type) {
		// Use single database
			$_database = $this->Database;
		} else {
		// Use type database
			$_database = $this->Databases[$type];
		}
		
		// Check connection to database
		if(!$_database->ping()) {
			$this->status = array(
				'result' => false,
				'error'	=> 'Unable to connect to database. (' . $_database->db['default'] . ')'
			);
			return false;
		}
		
		// Check remote connection settings
		if($type == 'remote') {			
			$_remote_site = $this->Utilities->siteSettings('REMOTE_SITE'); // Disabled, Primary, Secondary
			$_remote_id = $this->Utilities->siteSettings('REMOTE_ID');
			
			// See if local site is set as a secondary site
			if(empty($_remote_site) || $_remote_site != 'Secondary') {
				$this->status = array(
					'result' => false,
					'error'	=> 'Local site is not set as secondary.'
				);
				return false;
			}
			
			// See if local site has a remote id
			if(empty($_remote_id)) {
				$this->status = array(
					'result' => false,
					'error'	=> 'Local site does not have a remote id.'
				);
				return false;
			}
			
			// Get settings from remote site
			if(!$_database->Q(array(
				'assoc' => 'id',
				'query' => 'SELECT id, data FROM fks_site_settings WHERE id IN ("REMOTE_SITE", "REMOTE_SITE_IDS")'
			))) {
				$this->status = array(
					'result' => false,
					'error'	=> 'Unable to get settings from remote site. (' . $_database->db['default'] . ')'
				);
				return false;
			}
			
			$_settings = $_database->r['rows'];
			
			// See if remote site is set as primary
			if($_settings['REMOTE_SITE']['data'] != 'Primary') {
				$this->status = array(
					'result' => false,
					'error'	=> 'Remote site is not set as primary.'
				);
				return false;
			}
			
			// See if remote site is linked to local site
			if(empty($_settings['REMOTE_SITE_IDS']['data']) || !in_array($_remote_id, explode(',', $_settings['REMOTE_SITE_IDS']['data']))) {
				$this->status = array(
					'result' => false,
					'error'	=> 'Remote site is not linked to local site.'
				);
				return false;
			}
		}
		
		$this->status = array(
			'result' => true,
			'error'	=> false
		);
		
		return true;
	}
	
/*----------------------------------------------
	Get Data Types
----------------------------------------------*/
	private function getDataTypes($type = false) {
		// Check for database type
		if(!$type) {
		// Use single database
			$_database = $this->Database;
		} else {
		// Use type database
			$_database = $this->Databases[$type];
		}
		
		// Loop through valid tables
		foreach($this->tables as $table => $table_info) {
			// Check for database type
			if(!$type) {
				// Set default table datatypes to blank array
				$this->DataTypes[$table] = array();
			} else {
				// Set default table [type] datatypes to blank array
				$this->DataTypes[$table][$type] = array();
			}
			
			// Data Types table is not set, continue
			if(!isset($table_info['data_types'])) { continue; }
			
			// Table doesn't exist, continue
			if(!$_database->tableExists($table_info['data_types'])) { continue; }
			
			// Get datatype info from database
			if($_database->Q(array(
				'assoc' => (isset($table_info['query']) && isset($table_info['assoc']) ? $table_info['assoc'] : 'id'),
				'query' => '
					SELECT ' . (isset($table_info['query']) && isset($table_info['select']) ? $table_info['select'] : '*') . '
					
					FROM ' . $table_info['data_types'] . '
					
					' . (isset($table_info['query']) && isset($table_info['where']) ? (empty($table_info['where']) ? '' : 'WHERE ' . $table_info['where']) : 'WHERE active = 1 AND deleted = 0') . '
					
					ORDER BY
						position ASC
				'
			))) {
				// Set new table datatype values
				if(!$type) {
					$this->DataTypes[$table] = $_database->r['rows'];
				} else {
					$this->DataTypes[$table][$type] = $_database->r['rows'];
				}
			}
		}
	}
	
/*----------------------------------------------
	Status
----------------------------------------------*/
	public function status($error = false) {
		if($error) {
			return $this->status['error'];
		} else {
			return $this->status['result'];
		}
	}
	
/*----------------------------------------------
	Data Types
----------------------------------------------*/
	public function DataTypes($table = false) {
		if($table) {
			return $this->DataTypes[$table];
		} else {
			return $this->DataTypes;
		}
	}
	
/*----------------------------------------------
	Get Data
----------------------------------------------*/
	public function getData($type, $table, $target_id = 0, $data_type_ids = array()) {
		// Stop if status is false
		if(!$this->status()) { return false; }
		
		// Return false if table is not listed
		if(!in_array($table, array_keys($this->tables))) { return false; }
		
		// Configure variables
		if(is_null($type)) {
			$_data_types = $this->DataTypes[$table];
			$_database = $this->Database;
		} else {
			$_data_types = $this->DataTypes[$table][$type];
			$_database = $this->Databases[$type];
		}
		
		// Set return_ids to blank
		$return_ids = array();
		
		// Set data_type_ids to all types if empty
		if(empty($data_type_ids)) {
			$data_type_ids = array_keys($_data_types);
		} else {
			// Set return_ids to passed data_type_ids
			$return_ids = $data_type_ids;
		}
		
		// Convert data_type_ids to array if not already
		if(!is_array($data_type_ids)) { $data_type_ids = array($data_type_ids); }

		// Check each id to make sure it's valid
		foreach($data_type_ids as $k => $v) {
			if(!is_numeric($v) || !in_array($v, array_keys($_data_types))) { return false; }
		}
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		// Set out variable
		$out = array();
		
		// Set rows variable
		$rows = array();

		if($_database->Q(array(
			'params' => array(
				':target_id' => $target_id,
				':data_type_ids' => implode(',', $data_type_ids)
			),
			'query' => '
				SELECT
					*
				
				FROM
					' . $table_info['data'] . '
					
				WHERE
					' . (isset($table_info['base_column']) ? $table_info['base_column'] : 'target_id') . ' = :target_id
						AND
					FIND_IN_SET(' . (isset($table_info['data_types_column']) ? $table_info['data_types_column'] : 'data_type_id') . ', :data_type_ids)
			'
		))) {
			// Set rows to database rows
			$rows = $_database->r['rows'];
		}
		
		// Columns to ignore
		$ignore_columns = array(
			'date_created',
			'created_by',
			'date_modified',
			'modified_by',
			'date_deleted',
			'deleted_by',
			'active',
			'deleted'
		);
		
		// Loop through all rows
		foreach($rows as $row) {
			$_r = $row[(isset($table_info['data_types_column']) ? $table_info['data_types_column'] : 'data_type_id')];
			if(!isset($out[$_r])) {
				$_t = $_data_types[$_r];
				foreach($_t as $k => $v) {
					if(!in_array($k, $ignore_columns)) {
						$out[$_r][$k] = $v;
					}
				}
				$out[$_r]['value'] = $row['data'];
			} else {
				if(!is_array($out[$_r]['value'])) {
					$out[$_r]['value'] = array($out[$_r]['value']);
				}
				array_push($out[$_r]['value'], $row['data']);
			}
		}
		
		// Add all return_ids as null if missing
		if(!empty($return_ids)) {
			foreach($return_ids as $id) {
				if(!isset($out[$id])) {
					$_t = $_data_types[$id];
					foreach($_t as $k => $v) {
						if(!in_array($k, $ignore_columns)) {
							$out[$id][$k] = $v;
						}
					}
					$out[$id]['value'] = null;
				}
			}
		}

		return $out;
	}

/*----------------------------------------------
	Set Data
----------------------------------------------*/
	public function setData($type, $table, $target_id = 0, $values = array(), $server = false) {
		// Stop if status is false
		if(!$this->status()) { return false; }
		
		// Reset modified array
		$this->modified = array();
		
		// Return false if table is not listed
		if(!in_array($table, array_keys($this->tables))) { return false; }
		
		// Return false if values is empty
		if(empty($values)) { return false; }
		
		// Return false if any value ids are not numeric
		foreach($values as $k => $v) { if(!is_numeric($k)) { return false; } }

		// Return false if not server and session is not set or is guest
		if(!$server && (!isset($_SESSION['guest']) || $_SESSION['guest'])) { return false; }
		
		// Configure variables
		if(is_null($type)) {
			$_database = $this->Database;
		} else {
			$_database = $this->Databases[$type];
		}
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		$this->error = false;
		foreach($values as $data_type_id => $data){
			$query_ok = false;
			if(empty($data) && strlen($data) == 0) {
				$query_ok = $_database->Q(array(
					'params' => array(
						':target_id' => $target_id,
						':data_type_id' => $data_type_id
					),
					'query' => '
						DELETE FROM
							' . $table_info['data'] . '
						
						WHERE
							' . (isset($table_info['base_column']) ? $table_info['base_column'] : 'target_id') . ' = :target_id
								AND
							' . (isset($table_info['data_types_column']) ? $table_info['data_types_column'] : 'data_type_id') . ' = :data_type_id
					'
				));
			} else {
				$query_ok = $_database->Q(array(
					'params' => array(
						':target_id' => $target_id,
						':data_type_id' => $data_type_id,
						':data' => $data
					),
					'query' => '
						INSERT INTO
							' . $table_info['data'] . '
						
						(' . (isset($table_info['base_column']) ? $table_info['base_column'] : 'target_id') . ', ' . (isset($table_info['data_types_column']) ? $table_info['data_types_column'] : 'data_type_id') . ', data)
							VALUES
						(:target_id, :data_type_id, :data)
							
						ON DUPLICATE KEY UPDATE
							data = :data
					'
				));
			}
			if(
				$query_ok
				&& (
					!empty($data)
					|| strlen($data) > 0
					|| (
						empty($data)
						&& strlen($data) == 0
						&& $_database->r['row_count'] > 0
					)
				)
			) {
				$this->modified[$data_type_id] = $data;
				$_database->Q(array(
					'params' => array(
						':id' => $target_id,
						':date_modified' => gmdate('Y-m-d H:i:s'),
						':modified_by' => ($server ? 0 : $_SESSION['id'])
					),
					'query' => '
						UPDATE
							' . $table_info['base'] . '
						
						SET
							date_modified = :date_modified,
							modified_by = :modified_by
							
						WHERE
							id = :id
					'
				));
			} else {
				$this->error = array(
					'query_ok' => $query_ok,
					'data' => $data,
					'row_count' => isset($_database->r['row_count']) ? $_database->r['row_count'] : null
				);
				if(!$query_ok) { return false; }
			}
		}
		
		return true;
	}
	
/*----------------------------------------------
	Set Data
----------------------------------------------*/
	public function diff($type, $table, $target_id, $new, $ignore_columns = array()) {
		// Stop if status is false
		if(!$this->status()) { return false; }
		
		// Setup variables
		$found = false;
		$diff = array();
		$log_misc = array();
		$log_action = $this->tables[$table]['log_actions']['modified'];
		
		// Return false if table is not listed
		if(!in_array($table, array_keys($this->tables))) { return false; }
		
		// Configure variables
		if(is_null($type)) {
			$_database = $this->Database;
		} else {
			$_database = $this->Databases[$type];
		}
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		// Get basic data
		if(!$_database->Q(array(
			'params' => array(':id' => $target_id),
			'query' => ' SELECT * FROM ' . $table_info['base'] . ' WHERE id = :id'
		))) {
		// Something went wrong, return
			return false;
		}
		
		// Check to see if target was found
		if($_database->r['found'] == 0) {
			// Set log action to created
			$log_action = $this->tables[$table]['log_actions']['created'];
			
			if(!$_database->Q('SHOW COLUMNS FROM ' . $table_info['base'])) {
			// Something went wrong, return
				return false;
			}
			
			// Loop through all table columns and set old to null
			foreach($_database->r['rows'] as $column) { $old[$column['Field']] = null; }
		} else {
			// Set found to true
			$found = true;
			
			// Set old to current info
			$old = $_database->r['row'];
		}
		
		// Unset unwanted columns
		unset($old['id']);
		unset($old['date_created']);
		unset($old['created_by']);
		unset($old['date_modified']);
		unset($old['modified_by']);
		unset($old['date_deleted']);
		unset($old['deleted_by']);
		
		// Unset additional unwanted columns
		foreach($ignore_columns as $v) {
			unset($old[$v]);
		}
		
		// Get data of target_id from appropriate data table
		if(isset($table_info['data_types'])) {
			$old['data'] = $this->getData($type, $table, $target_id);
		} else {
			$old['data'] = array();
		}
		
		// Convert data to value only
		foreach($old['data'] as $k => $v) { $old['data'][$k] = $v['value']; }
		
		// Convert empty data arrays to empty strings
		if(empty($old['data'])) { $old['data'] = ''; }
		if(empty($new['data'])) { $new['data'] = ''; }
		
		// Loop through all old values
		foreach($old as $k => $v) {
			// Skip if new array does not contain old key
			if(!key_exists($k, $new)) { continue; }
			
			// Check if value is data array
			if($k == 'data' && !empty($new[$k])) {
				// Loop through child values as data
				foreach($new[$k] as $ck => $cv) {					
					// Data key is not set or data value is not the same as new data value
					if(!isset($v[$ck]) || $v[$ck] != $cv) {
						// Skip if old value is not set and new value is null anyways
						if(!isset($v[$ck]) && $cv == null) { continue; }
						
						// Set diff data value to new data value
						$diff[$k][$ck] = $cv;
						
						// UPDATE LOG (data)
						if($found) {
							if(!isset($v[$ck])) { $v[$ck] = null; }
							$log_misc[$k][$ck][0] = (strlen($v[$ck]) == 0 ? null : $v[$ck]);
							$log_misc[$k][$ck][1] = (strlen($cv) == 0 ? null : $cv);
						} else {
							$log_misc[$k][$ck] = (strlen($cv) == 0 ? null : $cv);
						}
					}
				}
			} else {
				// Treat both values as strings
				$v = (string)$v;
				$new[$k] = (string)$new[$k];
				
				// Set diff if new value is not the same as old
				if($v != $new[$k]) {
					$diff[$k] = $new[$k];
					
					// UPDATE LOG
					if($found) {
						$log_misc[$k][0] = (strlen($v) == 0 ? null : $v);
						$log_misc[$k][1] = (strlen($new[$k]) == 0 ? null : $new[$k]);
					} else {
						$log_misc[$k] = (strlen($new[$k]) == 0 ? null : $new[$k]);
					}
				}
			}
		}
		
		// Return False if no changes
		if(empty($diff)) { return false; }
		
		return array(
			'log_misc' => json_encode($log_misc),
			'log_action' => $log_action
		);
	}
}
?>