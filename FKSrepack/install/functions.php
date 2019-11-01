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
require_once(__DIR__ . '/../scripts/php/includes/Utilities.php');

class Functions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $fks_version = '0.1.191101';
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
		
		// Stores table configs
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
	public function updateFKSVersion() {
		// Needs a new class for default connection
		$Database = new \Database();
		
		// Update the version
		if($Database->Q(array(
			'params' => array(':version' => $this->fks_version),
			'query' => 'INSERT INTO fks_site_data SET id = "fks_version", data = :version ON DUPLICATE KEY UPDATE data = :version'
		))) {
			return array('result' => 'success', 'version' => $this->fks_version);
		} else {
			return array('result' => 'failure', 'version' => $this->fks_version);
		}
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// ---------------------------------------------------------------
	// Connect Public Function
	// ---------------------------------------------------------------
	public function testConnection(){
		// Set variables
		$Database = $this->Database;
		$tables = $this->Tables->tables;
		$message = '';
		
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
		
		// Return
		return array('result' => 'success', 'message' => $message);
	}
	
	// ---------------------------------------------------------------
	// Versions Public Function
	// ---------------------------------------------------------------
	public function getVersions(){
		// Set variables
		$Utilities = new \Utilities;
		$_versions = array(
			'current' => $Utilities->siteData('fks_version'),
			'installer' => $this->fks_version
		);
		
		// Make sure the version is set
		$_versions['current'] = !$_versions['current'] ? 0 : $_versions['current'];
		
		// TESTING - REMOVE ME
		//$_versions['current'] = '0.0.181025';
		//$_versions['installer'] = '0.1.191030';
		
		// Colors - Current
		if($_versions['current'] < $_versions['installer']) { $versions['current'] = '<span class="fks-text-danger">' . $_versions['current'] . '</span>'; }
		if($_versions['current'] == $_versions['installer']) { $versions['current'] = '<span class="fks-text-success">' . $_versions['current'] . '</span>'; }
		if($_versions['current'] > $_versions['installer']) { $versions['current'] = '<span class="fks-text-critical">' . $_versions['current'] . '</span>'; }
		
		// Colors - Installer
		$versions['installer'] = '<span class="fks-text-success">' . $_versions['installer'] . '</span>';
		
		// Return
		return array('result' => 'success', 'versions' => $versions);
	}
	
	// ---------------------------------------------------------------
	// Latest Version Public Function
	// ---------------------------------------------------------------
	public function getLatestVersion(){
		// Require stuff
		require_once(__DIR__ . '/../scripts/php/includes/Utilities.php');
		require_once(__DIR__ . '/../scripts/php/includes/Curl.php');
		
		// Set variables
		$Utilities = new \Utilities;
		$releases = array();
		$_versions = array(
			'current' => $Utilities->siteData('fks_version'),
			'installer' => $this->fks_version,
			'latest' => $Utilities->getGitHubReleases(array('repo' => 'https://api.github.com/repos/FlyingKumquat/FKSrepack/', 'type' => 'latest'))['name']
		);
		
		// Make sure the version is set
		$_versions['current'] = !$_versions['current'] ? 0 : $_versions['current'];
		
		// TESTING - REMOVE ME
		//$_versions['current'] = '0.0.181025';
		//$_versions['installer'] = '0.1.191030';
		
		// Colors - Current
		if($_versions['current'] < $_versions['installer']) { $versions['current'] = '<span class="fks-text-danger">' . $_versions['current'] . '</span>'; }
		if($_versions['current'] == $_versions['installer']) {
			if($_versions['installer'] < $_versions['latest']) { $versions['current'] = '<span class="fks-text-warning">' . $_versions['current'] . '</span>'; }
			if($_versions['installer'] == $_versions['latest']) { $versions['current'] = '<span class="fks-text-success">' . $_versions['current'] . '</span>'; }
			if($_versions['installer'] > $_versions['latest']) { $versions['current'] = '<span class="fks-text-critical">' . $_versions['current'] . '</span>'; }
		}
		if($_versions['current'] > $_versions['installer']) { $versions['current'] = '<span class="fks-text-critical">' . $_versions['current'] . '</span>'; }
		
		// Colors - Installer
		if($_versions['installer'] < $_versions['latest']) { $versions['installer'] = '<span class="fks-text-warning">' . $_versions['installer'] . '</span>'; }
		if($_versions['installer'] == $_versions['latest']) { $versions['installer'] = '<span class="fks-text-success">' . $_versions['installer'] . '</span>'; }
		if($_versions['installer'] > $_versions['latest']) { $versions['installer'] = '<span class="fks-text-critical">' . $_versions['installer'] . '</span>'; }
		
		// Colors - Latest
		$versions['latest'] = '<span class="fks-text-success">' . $_versions['latest'] . '</span>';
		
		// Only grab the YYMMDD formated date
		$_current = explode('.', $_versions['current'])[2];
		
		// Grab all releases
		$_releases = $Utilities->getGitHubReleases(array('repo' => 'https://api.github.com/repos/FlyingKumquat/FKSrepack/', 'type' => 'all'));
		
		// Loop through all the releases and only return missing
		foreach($_releases as $r) {
			// Only grab the YYMMDD formated date
			$_date = explode('.', $r['name'])[2];
			
			// Add the release if newer
			if($_date > $_current) { $releases[$_date] = $r; }
		}
		
		// Sort
		ksort($releases);
		
		// Return
		return array('result' => 'success', 'versions' => $versions, 'releases' => $releases);
	}
	
	// ---------------------------------------------------------------
	// Get Tables Public Function
	// ---------------------------------------------------------------
	public function getTables(){
		// Needed to get default connection name
		$Database = new \Database();
		
		// Get all tables
		$tables = $this->Tables->tables;
		
		// Get table versions
		$current_versions = $this->getTableVersions();
		
		// Build return table
		$return = '<form>';
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
					$current_version = '<span class="fks-text-warning">' . $_db_version . '</span>';
				} else {
					$current_version = '<span class="fks-text-success">' . $_db_version . '</span>';
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
				$return .= '<tr class="' . $db_name . ' ' . $table['name'] . '">'
					. '<td style="width:30px"><input type="checkbox" class="table-checkbox" database="' .  $db_name . '" table="' . $table['name'] . '"' . (!$_exists || $table['version'] > $_db_version ? ' checked' : '') . '></td>'
					. '<td><select class="form-control form-control-sm table-action-select" value="' . $_option . '">' . $_options . '</select></td>'
					. '<td>' . $db_name . '</td>'
					. '<td>' . $table['name'] . '</td>'
					. '<td class="version">' . $current_version . '</td>'
					. '<td>' . $table['version'] . '</td>'
				. '</tr>';
			}
		}
		$return .= '</table></form>';
		
		// Return
		return array('result' => 'success', 'table' => $return, 'default_connection' => $Database->db['default']);
	}
	
	// ---------------------------------------------------------------
	// Create Database Tables Public Function
	// ---------------------------------------------------------------
	public function createTable($data){
		// Check to make sure 'db', 'table', and 'value' are set
		if( !isset($data) || empty($data) ) { return array('result' => 'failure', 'message' => 'No data passed!'); }
		if( !isset($data['db']) ) { return array('result' => 'failure', 'message' => 'Missing database connection!'); }
		if( !isset($data['table']) ) { return array('result' => 'failure', 'message' => 'Missing table name!'); }
		if( !isset($data['value']) ) { return array('result' => 'failure', 'message' => 'Missing action value!'); }
		
		// Get all tables
		if(isset($this->Tables->tables[$data['db']][$data['table']])) {
			$table = $this->Tables->tables[$data['db']][$data['table']];
		} else {
			return array('result' => 'failure', 'message' => 'Unknown table');
		}
		
		// Collect old versions for the back up
		$Databas = new \Database();
		
		// FKS version
		$Utilities = new \Utilities();
		$backup['fks_version'] = $Utilities->siteData('fks_version');
		$backup['fks_version'] = !$backup['fks_version'] ? 0 : $backup['fks_version'];
		
		// Site version
		$backup['site_version'] = $Utilities->siteSettings('SITE_VERSION');
		$backup['site_version'] = !$backup['site_version'] ? 0 : $backup['site_version'];
		
		// Table version
		$_temp = $this->getTableVersions();
		$backup['table_version'] = isset($_temp[$data['db']][$data['table']]['version']) ? $_temp[$data['db']][$data['table']]['version'] : 0;
		
		// Set new version
		$data['version'] = $table['version'];
		
		// Set a temp database variable
		$_database = new \Database(array('db' => $data['db']));
		
		// Restore selected table using options
		$result = 'success';
		$message = '</br>' . $data['db'] . '.' . $data['table'];
		if($_database->tableRestore(
			$table,
			array(
				'backup' => ($data['value'] == 1 || $data['value'] == 2),
				'restore' => ($data['value'] == 2),
				'fks_version' => $backup['fks_version'],
				'site_version' => $backup['site_version'],
				'table_version' => $backup['table_version']
			)
		)) {
			// Set message
			if($data['value'] == 0) { $message .= ' - Reset table - ';}
			if($data['value'] == 1) { $message .= ' - Reset and Backed up table - ';}
			if($data['value'] == 2) { $message .= ' - Reset, Backup up, and restored table - ';}
			
			// Update table versions
			$this->updateTableVersions($data['db'], $data['table'], $table['version']);
		} else {
			// Set message
			if($data['value'] == 0) { $message .= ' - Failed to Reset table - ' . print_r($_database->r['error'],true);}
			if($data['value'] == 1) { $message .= ' - Failed to Reset and Backed up table - ' . $_database->r['error'];}
			if($data['value'] == 2) { $message .= ' - Failed to Reset, Backup up, and restored table - ' . print_r($_database->r['error'],true);}
			
			// Set result to failure
			$result = 'failure';
		}
		
		// Return
		return array('result' => $result, 'message' => $message, 'data' => $data);
	}
	
	// ---------------------------------------------------------------
	// Create Admin Account Public Function
	// ---------------------------------------------------------------
	public function createAccount( $data ){
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