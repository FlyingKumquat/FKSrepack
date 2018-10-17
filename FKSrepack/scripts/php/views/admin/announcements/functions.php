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
		$access_group_options = '';
		$content_pages_options = '';
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
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY title ASC'
		))) {
			$access_groups = $Database->r['rows'];
			
			foreach($access_groups as $k => $v) {
				if(isset($announcement) && in_array($k, explode(',', $announcement['access_groups']))) {
					$selected = ' selected';
				} else {
					$selected = '';
				}
				$access_group_options .= '<option value="' . $v['id'] . '"' . $selected . ($readonly ? ' disabled' : '') . '>' . $v['title'] . '</option>';
			}
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
			$menu_content = '';
			foreach($menu_items as $item) {
				// Skip if not active or is deleted or doesn't have content or doesn't belong in this menu
				if($item['active'] == 0 || $item['deleted'] == 1 || $item['has_content'] == 0 || $item['menu_id'] != $menu['id']) { continue; }
				
				if(isset($announcement) && in_array($item['id'], explode(',', $announcement['pages']))) {
					$selected = ' selected';
				} else {
					$selected = '';
				}
				
				$menu_content .= '<option value="' . $item['id'] . '"' . $selected . ($readonly ? ' disabled' : '') . '>' . $item['full_url'] . '</option>';
			}
			if(!empty($menu_content)) {
				$content_pages_options .= '<optgroup label="' . $menu['title'] . '">' . $menu_content . '</optgroup>';
			}
		}
		
		// Create modal title
		$title = '<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" data-toggle="tab" href="#modal_tab_1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> Settings</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#modal_tab_2" role="tab" draggable="false"><i class="fa fa-list-ul fa-fw"></i> Page Access</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#modal_tab_3" role="tab" draggable="false"><i class="fa fa-lock fa-fw"></i> Access Groups</a>
			</li>
		</ul>';
		
		// Create modal body
		$body = '<form id="editAnnouncementForm" role="form" action="javascript:void(0);"><div class="tab-content">
			<input type="hidden" name="id" value="' . (isset($announcement['id']) ? $announcement['id'] : '+') . '"/>
			<div class="tab-pane active" id="modal_tab_1" role="tabpanel">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="title" class="form-control-label">Title</label>
							<input type="text" class="form-control form-control-sm" id="title" name="title" aria-describedby="title_help" value="' . (isset($announcement['title']) ? $announcement['title'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="title_help" class="form-text text-muted">The title of this announcement.</small>
						</div>
					</div>
					<div class="col-md-6">
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="sticky" class="form-control-label">Sticky</label>
							<select class="form-control form-control-sm" id="sticky" name="sticky" aria-describedby="sticky_help"' . ($readonly ? ' disabled' : '') . '>
								<option value="0"' . ((isset($announcement['sticky']) && $announcement['sticky'] == 0) || !isset($announcement['sticky']) ? ' selected' : '') . '>No</option>
								<option value="1"' . (isset($announcement['sticky']) && $announcement['sticky'] == 1 ? ' selected' : '') . '>Yes</option>
							</select>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="sticky_help" class="form-text text-muted">Show this announcement to new members.</small>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="active" class="form-control-label">Status</label>
							<select class="form-control form-control-sm" id="active" name="active" aria-describedby="active_help"' . ($readonly ? ' disabled' : '') . '>
								<option value="0"' . (isset($announcement['active']) && $announcement['active'] == 0 ? ' selected' : '') . '>Disabled</option>
								<option value="1"' . ((isset($announcement['active']) && $announcement['active'] == 1) || !isset($announcement['active']) ? ' selected' : '') . '>Active</option>
							</select>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="active_help" class="form-text text-muted">The status of this announcement.</small>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="announcement" class="form-control-label">Body</label>
							<div id="announcement">' . (isset($announcement['announcement']) ? $announcement['announcement'] : '') . '</div>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="announcement_help" class="form-text text-muted">The body of this announcement.</small>
						</div>
					</div>
				</div>
			</div>
			
			<div class="tab-pane" id="modal_tab_2" role="tabpanel">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="pages" class="form-control-label">Content Pages</label>
							<select class="form-control form-control-sm" id="pages" name="pages" multiple="multiple" aria-describedby="pages_help"' . ($readonly ? ' disabled' : '') . '>
								' . $content_pages_options . '
							</select>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="pages_help" class="form-text text-muted">Select what pages this announcement should show up on. Leave blank for all.</small>
						</div>
					</div>
				</div>
			</div>
			
			<div class="tab-pane" id="modal_tab_3" role="tabpanel">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="access_groups" class="form-control-label">Access Groups</label>
							<select class="form-control form-control-sm" id="access_groups" name="access_groups" multiple="multiple" aria-describedby="access_groups_help"' . ($readonly ? ' disabled' : '') . '>
								' . $access_group_options . '
							</select>
							<div class="form-control-feedback" style="display: none;"></div>
							<small id="access_groups_help" class="form-text text-muted">Access Groups to restrict this announcement to. Leave blank for all.</small>
						</div>
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
		$Validator->validate('id', array('required' => true));
		$Validator->validate('title', array('required' => true, 'max_length' => 45));
		$Validator->validate('announcement', array('required' => true));
		$Validator->validate('sticky', array('required' => true, 'bool' => true));
		$Validator->validate('access_groups', array('required' => false, 'values_csv' => array_keys($access_groups)));
		$Validator->validate('pages', array('required' => false, 'values_csv' => array_keys($content_pages)));
		$Validator->validate('active', array('required' => true, 'bool' => true));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }	
		
		// Get updated form
		$form = $Validator->getForm();
		
		// See if the announcement exists
		if($Database->Q(array(
			'params' => array(
				'id' => $form['id']
			),
			'query' => 'SELECT id FROM fks_announcements WHERE id = :id'
		))) {
			if($Database->r['found'] == 1) {
			// Found announcement
				// Check Diffs
				$diff = $this->compareQueryArray($form['id'], 'fks_announcements', $form, false);
				
				if($diff) {
					// Update announcement
					if(!$Database->Q(array(
						'params' => array(
							':id' => $form['id'],
							':title' => $form['title'],
							':sticky' => $form['sticky'],
							':announcement' => $form['announcement'],
							':access_groups' => $form['access_groups'],
							':pages' => $form['pages'],
							':active' => $form['active'],
							':date_modified' => gmdate('Y-m-d H:i:s'),
							':modified_by' => $_SESSION['id']
						),
						'query' => '
							UPDATE
								fks_announcements
							
							SET
								title = :title,
								sticky = :sticky,
								announcement = :announcement,
								access_groups = :access_groups,
								pages = :pages,
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
					$MemberLog = new \MemberLog(\Enums\LogActions::ANNOUNCEMENT_MODIFIED, $_SESSION['id'], $form['id'], json_encode($diff));
				} else {
					// Return No Changes
					return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.', 'diff' => $diff);
				}
				
				return array('result' => 'success', 'title' => 'Announcement Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			} else {
			// Create new announcement
				// Save new announcement to database
				if(!$Database->Q(array(
					'params' => array(
						':title' => $form['title'],
						':sticky' => $form['sticky'],
						':announcement' => $form['announcement'],
						':access_groups' => $form['access_groups'],
						':pages' => $form['pages'],
						':date_created' => gmdate('Y-m-d H:i:s'),
						':created_by' => $_SESSION['id'],
						':active' => $form['active']
					),
					'query' => '
						INSERT INTO
							fks_announcements
							
						SET
							title = :title,
							sticky = :sticky,
							announcement = :announcement,
							access_groups = :access_groups,
							pages = :pages,
							date_created = :date_created,
							created_by = :created_by,
							active = :active
					'
				))) {
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
				}
				
				// Prepare member log
				$last_id = $Database->r['last_id'];
				unset($form['id']);
				
				// Save member log
				if($form && !empty($form)) {
					$MemberLog = new \MemberLog(\Enums\LogActions::ANNOUNCEMENT_CREATED, $_SESSION['id'], $last_id, json_encode($form));
				}
				
				// Return success
				return array('result' => 'success', 'title' => 'Announcement Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
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