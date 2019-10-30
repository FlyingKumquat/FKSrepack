<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/

class PageFunctions extends CoreFunctions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $access;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		parent::__construct();
		$this->access = $this->getAccess('site_settings');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	// -------------------- Get Access Groups -------------------- \\
	private function getAccessGroups() {
		// Set vars
		$Database = new \Database();
		$hierarchy = $this->getHierarchy($_SESSION['id']);
		$access = array();
		
		// Grab all access groups and create options
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT id,title,hierarchy FROM fks_access_groups WHERE active = 1 AND deleted = 0'
		))) {
			// Set data
			$access = $Database->r['rows'];
			
			// Loop through and set hierarchy
			foreach($access as $k => &$v) {
				$v['disabled'] = ($hierarchy < $v['hierarchy']);
			}
		}
		
		// Return access array
		return $access;
	}
	
	// -------------------- Get Member Data Types -------------------- \\
	private function getMemberDataTypes() {
		// Set vars
		$Database = new \Database();
		$dataTypes = array();
		
		// Grab all access groups and create options
		if($Database->Q(array(
			'assoc' => 'constant',
			'query' => 'SELECT id,constant,title FROM fks_member_data_types WHERE active = 1 AND deleted = 0'
		))) {
			// Set data
			$dataTypes = $Database->r['rows'];
		}
		
		//
		ksort($dataTypes);
		
		// Return access array
		return $dataTypes;
	}
	
	// -------------------- Generate Settings Tab -------------------- \\
	private function settingsTab($input_array = array(), $site_settings = null) {
		// Return if missing anything
		if(!is_array($input_array) || count($input_array) == 0 || is_null($site_settings)) { return array('inputs' => 'There was an issue generating inputs...', 'descriptions' => ''); }
		
		// Set return values
		$inputs = array();
		$descriptions = '';
		
		// loop through each input passed
		foreach($input_array as $i) {
			// Continue if data is missing but create a blank col if a number is given
			if(!isset($site_settings[$i])) {
				if(is_numeric($i)) { array_push($inputs, array('width' => $i)); }
				continue;
			}
			
			// Create temporary input array with default values
			$_input = array(
				'title' => $site_settings[$i]['title'],
				'type' => $site_settings[$i]['type'],
				'name' => $site_settings[$i]['id'],
				'value' => $site_settings[$i]['data'],
				'help' => $site_settings[$i]['help_text']
			);
			
			// Explode miscellaneous options
			$_options = json_decode($site_settings[$i]['misc'], true);
			
			// SITE_HOME_PAGE - Get a list of all active site pages
			if($i == 'SITE_HOME_PAGE') {
				$_options['options'] = array(array('title' => '-', 'value' => ''));
				foreach($this->getMenuItemStructures(false, true) as $k => $v) {
					array_push($_options['options'], array('title' => $v, 'value' => $k));
				}
			}
			
			// TIMEZONE - Get a list of all time zones
			if($i == 'TIMEZONE') {
				$_options['options'] = array(array('title' => 'Use Server (' . date_default_timezone_get() . ')', 'value' => ''));
				foreach($this->timeZones('ALL')['list'] as $k => $v) {
					array_push($_options['options'], array('title' => $v, 'value' => $v));
				}
			}
			
			// ALLOWED_TIME_ZONES - Get a list of all time zones and group them by region
			if($i == 'ALLOWED_TIME_ZONES') {
				$_options['options'] = array();
				foreach($this->timeZones('ALL')['list'] as $k => $v) {
					$_group = strpos($v, '/') !== false ? explode('/', $v)[0] : $v;
					array_push($_options['options'], array('title' => $v, 'value' => $v, 'group' => $_group));
				}
			}
			
			// Access Groups
			if($i == 'DEFAULT_ACCESS_GUEST' || $i == 'DEFAULT_ACCESS_LDAP' || $i == 'DEFAULT_ACCESS_LOCAL') {
				$_options['options'] = array();
				foreach($this->getAccessGroups() as $k => $v) {
					array_push($_options['options'], array('title' => $v['title'], 'value' => $v['id'], 'disabled' => $v['disabled']));
				}
			}
			
			// Remote Database - Get a list of all databases
			if($i == 'REMOTE_DATABASE') {
				$Databases = new \Database();
				$_options['options'] = array(array('title' => '-', 'value' => ''));
				foreach($Databases->db as $k => $v) {
					if( $k == 'persist' || $k == 'default' || $k == $Databases->db['default'] ){ continue; }
					array_push($_options['options'], array('title' => $k, 'value' => $k));
				}
			}
			
			// Add options if a select
			if($_input['type'] == 'select') {
				// Skip if options are not set - can't have a select without options
				if(!isset($_options['options']) || count($_options['options']) == 0) { continue; }
				
				// Create the options array
				$_input['options'] = array();
				
				// Loop through options and add them
				foreach($_options['options'] as $o) {
					//
					if(is_string($o)) { $o = array('title' => $o); }
					
					// Create temporary option array
					$_option = array(
						'title' => $o['title'],
						'value' => (isset($o['value']) ? $o['value'] : $o['title'])
					);
					
					// Add options if they are set
					foreach(array('group', 'selected', 'disabled') as $v) {
						if(isset($o[$v])) { $_option[$v] = $o[$v]; }
					}
					
					// Add option to input
					array_push($_input['options'], $_option);
				}
			}
			
			// Loop through optional values and add them if set
			foreach(array('required', 'width', 'size', 'attributes', 'properties', 'feedback', 'label') as $v) {
				if(isset($_options[$v])) { $_input[$v] = $_options[$v]; }
			}
			
			// Add input to return array
			array_push($inputs, $_input);
			
			// Add the descriptions
			$descriptions .= '<h6>' . $site_settings[$i]['title'] . '</h6>';
			$descriptions .= '<p>' . $site_settings[$i]['description'] . '</p>';
		}
		
		// Return everything
		return array('inputs' => $inputs, 'descriptions' => $descriptions);
	}
	
	// -------------------- General Tab Settings -------------------- \\
	private function tabGeneral($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('SITE_TITLE','MEMBER_REGISTRATION','REQUIRE_LOGIN','SITE_USERNAME','SITE_LAYOUT','SITE_HOME_PAGE','PROTECTED_USERNAMES'), $site_settings);
		$bodies[2] = $this->settingsTab(array('TIMEZONE','DATE_FORMAT','ALLOWED_TIME_ZONES'), $site_settings);
		$bodies[3] = $this->settingsTab(array('SITE_COLORS_SIGNATURE','SITE_FAVICON_URL','SITE_LOGO_LOGIN','SITE_LOGO_MAIN'), $site_settings);

		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs'], array('prefix' => 'SITE'));
		$bodies[2]['inputs'] = $this->buildFormGroups($bodies[2]['inputs'], array('prefix' => 'SITE'));
		$bodies[3]['inputs'] = $this->buildFormGroups($bodies[3]['inputs'], array('prefix' => 'SITE'));
		
		// Return the generated HTML
		return '<div class="fks-panel tabs">'
			. '<div class="header"><ul class="nav nav-tabs">'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab1-1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> Settings</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab1-2" role="tab" draggable="false"><i class="fa fa-clock-o fa-fw"></i> Date and Time</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab1-3" role="tab" draggable="false"><i class="fa fa-file-image-o fa-fw"></i> Styling</a></li>'
			. '</ul></div>'
			. '<div class="body"><div class="tab-content">'
				. '<div class="tab-pane" id="tab1-1" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab1-2" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[2]['inputs'] . '</div><div class="col-xl-5">' . $bodies[2]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab1-3" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[3]['inputs'] . '</div><div class="col-xl-5">' . $bodies[3]['descriptions'] . '</div></div></div>'
			. '</div></div>'
		. '</div>';
	}
	
	// -------------------- reCaptcha Tab Settings -------------------- \\
	private function tabCaptcha($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('CAPTCHA',6,'CAPTCHA_PRIVATE','CAPTCHA_PUBLIC'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs']);
		
		// Return the generated HTML
		return '<div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div>';
	}
	
	// -------------------- Email Tab Settings -------------------- \\
	private function tabEmail($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('EMAIL_AUTH',6,'EMAIL_USERNAME','EMAIL_PASSWORD','EMAIL_FROM_ADDRESS','EMAIL_REPLY_TO_ADDRESS','EMAIL_HOSTNAME','EMAIL_PORT','EMAIL_SECURE'), $site_settings);
		$bodies[2] = $this->settingsTab(array('EMAIL_VERIFICATION',6,'EMAIL_VERIFICATION_FROM_ADDRESS','EMAIL_VERIFICATION_REPLY_TO_ADDRESS','EMAIL_VERIFICATION_SUBJECT','EMAIL_VERIFICATION_TEMPLATE'), $site_settings);
		$bodies[3] = $this->settingsTab(array('FORGOT_PASSWORD',6,'FORGOT_PASSWORD_FROM_ADDRESS','FORGOT_PASSWORD_REPLY_TO_ADDRESS','FORGOT_PASSWORD_SUBJECT','FORGOT_PASSWORD_TEMPLATE'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs'], array('prefix' => 'SITE'));
		$bodies[2]['inputs'] = $this->buildFormGroups($bodies[2]['inputs'], array('prefix' => 'SITE'));
		$bodies[3]['inputs'] = $this->buildFormGroups($bodies[3]['inputs'], array('prefix' => 'SITE'));
		
		// Test email button
		$button = '<div class="row"><div class="col-xl-12"><div class="form-group">'
			. '<button type="button" class="btn fks-btn-info btn-sm test-email-btn"><i class="fa fa-paper-plane-o fa-fw"></i> Send Test Email</button>'
		. '</div></div></div>';
		
		// Return the generated HTML
		return '<div class="fks-panel tabs">'
			. '<div class="header"><ul class="nav nav-tabs">'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab3-1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> General</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab3-2" role="tab" draggable="false"><i class="fa fa-handshake-o fa-fw"></i> Verification</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab3-3" role="tab" draggable="false"><i class="fa fa-question fa-fw"></i> Forgot Password</a></li>'
			. '</ul></div>'
			. '<div class="body"><div class="tab-content">'
				. '<div class="tab-pane" id="tab3-1" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . $button . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab3-2" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[2]['inputs'] . '</div><div class="col-xl-5">' . $bodies[2]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab3-3" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[3]['inputs'] . '</div><div class="col-xl-5">' . $bodies[3]['descriptions'] . '</div></div></div>'
			. '</div></div>'
		. '</div>';
	}
	
	// -------------------- Access Tab Settings -------------------- \\
	private function tabAccess($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('DEFAULT_ACCESS_GUEST'), $site_settings);
		$bodies[2] = $this->settingsTab(array('DEFAULT_ACCESS_LOCAL'), $site_settings);
		$bodies[3] = $this->settingsTab(array('DEFAULT_ACCESS_LDAP'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs'], array('prefix' => 'SITE'));
		$bodies[2]['inputs'] = $this->buildFormGroups($bodies[2]['inputs'], array('prefix' => 'SITE'));
		$bodies[3]['inputs'] = $this->buildFormGroups($bodies[3]['inputs'], array('prefix' => 'SITE'));
		
		// Return the generated HTML
		return '<div class="fks-panel tabs">'
			. '<div class="header"><ul class="nav nav-tabs">'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab4-1" role="tab" draggable="false"><i class="fa fa-user fa-fw"></i> Guest</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab4-2" role="tab" draggable="false"><i class="fa fa-database fa-fw"></i> Local</a></li>'
				. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab4-3" role="tab" draggable="false"><i class="fa fa-exchange fa-fw"></i> LDAP</a></li>'
			. '</ul></div>'
			. '<div class="body"><div class="tab-content">'
				. '<div class="tab-pane" id="tab4-1" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab4-2" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[2]['inputs'] . '</div><div class="col-xl-5">' . $bodies[2]['descriptions'] . '</div></div></div>'
				. '<div class="tab-pane" id="tab4-3" role="tabpanel"><div class="row"><div class="col-xl-7">' . $bodies[3]['inputs'] . '</div><div class="col-xl-5">' . $bodies[3]['descriptions'] . '</div></div></div>'
			. '</div></div>'
		. '</div>';
	}
	
	// -------------------- LDAP Tab Settings -------------------- \\
	private function tabLDAP($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('ACTIVE_DIRECTORY','AD_LOGIN_SELECTOR','AD_ACCOUNT_CREATION','AD_PREFERRED','AD_FAILOVER',6,'AD_SERVER','AD_RDN','AD_BASE_DN','AD_FILTER'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs']);
		
		// Create the list of allowed member data types
		$options = '';
		foreach($this->getMemberDataTypes() as $k => $v) {
			$options .= '<option value="' . $v['constant'] . '">' . $v['constant'] . '</option>';
		}
		
		// Return with no options
		if(empty($options)) {
			$options = '<option value="0">-- no valid options found --</option>';
		}
		
		// Table
		if(isset($site_settings['AD_ATTRIBUTES'])) {
			$bodies[1]['inputs'] .= '<div class="row"><div class="col-xl-12">
				<div class="form-group attributes-input">
					<label for="SITE_AD_ATTRIBUTES" class="form-control-label">' . $site_settings['AD_ATTRIBUTES']['title'] . '</label>
					<div class="input-group">
						<select class="form-control" id="SITE_AD_ATTRIBUTES">' . $options . '</select>
						<input type="text" class="form-control attribute-name" placeholder="attribute name"/>
						<div class="input-group-append">
							<button type="button" class="btn btn-sm fks-btn-success add">Add</button>
						</div>
					</div>
					<div class="form-control-feedback"></div>
					<small name="AD_ATTRIBUTES" class="form-text text-muted">' . $site_settings['AD_ATTRIBUTES']['help_text'] . '</small>
				</div>
			</div></div><div class="row"><div class="col-xl-12">
				<ul class="fks-list-group fks-list-group-sm attributes-list">
					<li class="list-group-item">Here is the Title<div class="actions"><button type="button" class="btn fks-btn-danger remove"><i class="fa fa-times fa-fw"></i></button></div></li>
				</ul>
			</div></div>';
		}
		
		// Return the generated HTML
		return '<div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div>';
	}
	
	// -------------------- Error Tab Settings -------------------- \\
	private function tabError($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('ERROR_TO_DB','ERROR_TO_DISK','ERROR_EMAIL','ERROR_EMAIL_ADDRESS','ERROR_MESSAGE'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs']);
		
		// Return the generated HTML
		return '<div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div>';
	}
	
	// -------------------- Remote Site Tab Settings -------------------- \\
	private function tabRemote($site_settings) {
		// Generate inputs and descriptions
		$bodies[1] = $this->settingsTab(array('REMOTE_SITE','REMOTE_DATABASE'), $site_settings);
		
		// Build form groups
		$bodies[1]['inputs'] = $this->buildFormGroups($bodies[1]['inputs'], array('prefix' => 'SITE'));
		
		// Validate button
		$validate = '<div class="row remote-validate"><div class="col-xl-6"><div class="form-group">'
			. '<button type="button" class="btn fks-btn-info btn-sm remote-test-btn" style="width:100%;margin-top:26px;"><i class="fa fa-question fa-fw"></i> Validate</button>'
		. '</div></div></div>';
		
		//
		$members = '<div style="display:none;" class="remote-user-search">
			<div class="row">
				<div class="col-xl-6">' . $this->buildFormGroup(array('title' => 'Member Search','type' => 'text','name' => 'REMOTE_SEARCH','help' => 'Search for a user on the remote DB.',
					'group' => array(
						'after' => '<btn class="input-group-addon" id="icon_preview"><i class="fa fa-search fa-fw"></i></btn>'
					))) . '</div>
				<div class="col-xl-6"><button type="button" class="btn fks-btn-info btn-sm remote-search-btn" style="width:100%;margin-top:26px;"><i class="fa fa-search fa-fw"></i> Search</button></div>
			</div>
			<div class="row">
				<div class="col-xl-12">
					<div class="form-group">
						<label for="REMOTE_ADMINS" class="form-control-label">Remote Admins</label>
						<table class="table table-sm table-striped table-hover remote-admins mb-0" style="color:initial;">
							<thead class="thead-dark">
								<tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Access</th><th style="width:50px;text-align:center;padding-right:11px;"><i class="fa fa-trash fa-fw"></i></th></tr>
							</thead>
							<tbody></tbody>
						</table>
						<div class="form-control-feedback" name="REMOTE_ADMINS" style="display:none;margin-top:5px;"></div>
					</div>
				</div>
			</div>
		</div>';
		
		// Return the generated HTML
		return '<div class="row"><div class="col-xl-7">' . $bodies[1]['inputs'] . $validate . $members . '</div><div class="col-xl-5">' . $bodies[1]['descriptions'] . '</div></div>';
	}
	
	// -------------------- Reset Local Tables -------------------- \\
	private function resetLocalTables() {
		// Set variables
		$Database = new \Database();
		$return = true;
		
		// Clear member_data table
		if(!$Database->Q('DELETE FROM fks_member_data')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Reset member_data table auto increment
		if(!$Database->Q('ALTER TABLE fks_member_data AUTO_INCREMENT = 1')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Clear member_logs table
		if(!$Database->Q('DELETE FROM fks_member_logs')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Reset member_logs table auto increment
		if(!$Database->Q('ALTER TABLE fks_member_logs AUTO_INCREMENT = 1')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Return
		return $return;
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Site Settings -------------------- \\
	public function loadSiteSettings() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		
		// Grab all site settings
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_site_settings'
		))) {
			$d = $Database->r['rows'];
			foreach($d as $k => $v) {
				if( empty($v['description']) ) { $d[$k]['description'] = '(NULL)'; }
				if( empty($v['help_text']) ) { $d[$k]['help_text'] = '&nbsp;'; }
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Custom stuff
		if(!empty($d['EMAIL_PASSWORD']['data'])) {$d['EMAIL_PASSWORD']['data'] = '-[NOCHANGE]-';}
		
		// Tabs
		$tabs = '<ul class="nav nav-tabs">'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> General</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab2" role="tab" draggable="false"><i class="fa fa-google fa-fw"></i> reCaptcha</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab3" role="tab" draggable="false"><i class="fa fa-envelope fa-fw"></i> Email</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab4" role="tab" draggable="false"><i class="fa fa-lock fa-fw"></i> Access</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab5" role="tab" draggable="false"><i class="fa fa-address-card-o fa-fw"></i> Active Directory</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab6" role="tab" draggable="false"><i class="fa fa-exclamation-triangle fa-fw"></i> Error</a></li>'
			. '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab7" role="tab" draggable="false"><i class="fa fa-link fa-fw"></i> Remote Site</a></li>'
		. '</ul>';
		
		// Tab Content
		$body = '<form id="editSiteSettingsForm" class="fks-form fks-form-sm" role="form" action="javascript:void(0);" autocomplete="off"><div class="tab-content">'
			. '<div class="tab-pane" id="tab1" role="tabpanel">' . $this->tabGeneral($d) . '</div>'
			. '<div class="tab-pane" id="tab2" role="tabpanel">' . $this->tabCaptcha($d) . '</div>'
			. '<div class="tab-pane" id="tab3" role="tabpanel">' . $this->tabEmail($d) . '</div>'
			. '<div class="tab-pane" id="tab4" role="tabpanel">' . $this->tabAccess($d) . '</div>'
			. '<div class="tab-pane" id="tab5" role="tabpanel">' . $this->tabLDAP($d) . '</div>'
			. '<div class="tab-pane" id="tab6" role="tabpanel">' . $this->tabError($d) . '</div>'
			. '<div class="tab-pane" id="tab7" role="tabpanel">' . $this->tabRemote($d) . '</div>'
		. '</div></form>';
		
		$email_auth = array(
			'title' => 'Email Authentication',
			'description' => 'Turn this on if your SMTP server requires outgoing authentication. If this setting is enabled you need to fill out the Username and Password fields below.'
		);
		
		// Return form
		return array('result' => 'success', 'tabs' => $tabs, 'body' => $body, 'AD_ATTRIBUTES' => $d['AD_ATTRIBUTES']['data'], 'form_descriptions' => array('editSiteSettingsForm' => array('EMAIL_AUTH' => $email_auth)));
	}
	
	// -------------------- Save Site Settings -------------------- \\
	public function saveSiteSettings($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set variables
		$Database = new \Database();
		$validation = array();
		$updated = array();
		$create_log = true;
		$set_colors = false;
		$allowed = array(
			'access_groups' => array(),
			'time_zones' => array(),
			'databases' => array(),
			'attributes' => array()
		);
		
		// Grab all site settings
		if($Database->Q(array(
            'assoc' => 'id',
            'query' => 'SELECT * FROM fks_site_settings'
        ))) {
            // Set settings value
			$site_settings = $Database->r['rows'];
        } else {
            // Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
        }
		
		// Get all allowed access groups
		foreach($this->getAccessGroups() as $k => $v) {
			if( !$v['disabled'] ) { array_push($allowed['access_groups'], $v['id']); }
		}
		
		// Get all allowed time zones
		foreach($this->timeZones('ALL')['list'] as $k => $v) {
			array_push($allowed['time_zones'], $v);
		}
		
		// Get all allowed databases
		foreach($Database->db as $k => $v) {
			if( $k == 'persist' || $k == 'default' || $k == $Database->db['default'] ){ continue; }
			array_push($allowed['databases'], $k);
		}
		
		// Get allowed member data types for LDAP
		foreach($this->getMemberDataTypes() as $k => $v) {
			array_push($allowed['attributes'], $k);
		}
		
		// Loop through each setting and add to the validation array
		foreach($site_settings as $k => $v) {
			// Set initial validation array
			$validation[$k] = json_decode($v['validation'], true);
			
			// Add required option if missing
			if(!isset($validation[$k]['required'])) { $validation[$k]['required'] = true; }
		}
		
		// AD_PREFERRED - Can only choose LDAP if AD is enabled
		$validation['AD_PREFERRED']['values'] = ($data['ACTIVE_DIRECTORY'] ? array('LDAP', 'Local') : array('Local'));
		
		// AD_ATTRIBUTES - Can only choose LDAP if AD is enabled
		$data['AD_ATTRIBUTES'] = isset($data['AD_ATTRIBUTES']) ? $data['AD_ATTRIBUTES'] : array();
		$validation['AD_ATTRIBUTES']['values'] = $allowed['attributes'];
		$ad_attributes = $data['AD_ATTRIBUTES'];
		if(is_array($data['AD_ATTRIBUTES'])) {$data['AD_ATTRIBUTES'] = array_keys($data['AD_ATTRIBUTES']);}
		
		// Default access groups
		$validation['DEFAULT_ACCESS_GUEST']['values_csv'] = $allowed['access_groups'];
		$validation['DEFAULT_ACCESS_LDAP']['values_csv'] = $allowed['access_groups'];
		$validation['DEFAULT_ACCESS_LOCAL']['values_csv'] = $allowed['access_groups'];
		
		// Allowed time zones
		$validation['ALLOWED_TIME_ZONES']['values_csv'] = $allowed['time_zones'];
		
		// Remote database
		$validation['REMOTE_DATABASE']['values_csv'] = $allowed['databases'];
		
		// Remote admins
		$validation['REMOTE_ADMINS'] = array('required' => array('REMOTE_SITE' => 'Secondary'), 'not_empty' => true);
		
		// Validation
		$Validator = new \Validator($data);
		@$Validator->validate($validation);
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput(), 'data' => $data); }	
		$form = $Validator->getForm();
		
		// Return failure if Remote Site AND Active Directory is enabled
		if($form['ACTIVE_DIRECTORY'] == 1 && $form['REMOTE_SITE'] != 'Disabled') {
			return array('result' => 'validate', 'validation' => array(
				'ACTIVE_DIRECTORY' => array('set' => 'This can\'t be enabled if Remote Site is enabled!'),
				'REMOTE_SITE' => array('set' => 'This can\'t be enabled if Active Directory is enabled!')
			));
		}
		
		// JSON Encode AD Attributes
		$form['AD_ATTRIBUTES'] = json_encode($ad_attributes);
		
		// Set REMOTE_ID - TODO - set tables auto increment back to 1 ?????
		if($form['REMOTE_SITE'] == 'Primary'){$form['REMOTE_ID'] = 0;}
		if($form['REMOTE_SITE'] == 'Disabled'){$form['REMOTE_ID'] = null;}
		
		// If remote site was 'Disabled' or 'Primary' and we are changing to 'Secondary'
		if(($site_settings['REMOTE_SITE']['data'] == 'Disabled' || $site_settings['REMOTE_SITE']['data'] == 'Primary') && $form['REMOTE_SITE'] == 'Secondary') {
			// We do not want to create a member log since members will be removed
			$create_log = false;
			
			// Connect to Primary site to generate an ID
			if($Database->Q(array(
				'db' => $form['REMOTE_DATABASE'],
				'query' => 'SELECT data FROM fks_site_settings WHERE id = "REMOTE_SITE_IDS"'
			))){
				// Check for the record
				if($Database->r['found'] == 0) {
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Remote database is missing data!');
				}
				
				// Explode ID's if not null
				$_ids = is_null($Database->r['row']['data']) ? array() : explode(',', $Database->r['row']['data']);
				
				// Loop through known ID's and generate a new one
				while(true) {
					$_key = $this->makeKey(4);
					if(!in_array($_key, $_ids)){ break; }
				}
				
				// Add key to the known list
				array_push($_ids, $_key);
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Update Primary site's list of ID's
			if(!$Database->Q(array(
				'db' => $form['REMOTE_DATABASE'],
				'params' => array(
					':data' => implode(',', $_ids)
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = "REMOTE_SITE_IDS"'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Update local site's current ID
			if(!$Database->Q(array(
				'params' => array(
					':data' => $_key
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = "REMOTE_ID"'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Reset tables
			if(!$this->resetLocalTables()) {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Failed to reset tables!');
			}
			
			// Add passed member access group
			foreach($form['REMOTE_ADMINS'] as $k => $v) {
				if(!$Database->Q(array(
					'params' => array(
						':id' => 8,
						':member_id' => $v['id'],
						':data' => $v['access_id']
					),
					'query' => 'INSERT INTO fks_member_data SET id = :id, member_id = :member_id, data = :data'
				))){
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
				}
			} 
			
			// Log out the current user
			$this->Session->destroy();
		}
		
		// Unset Remote Admins if set
		if(array_key_exists('REMOTE_ADMINS', $form)) { unset($form['REMOTE_ADMINS']); }
		
		// Set signature color if null
		if($form['SITE_COLORS_SIGNATURE'] == null) { $form['SITE_COLORS_SIGNATURE'] = '#36e3fd'; }
		
		// Setup DataHandler if we need to check seperate Diffs
		$DataHandler = new \DataHandler(array(
			'fks_site_settings' => array(
				'base' => 'fks_site_settings',
				'log_actions' => array('modified' => \Enums\LogActions::SITE_SETTINGS_MODIFIED)
			)
		));
		
		// Loop through the data to figure out what was changed
		foreach($form as $k => $v) {
			// Skip if there is no change
			if($v === $site_settings[$k]['data']) { continue; }
			
			// Create log
			$log = array($site_settings[$k]['data'], $v);
			
			// If JSON diff
			if($site_settings[$k]['type'] == 'json') {
				$v = $v == '[]' ? '{}' : $v;
				
				$new_data = array(
					'columns' => array(
						'data' => $v
					),
					'data' => false
				);
				
				$tst = $DataHandler->diff('local', 'fks_site_settings', $k, $new_data, array(), array('columns' => array('data')));
				
				// Skip if no change
				if(!$tst){ continue; }
				
				$tst['log_misc'] = json_decode($tst['log_misc'], true);
				$log = $tst['log_misc']['data'];
			}

			// Update the field
			if(!$Database->Q(array(
				'params' => array(
					':id' => $k,
					':data' => $v
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = :id'
			))) {
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Add member log
			$updated[$k] = $log;
		}
		
		// Set colors if changed
		if(array_key_exists('SITE_COLORS_SIGNATURE', $updated)) {
			$set_colors = true;
			$this->setColors();
		}
		
		// Create member log
		if(count($updated) > 0 && $create_log) {
			$MemberLog = new \MemberLog(\Enums\LogActions::SITE_SETTINGS_MODIFIED, $_SESSION['id'], NULL, json_encode($updated));
			return array('result' => 'success', 'message' => 'Settings have been updated!', 'updated' => $updated, 'do_log' => $create_log, 'set_colors' => $set_colors);
		}
		
		// Return no changes
		return array('result' => 'info', 'message' => 'No changes detected!', 'do_log' => $create_log);
	}
	
	// -------------------- Load Site Settings History -------------------- \\
	public function loadSiteSettingsHistory() {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'title' => 'Site Settings History',
			'id' => null,
			'actions' => array(
				\Enums\LogActions::SITE_SETTINGS_MODIFIED
			)
		));
		
		return $history;
	}

    // -------------------- Edit Member -------------------- \\
    public function sendEmailForm() {
		// Check for write access
        if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }

        $body = '<form id="editModalForm" role="form" action="javascript:void(0);">
			<div class="row">
				<div class="col-md-12">
				    <p>This will use the current saved site settings so make sure your settings have been saved before attempting to send a test message.</p>
					<div class="form-group">
						<label for="to_address" class="form-control-label">To Address</label>
						<input type="email" class="form-control form-control-sm" id="to_address" name="to_address" aria-describedby="to_address_HELP" value="' . (isset($m['username']) ? $m['username'] : '') . '"' . ($this->access > 2 ? '' : $readonly) . '>
						<div class="form-control-feedback"></div>
						<small id="to_address_HELP" class="form-text text-muted">&nbsp;</small>
					</div>
				</div>
			</div>
		</form>';

        $parts = array(
            'title' => 'Send Test Email Form',
            'body' => $body,
            'footer' => ''
                . '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
                . '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editModalForm"><i class="fa fa-paper-plane-o fa-fw"></i> Send Email</button>'
        );

        return array('result' => 'success', 'parts' => $parts);
    }

    // -------------------- Function For Sending Test Email -------------------- \\
    public function sendTestMail($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$mail = $this->sendEmail(array(
			'to_address' => $data['to_address'],
			'subject' => 'Test Email',
			'body' => 'Hello<br><br>This is a test message!'
		));
		
		if($mail['result'] == 'success') {
			return array('result' => 'success', 'message' => 'Test email has been sent!');
		} else {
			return array('result' => 'failure', 'message' => $mail['message']);
		}
	}
	
	// -------------------- Remote Database Validator -------------------- \\
    public function validateRemoteDatabase($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Check for data
	    if( !isset($data) || is_null($data) ){ return array('result' => 'failure', 'message' => 'Data was not passed...'); }
		
		// Create database class
		$Database = new \Database(array('db' => $data));
		
		// Check for valid connection
		if( !isset($Database->db[$data]) ){ return array('result' => 'failure', 'message' => 'Not a valid connection.'); }
		
		// Attempt a connection
		if( !$Database->con() ){ return array('result' => 'failure', 'message' => $Database->r['error']); }
		
		// Return
		return array('result' => 'success', 'message' => 'Successfully validated!', 'data' => $data);
	}
	
	// -------------------- Remote Database Search -------------------- \\
    public function searchRemoteDatabase($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Check for data
	    if( !isset($data) || is_null($data) ){ return array('result' => 'failure', 'message' => 'Data was not passed...'); }
		
		// Create local database class
		$Database = new \Database();
		
		// Grab local access groups
		if($Database->Q('SELECT id, title FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY hierarchy DESC')) {
			$access = '';
			foreach($Database->r['rows'] as $k => $v) {
				$access .= '<option value="' . $v['id'] . '">' . $v['title'] . '</option>';
			}
		} else {
			return array('result' => 'failure', 'message' => 'Unable to load access groups!');
		}
		
		// Search for remote member
		if($Database->Q(array(
			'db' => $data['database'],
			'params' => array(
				'username' => '%' . $data['search'] . '%'
			),
			'query' => 'SELECT id, username FROM fks_members WHERE username LIKE :username AND active = 1 AND deleted = 0'
		))) {
			$table = '';
			foreach($Database->r['rows'] as $k => $v) {
				$table .= '<tr><td>' . $v['id'] . '</td><td class="username">' . $v['username'] . '</td><td><select member-id="' . $v['id'] . '" class="form-control form-control-sm member-access">' . $access . '</select></td><td><button type="button" class="btn fks-btn-success btn-sm member-add-btn"><i class="fa fa-plus fa-fw"></i></button></td></tr>';
			}
		} else {
			return array('result' => 'failure', 'message' => 'Database failure!');
		}
		
		// Create the modal body
		$body = '<table class="table table-striped table-hover table-sm"><thead class="thead-dark"><tr><th>ID</th><th>Username</th><th>Access</th><th style="width:50px;text-align:center;padding-right:11px;"><i class="fa fa-plus fa-fw"></i></th></tr></thead><tbody>' . $table . '</tbody></table>';
		
		// Return parts
        return array(
			'result' => 'success',
			'parts' => array(
				'title' => 'Member Lookup',
				'size' => 'lg',
				'body' => $body,
				'footer' => '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
			)
		);
	}
	
	// -------------------- Set Colors -------------------- \\
	public function setColors($colors = array()) {
		$fks_folder = parent::ROOT_DIR . '/scripts/js/plugins/fks/';
		$fks_colors_file = file_get_contents($fks_folder . 'fks-colors.scss');
		$fks_colors = array();
		$out = array();
		$regex = '/\/\/fks_colors_begin(.+?)\/\/fks_colors_end/s';
		
		// Get colors from database if empty
		if(empty($colors)) {
			$Database = new \Database;
			if($Database->Q('
					SELECT
						id, data
					FROM
						fks_site_settings
					WHERE
						id = "SITE_COLORS_SIGNATURE"
			')) {
				foreach($Database->r['rows'] as $row) {
					if($row['id'] == 'SITE_COLORS_SIGNATURE') { $colors['signature'] = $row['data']; }
				}
			}
		}
		
		// Get all colors from file
		preg_match_all($regex, preg_replace("/\r|\n|\t|\s/", "", $fks_colors_file), $matches, PREG_SET_ORDER, 0);
		
		// Stop if no matches found
		if(empty($matches)) { return false; }
		
		// Loop through matches and set colors array
		foreach(explode(',', $matches[0][1]) as $color) {
			$parts = explode(':', $color);
			$fks_colors[str_replace('\'', '', $parts[0])] = $parts[1];
		}
		
		// Change colors
		foreach($colors as $k => $v) {
			if(!array_key_exists($k, $fks_colors)) { continue; }
			$fks_colors[$k] = $v;
		}
		
		// Loop through colors and build out array
		foreach($fks_colors as $k => $v) {
			array_push($out, "\t'" . $k . "': " . $v);
		}
		
		// Create new colors
		$new_fks_colors = preg_replace($regex, "//fks_colors_begin\r\n" . implode(",\r\n", $out) . "\r\n//fks_colors_end", $fks_colors_file);

		// Save scss file
		file_put_contents($fks_folder . 'fks-colors.scss', $new_fks_colors);
		
		// Rebuild the CSS file
		$this->rebuildCSS($fks_folder, 'fks');
	}
}
?>