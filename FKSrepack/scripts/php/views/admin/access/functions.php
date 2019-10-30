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
		
		// Get all pages
		$pages = $this->getMenuItemStructures();
		
		// Loop through all access groups
		foreach($data as $k => &$v) {
			// Decode data
			$_data = json_decode($v['data'], true);
			$_types = array(0, 0, 0, 0);

			if(!empty($_data)) {
				// Loop through all pages
				foreach($pages as $pk => $pv) {
					// See if page is in data
					if(key_exists($pk, $_data)) {
						// Increase type count
						$_types[$_data[$pk]]++;
					} else {
						// Increase none count
						$_types[0]++;
					}
				}
			} else {
				// Set all to none
				$_types[0] = count($pages);
			}
			
			// Set all types
			$v['data_none'] = $_types[0];
			$v['data_read'] = $_types[1];
			$v['data_write'] = $_types[2];
			$v['data_admin'] = $_types[3];
		}
		
		// Format rows
		$data = $this->formatTableRows($data, $this->access);
		
		// Return success
		return array('result' => 'success', 'data' => $data, 'pages' => $pages);
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
				$button = 'Update Group';
				$access = json_decode($group['data'], true);
			} else {
				// Creating
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
		
		// Get pages
		$pages = $this->getMenuItemStructures(false, true);
		
		// Set home page options
		$home_page_options = array(
			array(
				'title' => 'Use Default (' . (empty($this->siteSettings('SITE_HOME_PAGE')) ? 'home' : $pages[$this->siteSettings('SITE_HOME_PAGE')]) . ')',
				'value' => ''
			)
		);
		
		// Home page options
		foreach($pages as $k => $v) {
			array_push($home_page_options, array('title' => $v, 'value' => $k));
		}
		
		// Configure form groups
		$form_groups = array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => (isset($group['id']) ? $group['id'] : '+')
			),
			array(
				'title' => 'Title',
				'type' => 'text',
				'name' => 'title',
				'value' => (isset($group['title']) ? $group['title'] : ''),
				'help' => 'The title of this group.',
				'required' => true
			),
			array(
				'title' => 'Hierarchy',
				'type' => 'number',
				'name' => 'hierarchy',
				'value' => (isset($group['hierarchy']) ? $group['hierarchy'] : 0),
				'help' => 'The hierarchy of this group.',
				'required' => true
			),
			array(
				'title' => 'Home Page',
				'type' => 'select',
				'name' => 'home_page',
				'value' => (isset($group['home_page']) ? $group['home_page'] : ''),
				'help' => 'The home page for this group.',
				'options' => $home_page_options,
				'attributes' => array(
					'class' => 'fks-select2'
				)
			),
			array(
				'title' => 'Status',
				'type' => 'select',
				'name' => 'active',
				'value' => (isset($group['active']) ? $group['active'] : 1),
				'help' => 'The status of this group.',
				'options' => array(
					array('title' => 'Disabled', 'value' => 0),
					array('title' => 'Active', 'value' => 1)
				)
			)
		);
		
		// Set inputs to disabled if readonly
		if($readonly) {
			foreach($form_groups as &$input) {
				if(!array_key_exists('properties', $input)) { $input['properties'] = array(); }
				array_push($input['properties'], 'disabled');
			}
		}
		
		// Set title tabs
		$title = array(
			'<i class="fa fa-cogs fa-fw"></i> Settings',
			'<i class="fa fa-lock fa-fw"></i> Page Access',
		);
		
		// Set body content
		$body = array(
			$this->buildFormGroups($form_groups),
			'
			<div class="row">
				<div class="col-sm-7">
					<input type="text" id="access_group_tree_q" value="" class="form-control" placeholder="Search">
				</div>
				<div class="col-sm-5" align="right">
					<div class="form-group">
						<span style="font-size: 14px; line-height: 27px;">
							<a href="javascript:void(0);" fks-action="expandAll">expand all</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0);" fks-action="collapseAll">collapse all</a>
						</span>
					</div>
				</div>
			</div>
			<div id="access_group_tree"></div>
			'
		);
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => $title,
				'size' => 'md',
				'body_before' => '<form id="editGroupForm" class="fks-form fks-form-sm" action="javascript:void(0);">',
				'body' => $body,
				'body_after' => '</form>',
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
		$Validator->validate(array(
			'id' => array('required' => true),
			'title' => array('required' => true, 'not_empty' => true, 'max_length' => 45),
			'hierarchy' => array('required' => true, 'not_empty' => true, 'numeric' => true, 'min_value' => 0),
			'home_page' => array('required' => false, 'values' => array_keys($this->getMenuItemStructures(false, true))),
			'data' => array('required' => true),
			'active' => array('required' => true, 'bool' => true)
		));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Check hierarchy
		if($form['hierarchy'] > $this->getHierarchy($_SESSION['id'])) {
			return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => array('hierarchy' => array('Value must not be higher than your own.')));
		}
		
		// Convert values to numeric
		foreach($form['data'] as $k => &$v) {
			$v = $v * 1;
			// Remove empty/unknown values
			if($v < 1 || $v > 3) { unset($form['data'][$k]); }
		}
		
		// JSON encode form data for diff check
		$form['data'] = json_encode($form['data']);
		
		// Create Data Handler
		$DataHandler = new \DataHandler(array(
			'fks_access_groups' => array(
				'base' => 'fks_access_groups',
				'log_actions' => array(
					'created' => \Enums\LogActions::ACCESS_GROUP_CREATED,
					'modified' => \Enums\LogActions::ACCESS_GROUP_MODIFIED
				)
			)
		));
		
		// Diff, Set, Log
		$DSL = $DataHandler->DSL(array(
			'type' => 'local',
			'table' => 'fks_access_groups',
			'target_id' => $form['id'],
			'values' => array(
				'columns' => $form,
				'data' => false
			),
			'json' => array(
				'columns' => array(
					'data'
				)
			)
		));

		// Return
		if($DSL['result'] == 'success') {
			if($DSL['diff']['log_type'] == 'created') {
				return array('result' => 'success', 'title' => 'Group Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			} else {
				return array('result' => 'success', 'title' => 'Group Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			}
		} else {
			return $DSL;
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