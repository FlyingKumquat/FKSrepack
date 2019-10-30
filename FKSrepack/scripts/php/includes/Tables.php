<?PHP
/*----------------------------------------------------------------------------------------------------
	Debug / Error reporting
----------------------------------------------------------------------------------------------------*/
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

require_once('Database.php');

class Tables {
/*----------------------------------------------------------------------------------------------------
	Global Variables
----------------------------------------------------------------------------------------------------*/
	// Public variables
	public $tables = array();
	
	// Private variables
	private $Database = null;
	
/*----------------------------------------------------------------------------------------------------
	Construct
----------------------------------------------------------------------------------------------------*/
	public function __construct($action = null, $member_id = null, $target_id = null, $misc = null) {
		// 
		$this->Database = new \Database();
		
		//
		$this->tables = array(
			$this->Database->db['default'] => array(
				'fks_access_groups' => array(
					'name' => 'fks_access_groups',
					'version' => 1903010423,
					'restore' => array(
						'include' => array('date_modified', 'modified_by', 'date_deleted', 'deleted_by', 'active', 'deleted')
					),
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'title' => array('VARCHAR(45)', 'DEFAULT NULL'),
						'hierarchy' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'home_page' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'data' => array('TEXT', 'NOT NULL'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					),
					'rows' => array(
						array('id' => 1, 'title' => 'Guest', 'hierarchy' => 1, 'data' => '{"1":1}', 'date_created' => '2017-01-19 07:08:00'),
						array('id' => 2, 'title' => 'User', 'hierarchy' => 2, 'data' => '{"2":1,"3":2,"4":1,"11":1,"13":2}', 'date_created' => '2017-01-20 08:48:00'),
						array('id' => 3, 'title' => 'Admin', 'hierarchy' => 9001, 'data' => '{"2":3,"3":3,"4":3,"5":3,"6":3,"7":3,"8":3,"9":3,"10":3,"11":3,"12":3,"13":3,"14":3}', 'date_created' => '2017-01-19 07:11:00')
					)
				),
				'fks_announcements' => array(
					'name' => 'fks_announcements',
					'version' => 1710270044,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'title' => array('VARCHAR(45)', 'DEFAULT NULL'),
						'sticky' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'announcement' => array('TEXT', 'NOT NULL'),
						'access_groups' => array('TINYTEXT', 'DEFAULT NULL'),
						'pages' => array('TINYTEXT', 'DEFAULT NULL'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					)
				),
				'fks_announcements_seen' => array(
					'name' => 'fks_announcements_seen',
					'version' => 1706010435,
					'columns' => array(
						'announcement_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'member_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'seen_date' => array('DATETIME', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_changelog' => array(
					'name' => 'fks_changelog',
					'version' => 1710260522,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'title' => array('VARCHAR(45)', 'DEFAULT NULL'),
						'version' => array('VARCHAR(45)', 'UNIQUE', 'NOT NULL'),
						'notes' => array('TINYTEXT', 'DEFAULT NULL'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					)
				),
				'fks_changelog_notes' => array(
					'name' => 'fks_changelog_notes',
					'version' => 1710260023,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'changelog_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'type' => array('VARCHAR(10)', 'NOT NULL'),
						'data' => array('TINYTEXT', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					)
				),
				'fks_changelog_pages' => array(
					'name' => 'fks_changelog_pages',
					'version' => 1710250704,
					'columns' => array(
						'page_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'note_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_member_data' => array(
					'name' => 'fks_member_data',
					'version' => 1710250149,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'member_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'data' => array('TEXT', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_member_data_types' => array(
					'name' => 'fks_member_data_types',
					'version' => 1910250208,
					'restore' => array(
						'include' => array()
					),
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'constant' => array('VARCHAR(40)', 'NOT NULL', 'UNIQUE'),
						'title' => array('VARCHAR(45)', 'DEFAULT NULL'),
						'input_type' => array('VARCHAR(40)', 'NOT NULL'),
						'help_text' => array('TINYTEXT', 'NOT NULL'),
						'position' => array('TINYINT(4)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 50
					),
					'rows' => array(
						array('id' => 1, 'constant' => 'USERNAME', 'title' => 'Username', 'input_type' => 'text', 'help_text' => 'This should never be changed.', 'deleted' => 1),
						array('id' => 2, 'constant' => 'FIRST_NAME', 'title' => 'First Name', 'input_type' => 'text', 'help_text' => 'The first name of this member.'),
						array('id' => 3, 'constant' => 'LAST_NAME', 'title' => 'Last Name', 'input_type' => 'text','help_text' => 'The last name of this member.'),
						array('id' => 4, 'constant' => 'EMAIL_ADDRESS', 'title' => 'Email Address', 'input_type' => 'email', 'help_text' => 'The email address of this member.'),
						array('id' => 5, 'constant' => 'AVATAR', 'title' => 'Avatar', 'input_type' => 'text', 'help_text' => ''),
						array('id' => 6, 'constant' => 'DATE_FORMAT', 'title' => 'Date Format', 'input_type' => 'text', 'help_text' => 'Acceptable formats: <a href=\"http://php.net/manual/en/function.date.php\" target=\"_blank\">PHP Date <i class=\"fa fa-external-link\"></i></a>'),
						array('id' => 7, 'constant' => 'TIMEZONE', 'title' => 'Time Zone', 'input_type' => 'select', 'help_text' => 'The preferred time zone to use.'),
						array('id' => 8, 'constant' => 'ACCESS_GROUPS', 'title' => 'Access Groups', 'input_type' => 'select', 'help_text' => ''),
						array('id' => 9, 'constant' => 'VERIFY_CODE', 'title' => 'Verify Code', 'input_type' => 'text', 'help_text' => ''),
						array('id' => 10, 'constant' => 'PASSWORD', 'title' => 'Password', 'input_type' => 'password', 'help_text' => 'Only change this if you want a new password.'),
						array('id' => 11, 'constant' => 'FULL_NAME', 'title' => 'Full Name', 'input_type' => 'text', 'help_text' => 'The full name of this member.'),
						array('id' => 12, 'constant' => 'EMAIL_VERIFIED', 'title' => 'Email Verified', 'input_type' => 'number', 'help_text' => ''),
						array('id' => 13, 'constant' => 'SITE_LAYOUT', 'title' => 'Site Layout', 'input_type' => 'select', 'help_text' => 'This changes the layout of the whole site.'),
						array('id' => 14, 'constant' => 'TWO_FACTOR', 'title' => 'Two Factor', 'input_type' => 'text', 'help_text' => ''),
						array('id' => 15, 'constant' => 'HOME_PAGE', 'title' => 'Home Page', 'input_type' => 'select', 'help_text' => 'What page should load when logging in.')
					)
				),
				'fks_member_logs' => array(
					'name' => 'fks_member_logs',
					'version' => 1710260522,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'action' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'member_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'target_id' => array('VARCHAR(32)', 'DEFAULT NULL'),
						'misc' => array('TEXT'),
						'date_created' => array('DATETIME', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					)
				),
				'fks_member_sites' => array(
					'name' => 'fks_member_sites',
					'version' => 1903010020,
					'columns' => array(
						'member_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'site_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'PRIMARY KEY'),
						'date_created' => array('DATETIME', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_members' => array(
					'name' => 'fks_members',
					'version' => 1710270601,
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'username' => array('VARCHAR(40)', 'NOT NULL'),
						'last_online' => array('DATETIME', 'DEFAULT NULL'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					)
				),
				'fks_menu_items' => array(
					'name' => 'fks_menu_items',
					'version' => 1711160210,
					'restore' => array(
						'include' => array('date_modified', 'modified_by', 'date_deleted', 'deleted_by', 'active', 'deleted')
					),
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'menu_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'parent_id' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'pos' => array('INT(10)', 'UNSIGNED', 'DEFAULT 0'),
						'title' => array('VARCHAR(40)', 'NOT NULL'),
						'title_data' => array('VARCHAR(40)', 'DEFAULT NULL'),
						'has_separator' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'is_external' => array('TINYINT(1)', 'UNSIGNED', 'DEFAULT 0'),
						'is_parent' => array('TINYINT(1)', 'UNSIGNED', 'DEFAULT 0'),
						'has_content' => array('TINYINT(1)', 'UNSIGNED', 'DEFAULT 1'),
						'url' => array('TINYTEXT', 'NOT NULL'),
						'icon' => array('VARCHAR(20)', 'DEFAULT NULL'),
						'label' => array('VARCHAR(40)', 'UNIQUE', 'DEFAULT NULL'),
						'hidden' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 50
					),
					'rows' => array(
						array('id'=>1,'menu_id'=>2,'parent_id'=>0,'pos'=>98,'title'=>'Log In','has_content'=>0,'url'=>'login','icon'=>'sign-in','label'=>'log_in','date_created'=>'2017-01-18 10:25:00'),
						array('id'=>2,'menu_id'=>2,'parent_id'=>0,'pos'=>99,'title'=>'Member Menu','title_data'=>'FIRST_NAME,USERNAME','is_parent'=>1,'has_content'=>0,'url'=>'member','icon'=>'user-circle','label'=>'member_menu','date_created'=>'2017-10-18 07:23:00'),
						array('id'=>3,'menu_id'=>2,'parent_id'=>2,'pos'=>1,'title'=>'Account Settings','url'=>'settings','icon'=>'cogs','label'=>'account_settings','date_created'=>'2017-10-18 07:23:00'),
						array('id'=>4,'menu_id'=>2,'parent_id'=>2,'pos'=>99,'title'=>'Log Out','has_separator'=>1,'is_external'=>1,'has_content'=>0,'url'=>'#logout','icon'=>'sign-out','label'=>'log_out','date_created'=>'2017-01-18 10:25:00'),
						array('id'=>5,'menu_id'=>1,'parent_id'=>0,'pos'=>99,'title'=>'Admin','is_parent'=>1,'has_content'=>0,'url'=>'admin','icon'=>'cogs','label'=>'admin','date_created'=>'2017-01-18 06:41:00'),
						array('id'=>6,'menu_id'=>1,'parent_id'=>5,'pos'=>2,'title'=>'Announcements','url'=>'announcements','icon'=>'bullhorn','label'=>'admin_announcements','date_created'=>'2017-06-01 10:07:18'),
						array('id'=>7,'menu_id'=>1,'parent_id'=>5,'pos'=>1,'title'=>'Access Groups','url'=>'access','icon'=>'lock','label'=>'access_groups','date_created'=>'2017-01-18 07:11:00'),
						array('id'=>8,'menu_id'=>1,'parent_id'=>5,'pos'=>4,'title'=>'Members','url'=>'members','icon'=>'users','label'=>'members','date_created'=>'2017-01-18 06:42:00'),
						array('id'=>9,'menu_id'=>1,'parent_id'=>5,'pos'=>5,'title'=>'Menus','url'=>'menus','icon'=>'list-ul','label'=>'menus','date_created'=>'2017-01-18 10:18:00'),
						array('id'=>10,'menu_id'=>1,'parent_id'=>5,'pos'=>7,'title'=>'Site Settings','url'=>'settings','icon'=>'server','label'=>'site_settings','date_created'=>'2017-01-18 07:12:00'),
						array('id'=>11,'menu_id'=>1,'parent_id'=>0,'pos'=>98,'title'=>'Site Name','title_data'=>'SITE_TITLE','is_parent'=>1,'has_content'=>0,'url'=>'site','icon'=>'globe','label'=>'site','date_created'=>'2017-01-20 09:45:00'),
						array('id'=>12,'menu_id'=>1,'parent_id'=>5,'pos'=>3,'title'=>'Changelog','url'=>'changelog','icon'=>'list-alt','label'=>'changelog','date_created'=>'2017-01-20 09:45:00'),
						array('id'=>13,'menu_id'=>1,'parent_id'=>11,'pos'=>2,'title'=>'Tracker','url'=>'reporter','icon'=>'crosshairs','label'=>'tracker','date_created'=>'2017-01-20 09:45:00','active'=>0),
						array('id'=>14,'menu_id'=>1,'parent_id'=>5,'pos'=>6,'title'=>'Site Errors','url'=>'errors','icon'=>'exclamation-triangle','label'=>'errors','date_created'=>'2017-11-16 09:45:00')
					)
				),
				'fks_menus' => array(
					'name' => 'fks_menus',
					'version' => 1710260656,
					'restore' => array(
						'include' => array('date_modified', 'modified_by', 'date_deleted', 'deleted_by', 'active', 'deleted')
					),
					'columns' => array(
						'id' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'),
						'title' => array('VARCHAR(40)', 'NOT NULL'),
						'date_created' => array('DATETIME', 'NOT NULL'),
						'created_by' => array('INT(10)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0'),
						'date_modified' => array('DATETIME', 'DEFAULT NULL'),
						'modified_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'date_deleted' => array('DATETIME', 'DEFAULT NULL'),
						'deleted_by' => array('INT(10)', 'UNSIGNED', 'DEFAULT NULL'),
						'active' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 1'),
						'deleted' => array('TINYINT(1)', 'UNSIGNED', 'NOT NULL', 'DEFAULT 0')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8',
						'AUTO_INCREMENT' => 1
					),
					'rows' => array(
						array('id' => 1, 'title' => 'Main', 'date_created' => '2017-01-18 06:06:00', 'created_by' => 0),
						array('id' => 2, 'title' => 'Member', 'date_created' => '2017-01-18 06:07:00', 'created_by' => 0)
					)
				),
				'fks_site_data' => array(
					'name' => 'fks_site_data',
					'version' => 1910090536,
					'restore' => array(
						'include' => array()
					),
					'columns' => array(
						'id' => array('VARCHAR(45)', 'NOT NULL', 'PRIMARY KEY'),
						'data' => array('TINYTEXT', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_site_errors' => array(
					'name' => 'fks_site_errors',
					'version' => 1711160153,
					'columns' => array(
						'error_code' => array('VARCHAR(16)', 'NOT NULL', 'PRIMARY KEY'),
						'error_file' => array('TINYTEXT', 'NOT NULL'),
						'error_line' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'error_function' => array('TINYTEXT', 'NOT NULL'),
						'error_class' => array('TINYTEXT', 'NOT NULL'),
						'error_member' => array('INT(10)', 'UNSIGNED', 'NOT NULL'),
						'error_message' => array('TEXT', 'NOT NULL'),
						'error_created' => array('DATETIME', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					)
				),
				'fks_site_settings' => array(
					'name' => 'fks_site_settings',
					'version' => 1910250734,
					'restore' => array(
						'include' => array('data')
					),
					'columns' => array(
						'id' => array('VARCHAR(50)', 'NOT NULL', 'PRIMARY KEY'),
						'title' => array('VARCHAR(50)', 'NOT NULL'),
						'type' => array('VARCHAR(10)', 'NOT NULL'),
						'data' => array('TEXT', 'DEFAULT NULL'),
						'misc' => array('VARCHAR(250)', 'DEFAULT \'{}\''),
						'validation' => array('VARCHAR(250)', 'DEFAULT \'{}\''),
						'help_text' => array('TINYTEXT', 'DEFAULT NULL'),
						'description' => array('TEXT', 'DEFAULT NULL'),
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					),
					'rows' => array(
						array(
							'id' => 'ACTIVE_DIRECTORY',
							'title' => 'Active Directory',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not members can use AD to log in.',
							'description' => 'Whether or not members can log in using Active Directory. This requires that the reset of the settings in this tab to be filled out.'
						),array(
							'id' => 'AD_ACCOUNT_CREATION',
							'title' => 'Create Account',
							'type' => 'select',
							'data' => 1,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Create a new member on the website if it doesn\'t exist.',
							'description' => 'When this is enabled a member account will be created in the site DB when there is a successful LDAP login. When disabled if a member can successfully log in with LDAP but the member does not exist in the website the login will fail and not let the member log in.'
						),array(
							'id' => 'AD_ATTRIBUTES',
							'title' => 'Member Data',
							'type' => 'json',
							'data' => '{}',
							'misc' => '{}',
							'validation' => '{}',
							'help_text' => 'Use these to match AD attributes to member data.',
							'description' => 'Matching Member Data to AD Attributes here will copy data from AD to Member Data when a member is created via an LDAP login. This will only work is Create Account is enabled.'
						),array(
							'id' => 'AD_BASE_DN',
							'title' => 'Base DN',
							'type' => 'text',
							'data' => 'OU=SBSUsers,OU=Users,OU=MyBusiness,DC=myDomain,DC=local',
							'misc' => '{}',
							'validation' => '{}',
							'help_text' => 'The base DN for the member directory.',
							'description' => 'Make sure this is the base directory for ALL users that you want to log in to this site. This is only used for looking up member data for new LDAP members.'
						),array(
							'id' => 'AD_FAILOVER',
							'title' => 'Allow Failover',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not to attempt to validate the other option.',
							'description' => 'If the site fails to validate a member with the Preferred Login, enabling this option will have the site attempt the other method as well. I.E. if AD is the Preferred Login and a member fails to validate or the site is unable to connect to the AD server the site will then try to validate the member using Local Login.'
						),array(
							'id' => 'AD_FILTER',
							'title' => 'Search Filter',
							'type' => 'text',
							'data' => null,
							'misc' => '{}',
							'validation' => '{}',
							'help_text' => 'The search filter when looking up data for users.',
							'description' => 'This is the search filter when doing the ldap_search command to lookup a users data in an attempt to fill in some fields. This is only used when the account is being created. Can use a wildcard and can also use %USERNAME% to use the current users username.'
						),array(
							'id' => 'AD_LOGIN_SELECTOR',
							'title' => 'Login Selector',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Allows member to choose what login method to use.',
							'description' => 'Whether or not members can choose what method to use to log in. When enabeled there will be a dropdown field added to the login form which allows users to choose to log in with AD, Local, or to use the site\'s default order.'
						),array(
							'id' => 'AD_PREFERRED',
							'title' => 'Preferred Login',
							'type' => 'select',
							'data' => 'Local',
							'misc' => '{"width":6,"options":[{"title":"Local"},{"title":"LDAP"}]}',
							'validation' => '{"not_empty":true,"values":["Local","LDAP"]}',
							'help_text' => 'Where the server should attempt to validate members first.',
							'description' => 'This option will tell the site where to attempt to validate members when they try to log in. If Active Directory is disabled this has to be set to Local!'
						),array(
							'id' => 'AD_RDN',
							'title' => 'Server RDN or DN',
							'type' => 'text',
							'data' => null,
							'misc' => '{}',
							'validation' => '{"not_empty":{"ACTIVE_DIRECTORY":1}}',
							'help_text' => 'The RDN or DN the AD server uses.',
							'description' => 'The Relative Distinguished Name or Distinguished Name that the active directory users must use to attempt a login. %USERNAME% must be used somewhere in order for this to work.'
						),array(
							'id' => 'AD_SERVER',
							'title' => 'Server Address',
							'type' => 'text',
							'data' => null,
							'misc' => '{"attributes":{"placeholder":"ldap://example.com:389"}}',
							'validation' => '{"not_empty":{"ACTIVE_DIRECTORY":1}}',
							'help_text' => 'IP address or hostname of the AD server.',
							'description' => 'This is the IP address or the hostname of the Active Directory server.'
						),array(
							'id' => 'ALLOWED_TIME_ZONES',
							'title' => 'Allowed Time Zones',
							'type' => 'select',
							'data' => 'UTC',
							'misc' => '{"properties":["multiple"]}',
							'validation' => '{}',
							'help_text' => 'Select which time zones can be used.',
							'description' => 'These selected time zones are the only time zones allowed to be used through out the site.'
						),array(
							'id' => 'CAPTCHA',
							'title' => 'Captcha',
							'type' => 'select',
							'data' => '0',
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not users have to pass a Captcha to register.',
							'description' => 'When enabled it requires users to pass a reCaptcha when attempting to register for an account.'
						),array(
							'id' => 'CAPTCHA_PRIVATE',
							'title' => 'Captcha Private Key',
							'type' => 'password',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"CAPTCHA":1}}',
							'help_text' => 'Get a key from <a href="https://www.google.com/recaptcha/" target="_blank">Google</a>.',
							'description' => 'This is a secret key that you don\'t give out to anyone.'
						),array(
							'id' => 'CAPTCHA_PUBLIC',
							'title' => 'Captcha Public Key',
							'type' => 'text',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"CAPTCHA":1}}',
							'help_text' => 'Get a key from <a href="https://www.google.com/recaptcha/" target="_blank">Google</a>.',
							'description' => 'This is a public key...'
						),array(
							'id' => 'DATE_FORMAT',
							'title' => 'Date Format',
							'type' => 'text',
							'data' => 'Y-m-d H:i:s',
							'misc' => '{"required":true,"width":6}',
							'validation' => '{}',
							'help_text' => 'Members will use this date format unless they set their own. <a href="http://php.net/manual/en/function.date.php" target="_blank">PHP Date</a>',
							'description' => 'This is the default format for all dates and times on the server. Members can set their own format to overide this.'
						),array(
							'id' => 'DEFAULT_ACCESS_GUEST',
							'title' => 'Guest Default Access Group',
							'type' => 'select',
							'data' => 1,
							'misc' => '{"attributes":{"class":"access-list"},"properties":["multiple"]}',
							'validation' => '{}',
							'help_text' => 'Default Access Group(s) for accounts that aren\'t logged in.',
							'description' => 'This group deals with people who visit your site but are not logged in. This is only useful if you do not require log in.'
						),array(
							'id' => 'DEFAULT_ACCESS_LDAP',
							'title' => 'LDAP Default Access Group',
							'type' => 'select',
							'data' => 2,
							'misc' => '{"attributes":{"class":"access-list"},"properties":["multiple"]}',
							'validation' => '{}',
							'help_text' => 'Default Access Group(s) for accounts that are created using LDAP.',
							'description' => 'This group is for accounts that are created through LDAP log in as long as the setting is enabled. This only happens on the first log in if the member needs to be created in the database.'
						),array(
							'id' => 'DEFAULT_ACCESS_LOCAL',
							'title' => 'Local Default Access Group',
							'type' => 'select',
							'data' => 3,
							'misc' => '{"attributes":{"class":"access-list"},"properties":["multiple"]}',
							'validation' => '{}',
							'help_text' => 'Default Access Group(s) for accounts that are created through registration.',
							'description' => 'This group is for accounts that are created on the Members Page. Or when someone registers if member registration is enabled.'
						),array(
							'id' => 'EMAIL_AUTH',
							'title' => 'Email Authentication',
							'type' => 'select',
							'data' => '0',
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'If the SMTP server requires authentication..',
							'description' => 'Turn this on if your SMTP server requires outgoing authentication. If this setting is enabled you need to fill out the Username and Password fields below.'
						),array(
							'id' => 'EMAIL_FROM_ADDRESS',
							'title' => 'Default From Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Emails sent from this site will come from this address.',
							'description' => 'Emails sent from this address will use this address. This is the default From Address setting and if any other "From Address" is missing it will use this setting.'
						),array(
							'id' => 'EMAIL_HOSTNAME',
							'title' => 'SMTP Hostname',
							'type' => 'text',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"operator":"or","conditions":{"EMAIL_VERIFICATION":1,"FORGOT_PASSWORD":1,"ERROR_EMAIL":1}}}',
							'help_text' => 'Hostname for the SMTP server.',
							'description' => 'Hostname for the SMTP server.'
						),array(
							'id' => 'EMAIL_PASSWORD',
							'title' => 'Auth Password',
							'type' => 'password',
							'data' => null,
							'misc' => '{"width":6,"attributes":{"class":"fks-base64"}}',
							'validation' => '{"not_empty":{"EMAIL_AUTH":1},"base64_decode":true,"unset":{"EMAIL_PASSWORD":"-[NOCHANGE]-"}}',
							'help_text' => 'Password for the email auth account.',
							'description' => 'This is the password used for the username to log in to the SMTP server for outgoing authentication. This has to be filled out if authentication is enabled.'
						),array(
							'id' => 'EMAIL_PORT',
							'title' => 'Port Number',
							'type' => 'number',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"min_value":0,"max_value":65535}',
							'help_text' => 'Port number of the SMTP server.',
							'description' => 'Port number of the SMTP server, defaults to 25.'
						),array(
							'id' => 'EMAIL_REPLY_TO_ADDRESS',
							'title' => 'Default Reply-To Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Email sent from this site will be replied to this address.',
							'description' => 'Emails sent from this address will use this as the reply-to address. This is the default Rely-To Address and if any other "Reply-To Address" is missing it will use this setting.'
						),array(
							'id' => 'EMAIL_SECURE',
							'title' => 'Security',
							'type' => 'select',
							'data' => 'None',
							'misc' => '{"width":6,"options":["None","TLS","SSL"]}',
							'validation' => '{"not_empty":true,"values":["None","TLS","SSL"]}',
							'help_text' => 'Select what security the SMTP server requires.',
							'description' => 'If Email Auth is enabled then you need to select what kind of security is required by the SMTP server.'
						),array(
							'id' => 'EMAIL_USERNAME',
							'title' => 'Auth Username',
							'type' => 'text',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"EMAIL_AUTH":1}}',
							'help_text' => 'Email address or username for the auth account.',
							'description' => 'This is the email address or username used to log in to the SMTP server for outgoing authentication. This has to been filled out if authentication is enabled.'
						),array(
							'id' => 'EMAIL_VERIFICATION',
							'title' => 'Email Verification',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not a valid email address is required on registration.',
							'description' => 'If this is enabled then when a user attempts to register a new account they will be forced to include an email address. They will then be sent an email with a link that they will need to click before the account is activated.'
						),array(
							'id' => 'EMAIL_VERIFICATION_FROM_ADDRESS',
							'title' => 'From Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Emails sent for verification will come from this address.',
							'description' => 'Emails sent from this site for verification will come from this address. This setting CAN be blank, if it is it will use the default From Address.'
						),array(
							'id' => 'EMAIL_VERIFICATION_REPLY_TO_ADDRESS',
							'title' => 'Reply-To Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Emails sent for verification will be replied to this address.',
							'description' => 'Emails sent from this site for verification will be replied to this address. This setting CAN be blank, if it is it will use the default Reply-To Address.'
						),array(
							'id' => 'EMAIL_VERIFICATION_SUBJECT',
							'title' => 'Email Subject',
							'type' => 'text',
							'data' => '%SITE_TITLE% - Email Verification',
							'misc' => '{}',
							'validation' => '{"not_empty":{"EMAIL_VERIFICATION":1}}',
							'help_text' => 'What the subject for email verifications will say.',
							'description' => 'What the subject for email verifications will say.'
						),array(
							'id' => 'EMAIL_VERIFICATION_TEMPLATE',
							'title' => 'Email Template',
							'type' => 'summernote',
							'data' => '<p>%FIRST_NAME%,</p><p>Someone with this email address is attempting to register on our site. If this is not you then you can ignore this message other wise your code is below.</p><p>Verification Code: %VERIFY_CODE%</p><p>- %SITE_TITLE%</p>',
							'misc' => '{"attributes":{"class":"fks-summernote fks-urlencode"}}',
							'validation' => '{"not_empty":{"EMAIL_VERIFICATION":1},"urldecode":true}',
							'help_text' => 'What emails will look like when sending verification codes.',
							'description' => 'What emails will look like when sending verification codes.'
						),array(
							'id' => 'ERROR_EMAIL',
							'title' => 'Email Errors',
							'type' => 'select',
							'data' => '0',
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether to email errors to someone.',
							'description' => 'Enabling this will send an email with the error code and error description. This requires that the General tab of Email is filled out.'
						),array(
							'id' => 'ERROR_EMAIL_ADDRESS',
							'title' => 'Email Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"ERROR_EMAIL":1},"email":true}',
							'help_text' => 'Who should receive the email.',
							'description' => 'When Email Errors is enabled FKS will send the errors to this address.'
						),array(
							'id' => 'ERROR_MESSAGE',
							'title' => 'Returned Message',
							'type' => 'text',
							'data' => 'Error Code: %CODE%',
							'misc' => '{"width":6}',
							'validation' => '{}',
							'help_text' => 'What the toaster message will say.',
							'description' => 'What the toaster message will say when an error occurs.<br/><u>Keywords</u> - %CODE%, %FUNCTION%, %LINE%, %CLASS%, and %FILE%.'
						),array(
							'id' => 'ERROR_TO_DB',
							'title' => 'Save to DB',
							'type' => 'select',
							'data' => '1',
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether you want to store errors in the DB.',
							'description' => 'Enabling this option will store errors in the database.'
						),array(
							'id' => 'ERROR_TO_DISK',
							'title' => 'Save to Disk',
							'type' => 'select',
							'data' => 'Fallback',
							'misc' => '{"width":6,"options":[{"title":"Yes"},{"title":"Fallback"},{"title":"No"}]}',
							'validation' => '{"not_empty":true,"values":["Yes","Fallback","No"]}',
							'help_text' => 'Whether you want to store errors to disk.',
							'description' => '<u>Yes</u> - Always store errors to disk.<br/><u>Fallback</u> - Only store errors on a database failure.</br><u>No</u> - Never store errors to disk.'
						),array(
							'id' => 'FORGOT_PASSWORD',
							'title' => 'Forgot Password',
							'type' => 'select',
							'data' => '0',
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not users can reset their passwords via email.',
							'description' => 'When enabled a user may request to have an email sent to reset their password. If Email Verification is not enabled then it\'s possible that members do not have an email address.'
						),array(
							'id' => 'FORGOT_PASSWORD_FROM_ADDRESS',
							'title' => 'From Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Emails sent for forgot password will come from this address.',
							'description' => 'Emails sent from this site for forgot password will come from this address. This setting CAN be blank, if it is it will use the default From Address.'
						),array(
							'id' => 'FORGOT_PASSWORD_REPLY_TO_ADDRESS',
							'title' => 'Reply-To Address',
							'type' => 'email',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"email":true}',
							'help_text' => 'Email sent for forgot password will be replied to this address.',
							'description' => 'Emails sent from this site for forgot password will be replied to this address. This setting CAN be blank, if it is it will use the default Reply-To Address.'
						),array(
							'id' => 'FORGOT_PASSWORD_SUBJECT',
							'title' => 'Email Subject',
							'type' => 'text',
							'data' => '%SITE_TITLE% - Forgot Password',
							'misc' => '{}',
							'validation' => '{"not_empty":{"FORGOT_PASSWORD":1}}',
							'help_text' => 'What the subject for forgot passwords will say.',
							'description' => 'What the subject for forgot passwords will say.'
						),array(
							'id' => 'FORGOT_PASSWORD_TEMPLATE',
							'title' => 'Email Template',
							'type' => 'summernote',
							'data' => null,
							'misc' => '{"attributes":{"class":"fks-summernote fks-urlencode"}}',
							'validation' => '{"not_empty":{"FORGOT_PASSWORD":1},"urldecode":true}',
							'help_text' => 'What emails will look like when sending forgot password codes.',
							'description' => 'What emails will look like when sending forgot password codes.'
						),array(
							'id' => 'MEMBER_REGISTRATION',
							'title' => 'Member Registration',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not the registration form is enabled on the log in page.',
							'description' => 'Allows guests to create accounts to log in with. Once created they will be assigned the Default Local Access Group, which can be changed in the Access tab.'
						),array(
							'id' => 'PROTECTED_USERNAMES',
							'title' => 'Protected Usernames',
							'type' => 'textarea',
							'data' => 'admin,administrator,fksrepack,snaggybore,flyingkumquat',
							'misc' => '{"attributes":{"rows":4}}',
							'validation' => '{}',
							'help_text' => 'Usernames that are not allowed during registration.',
							'description' => 'A comma seperated list of usernames that will not be allowed to be used during user registration. Capitalization does not matter.'
						),array(
							'id' => 'REMOTE_DATABASE',
							'title' => 'Remote Database',
							'type' => 'select',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"not_empty":{"REMOTE_SITE":"Secondary"},"nullify":{"inverse":true,"conditions":{"REMOTE_SITE":"Secondary"}}}',
							'help_text' => 'Which connection to use for the remote site.',
							'description' => 'Select which database connection to use for the remote site. This is only used if Remote Site is set to Secondary.'
						),array(
							'id' => 'REMOTE_ID',
							'title' => 'Remote ID',
							'type' => 'number',
							'data' => null,
							'misc' => '{}',
							'validation' => '{"required":false,"unset":true}',
							'help_text' => null,
							'description' => null
						),array(
							'id' => 'REMOTE_SITE',
							'title' => 'Remote Site',
							'type' => 'select',
							'data' => 'Disabled',
							'misc' => '{"width":6,"options":["Disabled","Primary","Secondary"]}',
							'validation' => '{"not_empty":true,"values":["Disabled","Primary","Secondary"]}',
							'help_text' => 'Whether to enable remote connections.',
							'description' => '<u>Disabled</u> - Stand alone site.<br/><u>Primary</u> - Allows other sites to connect to this site to use log in information.<br/><u>Secondary</u> - Will use log in information from a primary site.'
						),array(
							'id' => 'REMOTE_SITE_IDS',
							'title' => 'Remote Site ID\'s',
							'type' => 'text',
							'data' => null,
							'misc' => '{}',
							'validation' => '{"required":false,"unset":true}',
							'help_text' => null,
							'description' => null
						),array(
							'id' => 'REQUIRE_LOGIN',
							'title' => 'Require Login',
							'type' => 'select',
							'data' => 0,
							'misc' => '{"width":6,"options":[{"title":"Disabled","value":0},{"title":"Enabled","value":1}]}',
							'validation' => '{"bool":true,"not_empty":true}',
							'help_text' => 'Whether or not users have to log in to view the site.',
							'description' => 'This option forces that members log in to access this site. With this off members who are not logged in will be assigned the Default Guest Access Group, which can be changed in the Access tab.'
						),array(
							'id' => 'SITE_COLORS_SIGNATURE',
							'title' => 'Site Signature Color',
							'type' => 'color',
							'data' => '#36e3fd',
							'misc' => '{"attributes":{"class":"fks-color-picker"}}',
							'validation' => '{}',
							'help_text' => 'The site\'s signature color.',
							'description' => 'This color will be used through out the site and can be referenced with the class fks-[ELE]-signature. Example: fks-btn-signature'
						),array(
							'id' => 'SITE_FAVICON_URL',
							'title' => 'Site Favicon URL',
							'type' => 'text',
							'data' => 'img/_favicon.ico',
							'misc' => '{}',
							'validation' => '{}',
							'help_text' => 'This is the URL for the site\'s favicon.',
							'description' => 'The URL pointing to the image you want to use for the favicon.'
						),array(
							'id' => 'SITE_HOME_PAGE',
							'title' => 'Site Home Page',
							'type' => 'select',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{}',
							'help_text' => 'What page should load on visit.',
							'description' => 'Select which page should load for guests when visiting the site. Access groups can have their own default home page set but if they are blank they will default to this setting.'
						),array(
							'id' => 'SITE_LAYOUT',
							'title' => 'Site Layout',
							'type' => 'select',
							'data' => 'Default',
							'misc' => '{"width":6,"options":[{"title":"Default"},{"title":"Admin"}]}',
							'validation' => '{"values":["Default","Admin"]}',
							'help_text' => 'This changes the layout of the whole site.',
							'description' => '<u>Default</u> - This is a fixed width site.<br/><u>Admin</u> - This takes up the full width of the window.'
						),array(
							'id' => 'SITE_LOGO_LOGIN',
							'title' => 'Site Logo (Login)',
							'type' => 'textarea',
							'data' => '<img src="img/_favicon.ico" /><span><b>FKS</b>repack</span>',
							'misc' => '{"attributes":{"rows":4,"class":"fks-base64"}}',
							'validation' => '{"base64_decode":true}',
							'help_text' => 'This is the HTML for the site\'s Logo on the Login page only.',
							'description' => 'Custom HTML that will be displayed at the log in page above the DIV.'
						),array(
							'id' => 'SITE_LOGO_MAIN',
							'title' => 'Site Logo (Main)',
							'type' => 'textarea',
							'data' => '<img src="img/_favicon.ico" /><span class="d-none d-sm-inline"><b>FKS</b><span class="fks-text-signature">repack</span></span>',
							'misc' => '{"attributes":{"rows":4,"class":"fks-base64"}}',
							'validation' => '{"base64_decode":true}',
							'help_text' => 'This is the HTML for the site\'s Logo on main pages.',
							'description' => 'Custom HTML that will be displayed at the top of the site that will serve as the site title.'
						),array(
							'id' => 'SITE_TITLE',
							'title' => 'Site Title',
							'type' => 'text',
							'data' => 'FKSrepack',
							'misc' => '{"required":true,"width":6}',
							'validation' => '{"not_empty":true}',
							'help_text' => 'This is shown in the breadcrumbs and brower title.',
							'description' => 'This is shown in the browser window or tab as well as the breadcrumbs.'
						),array(
							'id' => 'SITE_USERNAME',
							'title' => 'Site Username',
							'type' => 'text',
							'data' => 'server',
							'misc' => '{"required":true,"width":6}',
							'validation' => '{"not_empty":true}',
							'help_text' => 'This is username that\'s used when the site creates things.',
							'description' => 'This is the username that will be displayed when the website creates or modifies anything.'
						),array(
							'id' => 'SITE_VERSION',
							'title' => 'Version',
							'type' => 'text',
							'data' => 0,
							'misc' => '{}',
							'validation' => '{"required":false,"unset":true}',
							'help_text' => 'This is the version number of this site.',
							'description' => 'This is the version number of this site.'
						),array(
							'id' => 'TIMEZONE',
							'title' => 'Default Time Zone',
							'type' => 'select',
							'data' => null,
							'misc' => '{"width":6}',
							'validation' => '{"time_zone":true}',
							'help_text' => 'Members will use this time zone unless they set their own.',
							'description' => 'This is the default time zone for all dates and times on this server. Members can set their own time zones to override this. You can select "Use Server" to use whatever the server\'s time zone is currently set to.'
						)
					)
				),
				'fks_versions' => array(
					'name' => 'fks_versions',
					'version' => 1910170623,
					'backup' => array('enabled' => false),
					'restore' => array('enabled' => false),
					'columns' => array(
						'title' => array('VARCHAR(100)', 'NOT NULL', 'PRIMARY KEY'),
						'connection' => array('VARCHAR(100)', 'NOT NULL', 'PRIMARY KEY'),
						'version' => array('INT(10)', 'UNSIGNED', 'NOT NULL')
					),
					'options' => array(
						'ENGINE' => 'InnoDB',
						'DEFAULT CHARSET' => 'utf8'
					),
					'rows' => array()
				)
			)
		);
		
		// Load "tables.php" if it exists otherwise set default settings
		if(is_file(__DIR__ . '/../config/tables.php')) {
			include(__DIR__ . '/../config/tables.php');
			
			// Merge extra tables with default tables
			if(isset($tables)) {
				foreach($tables as $k => $v) {
					if(array_key_exists($k, $this->tables)) {
						$this->tables[$k] = array_merge($v, $this->tables[$k]);
					} else {
						$this->tables[$k] = $v;
					}
				}
			}
		}
		
		// Loop through tables to fill the versions table
		foreach($this->tables as $key => $database) {	
			foreach($database as $table) {
				array_push($this->tables[$this->Database->db['default']]['fks_versions']['rows'], array('title' => $table['name'], 'connection' => $key, 'version' => 0));
			}
		}
	}

/*----------------------------------------------------------------------------------------------------
	Private Functions
----------------------------------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------------------------------
	Public Functions
----------------------------------------------------------------------------------------------------*/
	
}
?>