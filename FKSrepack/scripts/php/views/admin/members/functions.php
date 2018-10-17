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
		
		// Set vars
		$your_hierarchy = $this->getHierarchy($_SESSION['id']);
		$readonly = ($this->access == 1);
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		$del = $this->access < 3 ? ' WHERE deleted = 0' : '';
		
		// Grab all members
		if($Database->Q(array(
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
		
		foreach($data as $k => &$v) {
			$found = $DataTypes->getData(array(
					\Enums\DataTypes::FIRST_NAME,
					\Enums\DataTypes::LAST_NAME,
					\Enums\DataTypes::EMAIL_ADDRESS,
					\Enums\DataTypes::ACCESS_GROUPS
				),
				$v['id']
			);
			
			foreach( $found as $name => $value ) {
				$v[ strtolower($name) ] = !$value || $value == NULL ? '-' : $value;
			}
			
			$h = 0;
			$access = explode(',', $v['access_groups']);
			if($access[0] != '-') {
				foreach($access as $ak => $av){
					$access[$ak] = $access_groups[$av]['title'];
					if($access_groups[$av]['hierarchy'] > $h) { $h = $access_groups[$av]['hierarchy']; }
				}
			}
			$v['access_groups'] = implode(', ', $access);
			
			$v['tools'] = '<span class="pull-right">';
				// History
				if($this->access > 2) { $v['tools'] .= '<a class="history" href="javascript:void(0);" data-toggle="fks-tooltip" title="History"><i class="fa fa-history fa-fw"></i></a>&nbsp;'; }
				// View & Edit
				if($your_hierarchy >= $h && !$readonly) {
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
		
		// Store hierarchy
		$your_hierarchy = $this->getHierarchy($_SESSION['id']);
		$user_hierarchy = $this->getHierarchy($data);
		
		// Set vars
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		$readonly = '';
		
		// Grab member data
		if($Database->Q(array(
			'params' => array(
				':member_id' => $data
			),
			'query' => '
				SELECT
					m.*
				
				FROM
					fks_members AS m
					
				WHERE
					m.id = :member_id 
			'
		))){
			// Check to see if we can find member details
			if($Database->r['found'] == 1 ) {
				// Editing
				$m = $Database->r['row'];
				$d = $DataTypes->getData(\Enums\DataTypes::constants(), $m['id']);
				$readonly = $your_hierarchy >= $user_hierarchy ? '' : ' disabled';
				$button = 'Update Member';
			} else {
				// Creating
				$button = 'Add Member';
				$d = $DataTypes->getData(\Enums\DataTypes::constants(), 0);
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create modal title
		$title = '<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" data-toggle="tab" href="#modal_tab_1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> Settings</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#modal_tab_2" role="tab" draggable="false"><i class="fa fa-lock fa-fw"></i> Access Groups</a>
			</li>
		</ul>';
		
		// Create modal body
		$body = '<form id="modalForm" role="form" action="javascript:void(0);"><div class="tab-content">
			<div class="tab-pane active" id="modal_tab_1" role="tabpanel">
				<input type="hidden" name="ID" value="' . (isset($m['id']) ? $m['id'] : '+') . '"/>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="USERNAME" class="form-control-label">Username</label>
							<input type="text" class="form-control form-control-sm" id="USERNAME" name="USERNAME" aria-describedby="USERNAME_HELP" value="' . (isset($m['username']) ? $m['username'] : '') . '"' . ($this->access > 2 ? '' : $readonly) . '>
							<div class="form-control-feedback"></div>
							<small id="USERNAME_HELP" class="form-text text-muted">This should never be changed.</small>
						</div>
					</div>
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::FULL_NAME, ($d['FULL_NAME'] ? $d['FULL_NAME'] : ''), $readonly != '') . '
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="PASSWORD" class="form-control-label">Password 1</label>
							<input type="password" class="form-control form-control-sm" id="PASSWORD" name="PASSWORD" aria-describedby="PASSWORD_HELP" value="' . ($d['PASSWORD'] ? '-[NOCHANGE]-' : '') . '"' . $readonly . '>
							<div class="form-control-feedback"></div>
							<small id="PASSWORD_HELP" class="form-text text-muted">Only change this if you want a new password.</small>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="PASSWORD2" class="form-control-label">Password 2</label>
							<input type="password" class="form-control form-control-sm" id="PASSWORD2" name="PASSWORD2" aria-describedby="PASSWORD2_HELP" value="' . ($d['PASSWORD'] ? '-[NOCHANGE]-' : '') . '"' . $readonly . '>
							<div class="form-control-feedback"></div>
							<small id="PASSWORD2_HELP" class="form-text text-muted">Re-type your password.</small>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::FIRST_NAME, ($d['FIRST_NAME'] ? $d['FIRST_NAME'] : ''), $readonly != '') . '
					</div>
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::LAST_NAME, ($d['LAST_NAME'] ? $d['LAST_NAME'] : ''), $readonly != '') . '
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::EMAIL_ADDRESS, ($d['EMAIL_ADDRESS'] ? $d['EMAIL_ADDRESS'] : ''), $readonly != '') . '
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="ACTIVE" class="form-control-label">Status</label>
							<select class="form-control form-control-sm" id="ACTIVE" name="ACTIVE" aria-describedby="ACTIVE_HELP"' . $readonly . '>
								<option value="0"' . (isset($m['active']) && $m['active'] == 0 ? ' selected' : '') . '>Disabled</option>
								<option value="1"' . ((isset($m['active']) && $m['active'] == 1) || !isset($m['active']) ? ' selected' : '') . '>Active</option>
							</select>
							<div class="form-control-feedback"></div>
							<small id="ACTIVE_HELP" class="form-text text-muted">Whether this member can log in.</small>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::TIMEZONE, ($d['TIMEZONE'] ? $d['TIMEZONE'] : ''), $readonly != '') . '
					</div>
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::DATE_FORMAT, ($d['DATE_FORMAT'] ? $d['DATE_FORMAT'] : ''), $readonly != '') . '
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-6">
						' . $this->buildFormInputs(\Enums\DataTypes::SITE_LAYOUT, ($d['SITE_LAYOUT'] ? $d['SITE_LAYOUT'] : ''), $readonly != '') . '
					</div>
					<div class="col-md-6"></div>
				</div>
			</div>
			
			<div class="tab-pane" id="modal_tab_2" role="tabpanel">
				<div class="row">
					<div class="col-md-12">
						' . $this->buildFormInputs(\Enums\DataTypes::ACCESS_GROUPS, ($d['ACCESS_GROUPS'] ? $d['ACCESS_GROUPS'] : ''), $readonly != '') . '
					</div>
				</div>
			</div>
		</div></form>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => $title,
				'size' => 'lg',
				'body' => $body,
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . ($readonly == '' ? 'Cancel' : 'Close') . '</button>'
					. ($readonly == '' ? '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#modalForm"><i class="fa fa-undo fa-fw"></i> Reset</button>' : '')
					. ($readonly == '' && $this->access > 1 ? '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-save fa-fw"></i> ' . $button . '</button>' : '')
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
		
		// Set Vars
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		$Validator = new \Validator( $data );
		$names = \Enums\DataTypes::flip();
		$access_groups = array();
		$form_access_groups = array();
		$member_access_groups = array();
		$user_access_groups = array_keys($_SESSION['access_groups']);
		$member_data = array();
		$member = array();
		
		// Pre-Validate
		$Validator->validate('ID', array('required' => true));
		$Validator->validate('ACTIVE', array('bool' => true, 'required' => true));
		$Validator->validate('USERNAME', array('min_length' => 3, 'alphanumeric' => true, 'no_spaces' => true, 'required' => true));
		$Validator->validate('PASSWORD', array('match' => 'PASSWORD2'));
		
		// Grab All Access Groups
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
		
		foreach($names as $k => $v) {
			if(isset($data[$v])) {
				if($k == \Enums\DataTypes::ACCESS_GROUPS['id']) { $Validator->validate($v, array('values_csv' => array_keys($access_groups))); continue;}
				if($k == \Enums\DataTypes::SITE_LAYOUT['id']) { $Validator->validate($v, array('values_csv' => array('Default','Admin'))); continue;}
				
				$Validator->validate($v, constant("\Enums\DataTypes::$v")['validation']);
			}
		}
		
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
			'params' => array(
				'id' => $form['ID']
			),
			'query' => 'SELECT username FROM fks_members WHERE id = :id'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
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
						'params' => array(
							'username' => $form['USERNAME']
						),
						'query' => 'SELECT id FROM fks_members WHERE username = :username'
					))) {
					// Query succeeded
						if($Database->r['found'] != 0) {
						// Username is already in use
							return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('USERNAME' => 'Username is already in use.'));
						} else {
						// Username is not in use
						}
					} else {
					// Query failed?...
						// Return error message with error code
						return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
					}
				}
			}
			
			// Check for duplicate data values
			if( $check = $DataTypes->checkData(array(
				\Enums\DataTypes::USERNAME['id'] => $form['USERNAME'],
				\Enums\DataTypes::EMAIL_ADDRESS['id'] => $form['EMAIL_ADDRESS']
			)) ) {
				$validation = array();
				foreach($check as $k => $v) {
					if($v != false && $v != $form['ID']) { $validation[$names[$k]] = constant("\Enums\DataTypes::$names[$k]")['title'] . ' already in use.'; }
				}
				if( count($validation) > 0 ) {
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => $validation);
				}
			} else {
				return array('result' => 'failure', 'title' => 'Failure', 'message' => 'Check Data failed');
			}
		
			// Check for existing access groups
			if($getData = $DataTypes->getData(array(\Enums\DataTypes::ACCESS_GROUPS), $form['ID'])) {
			// Member has existings access group(s)
				$member_access_groups = ($getData['ACCESS_GROUPS'] ? explode(',', $getData['ACCESS_GROUPS']) : array());
			} else {
			// Unable to get access groups
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
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
			
			// Separate data
			foreach($form as $k => $v) {
				if(in_array($k, $names)) {
					$member_data[constant("\Enums\DataTypes::$k")['id']] = $v;
				} else {
					$member[strtolower($k)] = $v;
				}
			}
			
			// Check Diffs
			$diff1 = $this->compareQueryArray($form['ID'], 'fks_members', $member_data, false, true);
			$diff2 = $this->compareQueryArray($form['ID'], 'fks_members', $member, false);
			
			// Save member_data
			if($diff1 && !$DataTypes->setData($member_data, $form['ID'])) {
				// Failed to save data
				$diff1 = false;
			}

			// Save member
			if($diff2) {
				if(!$Database->Q(array(
					'params' => array(
						':id' => $form['ID'],
						':username' => $form['USERNAME'],
						':date_modified' => gmdate('Y-m-d H:i:s'),
						':modified_by' => $_SESSION['id'],
						':active' => $form['ACTIVE']
					),
					'query' => '
						UPDATE
							fks_members
						
						SET
							username = :username,
							date_modified = :date_modified,
							modified_by = :modified_by,
							active = :active
						
						WHERE
							id = :id
					'
				))) {
					$diff2 = false;
				}
			}
			
			// Prepare member log
			$diff3 = ($diff1 && $diff2 ? $diff1 + $diff2 : ($diff1 ? $diff1 : ($diff2 ? $diff2 : false)));
			
			// Save member log
			if($diff3 && !empty($diff3)) {
				$MemberLog = new \MemberLog(\Enums\LogActions::MEMBER_MODIFIED, $_SESSION['id'], $form['ID'], json_encode($diff3));
			} else {
				// Return No Changes
				return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.');
			}
			
			// Return Success
			return array('result' => 'success', 'title' => 'Saved Member Settings', 'message' => 'Updated member: ' . $form['USERNAME'], 'reload' => (isset($diff3[\Enums\DataTypes::SITE_LAYOUT['id']]) ? 'true' : 'false'));
		} else {
		// Create new member
			
			// Check for duplicate data values
			if( $check = $DataTypes->checkData(array(
				\Enums\DataTypes::USERNAME['id'] => $form['USERNAME'],
				\Enums\DataTypes::EMAIL_ADDRESS['id'] => $form['EMAIL_ADDRESS']
			)) ) {
				$validation = array();
				foreach($check as $k => $v) {
					if($v != false && $v != $form['ID']) { $validation[$names[$k]] = constant("\Enums\DataTypes::$names[$k]")['title'] . ' already in use.'; }
				}
				if( count($validation) > 0 ) {
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => $validation);
				}
			} else {
				return array('result' => 'failure', 'title' => 'Failure', 'message' => 'Check Data failed');
			}
			
			// Check if username is in use
			if($Database->Q(array(
				'params' => array(
					'username' => $form['USERNAME']
				),
				'query' => 'SELECT id FROM fks_members WHERE username = :username'
			))) {
			// Query succeeded
				if($Database->r['found'] != 0) {
				// Username is already in use
					return array('result' => 'validate', 'message' => 'There were issues with the form.', 'data' => $data, 'validation' => array('USERNAME' => 'Username is already in use.'));
				} else {
				// Username is not in use
				}
			} else {
			// Query failed?...
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
			
			// Separate data
			foreach($form as $k => $v) {
				if(in_array($k, $names)) {
					if(!empty($v)) {
						$member_data[constant("\Enums\DataTypes::$k")['id']] = $v;
					}
				} else {
					$member[strtolower($k)] = $v;
				}
			}
			
			// Save new member to database
			if(!$Database->Q(array(
				'params' => array(
					':username' => $form['USERNAME'],
					':date_created' => gmdate('Y-m-d H:i:s'),
					':created_by' => $_SESSION['id'],
					':active' => $form['ACTIVE'],
					':deleted' => 0
				),
				'query' => '
					INSERT INTO
						fks_members
						
					SET
						username = :username,
						date_created = :date_created,
						created_by = :created_by,
						active = :active,
						deleted = :deleted
				'
			))) {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			$last_id = $Database->r['last_id'];
			
			// Set data of the new member (if there is data to set)
			if(!empty($member_data)) { $DataTypes->setData($member_data, $last_id); }
			
			// Prepare member log
			unset($member['id']);
			$member['deleted'] = '0';
			$diff3 = $member + $member_data;
			
			// Save member log
			if($diff3 && !empty($diff3)) {
				$MemberLog = new \MemberLog(\Enums\LogActions::MEMBER_CREATED, $_SESSION['id'], $last_id, json_encode($diff3));
			}
			
			// Return Success
			return array('result' => 'success', 'title' => 'Saved Member Settings', 'message' => 'Created new member: ' . $form['USERNAME'], 'reload' => 'false');
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