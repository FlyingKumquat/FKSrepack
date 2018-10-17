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
		$this->access = $this->getAccess('access_groups');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/	
	private function test() {
		
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Access Groups -------------------- \\
	public function loadAccessGroups() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$del = $this->access < 3 ? ' WHERE ag.deleted = 0' : '';
		
		// Grab all access groups
		if($Database->Q(array(
			'query' => '
				SELECT 
					ag.*,
					(SELECT username FROM fks_members WHERE id = ag.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = ag.modified_by) AS modified_name
					
				FROM 
					fks_access_groups AS ag
			' . $del
		))) {
			// Set rows
			$data = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Loop through all access groups
		foreach($data as $k => &$v) {
			if(!empty($v['data'])) {
				$types = array(0, 0, 0, 0);
				$temp_data = json_decode($v['data'], true);
				foreach($temp_data as $permission) {
					$types[$permission]++;
				}
				$v['data_none'] = $types[0];
				$v['data_read'] = $types[1];
				$v['data_write'] = $types[2];
				$v['data_admin'] = $types[3];
			}
		}
		
		// Format rows
		$data = $this->formatTableRows($data, $this->access);
		
		// Return success
		return array('result' => 'success', 'data' => $data, 'access' => $this->access);
	}
	
	// -------------------- Edit Access Group -------------------- \\
	public function editGroup($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$readonly = ($this->access == 1);
		$menu_items = array();
		$Database = new \Database();
		
		// Grab all access group data
		if($Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => 'SELECT * FROM fks_access_groups WHERE id = :id'
		))) {
			if($Database->r['found'] == 1 ) {
				// Editing
				$group = $Database->r['row'];
				$title = ($readonly ? 'View' : 'Edit') . ' Group: ' . $group['title'];
				$button = 'Update Group';
				$access = json_decode($group['data'], true);
			} else {
				// Creating
				$title = 'Add Group';
				$button = 'Add Group';
			}
		} else {	
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all menu items
		if($Database->Q('SELECT * FROM fks_menu_items ORDER BY menu_id, parent_id, pos')) {
			// Store rows
			$menu_items = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Loop through all menu items
		foreach($menu_items as $k => $v) {
			$menu_items[$k] = array(
				'access' => 0,
				'icon' => empty($v['icon']) ? null : $v['icon'],
				'id' => $v['id'],
				'parent' => $v['parent_id'],
				'title' => $v['title'] . ' : ' . $v['id']
			);
			if(isset($access[$v['id']])) {
				$menu_items[$k]['access'] = $access[$v['id']];
			}
		}
		
		// Create modal body
		$body = '<form id="editGroupForm" role="form" action="javascript:void(0);">
			<input type="hidden" name="id" value="' . (isset($group['id']) ? $group['id'] : '+') . '"/>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="title" class="form-control-label">Title</label>
						<input type="text" class="form-control form-control-sm" id="title" name="title" aria-describedby="title_help" value="' . (isset($group['title']) ? $group['title'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="title_help" class="form-text text-muted">The title of this group.</small>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="hierarchy" class="form-control-label">Hierarchy</label>
						<input type="number" class="form-control form-control-sm" id="hierarchy" name="hierarchy" aria-describedby="title_help" value="' . (isset($group['hierarchy']) ? $group['hierarchy'] : '0') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="title_help" class="form-text text-muted">The hierarchy of this group.</small>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="active" class="form-control-label">Status</label>
						<select class="form-control form-control-sm" id="active" name="active" aria-describedby="active_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . (isset($group['active']) && $group['active'] == 0 ? ' selected' : '') . '>Disabled</option>
							<option value="1"' . ((isset($group['active']) && $group['active'] == 1) || !isset($group['active']) ? ' selected' : '') . '>Active</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="active_help" class="form-text text-muted">The status of this group.</small>
					</div>
				</div>
			</div>
			<hr style="margin: 10px 0px;">
			<div class="row">
				<div class="col-sm-8">
					<div class="form-group">
						<label class="form-control-label">Menu Access</label>
						<span style="margin-left: 10px; font-size: 14px; line-height: 27px;">
							<a href="javascript:void(0);" fks-action="expandAll">expand all</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0);" fks-action="collapseAll">collapse all</a>
						</span>
					</div>
				</div>
				<div class="col-sm-4" align="right">
					<input type="text" id="access_group_tree_q" value="" class="form-control form-control-sm" placeholder="Search">
				</div>
			</div>
			<div id="access_group_tree"></div>
		</form>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => $title,
				'size' => 'lg',
				'body' => $body,
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . ($readonly ? 'Close' : 'Cancel') . '</button>'
					. ($readonly ? '' : '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#editGroupForm"><i class="fa fa-undo fa-fw"></i> Reset</button>')
					. ($readonly ? '' : '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editGroupForm"><i class="fa fa-save fa-fw"></i> ' . $button . '</button>')
				,
				'callbackData' => array(
					'onOpen' => array('access' => $menu_items)
				)
			)
		);
	}
	
	// -------------------- Save Access Group -------------------- \\
	public function saveGroup($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// If you are disabling or deleting a group check to see if it's used in site settings
		if($data['active'] == 0) {
			if(in_array($data['id'], explode(',', $this->siteSettings('DEFAULT_ACCESS_GUEST')))) { return array('result' => 'failure', 'message' => 'Can\'t disable this group because it\'s being used as a guest default.'); }
			if(in_array($data['id'], explode(',', $this->siteSettings('DEFAULT_ACCESS_LDAP')))) { return array('result' => 'failure', 'message' => 'Can\'t disable this group because it\'s being used as a LDAP default.'); }
			if(in_array($data['id'], explode(',', $this->siteSettings('DEFAULT_ACCESS_LOCAL')))) { return array('result' => 'failure', 'message' => 'Can\'t disable this group because it\'s being used as a local default.'); }
		}
		
		// Set Vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Pre-Validate
		$Validator->validate('id', array('required' => true));
		$Validator->validate('title', array('required' => true, 'max_length' => 45));
		$Validator->validate('hierarchy', array('required' => true, 'numeric' => true));
		$Validator->validate('data', array('required' => true));
		$Validator->validate('active', array('required' => true, 'bool' => true));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Check hierarchy
		if($form['hierarchy'] > $this->getHierarchy($_SESSION['id'])) {
			return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => array('hierarchy' => 'Value must not be higher than your own.'));
		}
		
		// Convert values to numeric
		foreach($form['data'] as $k => $v) {
			$form['data'][$k] = $v * 1;
		}
		
		// See if the group exists
		if($Database->Q(array(
			'params' => array(
				'id' => $form['id']
			),
			'query' => 'SELECT id, hierarchy FROM fks_access_groups WHERE id = :id'
		))) {
			if($Database->r['found'] == 1) {
			// Found group
				// Check hierarchy
				if($Database->r['row']['hierarchy'] > $this->getHierarchy($_SESSION['id'])) {
					return array('result' => 'failure', 'message' => 'Access Denied!');
				}
				
				// JSON encode form data for diff check
				$form['data'] = json_encode($form['data']);
			
				// Check Diffs
				$diff = $this->compareQueryArray($form['id'], 'fks_access_groups', $form, array('data'));
				
				if($diff) {
					// Update group
					if(!$Database->Q(array(
						'params' => array(
							':id' => $form['id'],
							':title' => $form['title'],
							':hierarchy' => $form['hierarchy'],
							':data' => $form['data'],
							':active' => $form['active'],
							':date_modified' => gmdate('Y-m-d H:i:s'),
							':modified_by' => $_SESSION['id']
						),
						'query' => '
							UPDATE
								fks_access_groups
							
							SET
								title = :title,
								hierarchy = :hierarchy,
								data = :data,
								active = :active,
								date_modified = :date_modified,
								modified_by = :modified_by
							
							WHERE
								id = :id
						'
					))) {
						$diff = false;
					}
				}
				
				// Save member log
				if($diff && !empty($diff)) {
					$MemberLog = new \MemberLog(\Enums\LogActions::ACCESS_GROUP_MODIFIED, $_SESSION['id'], $form['id'], json_encode($diff));
				} else {
					// Return No Changes
					return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.', 'diff' => $diff);
				}
				
				// Return success
				return array('result' => 'success', 'title' => 'Group Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			} else {
			// Create new group
				// Save new group to database
				if(!$Database->Q(array(
					'params' => array(
						':title' => $form['title'],
						':hierarchy' => $form['hierarchy'],
						':data' => json_encode($form['data']),
						':active' => $form['active'],
						':date_created' => gmdate('Y-m-d H:i:s'),
						':created_by' => $_SESSION['id']
					),
					'query' => '
						INSERT INTO
							fks_access_groups
							
						SET
							title = :title,
							hierarchy = :hierarchy,
							data = :data,
							active = :active,
							date_created = :date_created,
							created_by = :created_by
					'
				))) {
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
				}
				
				$last_id = $Database->r['last_id'];
				
				// Prepare member log
				unset($form['id']);
				
				// Save member log
				if($form && !empty($form)) {
					$MemberLog = new \MemberLog(\Enums\LogActions::ACCESS_GROUP_CREATED, $_SESSION['id'], $last_id, json_encode($form));
				}
				
				// Return success
				return array('result' => 'success', 'title' => 'Group Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
	}
	
	// -------------------- Load Access Group History -------------------- \\
	public function loadGroupHistory($data) {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_access_groups',
			'id' => $data,
			'title' => 'Group History: ',
			'select' => 'title',
			'actions' => array(
				\Enums\LogActions::ACCESS_GROUP_CREATED,
				\Enums\LogActions::ACCESS_GROUP_MODIFIED
			)
		));
		
		return $history;
	}
}
?>