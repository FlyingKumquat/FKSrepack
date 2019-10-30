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
		$this->access = $this->getAccess('admin_announcements');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Announcements -------------------- \\
	public function loadAnnouncementsTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$del = $this->access < 3 ? ' WHERE deleted = 0' : '';
		
		// Grab all announcements
		if($Database->Q(array(
			'query' => '
				SELECT
					a.*,
					(SELECT username FROM fks_members WHERE id = a.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = a.modified_by) AS modified_name
				
				FROM
					fks_announcements AS a					
			' . $del
		))) {
			// Format and store rows
			$data = $this->formatTableRows($Database->r['rows'], $this->access);
			
			// Additional formatting
			foreach($data as &$row) {
				$row['sticky'] = ($row['sticky'] == 0 ? '<span class="fks-text-danger">No</span>' : '<span class="fks-text-success">Yes</span>');
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab All Access Groups
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT id,title FROM fks_access_groups'
		))){
			// Store rows
			$access_groups = $Database->r['rows'];
		}else{
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Loop through announcements
		foreach($data as $k => &$v) {
			if($v['access_groups'] != '-') {
				$a = explode(',', $v['access_groups']);
				foreach($a as $ak => $av){
					$a[$ak] = $access_groups[$av]['title'];
				}
				$v['access_groups'] = implode(', ', $a);
			}
		}
		
		// Return success
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Edit Announcement -------------------- \\
	public function editAnnouncement($data) {
		// Check for read announcements
		if($this->access < 1){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$readonly = ($this->access == 1);
		$Database = new \Database();
		$content_pages_options = array();
		$menus = array();
		$menu_items = array();
		
		// Grab announcement data
		if($Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => 'SELECT * FROM fks_announcements WHERE id = :id'
		))){
			if($Database->r['found'] == 1 ) {
				// Editing
				$announcement = $Database->r['row'];
				$title = ($readonly ? 'View' : 'Edit') . ' Announcement: ' . $announcement['title'];
				$button = 'Update Announcement';
			} else {
				// Creating
				$title = 'Add Announcement';
				$button = 'Add Announcement';
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab All Access Groups
		if($Database->Q(array(
			'query' => 'SELECT id AS value, title FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY title ASC'
		))) {
			$access_groups = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all menus
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => ' SELECT * FROM fks_menus WHERE deleted = 0 ORDER BY title ASC'
		))) {
			// Store rows
			$menus = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all menu itesm
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menu_items WHERE deleted = 0'
		))) {
			// Store rows
			$menu_items = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create menu item url structures
		foreach($menu_items as $k => &$v) {
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $menu_items[$v['parent_id']]['url']);
				if($menu_items[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $menu_items[$menu_items[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$v['full_url'] = implode('/', $url);
		}

		$this->aasort($menu_items, 'full_url');
		
		// Loop through all menus
		foreach($menus as $menu) {
			// Loop though all menu items
			foreach($menu_items as $item) {
				// Skip if not active or is deleted or doesn't have content or doesn't belong in this menu
				if($item['active'] == 0 || $item['deleted'] == 1 || $item['has_content'] == 0 || $item['menu_id'] != $menu['id']) { continue; }

				array_push($content_pages_options, array(
					'title' => $item['full_url'],
					'value' => $item['id'],
					'group' => $menu['title']
				));
			}
		}
		
		// Configure form groups
		$form_groups = array(
			'id' => array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => (isset($announcement['id']) ? $announcement['id'] : '+')
			),
			'title' => array(
				'title' => 'Title',
				'type' => 'text',
				'name' => 'title',
				'value' => (isset($announcement['title']) ? $announcement['title'] : ''),
				'help' => 'The title of this announcement.',
				'required' => true
			),
			'sticky' => array(
				'title' => 'Sticky',
				'type' => 'select',
				'name' => 'sticky',
				'value' => (isset($announcement['sticky']) ? $announcement['sticky'] : 0),
				'help' => 'Show this announcement to new members.',
				'options' => array(
					array('title' => 'No', 'value' => 0),
					array('title' => 'Yes', 'value' => 1)
				)
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'select',
				'name' => 'active',
				'value' => (isset($announcement['active']) ? $announcement['active'] : 1),
				'help' => 'The status of this announcement.',
				'options' => array(
					array('title' => 'Disabled', 'value' => 0),
					array('title' => 'Active', 'value' => 1)
				)
			),
			'announcement' => array(
				'title' => 'Body',
				'type' => 'summernote',
				'name' => 'announcement',
				'value' => (isset($announcement['announcement']) ? $announcement['announcement'] : ''),
				'help' => 'The body of this announcement.',
				'required' => true
			),
			'pages' => array(
				'title' => 'Content Pages',
				'type' => 'select',
				'name' => 'pages',
				'value' => (isset($announcement['pages']) ? $announcement['pages'] : ''),
				'help' => 'Select what pages this announcement should show up on. Leave blank for all.',
				'options' => $content_pages_options,
				'properties' => array('multiple')
			),
			'access_groups' => array(
				'title' => 'Access Groups',
				'type' => 'select',
				'name' => 'access_groups',
				'value' => (isset($announcement['access_groups']) ? $announcement['access_groups'] : ''),
				'help' => 'Access Groups to restrict this announcement to. Leave blank for all.',
				'options' => $access_groups,
				'properties' => array('multiple')
			)
		);
		
		// Set inputs to disabled if readonly
		if($readonly) {
			foreach($form_groups as &$input) {
				if(!array_key_exists('properties', $input)) { $input['properties'] = array(); }
				array_push($input['properties'], 'disabled');
			}
		}
		
		// Create modal title
		$title = array(
			'<i class="fa fa-gears fa-fw"></i> Settings',
			'<i class="fa fa-list-ul fa-fw"></i> Page Access',
			'<i class="fa fa-lock fa-fw"></i> Access Groups'
		);
		
		// Create modal body
		$body = array();
		
		$body[0] = '
			' . $this->buildFormGroup($form_groups['id']) . '
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($form_groups['title']) . '</div>
				<div class="col-md-6"></div>
			</div>
			<div class="row">
				<div class="col-md-6">' . $this->buildFormGroup($form_groups['sticky']) . '</div>
				<div class="col-md-6">' . $this->buildFormGroup($form_groups['active']) . '</div>
			</div>
			<div class="row">
				<div class="col-md-12">' . $this->buildFormGroup($form_groups['announcement']) . '</div>
			</div>
		';
		
		$body[1] = '
			<div class="row">
				<div class="col-md-12">' . $this->buildFormGroup($form_groups['pages']) . '</div>
			</div>
		';
		
		$body[2] = '
			<div class="row">
				<div class="col-md-12">' . $this->buildFormGroup($form_groups['access_groups']) . '</div>
			</div>
		';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => $title,
				'size' => 'lg',
				'body_before' => '<form id="editAnnouncementForm" class="fks-form fks-form-sm" action="javascript:void(0);">',
				'body' => $body,
				'body_after' => '</form>',
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . ($readonly ? 'Close' : 'Cancel') . '</button>'
					. ($readonly ? '' : '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#editAnnouncementForm"><i class="fa fa-undo fa-fw"></i> Reset</button>')
					. ($readonly ? '' : '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editAnnouncementForm"><i class="fa fa-save fa-fw"></i> ' . $button . '</button>')
			)
		);
	}
	
	// -------------------- Save Announcement -------------------- \\
	public function saveAnnouncement($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Decode announcement
		$data['announcement'] = str_replace('&plus;', '+', urldecode($data['announcement']));
		
		// Set empty access_groups
		$data['access_groups'] = isset($data['access_groups']) ? $data['access_groups'] : '';
		
		// Set empty access_groups
		$data['pages'] = isset($data['pages']) ? $data['pages'] : '';
		
		// Set Vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Grab All Access Groups
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_access_groups'
		))) {
			// Store rows
			$access_groups = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab All Content Pages
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menu_items'
		))) {
			// Store rows
			$content_pages = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Pre-Validate
		$Validator->validate(array(
			'id' => array('required' => true),
			'title' => array('required' => true, 'not_empty' => true, 'max_length' => 45),
			'announcement' => array('required' => true, 'not_empty' => true, 'urldecode' => true),
			'sticky' => array('required' => true, 'bool' => true),
			'access_groups' => array('required' => false, 'values_csv' => array_keys($access_groups)),
			'pages' => array('required' => false, 'values_csv' => array_keys($content_pages)),
			'active' => array('required' => true, 'bool' => true)
		));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }	
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Create Data Handler
		$DataHandler = new \DataHandler(array(
			'fks_announcements' => array(
				'base' => 'fks_announcements',
				'log_actions' => array(
					'created' => \Enums\LogActions::ANNOUNCEMENT_CREATED,
					'modified' => \Enums\LogActions::ANNOUNCEMENT_MODIFIED
				)
			)
		));
		
		// Diff, Set, Log
		$DSL = $DataHandler->DSL(array(
			'type' => 'local',
			'table' => 'fks_announcements',
			'target_id' => $form['id'],
			'values' => array(
				'columns' => $form,
				'data' => false
			)
		));

		// Return
		if($DSL['result'] == 'success') {
			if($DSL['diff']['log_type'] == 'created') {
				return array('result' => 'success', 'title' => 'Announcement Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			} else {
				return array('result' => 'success', 'title' => 'Announcement Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			}
		} else {
			return $DSL;
		}
	}
	
	// -------------------- Load Announcement History -------------------- \\
	public function loadAnnouncementHistory($data) {	
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_announcements',
			'id' => $data,
			'title' => 'Announcement History: ',
			'select' => 'title',
			'actions' => array(
				\Enums\LogActions::ANNOUNCEMENT_CREATED,
				\Enums\LogActions::ANNOUNCEMENT_MODIFIED
			)
		));
		
		return $history;
	}
}
?>