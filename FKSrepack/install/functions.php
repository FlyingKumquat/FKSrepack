<?PHP namespace FKS\Install;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
//error_reporting(E_ALL & ~E_WARNING);

require_once(__DIR__ . '/../scripts/php/includes/Database.php');
require_once(__DIR__ . '/../scripts/php/includes/Validator.php');
require_once(__DIR__ . '/../scripts/php/includes/DataHandler.php');
require_once(__DIR__ . '/../scripts/php/includes/Session.php');
require_once(__DIR__ . '/../scripts/php/includes/Tables.php');

class Functions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $fks_version = '0.1.191025';
	private $Database;
	private $Tables;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct(){
		// Destroy session
		$Session = new \Session();
		
		// Make a global database connection
		$this->Database = new \Database();
		
		//
		$this->Tables = new \Tables();
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	// -------------------- Get Table Versions --------------------
	private function getTableVersions(){
		// Needs a new class for default connection
		$Database = new \Database();
		
		// Grab all table versions
		if($Database->Q(array(
			'assoc' => array('connection','title'),
			'query' => 'SELECT title,connection,version FROM fks_versions'
		))) {
			return $Database->r['rows'];
		} else {
			return array();
		}
	}
	
	// -------------------- Update Table Versions --------------------
	private function updateTableVersions($connection, $table, $version){
		// Needs a new class for default connection
		$Database = new \Database();
		
		// Add table version or update existing
		if($Database->Q(array(
			'params' => array(
				':title' => $table,
				':connection' => $connection,
				':version' => $version
			),
			'query' => 'INSERT INTO fks_versions SET title = :title, connection = :connection, version = :version ON DUPLICATE KEY UPDATE version = :version'
		))) {
			return true;
		} else {
			return false;
		}
	}
	
	// -------------------- Update FKS Version --------------------
	private function updateFKSVersion() {
		// Needs a new class for default connection
		$Database = new \Database();
		
		// Update the version
		$Database->Q(array(
			'params' => array(':version' => $this->fks_version),
			'query' => 'INSERT INTO fks_site_data SET id = "fks_version", data = :version ON DUPLICATE KEY UPDATE data = :version'
		));
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Help Modal --------------------
	public function helpModal($data){
		switch($data)
		{
			case 'connect';
				$title = '<small>Database Connection</small>';
				$body = '<p>This will use the settings stored in the connections.php file which is found in the scripts > php > config folder.</p><p>Make sure this is up to date before attempting a connection.</p>';
				break;
				
			case 'tables';
				$title = '<small>Create Database Tables</small>';
				$body = '<p>This tab will attempt to locate any tables that are out of date and check them so you know.</p><p>You can check any of the tables if you want to wipe them and start over. Keep in mind that you will lose all data in the table.</p><p>Not updating tables when there is an update may cause issues with the site.</p>';
				break;
				
			case 'account';
				$title = '<small>Create Admin Account</small>';
				$body = '<p>Make sure you create an admin account if this is a new installation OR if you are re-creating the members table</p><p>If you do not have an admin account created you will not be able to access this site!</p><p>If you forget you can always come back to this install to create the account while skipping the database creation tab.</p><p>If you are only updating an existing site without updating the members table you should skip this step as to not overwrite an existing admin account</p>';
				break;
				
			default:
				$title = '?';
				$body = '?';
				break;
		}
		
		$parts = array(
			'title' => 'Help ' . $title,
			'body' => $body
		);
		
		return array('result' => 'success', 'parts' => $parts);
	}
	
	// ---------------------------------------------------------------
	// Test Database Public Function
	// ---------------------------------------------------------------
	public function testDatabase(){
		// Set variables
		$Database = $this->Database;
		$tables = $this->Tables->tables;
		$message = '';
		$return = '';
		
		// Loop through all the tables database connections
		foreach($tables as $k => $v) {
			// Skip this connection if it doesn't exist
			if(!array_key_exists($k, $Database->db)) {
				$message .= '<br/>Connection Unknown: ' . $k . '</br>';
				continue;
			}
			
			// Test connection
			if($Database->con(array('db' => $k))){
				$message .= '<br/>Connection Successful!</br>------------------<br/>';
			} else {
				$message .= '<br/>Connection Failed!</br>------------------<br/>';
				$message .= '<span class="fks-text-danger" style="font-family: inherit !important;">' . $Database->r['error'] . '</span><br/>';
			}
			
			// Add the connection details
			foreach($Database->db[$k] as $k => $v){
				$message .= $k . ' = ' . $v . '<br/>';
			}
		}
		
		// Get table versions
		$current_versions = $this->getTableVersions();
		
		// Build return table
		$return .= '<form>';
		$return .= '<table class="table table-striped table-hover table-sm table-bordered" style="margin-bottom:10px;">';
		$return .= '<thead class="thead-dark"><tr><th style="text-align: center;"><input type="checkbox" class="header_checkbox"></th><th>Backup and Restore</th><th>Database</th><th>Table Name</th><th>Current Version</th><th>Latest Version</th></tr></thead>';
		
		// Loop through all tables
		foreach($tables as $db_name => $db_data) {	
			// Create a temp new database connection
			$_database = new \Database(array('db' => $db_name));
			
			foreach($db_data as $table) {
				// Set some temp variables
				$_exists = $_database->tableExists($table['name']);
				$_db_version = isset($current_versions[$db_name][$table['name']]) ? $current_versions[$db_name][$table['name']]['version'] : 0;
				$_options = '<option value="0">no action</option>';
				$_option = 0;
				
				// See if table is missing from the DB
				if(!$_exists) {
					$current_version = '<span class="fks-text-danger">table missing</span>';
				} elseif($table['version'] > $_db_version) {
					$current_version = '<span class="fks-text-warning">' . $table['version'] . '</span>';
				} else {
					$current_version = '<span class="fks-text-success">' . $table['version'] . '</span>';
				}
				
				// Build dropdown actions
				if($_exists && (!isset($table['backup']['enabled']) || (isset($table['backup']['enabled']) && $table['backup']['enabled']))) {
					// Add back up option if enabled
					$_options .= '<option value="1">backup</option>';
					$_option = 1;
					
					// And restore if restore is enabled
					if(!isset($table['restore']['enabled']) || (isset($table['restore']['enabled']) && $table['restore']['enabled'])) {
						$_options .= '<option value="2">backup & restore</option>';
						$_option = $_exists && $table['version'] > $_db_version ? 2 : 1;
					}
				}
				
				// Create the table row for this db table
				$return .= '<tr>'
					. '<td style="width:30px"><input type="checkbox" class="table-checkbox" database="' .  $db_name . '" table="' . $table['name'] . '"' . (!$_exists || $table['version'] > $_db_version ? ' checked' : '') . '></td>'
					. '<td><select class="form-control form-control-sm table-action-select" value="' . $_option . '">' . $_options . '</select></td>'
					. '<td>' . $db_name . '</td>'
					. '<td>' . $table['name'] . '</td>'
					. '<td>' . $current_version . '</td>'
					. '<td>' . $_db_version . '</td>'
				. '</tr>';
			}
		}
		$return .= '</table></form>';
		
		// Return
		return array('result' => 'success', 'message' => $message, 'table' => $return);
	}
	
	// ---------------------------------------------------------------
	// Create Database Tables Public Function
	// ---------------------------------------------------------------
	public function createTables($data){
		// Check to make sure tables is set and has data
		if( !isset($data) || empty($data) ) { $this->updateFKSVersion(); return array('result' => 'success', 'message' => 'No tables selected...'); }
		
		// Set vars
		$Database = new \Database();
		$tables = $this->Tables->tables;
		$actions = array();
		$result = 'success';
		
		// Reset versions table if set - Needs to happen first so the other tables can update the versions
		if(isset($data[$Database->db['default']]['fks_versions'])) {
			//
			$tables[$Database->db['default']]['fks_versions']['rows'] = array(
				array(
					'title' => 'fks_versions',
					'connection' => $Database->db['default'],
					'version' => $tables[$Database->db['default']]['fks_versions']['version']
				)
			);
			
			//
			if(!$Database->tableReset($tables[$Database->db['default']]['fks_versions'])){return array('result' => 'failure', 'message' => 'Reset failure - ' . print_r($Database->r, true));}
			
			// Unset version table
			unset($data[$Database->db['default']]['fks_versions']);
		}
		
		// Loop through each selected database
		foreach($data as $db_name => $db_data) {
			// Set a temp database variable
			$_database = new \Database(array('db' => $db_name));
			
			// Loop through each of the selected tables
			foreach($db_data as $table => $value) {
				// Check for a legit table
				if(!isset($tables[$db_name][$table])) {
					$actions[$table] = '<br/>-- ' . $table . ' --' . '<br/>&#9;Unknown Table!';
					continue;
				}
				
				$actions[$table] = '<br/>' . $table;
				
				// Restore selected table using options
				if(!$_database->tableRestore(
					$tables[$db_name][$table],
					array(
						'backup' => ($value == 1 || $value == 2),
						'restore' => ($value == 2)
					)
				)) {
					if($value == 0) { $actions[$table] .= ' - Failed to Reset table - ' . print_r($_database->r,true);}
					if($value == 1) { $actions[$table] .= ' - Failed to Reset and Backed up table - ' . $_database->r['error'];}
					if($value == 2) { $actions[$table] .= ' - Failed to Reset, Backup up, and restored table - ' . print_r($_database->r,true);}
					$result = 'failure';
					continue;
				}
				
				//
				if($value == 0) { $actions[$table] .= ' - Reset table - ';}
				if($value == 1) { $actions[$table] .= ' - Reset and Backed up table - ';}
				if($value == 2) { $actions[$table] .= ' - Reset, Backup up, and restored table - ';}
				
				// Update table versions
				$this->updateTableVersions($db_name, $table, $tables[$db_name][$table]['version']);
			}
		}
		
		// Update FKS version
		$this->updateFKSVersion();
		
		// Return status and messages
		return array('result' => $result, 'message' => implode('<br/>', $actions));
	}
	
	// ---------------------------------------------------------------
	// Create Admin Account Public Function
	// ---------------------------------------------------------------
	public function createAccount( $data ){
		// Skip?
		if(isset($data['skip']) && $data['skip'] == 1){return array('result' => 'success', 'message' => 'Skipped admin account creation.');}
		
		// Validation
		$Validator = new \Validator($data);
		$Validator->validate(array(
			'username' => array('required' => true, 'not_empty' => true, 'min_length' => 3, 'max_length' => 40),
			'password' => array('required' => true, 'not_empty' => true, 'min_length' => 6, 'max_length' => 40),
			'overwrite' => array('required' => false)
		));
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Hash Password
		require_once($_SERVER['DOCUMENT_ROOT'] . '/scripts/php/includes/PasswordHash.php');
		$_ph = new \PasswordHash(13, FAlSE);
		$form['password'] = $_ph->HashPassword($form['password']);
		
		// Set Vars
		$Database = new \Database();
		
		// Search for existing active members
		if(!$Database->Q(array(
			'params' => array(
				':username' => $form['username']
			),
			'query' => 'SELECT id FROM fks_members WHERE username = :username AND active = 1'
		))){
			return array('result' => 'failure', 'message' => 'Could not lookup accounts.');
		}
		
		// Return if there is an active member with same name unless overwrite is enabled
		if($Database->r['found'] > 0) {
			if($form['overwrite'] == 1) {
				$form['id'] = $Database->r['row']['id'];
			} else {
				return array('result' => 'validate', 'message' => 'Account already exists, check Overwrite Existing User if you want to change this accounts password.', 'validation' => array('username' => array('duplicate' => 'Username already taken!')));
			}
		} else {
			$form['id'] = '+';
		}
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'	,					// Column name (data table link to data types table)
			)
		));
		
		// Attempt to create account
		if(!$DataHandler->setData('local', 'members', $form['id'], array('columns' => array('username' => $form['username']), 'data' => array('PASSWORD' => $form['password'],'ACCESS_GROUPS' => 3,'HOME_PAGE' => 10)), true)) {
			// Return DataHandler error
			return array('result' => 'failure', 'message' => 'Failed to create account ' . print_r($DataHandler->error, true));
		}
		
		// Return
		return array('result' => 'success', 'message' => 'Created admin account.');
	}
	
	// ---------------------------------------------------------------
	// Rename Install Folder Public Function
	// ---------------------------------------------------------------
	public function deleteInstall() {
		if(@rename($_SERVER['DOCUMENT_ROOT'] . '/install', $_SERVER['DOCUMENT_ROOT'] . '/_install')) {
			return array('result' => 'success', 'message' => 'Good');
		} else {
			return array('result' => 'failure', 'message' => 'Unable to rename folder, access denied?');
		}
	}
}
?>