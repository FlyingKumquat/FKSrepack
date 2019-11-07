<?PHP
/*##############################################
	Data Handler
	Version: 1.5.20191024
	Updated: 10/24/2019
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
// ini_set('display_errors',1);
// ini_set('display_startup_errors',1);
// error_reporting(-1);

require_once('Utilities.php');

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
	
	public $last_id = false;
	public $error = false;
	public $modified = array();
	
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
			'log_actions' => array(								// Log actions (required if using diff)
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
	//public function getData($type, $table, $target_id = 0, $data_type_ids = array(), $assoc_ids = false) {
	public function getData($type, $table, $target_id = 0, $values = array(), $assoc_ids = false) {
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
		
		// Set find ids to blank array
		$find_ids = array();
		
		// Convert values to array if not already
		if(!is_array($values) || empty($values)) { $values = array('columns' => array(), 'data' => array()); }
		if(!isset($values['columns'])) { $values['columns'] = array(); }
		if(!isset($values['data'])) { $values['data'] = array(); }
		
		// Set out variable
		$out = array('columns' => array(), 'data' => array());
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		// Do column stuff
		if(is_array($values['columns'])) {
			if($type == 'local' && $table == 'members' && $this->Utilities->siteSettings('REMOTE_SITE') == 'Secondary') {
				// don't grab local member data
			} else {
				if($_database->Q(array(
					'params' => array(
						':id' => $target_id
					),
					'query' => '
						SELECT
							*
						
						FROM
							' . $table_info['base'] . '
							
						WHERE
							id = :id
					'
				))) {
					if($_database->r['found'] == 1) {
						if(empty($values['columns'])) {
							$out['columns'] = $_database->r['row'];
						} else {
							foreach($_database->r['row'] as $column_name => $column_value) {
								if(in_array($column_name, $values['columns'])) {
									$out['columns'][$column_name] = $column_value;
								}
							}
						}
					}
				}
			}
		}

		// Do data stuff
		if(is_array($values['data'])) {
			// Check each id to make sure it's valid
			foreach($values['data'] as $k => $v) {
				if(!is_numeric($v)) {
					$_found = false;
					foreach($_data_types as $dtk => $dtv) {
						if(isset($dtv['constant']) && $dtv['constant'] == $v) {
							$v = $dtk;
							$_found = true;
							break;
						}
					}
					if(!$_found) { return false; }
				} else if(!in_array($v, array_keys($_data_types))) {
					return false;
				}
				array_push($find_ids, $v);
			}
			
			// Set find_ids to all types if empty
			if(empty($find_ids)) {
				$find_ids = array_keys($_data_types);
			} else {
				// Set return_ids to find_ids
				$return_ids = $find_ids;
			}
			
			// Set rows variable
			$rows = array();

			if($_database->Q(array(
				'params' => array(
					':target_id' => $target_id,
					':data_type_ids' => implode(',', $find_ids)
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
				$id = $row[(isset($table_info['data_types_column']) ? $table_info['data_types_column'] : 'data_type_id')];
				$_const = $id;
				
				if(!$assoc_ids && isset($_data_types[$id]['constant'])) {
					$_const = $_data_types[$id]['constant'];
				}
				
				if(!isset($out['data'][$_const])) {
					$_t = $_data_types[$id];
					foreach($_t as $k => $v) {
						if(!in_array($k, $ignore_columns)) {
							$out['data'][$_const][$k] = $v;
						}
					}
					$out['data'][$_const]['value'] = $row['data'];
				} else {
					if(!is_array($out['data'][$_const]['value'])) {
						$out['data'][$_const]['value'] = array($out['data'][$_const]['value']);
					}
					array_push($out['data'][$_const]['value'], $row['data']);
				}
			}
			
			// Add all return_ids as null if missing
			if(!empty($return_ids)) {
				foreach($return_ids as $id) {
					$_const = $id;
					
					if(!$assoc_ids && isset($_data_types[$id]['constant'])) {
						$_const = $_data_types[$id]['constant'];
					}
					
					if(!isset($out['data'][$_const])) {
						$_t = $_data_types[$id];
						foreach($_t as $k => $v) {
							if(!in_array($k, $ignore_columns)) {
								$out['data'][$_const][$k] = $v;
							}
						}
						$out['data'][$_const]['value'] = null;
					}
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
		
		// Return false if not server and session is not set or is guest
		if(!$server && (!isset($_SESSION['guest']) || $_SESSION['guest'])) { return false; }
		
		// Configure variables
		if(is_null($type)) {
			$_data_types = $this->DataTypes[$table];
			$_database = $this->Database;
		} else {
			$_data_types = $this->DataTypes[$table][$type];
			$_database = $this->Databases[$type];
		}
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		// Reset error
		$this->error = false;
		
		// Reset last id
		$this->last_id = false;
		
		// Do column stuff
		if($target_id == '+' || (isset($values['columns']) && is_array($values['columns']) && !empty($values['columns']))) {
			$_creating = ($target_id == '+');
			$_params = array();
			$_set = array();
			
			// Build parameters
			if(isset($values['columns']) && is_array($values['columns'])) {
				foreach($values['columns'] as $k => $v) {
					$_params[':' . $k] = $v;
				}
			}
			
			// Overwrite parameters
			$_params[':id'] = $target_id;
			$_params[':date_modified'] = gmdate('Y-m-d H:i:s');
			$_params[':modified_by'] = ($server ? 0 : $_SESSION['id']);
			
			if($_creating) {
				$_params[':date_created'] = $_params[':date_modified'];
				$_params[':created_by'] = $_params[':modified_by'];
			}
			
			// Build set query
			foreach(array_keys($_params) as $p) {
				if($p == ':id') { continue; }
				array_push($_set, str_replace(':', '', $p) . ' = ' . $p);
			}
			
			if($_creating) {
				unset($_params[':id']);
				// Create something
				if(!$_database->Q(array(
					'params' => $_params,
					'query' => '
						INSERT INTO
							' . $table_info['base'] . '
						
						SET
							' . implode(', ', $_set) . '
					'
				))) {
					$this->error = $_database->r;
					return false;
				}
				
				$target_id = $_database->r['last_id'];
				$this->last_id = $target_id;
			} else {				
				// Update something
				if(!$_database->Q(array(
					'params' => $_params,
					'query' => '
						UPDATE
							' . $table_info['base'] . '
						
						SET
							' . implode(', ', $_set) . '
							
						WHERE
							id = :id
					'
				))) {
					$this->error = $_database->r['error'];
					return false;
				}
			}
		}
		
		// Do data stuff
		if(isset($values['data']) && is_array($values['data']) && !empty($values['data'])) {
			// Set actual values to blank array
			$actual_values = array();
			
			// Check all data keys
			foreach($values['data'] as $k => $v) {
				if(!is_numeric($k)) {
					$_found = false;
					foreach($_data_types as $dtk => $dtv) {
						if(isset($dtv['constant']) && $dtv['constant'] == $k) {
							$k = $dtk;
							$_found = true;
							break;
						}
					}
					// Return false if constant does not link to id
					if(!$_found) { return false; }
				}
				if(key_exists($k, $_data_types)) {
					// Add if data id exists in data types
					$actual_values[$k] = $v;
				}
			}

			foreach($actual_values as $data_type_id => $data){
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
		}
		
		return true;
	}
	
/*----------------------------------------------
	Diff
----------------------------------------------*/
	public function diff($type, $table, $target_id, $new, $ignore_columns = array(), $json = array()) {
		// Stop if status is false
		if(!$this->status()) { return false; }
		
		// Setup variables
		$found = false;
		$diff = array();
		$log_misc = array();
		$log_action = $this->tables[$table]['log_actions']['modified'];
		$log_columns = false;
		$log_data = false;
		$log_type = 'modified';
		
		// Unwanted columns
		$ignore_columns = array_unique(array_merge($ignore_columns, array(
			'id',
			'date_created',
			'created_by',
			'date_modified',
			'modified_by',
			'date_deleted',
			'deleted_by'
		)));
		
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
		
		// Set table_info
		$table_info = $this->tables[$table];
		
		// -
		if(!isset($new['columns'])) { $new['columns'] = array(); }
		if(!isset($new['data'])) { $new['data'] = array(); }
		
		// Get old data
		if(!$old = $this->getData($type, $table, $target_id, array('columns' => (is_array($new['columns']) ? array() : false), 'data' => (is_array($new['data']) ? array() : false)), true)) { return false; }
		
		// Check to see if target was found
		if(empty($old['columns'])) {
			// Set log action to created
			$log_action = $this->tables[$table]['log_actions']['created'];
			$log_type = 'created';
			
			if(!$_database->Q('SHOW COLUMNS FROM ' . $table_info['base'])) {
			// Something went wrong, return
				return false;
			}
			
			// Loop through all table columns and set old to null
			foreach($_database->r['rows'] as $column) { $old['columns'][$column['Field']] = null; }
		} else {
			// Set found to true
			$found = true;
		}
		
		// Unset unwanted columns
		foreach($ignore_columns as $v) {
			unset($old['columns'][$v]);
		}
		
		// Convert data to value only
		foreach($old['data'] as $k => $v) { $old['data'][$k] = $v['value']; }
		
		// Associate new data by id if constant
		if(!empty($new['data'])) {
			$_temp_new = array();
			foreach($new['data'] as $k => $v) {
				if(!is_numeric($k)) {
					foreach($_data_types as $dtk => $dtv) {
						if(isset($dtv['constant']) && $dtv['constant'] == $k) {
							$k = $dtk;
							break;
						}
					}
				}
				$_temp_new[$k] = $v;
			}
			$new['data'] = $_temp_new;
		}
		
		// JSON diff function
		function jsonDiff($json_old, $json_new, &$log_misc) {
			// Set json arrays
			$json_old = (strlen($json_old) == 0 ? array() : json_decode($json_old, true));
			$json_new = (strlen($json_new) == 0 ? array() : json_decode($json_new, true));
			$json_log = array();
			
			// Loop through all new json
			foreach($json_new as $k => $v) {
				// Check for existing old data
				if(key_exists($k, $json_old)) {
					// See if value is different
					if($v !== $json_old[$k]) {
						$json_log[$k] = array($json_old[$k], $v);
					}
				} else {
					$json_log[$k] = array(null, $v);
				}
			}
			
			// Loop through all old json
			foreach($json_old as $k => $v) {
				// Check for missing new data
				if(!key_exists($k, $json_new)) {
					$json_log[$k] = array($v, null);
				}
			}
			
			// Set log misc
			$log_misc = $json_log;
			
			// Return encoded new json data
			return json_encode($json_new);
		}
		
		// Loop through all old columns
		if(is_array($new['columns'])) {
			foreach($old['columns'] as $k => $v) {
				// Skip if new columns does not contain old column
				if(!key_exists($k, $new['columns'])) { continue; }
				
				// Treat both values as strings
				$v = (string)$v;
				$new['columns'][$k] = (string)$new['columns'][$k];
				
				// Set diff if new value is not the same as old
				if($v != $new['columns'][$k]) {
					// Set to null if empty string
					$diff['columns'][$k] = (strlen($new['columns'][$k]) == 0 ? null : $new['columns'][$k]);
					
					// Check for json value
					if(isset($json['columns']) && in_array($k, $json['columns'])) {
						$diff['columns'][$k] = jsonDiff($v, $new['columns'][$k], $log_misc[$k]);
					} else {
						if($found) {
							$log_misc[$k][0] = (strlen($v) == 0 ? null : $v);
							$log_misc[$k][1] = $diff['columns'][$k];
						} else {
							$log_misc[$k] = $diff['columns'][$k];
						}
					}
					
					// Changes in columns
					$log_columns = true;
				}
			}
		}
		
		// Loop through all new data
		if(is_array($new['data'])) {
			foreach($new['data'] as $k => $v) {
				// Data key is not set or data value is not the same as new data value
				if(!isset($old['data'][$k]) || $old['data'][$k] != $v) {
					// Skip if old value is not set and new value is null anyways
					if(!isset($old['data'][$k]) && $v == null) { continue; }
					
					// Skip if data id does not exist in data types
					if(!key_exists($k, $_data_types)) { continue; }
					
					// Set diff data value to new data value
					$diff['data'][$k] = $v;
					
					// Check for json value
					if(isset($json['data']) && in_array($k, $json['data'])) {
						$diff['data'][$k] = jsonDiff($old['data'][$k], $v, $log_misc[$k]);
					} else {
						if($found) {
							if(!isset($old['data'][$k])) { $old['data'][$k] = null; }
							$log_misc[$k][0] = (strlen($old['data'][$k]) == 0 ? null : $old['data'][$k]);
							$log_misc[$k][1] = (strlen($v) == 0 ? null : $v);
						} else {
							$log_misc[$k] = (strlen($v) == 0 ? null : $v);
						}
					}
					
					// Changes in data
					$log_data = true;
				}
			}
		}
		
		// Return False if no changes
		if(empty($diff)) { return false; }
		
		return array(
			'log_misc' => json_encode($log_misc),
			'log_action' => $log_action,
			'log_columns' => $log_columns,
			'log_data' => $log_data,
			'log_type' => $log_type,
			'changes' => $diff
		);
	}

/*----------------------------------------------
	DIFF SET LOG
----------------------------------------------*/
	/*
	$args = array(
		'type' => 'local',				// local / remote
		'table' => 'table',				// table name
		'target_id' => 1,				// target id
		'values' => array(				// values to set
			'columns' => array(),
			'data' => array()
		),
		'json' => array(				// values to treat as json
			'columns' => array(),
			'data' => array()
		),
		'ignore_columns' => array(),	// columns to ignore
		'server' => false				// use server as member id
	);
	*/
	public function DSL($args) {
		$defaults = array(
			'values' => array(
				'columns' => array(),
				'data' => array()
			),
			'json' => array(
				'columns' => array(),
				'data' => array()
			),
			'ignore_columns' => array(),
			'server' => false
		);
		
		// Merge args with defaults
		$args = array_merge($defaults, $args);
		
		// Get diff
		$diff = $this->diff($args['type'], $args['table'], $args['target_id'], $args['values'], $args['ignore_columns'], $args['json']);
		
		// Check diff
		if(!$diff) {
			// Return no changes
			return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.');
		}
		
		// Set data (changes only)
		if(!$this->setData($args['type'], $args['table'], $args['target_id'], $diff['changes'], $args['server'])) {
			// Return failure
			return array('result' => 'failure', 'message' => 'Unable to Set Data.', 'error' => $this->error);
		}
		
		// Check if server is making changes
		if(!$args['server']) {
			// Save member log
			$MemberLog = new \MemberLog($diff['log_action'], $_SESSION['id'], ($this->last_id ? $this->last_id : $args['target_id']), $diff['log_misc']);
		}
		
		// Return success
		return array('result' => 'success', 'target_id' => ($this->last_id ? $this->last_id : $args['target_id']), 'diff' => $diff);
	}
}
?>