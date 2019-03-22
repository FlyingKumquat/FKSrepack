<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
//error_reporting(E_ALL & ~E_WARNING);

require_once(__DIR__ . '/../includes/Enums.php');
require_once(__DIR__ . '/../includes/Bitmask.php');
require_once(__DIR__ . '/../includes/MemberLog.php');
require_once(__DIR__ . '/../includes/Database.php');
require_once(__DIR__ . '/../includes/DataHandler.php');
require_once(__DIR__ . '/../includes/DataTypes.php');
require_once(__DIR__ . '/../includes/Validator.php');
require_once(__DIR__ . '/../includes/Crypter.php');
require_once(__DIR__ . '/../includes/Session.php');
require_once(__DIR__ . '/../includes/Utilities.php');
require_once(__DIR__ . '/../includes/Curl.php');
require_once(__DIR__ . '/../includes/PHPMailer/PHPMailerAutoload.php');

// Autoload
$files = array_diff(scandir(__DIR__ . '/../autoload'), array('.', '..'));
if(count($files) > 0) {
	foreach($files as $k => $v) {
		if(stripos(strrev($v), 'php.') !== 0) { continue; }
		require_once(__DIR__ . '/../autoload/' . $v);
	}
}

// Load "extenders.php" if it exists
if(is_file(__DIR__ . '/../config/extenders.php')) {
	require_once(__DIR__ . '/../config/extenders.php');
}

class CoreFunctions extends \Utilities {
	CONST ROOT_DIR = __DIR__ . '/../../..';
	CONST SLASH = (PHP_OS != 'Linux' ? '\\' : '/');
	
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $Session;
	public $Extenders;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		// Keep session alive
		$this->Session = new \Session(false);
		
		// Set extenders
		if(class_exists('\Extenders\Login'))	{ $this->Extenders['Login']		=	new \Extenders\Login(); }
		if(class_exists('\Extenders\Register'))	{ $this->Extenders['Register']	=	new \Extenders\Register(); }
		if(class_exists('\Extenders\Logout'))	{ $this->Extenders['Logout']	=	new \Extenders\Logout(); }
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/	
	// -------------------- LDAP Login -------------------- \\
	private function loginLDAP($form) {
		// Retun if AD is disabled
		if(!$this->siteSettings('ACTIVE_DIRECTORY')) {
			return array('result' => false, 'message' => 'LDAP is disabled');
		}
		
		// Get site settings
		$AD_SERVER = $this->siteSettings('AD_SERVER');
		$AD_RDN = $this->siteSettings('AD_RDN');
		$AD_ACCOUNT_CREATION = $this->siteSettings('AD_ACCOUNT_CREATION');
		$AD_BASE_DN = $this->siteSettings('AD_BASE_DN');
		$DEFAULT_ACCESS_LDAP = $this->siteSettings('DEFAULT_ACCESS_LDAP');
		
		// Set vars
		$ldap_conn = ldap_connect($AD_SERVER);
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		$alerts = array();
		
		// Add ldap settings
		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
		
		// Attempt to validate credentials
		if(!@ldap_bind($ldap_conn, stripslashes($AD_RDN). $form['username'], $form['password'])) {
			return array('result' => false, 'message' => ldap_error($ldap_conn));
		}
		
		// Check DB for the account
		if(!$Database->Q(array(
			'params' => array(
				':username' => $form['username']
			),
			'query' => 'SELECT id FROM fks_members WHERE username = :username AND active = 1 AND deleted = 0'
		))){
			return array('result' => false, 'message' => 'LDAP DB member lookup error');
		}
		
		// If the account is not found either return false or create it
		if($Database->r['found'] != 1) {
			// Return false because account creation is disabled
			if($AD_ACCOUNT_CREATION == 0) {
				return array('result' => false, 'message' => 'LDAP account creation is disabled');
			}
			
			// Create the new account in the DB
			if(!$Database->Q(array(
				'params' => array(
					':username' => $form['username'],
					':date_created' => gmdate('Y-m-d h:i:s'),
					':tech' => 0
				),
				'query' => '
					INSERT INTO 
						fks_members 
					
					SET 
						username = :username,
						date_created = :date_created,
						created_by = :tech
					'
			))){
				return array('result' => false, 'message' => 'LDAP account creation failed');
			}
			
			// Set the new account ID
			$account_id = $Database->r['last_id'];
			
			// Grab account details from AD
			if($res = @ldap_search($ldap_conn, $AD_BASE_DN, '(samAccountName=' . $form['username'] . ')', array('cn', 'sn', 'givenName', 'mail', 'badpwdcount'))) {
				$data = ldap_get_entries($ldap_conn, $res);
				
				if($data['count'] > 0) {
					// Check to see if data is set 
					if( isset($data[0]['givenname'][0]) && !empty($data[0]['givenname'][0]) ) { $member_data[\Enums\DataTypes::FIRST_NAME['id']] = $data[0]['givenname'][0]; }
					if( isset($data[0]['sn'][0]) && !empty($data[0]['sn'][0]) ) { $member_data[\Enums\DataTypes::LAST_NAME['id']] = $data[0]['sn'][0]; }
					if( isset($data[0]['cn'][0]) && !empty($data[0]['cn'][0]) ) { $member_data[\Enums\DataTypes::FULL_NAME['id']] = $data[0]['cn'][0]; }
					if( isset($data[0]['mail'][0]) && !empty($data[0]['mail'][0]) ) { $member_data[\Enums\DataTypes::EMAIL_ADDRESS['id']] = $data[0]['mail'][0]; }
					
					// Bad password attempts alert
					if( isset($data[0]['badpwdcount'][0]) && $data[0]['badpwdcount'][0] > 0 ) {
						array_push($alerts, array(
							'type' => 'info',
							'msg' => 'You have ' . $data[0]['badpwdcount'][0] . ' failed log in attempts.',
							'timeOut' => 'null',
							'extendedTimeOut' => 'null',
							'positionClass' => 'toast-bottom-right'
						));
					}
					
					// Account expiration notice
					//
				} else {
					// Most likely an invalid base_dn
					array_push($alerts, array(
						'type' => 'warning',
						'msg' => 'Unable to grab new member data from AD. Check the Base_DN.',
						'timeOut' => 'null',
						'extendedTimeOut' => 'null',
						'positionClass' => 'toast-bottom-right'
					));
				}
			}
			
			// Set default access group
			$member_data[\Enums\DataTypes::ACCESS_GROUPS['id']] = $DEFAULT_ACCESS_LDAP;
			
			// Add member data to the new account
			$DataTypes->setData($member_data, $account_id, true);
		} else {
			// Set the account ID
			$account_id = $Database->r['row']['id'];
		}
		
		// Return successful with the member id
		return array('result' => true, 'message' => 'LDAP login successful', 'member' => $account_id, 'alerts' => $alerts);
	}
	
	// -------------------- Local Login -------------------- \\
	private function loginLocal($form) {
		// Need to set the correct database connection whether it is local or remote
		$args = array();
		if($this->siteSettings('REMOTE_SITE') == 'Secondary') {
			$args['db'] = $this->siteSettings('REMOTE_DATABASE');
		}
		$Database = new \Database($args);
		
		// Set variables
		require_once(__DIR__ . '/../includes/PasswordHash.php');
		$t_hasher = new \PasswordHash(13, FALSE);
		$alerts = array();
		
		// Grab database password (10) by looking up username
		if(!$Database->Q(array(
			'params' => array(
				':username' => $form['username'],
				':password_id' => 10
			),
			'query' => '
				SELECT
					m.id,
					m.username,
					(SELECT data from fks_member_data WHERE id = :password_id AND member_id = m.id) AS password
				
				FROM
					fks_members AS m
					
				WHERE
					m.username = :username 
						AND
					m.active = 1 
						AND
					m.deleted = 0'
		))){
			return array('result' => false, 'message' => 'Local DB error!');
		}
		
		// Return if no user found
		if( $Database->r['found'] != 1 ){ return array('result' => false, 'message' => 'Local member not found'); }
		
		// Return true/false depending on the password
		if($t_hasher->CheckPassword($form['password'], $Database->r['row']['password'])) {
			return array(
				'result' => true,
				'message' => 'Local login successful!',
				'member' => $Database->r['row']['id'],
				'alerts' => $alerts
			);
		} else {
			return array(
				'result' => false,
				'message' => 'Local password incorrect'
			);
		}
	}
	
	// -------------------- Create and Send Verification Code -------------------- \\
	private function sendVerificationEmail( $member_id ) {
		// Set vars
		//$DataTypes = new \DataTypes();
		$subject = $this->siteSettings('EMAIL_VERIFICATION_SUBJECT');
		$body = $this->siteSettings('EMAIL_VERIFICATION_TEMPLATE');
		
		// Create an array for Data Handler to use for members data
		$_info = array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		);
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler($_info);
		
		// Grab members first name (2) and email address (4)
		if( !$data = $DataHandler->getData('remote', 'members', $member_id, array(2,4)) ) { return array('result' => 'failure', 'message' => 'Failed to get email address'); }
		
		// Create new code
		$verify_code = $this->makeKey(6, '1234567890');
		
		// Json encode for storing in the DB
		$json = json_encode(array('code' => $verify_code, 'date' => gmdate('Y-m-d h:i:s')));
		
		// Save verify code (9)
		if( !$DataHandler->setData('remote', 'members', $member_id, array(9 => $json), true) ) { return array('result' => 'failure', 'message' => 'Failed to set verify code'); }
		
		// Create replacement array
		$replacement = array(
			'%VERIFY_CODE%' => $verify_code,
			'%FIRST_NAME%' => ($data[2]['value'] ? $data[4]['value'] : 'Hello'),
			'%SITE_USERNAME%' => $this->siteSettings('SITE_USERNAME'),
			'%SITE_TITLE%' => $this->siteSettings('SITE_TITLE')
		);
		
		// Replace stuff
		$subject = strtr($subject, $replacement);
		$body = strtr($body, $replacement);
		
		// Send email
		$mail = $this->sendEmail(array(
			'to_address' => $data[4]['value'],
			'subject' => $subject,
			'body' => $body
		));
		
		// If we fail to send email remove the verify code (9)
		if($mail['result'] == 'failure') {
			if( !$DataHandler->setData('remote', 'members', $member_id, array(9 => null), true) ) { return array('result' => 'failure', 'message' => 'Failed to null verify code'); }
		}
		
		// Return
		return $mail;
	}
	
	// -------------------- Check for unused Email Address -------------------- \\
	private function checkForUnusedEmail( $email_address ) {
		// Set database
		$Database = new \Database(array('db' => $this->siteSettings('REMOTE_DATABASE')));
		
		// Query for email address
		if($Database->Q(array(
			'params' => array(
				':email_address_id' => 4,
				':email_address' => $email_address
			),
			'query' => '
				SELECT
					m.username
					
				FROM
					fks_member_data AS d
					
				INNER JOIN
					fks_members AS m
						ON
					m.id = d.member_id
				
				WHERE
					d.id = :email_address_id
						AND
					d.data = :email_address
						AND
					m.deleted = 0
				'
		))) {
			if( $Database->r['found'] == 0 ){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	// -------------------- Check for First Access (Secondary Site) -------------------- \\
	private function checkForRemoteAccess($member_id) {
		// TODO - Error Checks
		// Set database
		$Database = new \Database();
		
		// Check to see if this member is listed in the table
		if($Database->Q(array(
			'db' => $this->siteSettings('REMOTE_DATABASE'),
			'params' => array(
				':member_id' => $member_id,
				':site_id' => $this->siteSettings('REMOTE_ID')
			),
			'query' => 'SELECT date_created FROM fks_member_sites WHERE member_id = :member_id AND site_id = :site_id'
		))) {
			// If member already exists return
			if( $Database->r['found'] > 0 ){ return; }
		} else {
			// Query failed
			return;
		}
		
		// Check to see if they already have an access group
		if(!$Database->Q(array(
			'params' => array(
				':id' => 8,
				':member_id' => $member_id
			),
			'query' => 'SELECT data FROM fks_member_data WHERE id = :id AND member_id = :member_id'
		))) {
			// Query failed
			return;
		}
		
		// Give member default local access group
		if( $Database->r['found'] == 0 ) {
			if(!$Database->Q(array(
				'params' => array(
					':id' => 8,
					':member_id' => $member_id,
					':data' => $this->siteSettings('DEFAULT_ACCESS_LOCAL')
				),
				'query' => 'INSERT INTO fks_member_data SET id = :id, member_id = :member_id, data = :data'
			))) {
				// Query failed
				return;
			}
		}
		
		// Add member to the list
		if(!$Database->Q(array(
			'db' => $this->siteSettings('REMOTE_DATABASE'),
			'params' => array(
				':member_id' => $member_id,
				':site_id' => $this->siteSettings('REMOTE_ID'),
				':date_created' => gmdate('Y-m-d H:i:s')
			),
			'query' => 'INSERT INTO fks_member_sites SET member_id = :member_id, site_id = :site_id, date_created = :date_created'
		))) {
			// Query failed
			return;
		}
		
		// Return
		return;
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Build Navigation -------------------- \\
	public function buildMenus($data) {
		foreach($data as $k => $v) {
			if(!is_numeric($v)) {
				return array('result' => 'failure', 'message' => 'Error loading menus.');
			}
		}
		
		$Database = new \Database;
		if(!$Database->Q(array(
			'query' => '
				SELECT
					*
				
				FROM
					fks_menu_items
					
				WHERE
					deleted = 0
						AND
					active = 1
						AND
					hidden = 0
						AND
					menu_id IN (' . implode(',', $data) . ')
					
				ORDER BY
					pos ASC
			'
		))) {
			return array('result' => 'failure', 'message' => 'DB Error loading menus.', 'data' => $Database->r);
		}
		$rows = $Database->r['rows'];
		$menus = array();
		
		function basicTitle($item) {
			return '<span class="title">
				' . ($item['icon'] != null ? '<i class="fa fa-' . $item['icon'] . ' fa-fw"></i>' : '') . '
				<span class="text">' . generateTitle($item) . '</span>
			</span>';
		}
		
		function generateTitle($item) {
			// Return title if title_data is not set
			if( empty($item['title_data']) ) {
				return $item['title'];
			}
			
			// Set vars
			$data_values = array();
			$getDataArray = array();
			$DataTypes = new \DataTypes();
			$member_datatypes = \Enums\DataTypes::flip();
			$title_data = explode(',', $item['title_data']);
			
			// Get a list of enums to grab data with
			foreach($title_data as $v) {
				if( in_array($v, $member_datatypes) ) {
					array_push($getDataArray, constant("\Enums\DataTypes::$v"));
				}
			}
			
			// Get all member data
			if( $get_data = $DataTypes->getData($getDataArray, $_SESSION['id']) ) {
				$data_values = array_merge($data_values, $get_data);
			}
			
			// Check for custom values, these override member data
			if( in_array('USERNAME', $title_data) ) { $data_values['USERNAME'] = $_SESSION['username']; }
			if( in_array('SITE_TITLE', $title_data) ) { $data_values['SITE_TITLE'] = $_SESSION['site_settings']['SITE_TITLE']; }
			
			// Loop through all title_data possibilities
			foreach($title_data as $d) {
				if( isset($data_values[$d]) && $data_values[$d] && !empty( $data_values[$d] ) ) { return $data_values[$d]; }
			}
			
			// Return the title if everything failed
			return $item['title'];
		}
		
		function basicNav($item, $url) {
			//<a href="' . $url . '"' . ($item['is_external'] ? ' target="_blank"' : '') . '> // Backup of external links having a _blank target
			return ($item['has_separator'] == 1 || $item['has_separator'] == 3 ? '<li class="nav-separator"></li>' : '') . '<li class="nav-item">
				<a href="' . $url . '">
					' . basicTitle($item) . '
					<span class="selected"></span>
				</a>
			</li>' . ($item['has_separator'] == 2 || $item['has_separator'] == 3 ? '<li class="nav-separator"></li>' : '');
		}
		
		function subMenu($item, $contains) {
			return ($item['has_separator'] ? '<li class="nav-separator"></li>' : '') . '<li class="nav-item">
				<a href="javascript:void(0);">
					' . basicTitle($item) . '
					<span class="arrow"></span>
					<span class="selected"></span>
				</a>
				<ul class="sub-menu">
					' . $contains . '
				</ul>
			</li>';
		}
		
		foreach($rows as $row) {
			if($row['parent_id'] != 0) { continue; }
			//###################### CHECK ACCESS ######################
			if(!$this->checkAccess($row['label'], 1)) { continue; }
			//##########################################################
			$parts = array('', '', '');
			$url_first = ($row['is_external'] ? $row['url'] : '#' . $row['url']);
			if($row['is_parent'] == 1) {
			// has children
				$parts[1] = '';
				foreach($rows as $crow) {
					if($crow['parent_id'] != $row['id']) { continue; }
					//###################### CHECK ACCESS ######################
					if(!$this->checkAccess($crow['label'], 1)) { continue; }
					//##########################################################
					$url_second = ($crow['is_external'] ? $crow['url'] : $url_first . '/' . $crow['url']);
					if($crow['is_parent'] == 1) {
					// child has children
						$parts[2] = '';
						foreach($rows as $ccrow) {
							if($ccrow['parent_id'] != $crow['id']) { continue; }
							//###################### CHECK ACCESS ######################
							if(!$this->checkAccess($ccrow['label'], 1)) { continue; }
							//##########################################################
							$url_third = ($ccrow['is_external'] ? $ccrow['url'] : $url_second . '/' . $ccrow['url']);
							$parts[2] .= basicNav($ccrow, $url_third);
						}
						if($parts[2] != '') {
						// list not empty
							$parts[1] .= subMenu($crow, $parts[2]);
						}
					} else {
					// no children
						$parts[1] .= basicNav($crow, $url_second);
					}
				}
				if($parts[1] != '') {
				// list not empty
					$parts[0] .= subMenu($row, $parts[1]);
				}
			} else {
			// no children
				$parts[0] .= basicNav($row, $url_first);
			}
			if(!isset($menus[$row['menu_id']])) { $menus[$row['menu_id']] = ''; }
			$menus[$row['menu_id']] .= $parts[0];
		}
		
		foreach($menus as $k => $v) {
			$menus[$k] = '<ul class="nav-list">' . $v . '</ul>';
		}
		
		return array('result' => 'success', 'menus' => $menus);
	}
	
	// -------------------- ACCOUNT LOGIN -------------------- \\
	public function accountLogin($form){
		// Check for blank fields
		if( empty($form['username']) || empty($form['password']) ){ return array('result' => 'failure', 'message' => 'Blank fields detected'); }
		
		// Base64 decode password
		$form['password'] = base64_decode($form['password']);
		
		// Custom function to run before login
		if(isset($this->Extenders['Login'])) {
			$before = $this->Extenders['Login']->before($form);
			if(!$before[0]) {
				return array('result' => 'failure', 'message' => $before[1]);
			}
		}
		
		// Set variables
		$settings = array(
			'ACTIVE_DIRECTORY' => $this->siteSettings('ACTIVE_DIRECTORY'),
			'AD_FAILOVER' => $this->siteSettings('AD_FAILOVER'),
			'AD_PREFERRED' => strtolower($this->siteSettings('AD_PREFERRED')),
			'AD_LOGIN_SELECTOR' => $this->siteSettings('AD_LOGIN_SELECTOR'),
			'EMAIL_VERIFICATION' => $this->siteSettings('EMAIL_VERIFICATION'),
			'REMOTE_SITE' => $this->siteSettings('REMOTE_SITE')
		);
		$selector = isset($form['selector']) && $settings['AD_LOGIN_SELECTOR'] ? $form['selector'] : 'default';
		$attempts = array(
			'member' => 0,
			'result' => 0,
			'failed' => array(),
			'message' => '',
			'methods' => array()
		);
		$alerts = array();	// Used to store toastr alerts for after the member logs in
		
		// Create methods list
		if($selector == 'default') {
			array_push($attempts['methods'], $settings['AD_PREFERRED']);
			if($settings['AD_FAILOVER']) {
				if($settings['AD_PREFERRED'] == 'local') {
					if($settings['ACTIVE_DIRECTORY']) {array_push($attempts['methods'], 'ldap');}
				} else {
					array_push($attempts['methods'], 'local');
				}
			}
		} else {
			array_push($attempts['methods'], $selector);
		}
		
		// New way of logging in
		foreach($attempts['methods'] as $m) {
			switch($m)
			{
				case 'ldap':
					$attempt = $this->loginLDAP($form);
					break;
					
				case 'local':
					$attempt = $this->loginLocal($form);
					break;
					
				default:
					$attempt['result'] = 0;
					$attempt['message'] = 'Failed unknown method (' . $m . ')';
					$attempt['alerts'] = array();
					break;
			}
			
			// Update attempts
			$attempts['result'] = $attempt['result'];
			$attempts['message'] .= $attempt['message'] . '<br/>';
			
			// Add alerts
			if(isset($attempt['alerts']) && count($attempt['alerts']) > 0) {
				foreach($attempt['alerts'] as $v){
					array_push($alerts, $v);
				}
			}
			
			// If successful login, break
			if($attempts['result']) {
				$attempts['member'] = $attempt['member'];
				break;
			} else {
				array_push($attempts['failed'], $m);
			}
		}
		
		// Return if all attempts failed
		if(!$attempts['result']) {
			return array('result' => 'failure', 'message' => $attempts['message'], 'debug' => $attempts);
		}
		
		// Create an array for Data Handler to use for members data
		$_info = array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		);
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler($_info);
		
		// Grab member data so we can check a few more things, return if it fails
		if( !$member_data = $DataHandler->getData('remote', 'members', $attempts['member'], array(4,9,12)) ) {
			return array('result' => 'failure', 'message' => 'Data Handler Error', 'attempt' => $attempt, 'data' => $member_data);
		}
		
		// JSON decode the verify code if there is one
		$member_data[9]['value'] = (is_null($member_data[9]['value']) ? null : json_decode($member_data[9]['value'], true));
		
// Testing
//return array('result' => 'failure', 'message' => 'Testing - Unverified', 'attempt' => $attempt, 'data' => $member_data);
		
		// Check if Email Verification is enabled check to see if email is verified (12) and email is set (4)
		if( $settings['EMAIL_VERIFICATION'] == 1 && (is_null($member_data[12]['value']) || is_null($member_data[4]['value']))  ) {
			// Unset email_verified
			$DataHandler->setData('remote', 'members', $attempts['member'], array(12 => null), true);
			
			// Check to see if this account has an email address
			if( is_null($member_data[4]['value']) ) {
				// Add id to session for adding email address
				$this->Session->update(array(
					'temp_id' => $attempts['member']
				));
				return array('result' => 'email', 'message' => 'You must have an email address, please enter one.', 'debug' => $attempts);
			}
			
			// Check to see if there is a verify code (9) in the DB
			if( is_null($member_data[9]['value']) ) {
				$sendEmail = $this->sendVerificationEmail($attempts['member']);
				
				if( $sendEmail['result'] == 'success' ) {
					return array('result' => 'verify', 'message' => 'A code has been emailed to you, please check your email.', 'debug' => $attempts);
				} else {
					return array('result' => 'failure', 'message' => 'Unable to send verification email!', 'debug' => $attempts, 'status' => $sendEmail);
				}
			}
			
			// Check to see if a code was passed with the form
			if( !isset($form['verify_code']) || empty($form['verify_code']) ) {
				return array('result' => 'verify', 'message' => 'You must verify your email address, please enter the code that was emailed to you.', 'debug' => $attempts);
			}
			
			// Check to see if the code is correct
			if( $form['verify_code'] != $member_data[9]['value']['code'] ) {
				return array('result' => 'failure', 'message' => 'The code you supplied is incorrect!', 'debug' => $attempts);
			}
			
			// Everything is good, remove the verify code (9), verify account (12), and make the server do it
			$DataHandler->setData('remote', 'members', $attempts['member'], array(9 => null, 12 => 1), true);
		}
		
		// Check for Two Step Auth
		//
		//
		//
		
		// Check for remote site first login
		if($settings['REMOTE_SITE'] == 'Secondary') {
			$this->checkForRemoteAccess($attempts['member']);
		}
		
		// Prepare session data
		$session_data = array(
			'id' => $attempts['member'],
			'username' => $form['username'],
			'ip' => $this->Session->getIP(),
			'timeout' => 3720,
			'last_action' => time(),
			'started' => gmdate('Y-m-d h:i:s'),
			'guest' => false
		);
		
		// Start session
		$this->Session->start($session_data);
		
		// Add login event to member log
		$log_misc = $this->Session->parse_user_agent();
		$log_misc['ip_address'] = $this->Session->getIP();
		$log_misc['session_id'] = session_id();
		$MemberLog = new \MemberLog(\Enums\LogActions::LOGIN, $session_data['id'], null, json_encode($log_misc));
		
		$this->updateSessionAccess();	
		
		// Custom function to run after login
		if(isset($this->Extenders['Login'])) {
			$after = $this->Extenders['Login']->after($form);
			if(!$after[0]) {
				$this->logout();
				return array('result' => 'failure', 'message' => $after[1]);
			}
		}
		
		// Return log in successful with attempts and alerts
		return array('result' => 'success', 'message' => 'Login successful!', 'debug' => $attempts, 'alerts' => $alerts);
	}
	
	// -------------------- ACCOUNT REGISTER -------------------- \\
	public function accountRegister($data) {
		// Check access
		if($this->siteSettings('MEMBER_REGISTRATION') == 0) { return array('result' => 'failure', 'message' => 'Member registration is not enabled!'); }
		
		// Custom function to run before register validation
		if(isset($this->Extenders['Register'])) {
			$beforePreValidate = $this->Extenders['Register']->beforePreValidate($data);
			if(!$beforePreValidate[0]) {
				return array('result' => 'failure', 'message' => $beforePreValidate[1]);
			}
		}
		
		// Validate form
		$Validator = new \Validator($data);
		$Validator->validate('username_register', array('required' => true, 'no_spaces' => true, 'min_length' => 3, 'max_length' => 40, 'not_values_i' => explode(',', $this->siteSettings('PROTECTED_USERNAMES'))));
		$Validator->validate('email_register', array('required' => ($this->siteSettings('EMAIL_VERIFICATION')), 'email' => true, 'max_length' => 255));
		$Validator->validate('password_register', array('required' => true, 'match' => 'repeat_password_register', 'min_length' => 3, 'max_length' => 255));
		
		// Custom function to run during register validation
		if(isset($this->Extenders['Register'])) {
			$beforeMidValidate = $this->Extenders['Register']->beforeMidValidate($Validator);
			if(!$beforeMidValidate[0]) {
				return array('result' => 'failure', 'message' => $beforeMidValidate[1]);
			}
		}
		
		// Get validation variables
		$result = $Validator->getResult();
		$output = $Validator->getOutput();
		
		// If Captcha is enabled, check for correct response
		if($this->siteSettings('CAPTCHA') == 1) {
			require_once(__DIR__ . '/../includes/ReCaptcha_autoloader.php');
			$recaptcha = new \ReCaptcha\ReCaptcha($this->siteSettings('CAPTCHA_PRIVATE'));
			$resp = $recaptcha->verify($data['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
			if(!$resp->isSuccess()){
				$result = false;
				$output['g-recaptcha-response'] = 'Captcha was incorrect.';
			}
		}
		
		// Create the database connection
		$Database = new \Database;
		
		// Check to see if username is already taken
		if($Database->Q(array(
			'params' => array(
				':username' => $data['username_register']
			),
			'query' => 'SELECT id,username FROM fks_members WHERE username = :username AND deleted = 0'
		))) {
			if( $Database->r['found'] != 0 ){
				$result = false;
				if( !isset($output['username_register']) ) {
					$output['username_register'] = 'Username already taken';
				}
			}
		} else {
			return array('result' => 'failure', 'message' => 'Unable to grab member data from DB!');
		}
		
		// Check to see if email is already taken
		if($Database->Q(array(
			'params' => array(
				':id' => \Enums\DataTypes::EMAIL_ADDRESS['id'],
				':email' => $data['email_register']
			),
			'query' => '
				SELECT
					d.id,
					d.data
					
				FROM
					fks_member_data AS d
					
				INNER JOIN
					fks_members AS m
						ON
					d.member_id = m.id
				
				WHERE
					d.id = :id
						AND
					d.data = :email
						AND
					m.deleted = 0
				'
		))) {
			if( $Database->r['found'] != 0 ){
				$result = false;
				if( !isset($output['email_register']) ) {
					$output['email_register'] = 'Email already taken';
				}
			}
		} else {
			return array('result' => 'failure', 'message' => 'Unable to grab data from DB!');
		}

		// Check for failures
		if(!$result) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $output); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Custom function to run after register validation
		if(isset($this->Extenders['Register'])) {
			$beforePostValidate = $this->Extenders['Register']->beforePostValidate($form);
			if(!$beforePostValidate[0]) {
				return array('result' => 'failure', 'message' => $beforePostValidate[1]);
			}
		}
		
		// Create account
		if($Database->Q(array(
			'params' => array(
				':username' => $form['username_register'],
				':date_created' => gmdate('Y-m-d H:i:s')
			),
			'query' => 'INSERT INTO fks_members SET username = :username, date_created = :date_created'
		))) {
			$member_id = $Database->r['last_id'];
		} else {
			return array('result' => 'failure', 'message' => 'Unable to create account!');
		}
		
		// Add password
		require_once(__DIR__ . '/../includes/PasswordHash.php');
		$PasswordHash = new \PasswordHash(13, FAlSE);
		$password = $PasswordHash->HashPassword( $form['password_register'] );
		$aData = array(
			\Enums\DataTypes::PASSWORD['id'] => $password,
			\Enums\DataTypes::ACCESS_GROUPS['id'] => $this->siteSettings('DEFAULT_ACCESS_LOCAL')
		);
		
		// Add email address if included
		if( isset($form['email_register']) && !empty($form['email_register'])) {
			$aData[\Enums\DataTypes::EMAIL_ADDRESS['id']] = $form['email_register'];
		}
		
		// Set Account Data
		$DataTypes = new \DataTypes();
		$DataTypes->setData($aData, $member_id, true);
		
		// Send email verification if enabled
		if( $this->siteSettings('EMAIL_VERIFICATION') ) {
			if(!$this->sendVerificationEmail($member_id)){
				return array('result' => 'failure', 'message' => 'Failed to send email verification email!');
			}
		}
		
		// Custom function to run after register
		if(isset($this->Extenders['Register'])) {
			$after = $this->Extenders['Register']->after($form);
			if(!$after[0]) {
				return array('result' => 'failure', 'message' => $after[1]);
			}
		}
		
		// Return
		return array('result' => 'success', 'message' => 'Account created, ' . ($this->siteSettings('EMAIL_VERIFICATION') ? 'check your email for a code.' : 'please log in.'), 'verify' => $this->siteSettings('EMAIL_VERIFICATION'));
	}
	
	// -------------------- Add Email Address -------------------- \\
	public function accountAddEmail( $data ) {
		// Make sure session id was stored
		if( !isset($_SESSION['temp_id']) ) {
			return array('result' => 'login', 'message' => 'You were logged out, please log back in.');
		}
		
		// Make sure email is unused
		if( !$this->checkForUnusedEmail($data['email']) ) { 
			return array('result' => 'failure', 'message' => 'That email address is already in use!');
		}
		
		// Create an array for Data Handler to use for members data
		$_info = array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		);
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler($_info);
		
		// Add email address
		if( !$DataHandler->setData('remote', 'members', $_SESSION['temp_id'], array(4 => $data['email']), true) ) {
			return array('result' => 'failure', 'message' => 'Unable to add email address!');
		}
		
		// Check for email verification
		if( $this->siteSettings('EMAIL_VERIFICATION') == 1 ) {
			if( $this->sendVerificationEmail($_SESSION['temp_id']) ) {
				$verify = true;
			} else {
				$verify = false;
			}
		} else {
			$verify = false;
		}
		
		// Unset temp id
		$this->Session->remove('temp_id');
		
		// Return
		return array('result' => 'success', 'message' => 'Added email address, ' . ($verify ? 'check your email for a verification code.' : 'please log in.'), 'verify' => $verify);
	}
	
	// -------------------- Keep Alive -------------------- \\
	public function keepAlive($data) {
		if(!$this->Session->active()) { return array('result' => 'failure', 'message' => 'Unable to continue session.'); }
		
		// No actions, skip updating
		if($data == 0) { goto keepAlive_skip; }
		
		// Keep session alive
		$this->Session->keepAlive();
		
		if(!$this->updateSessionAccess()) {
			$this->Session->destroy(true);
			return array('result' => 'logout', 'message' => 'Unable to continue session.');
		}
		
		if(!$this->Session->guest()) {
			$Database = new \Database;
			if(!$Database->Q(array(
				'params' => array(':last_online' => gmdate('Y-m-d H:i:s'), ':id' => $_SESSION['id']),
				'query' => 'UPDATE fks_members SET last_online = :last_online WHERE id = :id'
			))){
			// Uh Oh
			}
		}
	
	keepAlive_skip:
		$access = array();
		if(isset($_SESSION['access_groups']) && is_array($_SESSION['access_groups'])) {
			foreach($_SESSION['access_groups'] as $group) {
				foreach($group as $label => $value) {
					$access[$label] = (!isset($access[$label]) || $access[$label] < $value ? $value : $access[$label]);
				}
			}
		}

		return array(
			'result' => 'success',
			'guest' => $this->Session->guest(),
			'timeout' => $_SESSION['timeout'],
			'last_action' => $_SESSION['last_action'],
			'access' => $access,
			'site_home_page' => $_SESSION['site_home_page'],
			'announcements' => $_SESSION['announcements']
		);
	}
	
	// -------------------- Logout -------------------- \\
	public function logout($type) {
		if($this->Session->guest()) { return array('result' => 'failure'); }
		
		// Custom function to run before logout
		if(isset($this->Extenders['Logout'])) {
			$before = $this->Extenders['Logout']->before($type, $this->Session->cache['_SESSION']);
			if(!$before[0]) {
				return array('result' => 'failure', 'message' => $before[1]);
			}
		}
		
		$type = '\Enums\LogActions::' . $type;
		if(!defined($type)) { $type = '\Enums\LogActions::LOGOUT_UNKNOWN'; }
		$type = constant($type);

		// Add logout event to member log
		$log_misc = $this->Session->parse_user_agent();
		$log_misc['ip_address'] = $this->Session->getIP();
		$log_misc['session_id'] = $this->Session->cache['session_id'];
		$MemberLog = new \MemberLog($type, $this->Session->cache['_SESSION']['id'], null, json_encode($log_misc));
		
		$this->Session->destroy(true);
		
		// Custom function to run after logout
		if(isset($this->Extenders['Logout'])) {
			$after = $this->Extenders['Logout']->after($type, $this->Session->cache['_SESSION']);
			if(!$after[0]) {
				return array('result' => 'failure', 'message' => $after[1]);
			}
		}
		
		return array('result' => 'success', 'type' => $type);
	}
	
	// -------------------- Update Session Access -------------------- \\
	public function updateSessionAccess() {
		$member = $_SESSION['id'];
		
		// Set Variables
		$Database = new \Database;
		$temp_data = array();
		$access_out = array();
		
		// Grab All Access Groups
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_access_groups WHERE active = 1 AND deleted = 0'
		))) {
			$access_groups = $Database->r['rows'];
		} else {
			return false;
		}
		
		// Grab All Menu Items
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menu_items WHERE active = 1 AND deleted = 0'
		))) {
			$menu_items = $Database->r['rows'];
		} else {
			return false;
		}
		
		// Grab All Site Settings
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT id,data FROM fks_site_settings'
		))) {
			$site_settings = array();
			foreach($Database->r['rows'] as $k => $v) {
				$site_settings[$k] = $v['data'];
			}
		} else {
			return false;
		}
		
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'data' => 'fks_member_data',					// Data table name
				'data_types' => 'fks_member_data_types',		// Data type table name
				'base_column' => 'member_id',					// Column name (optional | data table link to base table | default: target_id)
				'data_types_column' => 'id'						// Column name (optional | data table link to data types table | default: data_type_id)
			)
		));
		
		$_member_data = $DataHandler->getData('local', 'members', $member, array(
			6,		// date format
			7,		// timezone
			8,		// access groups
			13,		// site layout
			15		// site home page
		));
		
		// Grab Member's Access Groups
		$temp_access = null;
		$access_out = null;
		if(empty($_member_data[8]['value'])) {
			if($member == 0) {
				$temp_access = $site_settings['DEFAULT_ACCESS_GUEST'];
			}
		} else {
			$temp_access = $_member_data[8]['value'];
		}

		if(!empty($temp_access)) {
			foreach(explode(',', $temp_access) as $group) {
				$access_out[$group] = array();
				foreach(json_decode($access_groups[$group]['data'], true) as $item => $access) {
					if(!isset($menu_items[$item])) { continue; }
					$access_out[$group][$menu_items[$item]['label']] = $access;
				}
			}
		}
		
		// Grab Member's Site Home Page
		$site_home_page = null;
		$hierarchy = 0;
		if(!empty($_member_data[15]['value'])) {
			$site_home_page = $_member_data[15]['value'];
		}
		
		if(empty($site_home_page) && !empty($temp_access)) {
			foreach(explode(',', $temp_access) as $group) {
				if(!empty($access_groups[$group]['home_page']) && $access_groups[$group]['hierarchy'] <= $hierarchy) {
					$site_home_page = $access_groups[$group]['home_page'];
					$hierarchy = $access_groups[$group]['hierarchy'];
				}
			}
		}
		
		if(empty($site_home_page)) {
			$site_home_page = $site_settings['SITE_HOME_PAGE'];
		}
		
		if(!empty($site_home_page)) {
			$site_home_page = $this->getMenuItemStructures(false, true)[$site_home_page];
		}
		
		// Grab Member's Site Layout
		$site_layout = null;
		if(!empty($_member_data[13]['value'])) {
			$site_layout = $_member_data[13]['value'];
		} else {
			$site_layout = $site_settings['SITE_LAYOUT'];
		}
		
		
		// Grab Member's Timezone & Date Format
		$time_settings = array(
			'timezone' => $_member_data[7]['value'],
			'date_format' => $_member_data[6]['value']
		);
		if(empty($time_settings['timezone'])) { $time_settings['timezone'] = $site_settings['TIMEZONE']; }
		if(empty($time_settings['timezone'])) { $time_settings['timezone'] = @date_default_timezone_get(); }
		if(empty($time_settings['date_format'])) { $time_settings['date_format'] = $site_settings['DATE_FORMAT']; }

		
		// Load announcements
		$announcements = isset($_SESSION['timezone']) ? $this->loadAnnouncements($site_settings) : array();
		
		// Check fks and site versions
		if(isset($_SESSION['site_settings'])) {
			$fks_version_session = $this->siteSettings('FKS_VERSION');
			$site_version_session = $this->siteSettings('SITE_VERSION');
			$fks_version_database = $site_settings['FKS_VERSION'];
			$site_version_database = $site_settings['SITE_VERSION'];
			if(($fks_version_session != $fks_version_database) || ($site_version_session != $site_version_database)) {
				return false;
			}
		}
		
		// Update Session
		$this->Session->update(array(
			'site_settings' => $site_settings,
			'access_groups' => $access_out,
			'timezone' => $time_settings['timezone'],
			'date_format' => $time_settings['date_format'],
			'site_layout' => $site_layout,
			'site_home_page' => $site_home_page,
			'announcements' => $announcements
		));
		
		// Return
		return true;
	}
	
	//---------------------------------------------\\
	//	Check Access Groups for Access
	//---------------------------------------------\\
	public function checkAccess($checking, $value, $die = false) {
		if(!isset($_SESSION['access_groups']) || !is_array($_SESSION['access_groups']) || empty($_SESSION['access_groups']) || !$checking || !$value) { 
			if($die) {
				die(include(__DIR__ . '/../../../views/403.php'));
			}
			
			return false;
		}
		$out = 0;
		foreach($_SESSION['access_groups'] as $group) {
			if(isset($group[$checking]) && $group[$checking] > $out) { $out = $group[$checking]; }
		}
		
		if($out >= $value) {
			return true;
		}
		
		if($die) {
			die(include(__DIR__ . '/../../../views/403.php'));
		}
		
		return false;
	}
	
	public function getAccess($checking) {
		if(!isset($_SESSION['access_groups']) || !is_array($_SESSION['access_groups']) || !$checking) { return 0; }
		$out = 0;
		foreach($_SESSION['access_groups'] as $group) {
			if(isset($group[$checking]) && $group[$checking] > $out) { $out = $group[$checking]; }
		}
		return $out;
	}
	
	// -------------------- Check Hierarchy -------------------- \\
	public function getHierarchy( $id ) {
		// Set vars
		$Database = new \Database;
		$DataTypes = new \DataTypes;
		$hierarchy = 0;
		
		if($id == 0) {
			// Guest Account
			$access_group = $this->siteSettings('DEFAULT_ACCESS_GUEST');
		} else {
			// User Account
			$access_group = $DataTypes->getData(array(\Enums\DataTypes::ACCESS_GROUPS), $id)['ACCESS_GROUPS'];
			if( !$access_group ) {
				return 0;
			}
		}
		
		if($Database->Q(array(
			'query' => 'SELECT hierarchy FROM fks_access_groups WHERE id IN (' . $access_group . ')'
		))) {
			foreach($Database->r['rows'] AS $r) {
				if($r['hierarchy'] > $hierarchy) {
					$hierarchy = $r['hierarchy'];
				}
			}
		} else {
			return 0;
		}

		return $hierarchy;
	}
	
	// -------------------- Load Announcements -------------------- \\
	public function loadAnnouncements($site_settings) {
		// Set variables
		$announcements = array();
		$menu_items = array();
		$member = $_SESSION['id'];
		$Database = new \Database;
		
		// Grab all menu items
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => ' SELECT * FROM fks_menu_items'
		))) {
			return array('result' => 'failure', 'message' => 'DB Error loading menu items.');
		}
		
		// Create menu item url structures
		$rows = $Database->r['rows'];
		foreach($rows as $k => $v) {
			// Continue if no access
			if(!$this->checkAccess($v['label'], 1)) { continue; }
			
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $rows[$v['parent_id']]['url']);
				if($rows[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $rows[$rows[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$menu_items[$k] = implode('/', $url);
		}
		
		if(!$this->Session->guest()) {
			// Member is NOT a guest
			if($Database->Q(array(
				'params' => array(
					':member_id' => $member
				),
				'query' => '
					SELECT
						*
						
					FROM
						fks_announcements
						
					WHERE
						(
							date_created > (SELECT date_created FROM fks_members WHERE id = :member_id)
							OR sticky = 1
						)
						AND id NOT IN (SELECT announcement_id FROM fks_announcements_seen WHERE member_id = :member_id)
						AND active = 1
						AND deleted = 0
						
					ORDER BY
						id ASC
				'
			))) {
				if($Database->r['found'] > 0) {
					$DataTypes = new \DataTypes();
					$access_groups = $DataTypes->getData(array(\Enums\DataTypes::ACCESS_GROUPS), $member);
					if(!$access_groups['ACCESS_GROUPS']){return $announcements;}
					$access_groups = explode(',', $access_groups['ACCESS_GROUPS']);
					foreach($Database->r['rows'] as $k => $v) {
						if(!empty($v['access_groups']) && count(array_intersect($access_groups, explode(',', $v['access_groups']))) == 0) { continue; }
						$pages = array();
						if($v['pages'] == null) { $pages = null; }
						else {
							foreach(explode(',', $v['pages']) as $id) {
								// Continue if menu item is not set
								if(!isset($menu_items[$id])) { continue; }
								array_push($pages, $menu_items[$id]);
							}
						}
						
						// Continue if pages is not null and there are no pages in the array
						if($pages != null && count($pages) == 0) { continue; }
						
						$announcements[$v['id']] = array(
							'id' => $v['id'],
							'title' => $v['title'],
							'announcement' => $v['announcement'],
							'seen' => false,
							'viewed' => false,
							'pages' => $pages,
							'created' => $this->formatDateTime($v['date_created'])
						);
					}
				}
			}
		} else {
			// Member is a guest, set seen announcements array
			$announcements_seen = array();
			if(isset($_SESSION['announcements'])) {
				foreach($_SESSION['announcements'] as $k => $v) {
					if($v['seen']) { array_push($announcements_seen, $k); }
				}
			}
			
			if($Database->Q(array(
				'params' => array(
					':date_created' => $_SESSION['started']
				),
				'query' => '
					SELECT
						*
						
					FROM
						fks_announcements
						
					WHERE
						(
							date_created > :date_created
							OR sticky = 1
						)
						AND id NOT IN ("' . implode('","', $announcements_seen) . '")
						AND active = 1
						AND deleted = 0
						
					ORDER BY
						id ASC
				'
			))) {
				if($Database->r['found'] > 0) {
					$access_groups = explode(',', $site_settings['DEFAULT_ACCESS_GUEST']);
					foreach($Database->r['rows'] as $k => $v) {
						if(!empty($v['access_groups']) && count(array_intersect($access_groups, explode(',', $v['access_groups']))) == 0) { continue; }
						$pages = array();
						if($v['pages'] == null) { $pages = null; }
						else {
							foreach(explode(',', $v['pages']) as $id) {
								// Continue if menu item is not set
								if(!isset($menu_items[$id])) { continue; }
								array_push($pages, $menu_items[$id]);
							}
						}
						
						// Continue if pages is not null and there are no pages in the array
						if($pages != null && count($pages) == 0) { continue; }
						
						$announcements[$v['id']] = array(
							'id' => $v['id'],
							'title' => $v['title'],
							'announcement' => $v['announcement'],
							'seen' => false,
							'viewed' => false,
							'pages' => $pages,
							'created' => $this->formatDateTime($v['date_created'])
						);
					}
				}
			}
		}
		
		return $announcements;
	}
	
	// -------------------- Save Announcements -------------------- \\
	public function saveAnnouncements($seen) {
		if(count($seen) < 1) { return array('result' => 'failure', 'code' => 1); }
		
		// Set variables
		$member = $_SESSION['id'];
		$Database = new \Database;
		
		foreach($seen as $v) {
			if(!is_numeric($v) || $v < 1) { return array('result' => 'failure', 'code' => 2); }
			if(!isset($_SESSION['announcements'][$v])) { continue; }
			if(!$this->Session->guest()) {
				// Member is NOT a guest
				unset($_SESSION['announcements'][$v]);
				if(!$Database->Q(array(
					'params' => array(
						':announcement_id' => $v,
						':member_id' => $member,
						':seen_date' => gmdate('Y-m-d H:i:s')
					),
					'query' => '
						INSERT INTO
							fks_announcements_seen
						
						SET
							announcement_id = :announcement_id,
							member_id = :member_id,
							seen_date = :seen_date
					'
				))) {
					return array('result' => 'failure', 'code' => 3);
				}
			} else {
				// Member is a guest, set seen to true
				$_SESSION['announcements'][$v]['seen'] = true;
			}
		}

		return array('result' => 'success');
	}
}
?>