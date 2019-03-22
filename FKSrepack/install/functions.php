<?PHP namespace FKS\Install;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
//error_reporting(E_ALL & ~E_WARNING);

require_once(__DIR__ . '/../scripts/php/includes/Database.php');
require_once(__DIR__ . '/../scripts/php/includes/DataTypes.php');
require_once(__DIR__ . '/../scripts/php/includes/Validator.php');
require_once(__DIR__ . '/../scripts/php/includes/Enums.php');
require_once(__DIR__ . '/../scripts/php/includes/Session.php');

class Functions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $fks_version = '0.0.190322';
	public $Database;
	public $versions = array(
		'fks_versions' 				=> 1710220423,
		'fks_announcements_seen' 	=> 1706010435,
		'fks_announcements' 		=> 1710270044,
		'fks_access_groups' 		=> 1903010423,
		'fks_changelog_pages'		=> 1710250704,
		'fks_changelog_notes'		=> 1710260023,
		'fks_changelog'				=> 1710260522,
		'fks_member_data' 			=> 1710250149,
		'fks_member_data_types' 	=> 1903010423,
		'fks_member_logs' 			=> 1710260522,
		'fks_member_sites' 			=> 1903010020,
		'fks_members' 				=> 1710270601,
		'fks_menu_items' 			=> 1711160210,
		'fks_menus' 				=> 1710260656,
		'fks_site_errors' 			=> 1711160153,
		'fks_site_settings' 		=> 1903010423
	);
	public $current_versions = array();
	
	private $backup_data = null;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct(){
		// Destroy session
		$Session = new \Session();
		//if($Session->active()){$Session->destroy();}
		
		// Make a global database connection
		$this->Database = new \Database();
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	// -------------------- Check Table Exists --------------------
	private function checkTableExists($table){
		// Get schema name
		$schema = $this->Database->db[$this->Database->db['default']]['name'];
		
		// Check if table exists
		if($this->Database->Q(array(
			'params' => array(':schema' => $schema, ':table' => $table),
			'query' => 'SELECT * FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table LIMIT 1'
		))) {
			if($this->Database->r['found'] == 1) { return true; }
			else { return false; }
		} else {
			return false;
		}
	}
	
	// -------------------- Get Table Versions --------------------
	private function getTableVersions(){
		// Check to see if versions table exists
		if(!$this->checkTableExists('fks_versions')) {
			return array();
		}
		
		// Grab all table versions
		if($this->Database->Q(array(
			'assoc' => 'title',
			'query' => 'SELECT title,version FROM fks_versions'
		))) {
			return $this->Database->r['rows'];
		} else {
			return array();
		}
	}
	
	// -------------------- Update Table Versions --------------------
	private function updateTableVersions($table){
		// Check to see if versions table exists
		if(!$this->checkTableExists($table)) {
			return false;
		}
		
		// Add table version or update existing
		if($this->Database->Q(array(
			'params' => array(
				':title' => $table,
				':version' => $this->versions[$table]
			),
			'query' => 'INSERT INTO fks_versions SET title = :title, version = :version ON DUPLICATE KEY UPDATE version = :version'
		))) {
			return true;
		} else {
			return false;
		}
	}
	
	// -------------------- Update FKS Version --------------------
	private function updateFKSVersion() {
		$this->Database->Q(array(
			'params' => array(
				':version' => $this->fks_version
			),
			'query' => 'UPDATE fks_site_settings SET data = :version WHERE id = "FKS_VERSION"'
		));
	}
	
	// -------------------- Drop Table --------------------
	private function dropTable( $table_name ) {
		// Set database
		$Database = $this->Database;
		
		// Grab all existing versions
		if($table_name == 'fks_versions') {
			if($this->checkTableExists('fks_versions')) {
				if($Database->Q("SELECT * FROM fks_versions")){
					$this->current_versions = $Database->r['rows'];
				} else {
					return array('result' => 'failure', 'message' => $Database->r['error']);
				}
			}
		}
		
		// Drop table and return status
		if($Database->Q("DROP TABLE IF EXISTS `" . $table_name . "`;")) {
			return array('result' => 'success', 'message' => '');
		} else {
			return array('result' => 'failure', 'message' => $Database->r['error']);
		}
	}
	
	// -------------------- Backup Table --------------------
	private function backupTable( $table_name ) {
		// Set database
		$Database = $this->Database;
		
		// Grab all data from the table
		if(!$Database->Q("SELECT * FROM " . $table_name)) {
			return array('result' => 'failure', 'message' => $Database->r['error']);
		}
		
		// Save the table data so we can use it to restore
		$this->backup_data = $Database->r['rows'];
		
		// Create the directory if it doesn't exist
		if( !is_dir('backups/' . $table_name) ) {
			if( !mkdir('backups/' . $table_name, 0777, true) ) {
				return array('result' => 'failure', 'message' => 'Failed to create backup directory.');
			}
		}
		
		// Save file
		if( file_put_contents('backups/' . $table_name . '/' . $table_name . '-' . time() . '.txt', json_encode($Database->r['rows'])) === FALSE) {
			return array('result' => 'failure', 'message' => 'Failed to create backup file.');
		}
		
		// Return success
		return array('result' => 'success', 'message' => '');
	}
	
	// -------------------- Create Table --------------------
	private function createTable( $table_name ) {
		// Set vars
		$Database = $this->Database;
		$message = '';
		$success = true;
			
		switch($table_name)
		{
			case 'fks_access_groups':
				if(!$Database->Q("CREATE TABLE `fks_access_groups` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`title` VARCHAR(45) DEFAULT NULL,
						`hierarchy` INT(11) UNSIGNED NOT NULL,
						`home_page` INT(11) UNSIGNED DEFAULT NULL,
						`data` TEXT NOT NULL,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
			
			case 'fks_announcements':
				if(!$Database->Q("CREATE TABLE `fks_announcements` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`title` VARCHAR(45) DEFAULT NULL,
						`sticky` TINYINT(1) UNSIGNED NOT NULL,
						`announcement` TEXT NOT NULL,
						`access_groups` TINYTEXT DEFAULT NULL,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`pages` TINYTEXT DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_announcements_seen':
				if(!$Database->Q("CREATE TABLE `fks_announcements_seen` (
						`announcement_id` INT(11) UNSIGNED NOT NULL,
						`member_id` INT(11) UNSIGNED NOT NULL,
						`seen_date` DATETIME NOT NULL,
						PRIMARY KEY (`announcement_id`,`member_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_changelog':
				if(!$Database->Q("CREATE TABLE `fks_changelog` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`title` VARCHAR(45),
						`version` VARCHAR(45) UNIQUE NOT NULL,
						`notes` TINYTEXT,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_changelog_notes':
				if(!$Database->Q("CREATE TABLE `fks_changelog_notes` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`changelog_id` INT(11) UNSIGNED NOT NULL,
						`type` VARCHAR(10) NOT NULL,
						`data` TINYTEXT NOT NULL,
						PRIMARY KEY (`id`,`changelog_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_changelog_pages':
				if(!$Database->Q("CREATE TABLE `fks_changelog_pages` (
						`page_id` INT(11) UNSIGNED NOT NULL,
						`note_id` INT(11) UNSIGNED NOT NULL,
						PRIMARY KEY (`page_id`,`note_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_member_data':
				if(!$Database->Q("CREATE TABLE `fks_member_data` (
						`id` INT(11) UNSIGNED NOT NULL,
						`member_id` INT(11) UNSIGNED NOT NULL,
						`data` TEXT NOT NULL,
						PRIMARY KEY (`id`,`member_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_member_data_types':
				if(!$Database->Q("CREATE TABLE `fks_member_data_types` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`title` VARCHAR(40) NOT NULL,
						`description` VARCHAR(40) NOT NULL,
						`input_type` VARCHAR(40) NOT NULL,
						`help_text` VARCHAR(40) NOT NULL,
						`position` TINYINT(4) UNSIGNED DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_member_logs':
				if(!$Database->Q("CREATE TABLE `fks_member_logs` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`action` INT(11) UNSIGNED NOT NULL,
						`member_id` INT(11) UNSIGNED NOT NULL,
						`target_id` VARCHAR(32) DEFAULT NULL,
						`misc` TEXT,
						`date_created` DATETIME NOT NULL,
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_member_sites':
				if(!$Database->Q("CREATE TABLE `fks_member_sites` (
						`member_id` INT(11) UNSIGNED NOT NULL,
						`site_id` INT(11) UNSIGNED NOT NULL,
						`date_created` DATETIME NOT NULL,
						PRIMARY KEY (`member_id`,`site_id`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_members':
				if(!$Database->Q("CREATE TABLE `fks_members` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`username` VARCHAR(40) NOT NULL,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`last_online` DATETIME DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_menu_items':
				if(!$Database->Q("CREATE TABLE `fks_menu_items` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`menu_id` INT(11) UNSIGNED NOT NULL,
						`parent_id` INT(11) UNSIGNED NOT NULL,
						`pos` INT(11) UNSIGNED DEFAULT '0',
						`title` VARCHAR(40) NOT NULL,
						`title_data` VARCHAR(40) DEFAULT NULL,
						`has_separator` TINYINT(1) NOT NULL,
						`is_external` TINYINT(1) UNSIGNED DEFAULT '0',
						`is_parent` TINYINT(1) UNSIGNED DEFAULT '0',
						`has_content` TINYINT(1) UNSIGNED DEFAULT '1',
						`url` TINYTEXT NOT NULL,
						`icon` VARCHAR(20) DEFAULT NULL,
						`label` VARCHAR(40) UNIQUE DEFAULT NULL,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`hidden` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_menus':
				if(!$Database->Q("CREATE TABLE `fks_menus` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`title` VARCHAR(40) NOT NULL,
						`date_created` DATETIME NOT NULL,
						`created_by` INT(11) UNSIGNED DEFAULT '0',
						`date_modified` DATETIME DEFAULT NULL,
						`modified_by` INT(11) UNSIGNED DEFAULT NULL,
						`date_deleted` DATETIME DEFAULT NULL,
						`deleted_by` INT(11) UNSIGNED DEFAULT NULL,
						`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
						`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_site_errors':
				if(!$Database->Q("CREATE TABLE `fks_site_errors` (
						`error_code` VARCHAR(16) NOT NULL,
						`error_file` TINYTEXT NOT NULL,
						`error_line` INT(10) UNSIGNED NOT NULL,
						`error_function` TINYTEXT NOT NULL,
						`error_class` TINYTEXT NOT NULL,
						`error_member` INT(11) NOT NULL,
						`error_message` TEXT NOT NULL,
						`error_created` DATETIME NOT NULL,
						PRIMARY KEY (`error_code`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_site_settings':
				if(!$Database->Q("CREATE TABLE `fks_site_settings` (
						`id` VARCHAR(50) NOT NULL,
						`title` VARCHAR(50) NOT NULL,
						`type` VARCHAR(10) NOT NULL,
						`data` TEXT DEFAULT NULL,
						`misc` VARCHAR(100) DEFAULT '{}',
						`help_text` TINYTEXT DEFAULT NULL,
						`description` TEXT DEFAULT NULL,
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_versions':
				if(!$Database->Q("CREATE TABLE `fks_versions` (
						`title` VARCHAR(45) NOT NULL,
						`version` INT(10) UNSIGNED NOT NULL,
						PRIMARY KEY (`title`)
					) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
			
			default:
				break;
		}
		
		// Return status and message
		return array('result' => ($success ? 'success' : 'failure'), 'message' => $message);
	}
	
	// -------------------- Fill Table --------------------
	private function fillTable( $table_name ) {
		// Set vars
		$Database = $this->Database;
		$message = '';
		$success = true;
			
		switch($table_name)
		{
			case 'fks_access_groups':
				if(!$Database->Q('INSERT INTO fks_access_groups VALUES 
					(1,"Guest",1,NULL,"{\"1\":1}","2017-01-19 07:08:00",0,NULL,NULL,NULL,NULL,1,0),
					(2,"User",2,NULL,"{\"2\":1,\"3\":2,\"4\":1,\"11\":1,\"13\":2}","2017-01-20 08:48:00",0,NULL,NULL,NULL,NULL,1,0),
					(3,"Admin",9001,NULL,"{\"2\":3,\"3\":3,\"4\":3,\"5\":3,\"6\":3,\"7\":3,\"8\":3,\"9\":3,\"10\":3,\"11\":3,\"12\":3,\"13\":3,\"14\":3}","2017-01-19 07:11:00",0,NULL,NULL,NULL,NULL,1,0)'
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_member_data_types':
				if(!$Database->Q('INSERT INTO fks_member_data_types VALUES 
					(1,"Username","Description","text","Help text.",NULL,1,0),
					(2,"First Name","","","",NULL,1,0),
					(3,"Last Name","","","",NULL,1,0),
					(4,"Email Address","","","",NULL,1,0),
					(5,"Avatar","","","",NULL,1,0),
					(6,"Date Format","","","",NULL,1,0),
					(7,"Timezone","","","",NULL,1,0),
					(8,"Access Groups","","","",NULL,1,0),
					(9,"Verify Code","","","",NULL,1,0),
					(10,"Password","","","",NULL,1,0),
					(11,"Full Name","","","",NULL,1,0),
					(12,"Email Verified","","","",NULL,1,0),
					(13,"Site Layout","","","",NULL,1,0),
					(14,"Two Factor","","","",NULL,1,0),
					(15,"Home Page","","","",NULL,1,0)'
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_menu_items':
				if(!$Database->Q('INSERT INTO fks_menu_items VALUES 
					(1,2,0,98,"Log In",NULL,0,0,0,0,"login","sign-in","log_in","2017-01-18 10:25:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(2,2,0,99,"Member Menu","FIRST_NAME,USERNAME",0,0,1,0,"member","user-circle","member_menu","2017-10-18 07:23:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(3,2,2,1,"Account Settings",NULL,0,0,0,1,"settings","cogs","account_settings","2017-10-18 07:23:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(4,2,2,99,"Log Out",NULL,1,1,0,0,"#logout","sign-out","log_out","2017-01-18 10:25:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(5,1,0,99,"Admin",NULL,0,0,1,0,"admin","cogs","admin","2017-01-18 06:41:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(6,1,5,2,"Announcements",NULL,0,0,0,1,"announcements","bullhorn","admin_announcements","2017-06-01 10:07:18",1,NULL,NULL,NULL,NULL,1,0,0),
					(7,1,5,1,"Access Groups",NULL,0,0,0,1,"access","lock","access_groups","2017-01-18 07:11:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(8,1,5,4,"Members",NULL,0,0,0,1,"members","users","members","2017-01-18 06:42:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(9,1,5,5,"Menus",NULL,0,0,0,1,"menus","list-ul","menus","2017-01-18 10:18:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(10,1,5,7,"Site Settings",NULL,0,0,0,1,"settings","server","site_settings","2017-01-18 07:12:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(11,1,0,98,"Site Name","SITE_TITLE",0,0,1,0,"site","globe","site","2017-01-20 09:45:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(12,1,5,3,"Changelog",NULL,0,0,0,1,"changelog","list-alt","changelog","2017-01-20 09:45:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(13,1,11,2,"Tracker",NULL,0,0,0,1,"reporter","crosshairs","tracker","2017-01-20 09:45:00",0,NULL,NULL,NULL,NULL,1,0,0),
					(14,1,5,6,"Site Errors",NULL,0,0,0,1,"errors","exclamation-triangle","errors","2017-11-16 09:45:00",0,NULL,NULL,NULL,NULL,1,0,0)'
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_menus':
				if(!$Database->Q('INSERT INTO fks_menus VALUES 
					(1,"Main","2017-01-18 06:06:00",0,NULL,NULL,NULL,NULL,1,0),
					(2,"Member","2017-01-18 06:07:00",0,NULL,NULL,NULL,NULL,1,0)'
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_site_settings':
				if(!$Database->Q('INSERT INTO fks_site_settings VALUES 
					("ACTIVE_DIRECTORY","Active Directory","bool","0","{}","Whether or not members can use AD to log in.","Whether or not members can log in using Active Directory. This requires that the reset of the settings in this tab to be filled out."),
					("AD_ACCOUNT_CREATION","Create Account","bool","1","{}","Create a new member on the website if it doesn\'t exist.","When this is enabled a member account will be created in the site DB when there is a successful LDAP login. When disabled if a member can successfully log in with LDAP but the member does not exist in the website the login will fail and not let the member log in."),
					("AD_BASE_DN","Base DN","text","OU=SBSUsers,OU=Users,OU=MyBusiness,DC=myDomain,DC=local","{}","The base DN for the member directory.","Make sure this is the base directory for ALL users that you want to log in to this site. This is only used for looking up member data for new LDAP members."),
					("AD_FAILOVER","Allow Failover","bool","0","{}","Whether or not to attempt to validate the other option.","If the site fails to validate a member with the Preferred Login, enabling this option will have the site attempt the other method as well. I.E. if AD is the Preferred Login and a member fails to validate or the site is unable to connect to the AD server the site will then try to validate the member using Local Login."),
					("AD_LOGIN_SELECTOR","Login Selector","bool","0","{}","Allows member to choose what login method to use.","Whether or not members can choose what method to use to log in. When enabeled there will be a dropdown field added to the login form which allows users to choose to log in with AD, Local, or to use the site\'s default order."),
					("AD_PREFERRED","Preferred Login","dropdown","Local","{\"options\":[\"Local\",\"LDAP\"]}","Where the server should attempt to validate members first.","This option will tell the site where to attempt to validate members when they try to log in. If Active Directory is disabled this has to be set to Local!"),
					("AD_RDN","Server RDN","text",NULL,"{}","The domain name the AD server uses.","The Relative Distinguished Name that the active directory users must use to attempt a login."),
					("AD_SERVER","Server Address","text",NULL,"{}","IP address or hostname of the AD server.","This is the IP address or the hostname of the Active Directory server."),
					("CAPTCHA","Captcha","bool","0","{}","Whether or not users have to pass a Captcha to register.","When enabled it requires users to pass a reCaptcha when attempting to register for an account."),
					("CAPTCHA_PRIVATE","Captcha Private Key","password",NULL,"{}","Get a key from <a href=\"https://www.google.com/recaptcha/\" target=\"_blank\">Google</a>.","This is a secret key that you don\'t give out to anyone."),
					("CAPTCHA_PUBLIC","Captcha Public Key","text",NULL,"{}","Get a key from <a href=\"https://www.google.com/recaptcha/\" target=\"_blank\">Google</a>.","This is a public key..."),
					("DATE_FORMAT","Date Format","text","Y-m-d H:i:s","{\"required\":\"true\"}","Members will use this date format unless they set their own. <a href=\"http://php.net/manual/en/function.date.php\" target=\"_blank\">PHP Date</a>","This is the default format for all dates and times on the server. Members can set their own format to overide this."),
					("DEFAULT_ACCESS_GUEST","Guest Default Access Group","number","1","{}","Default Access Group(s) for accounts that aren\'t logged in.","This group deals with people who visit your site but are not logged in. This is only useful if you do not require log in."),
					("DEFAULT_ACCESS_LDAP","LDAP Default Access Group","number","2","{}","Default Access Group(s) for accounts that are created using LDAP.","This group is for accounts that are created through LDAP log in as long as the setting is enabled. This only happens on the first log in if the member needs to be created in the database."),
					("DEFAULT_ACCESS_LOCAL","Local Default Access Group","number","2","{}","Default Access Group(s) for accounts that are created through registration.","This group is for accounts that are created on the Members Page. Or when someone registers if member registration is enabled."),
					("EMAIL_AUTH","Email Authentication","bool","0","{}","If the SMTP server requires authentication..","Turn this on if your SMTP server requires outgoing authentication. If this setting is enabled you need to fill out the Username and Password fields below."),
					("EMAIL_FROM_ADDRESS","Default From Address","email",NULL,"{}","Emails sent from this site will come from this address.","Emails sent from this address will use this address. This is the default From Address setting and if any other \"From Address\" is missing it will use this setting."),
					("EMAIL_HOSTNAME","SMTP Hostname","text",NULL,"{}","Hostname for the SMTP server.","Hostname for the SMTP server."),
					("EMAIL_PASSWORD","Auth Password","password",NULL,"{}","Password for the email auth account.","This is the password used for the username to log in to the SMTP server for outgoing authentication. This has to be filled out if authentication is enabled."),
					("EMAIL_PORT","Port Number","number","25","{}","Port number of the SMTP server.","Port number of the SMTP server."),
					("EMAIL_REPLY_TO_ADDRESS","Default Reply-To Address","email",NULL,"{}","Email sent from this site will be replied to this address.","Emails sent from this address will use this as the reply-to address. This is the default Rely-To Address and if any other \"Reply-To Address\" is missing it will use this setting."),
					("EMAIL_SECURE","Security","dropdown","None","{\"options\":[\"None\",\"TLS\",\"SSL\"]}","Select what security the SMTP server requires.","If Email Auth is enabled then you need to select what kind of security is required by the SMTP server."),
					("EMAIL_USERNAME","Auth Username","text",NULL,"{}","Email address or username for the auth account.","This is the email address or username used to log in to the SMTP server for outgoing authentication. This has to been filled out if authentication is enabled."),
					("EMAIL_VERIFICATION","Email Verification","bool","0","{}","Whether or not a valid email address is required on registration.","If this is enabled then when a user attempts to register a new account they will be forced to include an email address. They will then be sent an email with a link that they will need to click before the account is activated."),
					("EMAIL_VERIFICATION_FROM_ADDRESS","From Address","email",NULL,"{}","Emails sent for verification will come from this address.","Emails sent from this site for verification will come from this address. This setting CAN be blank, if it is it will use the default From Address."),
					("EMAIL_VERIFICATION_REPLY_TO_ADDRESS","Reply-To Address","email",NULL,"{}","Emails sent for verification will be replied to this address.","Emails sent from this site for verification will be replied to this address. This setting CAN be blank, if it is it will use the default Reply-To Address."),
					("EMAIL_VERIFICATION_SUBJECT","Email Subject","text","%SITE_TITLE% - Email Verification","{}","What the subject for email verifications will say.","What the subject for email verifications will say."),
					("EMAIL_VERIFICATION_TEMPLATE","Email Template","div","<p>Hello,</p><p>Someone with this email address is attempting to register on our site. If this is not you then you can ignore this message other wise your code is below.</p><p>Verification Code: %VERIFY_CODE%</p><p>- %SITE_TITLE%</p>","{}","What emails will look like when sending verification codes.","What emails will look like when sending verification codes."),
					("ERROR_EMAIL","Email Errors","bool",0,"{}","Whether to email errors to someone.","Enabling this will send an email with the error code and error description. This requires that the General tab of Email is filled out."),
					("ERROR_EMAIL_ADDRESS","Email Address","email",NULL,"{}","Who should receive the email.","When Email Errors is enabled FKS will send the errors to this address."),
					("ERROR_MESSAGE","Returned Message","text","Error Code: %CODE%","{}","What the toaster message will say.","What the toaster message will say when an error occurs.<br/><u>Keywords</u> - %CODE%, %FUNCTION%, %LINE%, %CLASS%, and %FILE%."),
					("ERROR_TO_DB","Save to DB","bool",1,"{}","Whether you want to store errors in the DB.","Enabling this option will store errors in the database."),
					("ERROR_TO_DISK","Save to Disk","dropdown","Fallback","{\"options\":[\"Yes\",\"Fallback\",\"No\"]}","Whether you want to store errors to disk.","<u>Yes</u> - Always store errors to disk.<br/><u>Fallback</u> - Only store errors on a database failure.</br><u>No</u> - Never store errors to disk."),
					("FORGOT_PASSWORD","Forgot Password","bool","0","{}","Whether or not users can reset their passwords via email.","When enabled a user may request to have an email sent to reset their password. If Email Verification is not enabled then it\'s possible that members do not have an email address."),
					("FORGOT_PASSWORD_FROM_ADDRESS","From Address","email",NULL,"{}","Emails sent for forgot password will come from this address.","Emails sent from this site for forgot password will come from this address. This setting CAN be blank, if it is it will use the default From Address."),
					("FORGOT_PASSWORD_REPLY_TO_ADDRESS","Reply-To Address","email",NULL,"{}","Email sent for forgot password will be replied to this address.","Emails sent from this site for forgot password will be replied to this address. This setting CAN be blank, if it is it will use the default Reply-To Address."),
					("FORGOT_PASSWORD_SUBJECT","Email Subject","text","%SITE_TITLE% - Forgot Password","{}","What the subject for forgot passwords will say.","What the subject for forgot passwords will say."),
					("FORGOT_PASSWORD_TEMPLATE","Email Template","div",NULL,"{}","What emails will look like when sending forgot password codes.","What emails will look like when sending forgot password codes."),
					("MEMBER_REGISTRATION","Member Registration","bool","0","{\"required\":\"true\"}","Whether or not the registration form is enabled on the log in page.","Allows guests to create accounts to log in with. Once created they will be assigned the Default Local Access Group, which can be changed in the Access tab."),
					("PROTECTED_USERNAMES","Protected Usernames","textarea","admin,administrator,fksrepack,snaggybore,flyingkumquat","{\"attributes\":\"rows=\'3\'\"}","Usernames that are not allowed during registration.","A comma seperated list of usernames that will not be allowed to be used during user registration. Capitalization does not matter."),
					("REQUIRE_LOGIN","Require Login","bool","0","{\"required\":\"true\"}","Whether or not users have to log in to view the site.","This option forces that members log in to access this site. With this off members who are not logged in will be assigned the Default Guest Access Group, which can be changed in the Access tab."),
					("REMOTE_DATABASE","Remote Database","text",NULL,"{}","Which connection to use for the remote site.","Select which database connection to use for the remote site. This is only used if Remote Site is set to Secondary."),
					("REMOTE_ID","Remote ID","number",NULL,"{}",NULL,NULL),
					("REMOTE_SITE","Remote Site","dropdown","Disabled","{\"required\":\"true\",\"options\":[\"Disabled\",\"Primary\",\"Secondary\"]}","Whether to enable remote connections.","<u>Disabled</u> - Stand alone site.<br/><u>Primary</u> - Allows other sites to connect to this site to use log in information.<br/><u>Secondary</u> - Will use log in information from a primary site."),
					("REMOTE_SITE_IDS","Remote Site ID\'s","text",NULL,"{}",NULL,NULL),
					("SITE_HOME_PAGE","Site Home Page","web_page",NULL,"{}","What page should load on visit.","Select which page should load for guests when visiting the site. Access groups can have their own default home page set but if they are blank they will default to this setting."),
					("SITE_LAYOUT","Site Layout","dropdown","Default","{\"options\":[\"Default\",\"Admin\"]}","This changes the layout of the whole site.","<u>Default</u> - This is a fixed width site.<br/><u>Admin</u> - This takes up the full width of the window."),
					("SITE_TITLE","Site Title","text","FKSrepack","{\"required\":\"true\"}","This is shown in the breadcrumbs and brower title.","This is shown in the browser window or tab as well as the breadcrumbs."),
					("SITE_USERNAME","Site Username","text","server","{\"required\":\"true\"}","This is username that\'s used when the site creates things.","This is the username that will be displayed when the website creates or modifies anything."),
					("SITE_VERSION","Version","text","0","{\"required\":\"true\"}","This is the version number of this site.","This is the version number of this site."),
					("FKS_VERSION","Version","text","' . $this->fks_version . '","{\"required\":\"true\"}","This is the version number of FKSrepack.","This is the version number of FKSrepack."),
					("TIMEZONE","Timezone","timezone",NULL,"{}","Members will use this timezone unless they set their own.","This is the default timezone for all dates and times on this server. Members can set their own timezones to overide this.")
					'
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_versions':
				foreach($this->current_versions as $v) {
					if(!$Database->Q(array(
						'params' => array(
							':title' => $v['title'],
							':version' => $v['version']
						),
						'query' => 'INSERT INTO fks_versions SET title = :title, version = :version'
					))){
						$message = 'One or more verions could not be inserted!';
						$success = false;
					}
				}
				break;
				
			default:
				// If the table doesn't have a case then we don't want to return false (nothing to fill)
				// However, if the table passed doesn't exist we do want to fail
				if(isset($this->fks_versions[$table_name])){$success = false;}
				break;
		}
		
		// Return status and message
		return array('result' => ($success ? 'success' : 'failure'), 'message' => $message);
	}
	
	// -------------------- Restore Table --------------------
	private function restoreTable( $table_name ) {
		// Set vars
		$Database = $this->Database;
		$message = '';
		$success = true;
		
		// Get primary key(s)
		$table_keys = $Database->primaryKeys($table_name);
		
		switch($table_name)
		{
			// Special query
			case 'fks_site_settings':
				// Cycle through each row
				foreach( $this->backup_data as $row ) {
					// Set variables
					$params = null;				// Array of parametrized values
					$query = array();			// Insert query
					
					// Loop through each field to generate query
					foreach( $row as $f => $d ) {
						$params[':' . $f] = $d;
						array_push($query, $f . ' = :' . $f);
					}
					
					// Insert into table
					if(!$Database->Q(array(
						'params' => $params,
						'query' => 'INSERT INTO ' . $table_name . ' SET ' . implode(', ', $query) . ' ON DUPLICATE KEY UPDATE data = :data'
					))){
						$message = $Database->r['error'];
						$success = false;
					}
				}
				break;
			
			// Default query
			default:
				// Cycle through each row
				foreach( $this->backup_data as $row ) {
					// Set variables
					$params = null;				// Array of parametrized values
					$query = array();			// Insert query
					$update = array();			// On dupe update query
					
					// Loop through each field to generate query
					foreach( $row as $f => $d ) {
						$params[':' . $f] = $d;
						array_push($query, $f . ' = :' . $f);
						
						// Check for primary key(s)
						if( !in_array($f, $table_keys) ) {
							array_push($update, $f . ' = :' . $f);
						}
					}
					
					// Insert into table
					if(!$Database->Q(array(
						'params' => $params,
						'query' => 'INSERT INTO ' . $table_name . ' SET ' . implode(', ', $query) . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update)
					))){
						$message = $Database->r['error'];
						$success = false;
					}
				}
				break;
		}
		
		// Return status and message
		return array('result' => ($success ? 'success' : 'failure'), 'message' => $message);
	}
	
	// -------------------- Add Foreign Key --------------------
	// NOTE: This is no longer used
	private function addForeignKey($table_name) {
		// Set vars
		$Database = $this->Database;
		$message = '';
		$success = true;
			
		switch($table_name)
		{
			case 'fks_changelog_pages':
				if(!$Database->Q("ALTER TABLE `fks_changelog_pages` 
					ADD INDEX (`note_id`);
					ALTER TABLE `fks_changelog_pages` 
						ADD CONSTRAINT
							FOREIGN KEY (`page_id`)
							REFERENCES `fks_menu_items` (`id`)
							ON DELETE CASCADE
							ON UPDATE CASCADE,
					ADD CONSTRAINT
						FOREIGN KEY (`note_id`)
							REFERENCES `fks_changelog_notes` (`id`)
							ON DELETE CASCADE
							ON UPDATE CASCADE;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
			
			case 'fks_changelog_notes':
				if(!$Database->Q("ALTER TABLE `fks_changelog_notes` 
					ADD INDEX (`changelog_id`);
					ALTER TABLE `fks_changelog_notes` 
					ADD CONSTRAINT
					FOREIGN KEY (`changelog_id`)
					REFERENCES `fks_changelog` (`id`)
					ON DELETE CASCADE
					ON UPDATE CASCADE;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			case 'fks_menu_items':
				if(!$Database->Q("ALTER TABLE `fks_menu_items` 
					ADD INDEX (`menu_id`);
					ALTER TABLE `fks_menu_items` 
					ADD CONSTRAINT
					FOREIGN KEY (`menu_id`)
					REFERENCES `fks_menus` (`id`)
					ON DELETE CASCADE
					ON UPDATE CASCADE;"
				)){ 
					$message = $Database->r['error'];
					$success = false;
				}
				break;
				
			default:
				$success = false;
				break;
		}
		
		// Return status and message
		return array('result' => ($success ? 'success' : 'failure'), 'message' => $message);
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
			'body' => $body,
			'footer' => '<button class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>'
		);
		
		return array('result' => 'success', 'parts' => $parts);
	}
	
	// ---------------------------------------------------------------
	// Test Database Public Function
	// ---------------------------------------------------------------
	public function testDatabase(){
		// Set vars
		$Database = $this->Database;
		$tables = array();
		$return = '';
		$current = 0;
		
		// Test connection
		if($Database->con()){
			$message = 'Connection Successful!</br>------------------<br/>';
			foreach($Database->db[$Database->db['default']] as $k => $v){
				$message .= $k . ' = ' . $v . '<br/>';
			}
		} else {
			$message = 'Connection Failed!</br>------------------<br/>';
			foreach($Database->db[$Database->db['default']] as $k => $v){
				$message .= $k . ' = ' . $v . '<br/>';
			}
			$message .= '<br/>' . $Database->r['error'];
			
			return array('result' => 'failure', 'message' => $message);
		}
		
		// Get table versions
		$current_versions = $this->getTableVersions();
		
		// Check for existing tables and version numbers
		foreach( $this->versions as $k => $v ) {
			// Check exists
			$tables[$k]['exists'] = $this->checkTableExists($k) ? 1 : 0;
			
			// Get version
			$tables[$k]['version'] = isset($current_versions[$k]['version']) ? $current_versions[$k]['version'] : 0;
		}
		
		// Build return table
		$return .= '<form>';
		$return .= '<table class="table table-striped table-hover table-sm table-bordered" style="margin-bottom:10px;">';
		$return .= '<thead class="thead-dark"><tr><th><input type="checkbox" class="header_checkbox"></th><th>Backup and Restore</th><th>Table Name</th><th>Current Version</th><th>Latest Version</th></tr></thead>';
		foreach($tables as $k => $v) {
			
			if($v['exists'] == 0) {
				$current = '<span style="color:red;">table missing</span>';
			} elseif($v['version'] < $this->versions[$k]) {
				$current = '<span style="color:red">' . $v['version'] . '</span>';
			} else {
				$current = '<span style="color:green">' . $v['version'] . '</span>';
			}
			
			
			$return .= '<tr>
				<td style="width:30px"><input type="checkbox" class="table-checkbox" name="' . $k . '" value="1"' . ($v['exists'] == 0 || $v['version'] < $this->versions[$k] ? ' checked' : '') . '></td>
				<td><select class="form-control form-control-sm table-action-select"><option value="0">no action</option><option value="1">backup</option><option value="2"' . ($v['exists'] == 0 || $v['version'] < $this->versions[$k] ? ' selected' : '') . '>backup & restore</option></select></td>
				<td>' . $k . '</td>
				<td>' . $current . '</td>
				<td>' . $this->versions[$k] . '</td>
			</tr>';
		}
		$return .= '</table></form>';
		
		// Return
		return array('result' => 'success', 'message' => $message, 'table' => $return);
	}
	
	// ---------------------------------------------------------------
	// Create Database Tables Public Function
	// ---------------------------------------------------------------
	public function createTables( $tables ){
		// Check to make sure tables is set and has data
		if( !isset($tables) || empty($tables) ) { $this->updateFKSVersion(); return array('result' => 'success', 'message' => 'No tables selected...'); }
		
		// Set vars
		$Database = $this->Database;
		$actions = '';
		$result = 'success';
		
		// Loop through each selected table
		foreach( $tables as $t => $v ) {
			// Check to make sure table is legit
			if( isset( $this->versions[$t] ) ) {
				$actions[$t] = '<br/>-- ' . $t . ' --';
			} else {
				$actions[$t] = '<br/>-- ' . $t . ' --' . '<br/>&#9;Unknown Table!';
				continue;
			}
			
			// Backup table
			if( $v == 1 || $v == 2 ) {
				$status = $this->backupTable($t);
				if( $status['result'] == 'success' ) {
					$actions[$t] .= '<br/>&#9;Backed up table';
				} else {
					$actions[$t] .= '<br/>&#9;Failed to backup table - ' . $status['message'];
					$result = 'failure';
					continue;
				}
			}
			
			// Drop table
			$status = $this->dropTable($t);
			if( $status['result'] == 'success' ) {
				$actions[$t] .= '<br/>&#9;Dropped table';
			} else {
				$actions[$t] .= '<br/>&#9;Failed to drop table - ' . $status['message'];
				$result = 'failure';
				continue;
			}
			
			// Create table
			$status = $this->createTable($t);
			if( $status['result'] == 'success' ) {
				$actions[$t] .= '<br/>&#9;Created table';
			} else {
				$actions[$t] .= '<br/>&#9;Failed to create table - ' . $status['message'];
				$result = 'failure';
				continue;
			}
			
			// Fill table
			$status = $this->fillTable($t);
			if( $status['result'] == 'success' ) {
				$actions[$t] .= '<br/>&#9;Filled table';
			} else {
				$actions[$t] .= '<br/>&#9;Failed to fill table - ' . $status['message'];
				$result = 'failure';
				continue;
			}
			
			// Restore table
			if( $v == 2 ) {
				$status = $this->restoreTable($t);
				if( $status['result'] == 'success' ) {
					$actions[$t] .= '<br/>&#9;Restored table';
				} else {
					$actions[$t] .= '<br/>&#9;Failed to restore table - ' . $status['message'];
					$result = 'failure';
					continue;
				}
			}
			
			// Update table versions
			$this->updateTableVersions($t);
		}
		
		// Update FKS version
		$this->updateFKSVersion();
		
		// Return status and messages
		return array('result' => $result, 'message' => implode('<br/>', $actions), 'data' => $tables);
	}
	
	// ---------------------------------------------------------------
	// Create Admin Account Public Function
	// ---------------------------------------------------------------
	public function createAccount( $data ){
		// Skip?
		if(isset($data['skip']) && $data['skip'] == 1){return array('result' => 'success', 'message' => 'Skipped admin account creation.');}
		
		// Validation
		$Validator = new \Validator($data);
		$Validator->validate('username', array('required' => true, 'min_length' => 3, 'max_length' => 40));
		$Validator->validate('password', array('required' => true, 'min_length' => 3, 'max_length' => 40));
		if( !$Validator->getResult() ){ return array('result' => 'failure', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		$data = $Validator->getForm();
		
		// Hash Password
		require_once($_SERVER['DOCUMENT_ROOT'] . '/scripts/php/includes/PasswordHash.php');
		$_ph = new \PasswordHash(13, FAlSE);
		$data['password'] = $_ph->HashPassword($data['password']);
		
		// Set Vars
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		
		// Search for existing active members
		if(!$Database->Q(array(
			'params' => array(
				':username' => $data['username']
			),
			'query' => 'SELECT id FROM fks_members WHERE username = :username AND active = 1'
		))){
			return array('result' => 'failure', 'message' => 'Could not lookup accounts.');
		}
		
		// Return if there is an active member with same name
		if($Database->r['found'] > 0) {
			return array('result' => 'failure', 'message' => 'Account already exists!');
		}
		
		// Create Member
		if(!$Database->Q(array(
			'params' => array(
				':username' => $data['username'],
				':time' => gmdate('Y-m-d h:i:s'),
				':tech' => 0
			),
			'query' => '
				INSERT INTO 
					fks_members 
				
				SET 
					username = :username,
					date_created = :time,
					created_by = :tech
				'
		))){
			return array('result' => 'failure', 'message' => 'Could not create account.');
		}
		
		// Get member id
		$member_id = $Database->r['last_id'];
		
		// Set member data
		$DataTypes->setData(
			array(
				\Enums\DataTypes::PASSWORD['id'] => $data['password'],
				\Enums\DataTypes::ACCESS_GROUPS['id'] => 3
			), 
			$member_id,
			true
		);
		
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