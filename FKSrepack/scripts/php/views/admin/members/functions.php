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
		$this->access = $this->getAccess('members');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/	
	private function test() {
		
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Members -------------------- \\
	public function loadMembersTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set variables
		$your_hierarchy = $this->getHierarchy($_SESSION['id']);
		$readonly = ($this->access == 1);
		$Database = new \Database();
		$del = $this->access < 3 ? ' WHERE deleted = 0' : '';
		
		// Grab all members
		if($Database->Q(array(
			'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
			'assoc' => 'id',
			'query' => '
				SELECT
					m.*,
					(SELECT username FROM fks_members WHERE id = m.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = m.modified_by) AS modified_name
				
				FROM
					fks_members as m
			' . $del
		))) {
			// Format rows
			$data = $this->formatTableRows($Database->r['rows']);
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab All Access Groups
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT id,title,hierarchy FROM fks_access_groups'
		))) {
			// Store access groups
			$access_groups = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		));
		
		// Loop through each member to grab data
		foreach($data as $k => &$v) {
			// Grab data using DataHandler
			$_r = $DataHandler->getData('remote', 'members', $v['id'], array('columns' => false, 'data' => array('FIRST_NAME','LAST_NAME','EMAIL_ADDRESS')));
			$_l = $DataHandler->getData('local', 'members', $v['id'], array('columns' => false, 'data' => array('ACCESS_GROUPS')));
			$_h = 0;
			
			// Convert access group id's to names
			if(!empty($_l['data']['ACCESS_GROUPS']['value'])) {
				$_a = explode(',', $_l['data']['ACCESS_GROUPS']['value']);
				if($_a[0] != '-') {
					foreach($_a as $ak => $av) {
						// Remove group from list and skip if it doesn't exist
						if(!key_exists($av, $access_groups)) {
							unset($_a[$ak]);
							continue;
						}
						
						$_a[$ak] = $access_groups[$av]['title'];
						if($access_groups[$av]['hierarchy'] > $_h) { $_h = $access_groups[$av]['hierarchy']; }
					}
				}
				$v['access_groups'] = implode(', ', $_a);
			} else {
				$v['access_groups'] = '-';
			}
			
			// Datatable columns
			$v['first_name'] = is_null($_r['data']['FIRST_NAME']['value']) ? '-' : $_r['data']['FIRST_NAME']['value'];
			$v['last_name'] = is_null($_r['data']['LAST_NAME']['value']) ? '-' : $_r['data']['LAST_NAME']['value'];
			$v['email_address'] = is_null($_r['data']['EMAIL_ADDRESS']['value']) ? '-' : $_r['data']['EMAIL_ADDRESS']['value'];
			
			
			// Tools
			$v['tools'] = '<span class="pull-right">';
				// History
				if($this->access > 2) { $v['tools'] .= '<a class="history" href="javascript:void(0);" data-toggle="fks-tooltip" title="History"><i class="fa fa-history fa-fw"></i></a>&nbsp;'; }
				
				// View & Edit
				if($your_hierarchy >= $_h && !$readonly) {
					$v['tools'] .= '<a class="edit" href="javascript:void(0);" data-toggle="fks-tooltip" title="Edit"><i class="fa fa-edit fa-fw"></i></a>';
				} else {
					$v['tools'] .= '<a class="edit" href="javascript:void(0);" data-toggle="fks-tooltip" title="View"><i class="fa fa-eye fa-fw"></i></a>';
				}
			$v['tools'] .= '</span>';
		}
		
		return array('result' => 'success', 'data' => $data, 'access' => $this->access);
	}
	
	// -------------------- Edit Member -------------------- \\
	public function editMember( $data ) {
		// Check for read access
		if( $this->access < 1 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set variables
		$your_hierarchy = $this->getHierarchy($_SESSION['id']);
		$user_hierarchy = $this->getHierarchy($data);
		$pages = $this->getMenuItemStructures(false, true);
		$Database = new \Database();
		$readonly = false;
		$adding = true;
		$secondary = $this->siteSettings('REMOTE_SITE') == 'Secondary' ? true : false;
		
		// Grab member data
		if($Database->Q(array(
			'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
			'params' => array( ':member_id' => $data ),
			'query' => 'SELECT m.* FROM fks_members AS m WHERE m.id = :member_id'
		))){
			// Check to see if we can find member details
			if($Database->r['found'] == 1 ) {
				// Editing
				$member = $Database->r['row'];
				$readonly = ($your_hierarchy < $user_hierarchy);
				$adding = false;
			} else {
				$member['id'] = '+';
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		));
		
		// Create an array of member data to grab
		$data = array(
			'remote' => array('columns' => false, 'data' => array('FULL_NAME', 'PASSWORD', 'FIRST_NAME', 'LAST_NAME', 'EMAIL_ADDRESS', 'TIMEZONE', 'DATE_FORMAT')),
			'local' => array('columns' => false, 'data' => array('SITE_LAYOUT', 'HOME_PAGE', 'ACCESS_GROUPS'))
		);
		
		// Grab local and remote member data
		if(!$data['remote'] = $DataHandler->getData('remote', 'members', $member['id'], $data['remote'])) { return array('result' => 'failure', 'message' => 'Failed to get remote member data!'); }
		if(!$data['local'] = $DataHandler->getData('local', 'members', $member['id'], $data['local'])) { return array('result' => 'failure', 'message' => 'Failed to get local member data!'); }
		
		// Set up parameters for inputs
		$input = array(
			'username' => array(
				'title' => 'Username',
				'type' => 'text',
				'name' => 'USERNAME',
				'value' => (isset($member['username']) ? $member['username'] : ''),
				'help' => 'This should never be changed.',
				'required' => true,
				'properties' => array()
			),
			'fullname' => array(
				'title' => $data['remote']['data']['FULL_NAME']['title'],
				'type' => 'text',
				'name' => 'FULL_NAME',
				'value' => $data['remote']['data']['FULL_NAME']['value'],
				'help' => $data['remote']['data']['FULL_NAME']['help_text'],
				'required' => false,
				'properties' => array()
			),
			'password1' => array(
				'title' => 'Password 1',
				'type' => 'password',
				'name' => 'PASSWORD',
				'value' => (empty($data['remote']['data']['PASSWORD']['value']) ? '' : '-[NOCHANGE]-'),
				'help' => 'Only change this if you want a new password.',
				'required' => false,
				'properties' => array(),
				'attributes' => array(
					'class' => 'fks-base64'
				)
			),
			'password2' => array(
				'title' => 'Password 2',
				'type' => 'password',
				'name' => 'PASSWORD2',
				'value' => (empty($data['remote']['data']['PASSWORD']['value']) ? '' : '-[NOCHANGE]-'),
				'help' => 'Re-type your password.',
				'required' => false,
				'properties' => array(),
				'attributes' => array(
					'class' => 'fks-base64'
				)
			),
			'firstname' => array(
				'title' => $data['remote']['data']['FIRST_NAME']['title'],
				'type' => 'text',
				'name' => 'FIRST_NAME',
				'value' => $data['remote']['data']['FIRST_NAME']['value'],
				'help' => $data['remote']['data']['FIRST_NAME']['help_text'],
				'properties' => array()
			),
			'lastname' => array(
				'title' => $data['remote']['data']['LAST_NAME']['title'],
				'type' => 'text',
				'name' => 'LAST_NAME',
				'value' => $data['remote']['data']['LAST_NAME']['value'],
				'help' => $data['remote']['data']['LAST_NAME']['help_text'],
				'required' => false,
				'properties' => array()
			),
			'email' => array(
				'title' =>  $data['remote']['data']['EMAIL_ADDRESS']['title'],
				'type' => 'email',
				'name' => 'EMAIL_ADDRESS',
				'value' => $data['remote']['data']['EMAIL_ADDRESS']['value'],
				'help' => $data['remote']['data']['EMAIL_ADDRESS']['help_text'],
				'required' => false,
				'properties' => array()
			),
			'status' => array(
				'title' => 'Status',
				'type' => 'select',
				'name' => 'ACTIVE',
				'help' => 'Whether this member can log in.',
				'required' => true,
				'properties' => array(),
				'options' => array(
					array(
						'title' => 'Disabled',
						'value' => '0'
					),
					array(
						'title' => 'Active',
						'value' => '1',
						'selected' => ((isset($member['active']) && $member['active'] == 1) || !isset($member['active']))
					)
				)
			),
			'timezone' => array(
				'title' => $data['remote']['data']['TIMEZONE']['title'],
				'type' => 'select',
				'name' => 'TIMEZONE',
				'value' => $data['remote']['data']['TIMEZONE']['value'],
				'help' => $data['remote']['data']['TIMEZONE']['help_text'],
				'properties' => array(),
				'options' => array(
					array(
						'title' => 'Use Default (' . date_default_timezone_get() . ')',
						'value' => ''
					)
				),
				'attributes' => array(
					'class' => 'fks-select2'
				)
			),
			'dateformat' => array(
				'title' => $data['remote']['data']['DATE_FORMAT']['title'],
				'type' => 'text',
				'name' => 'DATE_FORMAT',
				'value' => '',
				'help' => $data['remote']['data']['DATE_FORMAT']['help_text'],
				'required' => false,
				'properties' => array()
			),
			'sitelayout' => array(
				'title' => $data['local']['data']['SITE_LAYOUT']['title'],
				'type' => 'select',
				'name' => 'SITE_LAYOUT',
				'help' => $data['local']['data']['SITE_LAYOUT']['help_text'],
				'required' => true,
				'properties' => array(),
				'options' => array()
			),
			'homepage' => array(
				'title' => $data['local']['data']['HOME_PAGE']['title'],
				'type' => 'select',
				'name' => 'HOME_PAGE',
				'help' => $data['local']['data']['HOME_PAGE']['help_text'],
				'properties' => array(),
				'options' => array(
					array(
						'title' => 'Use Default (' . (empty($this->siteSettings('SITE_HOME_PAGE')) ? 'home' : $pages[$this->siteSettings('SITE_HOME_PAGE')]) . ')',
						'value' => ''
					)
				),
				'attributes' => array(
					'class' => 'fks-select2'
				)
			),
			'accessgroups' => array(
				'title' => $data['local']['data']['ACCESS_GROUPS']['title'],
				'type' => 'select',
				'name' => 'ACCESS_GROUPS',
				'help' => $data['local']['data']['ACCESS_GROUPS']['help_text'],
				'properties' => array('multiple'),
				'options' => array()
			)
		);

		// Generate timezones
		foreach($this->timeZones()['list'] as $timezone) {
			array_push($input['timezone']['options'], array('title' => $timezone, 'value' => $timezone, 'selected' => ($timezone == $data['remote']['data']['TIMEZONE']['value'])) );
		}
		
		// Grab site layouts from site settings
		if($Database->Q('SELECT data,misc FROM fks_site_settings WHERE id = "SITE_LAYOUT"')){
			array_push($input['sitelayout']['options'], array('title' => 'Use Default (' . $Database->r['row']['data'] . ')', 'value' => '') );
			foreach(json_decode($Database->r['row']['misc'], true)['options'] as $v) {
				array_push($input['sitelayout']['options'], array('title' => $v['title'], 'value' => $v['title'], 'selected' => ($v['title'] == $data['local']['data']['SITE_LAYOUT']['value'])) );
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Home page options
		foreach($pages as $k => $v) {
			array_push($input['homepage']['options'], array('title' => $v, 'value' => $k, 'selected' => ($k == $data['local']['data']['HOME_PAGE']['value'])) );
		}
		
		// Grab access groups
		$data['local']['data']['ACCESS_GROUPS']['value'] = explode(',', $data['local']['data']['ACCESS_GROUPS']['value']);
		if($Database->Q('SELECT id,title,hierarchy FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY hierarchy')){
			foreach($Database->r['rows'] as $k => $v) {
				array_push($input['accessgroups']['options'], array('title' => $v['title'], 'value' => $v['id'], 'selected' => (in_array($v['id'], $data['local']['data']['ACCESS_GROUPS']['value'])), 'disabled' => ($your_hierarchy < $v['hierarchy'])) );
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Set to read only if member only has read access or is lower hierarchy than the editing member
		if( $this->access == 1 || $readonly || $secondary ) {
			foreach($input as $k => &$v) {
				if( $secondary ) {
					if(!in_array($v['id'], array('SITE_LAYOUT', 'HOME_PAGE', 'ACCESS_GROUPS'))){
						array_push($v['properties'], 'disabled');
					}
				} else {
					array_push($v['properties'], 'disabled');
				}
			}
		}
		
		// Modal tab 1 body
		$body[0] = '<input type="hidden" name="ID" value="' . (isset($member['id']) ? $member['id'] : '+') . '"/>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['username']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['fullname']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['password1']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['password2']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['firstname']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['lastname']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['email']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['status']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['timezone']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['dateformat']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($input['sitelayout']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($input['homepage']) . '</div>
			</div>';
		
		// Modal tab 2 body
		$body[1] = '<div class="row"><div class="col-md-12">' . $this->buildFormGroup($input['accessgroups']) . '</div></div>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => array('<i class="fa fa-gears fa-fw"></i> Settings', '<i class="fa fa-lock fa-fw"></i> Access Groups'),
				'size' => 'lg',
				'body_before' => '<form id="modalForm" class="fks-form fks-form-sm" action="javascript:void(0);">',
				'body' => array($body[0], $body[1]),
				'body_after' => '</form>',
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . (!$readonly ? 'Cancel' : 'Close') . '</button>'
					. (!$readonly ? '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#modalForm"><i class="fa fa-undo fa-fw"></i> Reset</button>' : '')
					. (!$readonly && $this->access > 1 ? '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-save fa-fw"></i> ' . ($adding ? 'Add' : 'Update') . ' Member</button>' : '')
			)
		);
	}
	
	// -------------------- Save Member -------------------- \\
	public function saveMember( $data ) {
		// Check for write access
		if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Store hierarchy
		$your_hierarchy = $this->getHierarchy($_SESSION['id']);
		
		// Check for Hierarchy (unless you are editing yourself)
		if($_SESSION['id'] != $data['ID']) {
			$user_hierarchy = $this->getHierarchy($data['ID']);
			if($your_hierarchy < $user_hierarchy){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		}
		
		// Set variables
		$Database = new \Database();
		$Validator = new \Validator( $data );
		$access_groups = array();
		$form_access_groups = array();
		$member_access_groups = array();
		$user_access_groups = array_keys($_SESSION['access_groups']);
		$member_data = array();
		$member = array();
		
		// Grab all Access Groups for validation
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_access_groups'
		))){
			// Store access groups
			$access_groups = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all Site Layouts for validation
		if($Database->Q('SELECT validation FROM fks_site_settings WHERE id = "SITE_LAYOUT"')){
			// Store site layouts
			$site_layouts = json_decode($Database->r['row']['validation'], true)['values'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Validation
		$Validator->validate(array(
			'ID' => array('required' => true),
			'ACTIVE' => array('required' => true, 'bool' => true),
			'USERNAME' => array('required' => true, 'not_empty' => true, 'min_length' => 3, 'alphanumeric' => true, 'no_spaces' => true),
			'PASSWORD' => array('match' => 'PASSWORD2'),
			'ACCESS_GROUPS' => array('values_csv' => array_keys($access_groups)),
			'SITE_LAYOUT' => array('values' => $site_layouts),
			'FULL_NAME' => array('min_length' => 3),
			'FIRST_NAME' => array('min_length' => 3, 'max_length' => 45),
			'LAST_NAME' => array('min_length' => 3, 'max_length' => 45),
			'EMAIL_ADDRESS' => array('email' => true),
			'TIMEZONE' => array('values' => $this->timeZones()['list']),
			'DATE_FORMAT' => array('required' => false),
			'HOME_PAGE' => array('required' => false, 'values' => array_keys($this->getMenuItemStructures(false, true)))
		));
		
		// Check for failures
		if( !$Validator->getResult() ){ return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Base64 decode password
		$form['PASSWORD'] = base64_decode($form['PASSWORD']);
		
		// Remove Password if NoChange or Blank OR hash password
		if($form['PASSWORD'] == '-[NOCHANGE]-' || trim($form['PASSWORD']) == '') {
			unset($form['PASSWORD']);
		} else {
			// Hash password
			require_once(parent::ROOT_DIR . '/scripts/php/includes/PasswordHash.php');
			$PasswordHash = new \PasswordHash(13, FALSE);
			$form['PASSWORD'] = $PasswordHash->HashPassword($form['PASSWORD']);
		}
		
		// See if the member exists
		if(!$Database->Q(array(
			'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
			'params' => array( ':id' => $form['ID'] ),
			'query' => 'SELECT username FROM fks_members WHERE id = :id'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'	,					// Column name (data table link to data types table)
				'log_actions' => array(							// Log actions (required if using diff)
					'created' => \Enums\LogActions::MEMBER_CREATED,
					'modified' => \Enums\LogActions::MEMBER_MODIFIED
				)
			)
		));
		
		// Check if member exists
		if($Database->r['found'] == 1) {
		// Found member
			$row = $Database->r['row'];
			
			// Username checking
			if(strtolower($row['username']) != strtolower($form['USERNAME'])) {
			// Username was changed
				if($this->access < 3) {
				// Only admins can change usernames.
				// This is because of LDAP
					return array('result' => 'validate', 'title' => 'Form Validation', 'message' => 'Admin access required.', 'data' => $data, 'validation' => array('USERNAME' => 'Only admins can change this field.'));
				} else {
				// Check if username is in use
					if($Database->Q(array(
						'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
						'params' => array( ':username' => $form['USERNAME'] ),
						'query' => 'SELECT id FROM fks_members WHERE username = :username'
					))) {
						// Return if the username is in use
						if($Database->r['found'] != 0) {
							return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('USERNAME' => 'Username is already in use.'));
						}
					} else {
						// Return error message with error code
						return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
					}
				}
			}
			
			// Check for duplicate email address
			if($Database->Q(array(
				'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
				'params' => array( 
					':id' => 4,
					':member_id' => $form['ID'],
					':data' => $form['EMAIL_ADDRESS']
				),
				'query' => 'SELECT member_id FROM fks_member_data WHERE id = :id AND data = :data AND member_id != :member_id'
			))) {
				// Return if the username is in use
				if($Database->r['found'] != 0) {
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('EMAIL_ADDRESS' => 'Email Address is already in use.'));
				}
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}

			// Check for existing access groups
			if($getData = $DataHandler->getData('local', 'members', $form['ID'], array('columns' => false, 'data' => array('ACCESS_GROUPS')) )) {
				// Member has existings access group(s)
				$member_access_groups = (!empty($getData['data']['ACCESS_GROUPS']['value']) ? explode(',', $getData['data']['ACCESS_GROUPS']['value']) : array());
			} else {
				// Unable to get access groups
				return array('result' => 'failure', 'message' => 'Failed to get Access Groups');
			}
			
			// Convert passed access groups to an array
			$form_access_groups = isset($form['ACCESS_GROUPS']) ? explode(',', $form['ACCESS_GROUPS']) : array();
			
			// Loop through each access group and check change permissions
			foreach($access_groups as $k => $v) {
				if(in_array($k, $member_access_groups) && !in_array($k, $form_access_groups)) {
				// Wants to remove access group (or value wasn't passed due to being disabled)
					if($your_hierarchy < $v['hierarchy']) {
					// You don't have permission, add missing group back
						array_push($form_access_groups, $k);
					}
				} else if(!in_array($k, $member_access_groups) && in_array($k, $form_access_groups)) {
				// Wants to add access group
					if($your_hierarchy < $v['hierarchy']) {
					// You don't have permission, remove extra group
						if(($del = array_search($k, $form_access_groups)) !== false) {
							unset($form_access_groups[$del]);
						}
					}
				}
			}
			
			// Convert form access groups back to csv
			sort($form_access_groups);
			$form['ACCESS_GROUPS'] = implode(',', $form_access_groups);

			// Check to see if this is a local or remote connection
			$connection = $this->siteSettings('REMOTE_SITE') == 'Secondary' ? 'remote' : 'local';
			
			// Create array for storing changeable data
			$member = array('columns' => array(), 'data' => array());
			
			// Seperate data (email, timezone, date) from member columns (id, username, active, deleted)
			foreach($form as $k => $v) {
				if(in_array($k, array('ID', 'USERNAME', 'ACTIVE'))) {
					// Only store column data if it is a local connection
					if($connection == 'local') { $member['columns'][strtolower($k)] = $v; }
				} else {
					// Store specific data if it is a remote connection
					if($connection == 'local') {
						// Local connection takes everything
						$member['data'][$k] = $v;
					} else {
						if(in_array($k, array('SITE_LAYOUT', 'HOME_PAGE', 'ACCESS_GROUPS'))) {
							$member['data'][$k] = $v;
						}
					}
				}
			}
			
			// Update the member
			$DSL = $DataHandler->DSL(array(
				'type' => $connection,
				'table' => 'members',
				'target_id' => $form['ID'],
				'values' => $member,
				'ignore_columns' => array(),	// Optional
				'server' => false				// Optional
			));

			// Return
			if($DSL['result'] == 'success') {
				return array('result' => 'success', 'title' => 'Saved Member Settings', 'message' => 'Updated member: ' . $form['USERNAME'], 'reload' => (isset($diff['changes']['data'][13]) ? 'true' : 'false'));
			} else {
				return $DSL;
			}

		} else {
		// Create new member
			// Return failure if set to Secondary Site
			if( $this->siteSettings('REMOTE_SITE') == 'Secondary' ) {
				return array('result' => 'failure', 'title' => 'Site Error', 'message' => 'Can not create members on a Secondary Site.');
			}
		
			// Check if username is in use
			if($Database->Q(array(
				'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
				'params' => array( ':username' => $form['USERNAME'] ),
				'query' => 'SELECT id FROM fks_members WHERE username = :username'
			))) {
				// Return if the username is in use
				if($Database->r['found'] != 0) {
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('USERNAME' => 'Username is already in use.'));
				}
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Check for duplicate email address
			if($Database->Q(array(
				'db' => ($this->siteSettings('REMOTE_SITE') == 'Secondary' ? $this->siteSettings('REMOTE_DATABASE') : $Database->db['default']),
				'params' => array( 
					':id' => 4,
					':member_id' => $form['ID'],
					':data' => $form['EMAIL_ADDRESS']
				),
				'query' => 'SELECT member_id FROM fks_member_data WHERE id = :id AND data = :data AND member_id != :member_id'
			))) {
				// Return if the username is in use
				if($Database->r['found'] != 0) {
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('EMAIL_ADDRESS' => 'Email Address is already in use.'));
				}
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Convert passed access groups to an array
			$form_access_groups = isset($form['ACCESS_GROUPS']) ? explode(',', $form['ACCESS_GROUPS']) : array();
			
			// Loop through each access group and check change permissions
			foreach($access_groups as $k => $v) {
				if(in_array($k, $form_access_groups)) {
				// Wants to add access group
					if($your_hierarchy < $v['hierarchy']) {
					// You don't have permission, remove extra group
						if(($del = array_search($k, $form_access_groups)) !== false) {
							unset($form_access_groups[$del]);
						}
					}
				}
			}
			
			// Convert form access groups back to csv
			sort($form_access_groups);
			$form['ACCESS_GROUPS'] = implode(',', $form_access_groups);

			// Create array for storing changeable data
			$member = array('columns' => array(), 'data' => array());
			
			// Seperate data (email, timezone, date) from member columns (id, username, active, deleted)
			foreach($form as $k => $v) {
				if(in_array($k, array('ID', 'USERNAME', 'ACTIVE'))) {
					$member['columns'][strtolower($k)] = $v;
				} else {
					$member['data'][$k] = $v;
				}
			}
			
			// Update the member
			$DSL = $DataHandler->DSL(array(
				'type' => 'local',
				'table' => 'members',
				'target_id' => '+',
				'values' => $member,
				'ignore_columns' => array(),	// Optional
				'server' => false				// Optional
			));

			// Return
			if($DSL['result'] == 'success') {
				return array('result' => 'success', 'title' => 'Saved Member Settings', 'message' => 'Updated member: ' . $form['USERNAME'], 'reload' => (isset($diff['changes']['data'][13]) ? 'true' : 'false'));
			} else {
				return $DSL;
			}
		}
	}
	
	// -------------------- Load Member History -------------------- \\
	public function loadMemberHistory($data) {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_members',
			'id' => $data,
			'title' => 'Member History: ',
			'select' => 'username',
			'actions' => array(
				\Enums\LogActions::MEMBER_CREATED,
				\Enums\LogActions::MEMBER_MODIFIED
			)
		));
		
		return $history;
	}
}
?>