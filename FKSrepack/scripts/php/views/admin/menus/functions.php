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
		$this->access = $this->getAccess('menus');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function test() {
		
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Menus -------------------- \\
	public function loadMenusTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$del = $this->access < 3 ? ' WHERE deleted = 0' : '';
		
		// Grab menus
		if(!$Database->Q(array(
			'query' => '
				SELECT
					m.*,
					(SELECT username FROM fks_members WHERE id = m.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = m.modified_by) AS modified_name
				
				FROM
					fks_menus AS m
			' . $del
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		$data = $this->formatTableRows($Database->r['rows'], $this->access);
		
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Edit Menu -------------------- \\
	public function editMenu($data) {
		// Check for read access
		if($this->access < 1){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$readonly = ($this->access == 1);
		$Database = new \Database();
		
		// Grab menu data
		if($Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => 'SELECT * FROM fks_menus WHERE id = :id'
		))){
			if($Database->r['found'] == 1 ) {
				$m = $Database->r['row'];
				$title = ($readonly ? 'View' : 'Edit') . ' Menu: ' . $m['title'];
				$button = 'Update Menu';
			} else {
				$title = 'Add Menu';
				$button = 'Add Menu';
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create modal body
		$body = '<form id="editMenuForm" role="form" action="javascript:void(0);">
			<input type="hidden" name="id" value="' . (isset($m['id']) ? $m['id'] : '+') . '"/>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="title" class="form-control-label">Title</label>
						<input type="text" class="form-control form-control-sm" id="title" name="title" aria-describedby="title_help" value="' . (isset($m['title']) ? $m['title'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="title_help" class="form-text text-muted">The title of this menu.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="active" class="form-control-label">Status</label>
						<select class="form-control form-control-sm" id="active" name="active" aria-describedby="active_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . (isset($m['active']) && $m['active'] == 0 ? ' selected' : '') . '>Disabled</option>
							<option value="1"' . ((isset($m['active']) && $m['active'] == 1) || !isset($m['active']) ? ' selected' : '') . '>Active</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="active_help" class="form-text text-muted">The status of this menu.</small>
					</div>
				</div>
			</div>
		</form>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => $title,
				'size' => 'md',
				'body' => $body,
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . ($readonly ? 'Close' : 'Cancel') . '</button>'
					. ($readonly ? '' : '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#editMenuForm"><i class="fa fa-undo fa-fw"></i> Reset</button>')
					. ($readonly ? '' : '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editMenuForm"><i class="fa fa-save fa-fw"></i> ' . $button . '</button>')
			)
		);
	}
	
	// -------------------- Save Menu -------------------- \\
	public function saveMenu($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set Vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Pre-Validate
		$Validator->validate('id', array('required' => true));
		$Validator->validate('title', array('required' => true, 'max_length' => 40));
		$Validator->validate('active', array('required' => true, 'bool' => true));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// See if the menu exists
		if($Database->Q(array(
			'params' => array(
				'id' => $form['id']
			),
			'query' => 'SELECT id FROM fks_menus WHERE id = :id'
		))) {
			if($Database->r['found'] == 1) {
			// Found menu
				// Check Diffs
				$diff = $this->compareQueryArray($form['id'], 'fks_menus', $form, false);
				
				if($diff) {
					// Update menu
					if(!$Database->Q(array(
						'params' => array(
							':id' => $form['id'],
							':title' => $form['title'],
							':active' => $form['active'],
							':date_modified' => gmdate('Y-m-d H:i:s'),
							':modified_by' => $_SESSION['id']
						),
						'query' => '
							UPDATE
								fks_menus
							
							SET
								title = :title,
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
					$MemberLog = new \MemberLog(\Enums\LogActions::MENU_MODIFIED, $_SESSION['id'], $form['id'], json_encode($diff));
				} else {
					// Return No Changes
					return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.', 'diff' => $diff);
				}
				
				return array('result' => 'success', 'title' => 'Menu Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			} else {
			// Create new menu
				// Save new menu to database
				if(!$Database->Q(array(
					'params' => array(
						':title' => $form['title'],
						':date_created' => gmdate('Y-m-d H:i:s'),
						':created_by' => $_SESSION['id'],
						':active' => $form['active']
					),
					'query' => '
						INSERT INTO
							fks_menus
							
						SET
							title = :title,
							date_created = :date_created,
							created_by = :created_by,
							active = :active
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
					$MemberLog = new \MemberLog(\Enums\LogActions::MENU_CREATED, $_SESSION['id'], $last_id, json_encode($form));
				}
				
				return array('result' => 'success', 'title' => 'Menu Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
	}
	
	// -------------------- Grab All Menu Items -------------------- \\
	public function loadMenuItemsTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$readonly = ($this->access == 1);
		$admin = ($this->access == 3);
		$Database = new \Database();
		$DataTypes = new \DataTypes();
		$del = $this->access < 3 ? ' WHERE mi.deleted = 0' : '';
		
		// Grab menu items
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => '
				SELECT
					mi.*,
					m.title AS menu_title,
					cb.username AS created_name,
					mb.username AS modified_name
				
				FROM
					fks_menu_items AS mi
					
				INNER JOIN
					fks_menus AS m
						ON
					mi.menu_id = m.id
					
				LEFT OUTER JOIN 
					fks_members AS cb
						ON
					mi.created_by = cb.id
					
				LEFT OUTER JOIN 
					fks_members AS mb
						ON
					mi.modified_by = mb.id
					
			' . $del
		))) {
			// Format rows
			$data = $this->formatTableRows($Database->r['rows'], $this->access, false);
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		foreach($data as $k => &$v) {
			// Set tools to array if not already
			if(!is_array($v['tools'])) { $v['tools'] = array(); }
			
			// Set parent title
			$v['parent_title'] = $v['parent_id'] == 0 ? '<i>none</i> : 0' : $data[$v['parent_id']]['title'] . ' : ' . $v['parent_id'];
			
			// Check for missing files
			if($v['has_content'] == 1 && $v['is_external'] == 0) {
				// Set vars
				$parent = '';
				$parent_id = $v['parent_id'];
				
				// Get parent URL
				get_parents:
				if($parent_id != 0) {
					if($Database->Q(array(
						'params' => array(':id' => $parent_id),
						'query' => 'SELECT parent_id,url FROM fks_menu_items WHERE id = :id'
					))) {
						$parent_data = $Database->r['row'];
						$parent = '/' . $parent_data['url'] . $parent;
						
						if($parent_data['parent_id'] != 0) {
							$parent_id = $parent_data['parent_id'];
							goto get_parents;
						}
					} else {
						return array('result' => 'failure', 'message' => 'Could not grab parents');
					}
				}
				
				// Check for files
				if(
					!is_file(parent::ROOT_DIR . '/views' . $parent . '/' . $v['url'] . '.php') || 
					!is_file(parent::ROOT_DIR . '/scripts/js/views' . $parent . '/' . $v['url'] . '.js') || 
					!is_file(parent::ROOT_DIR . '/scripts/php/views' . $parent . '/' . $v['url'] . '/functions.php')
				) {
					array_unshift($v['tools'], '<a class="create" href="javascript:void(0);" data-toggle="fks-tooltip" title="Create missing files"><i class="fa fa-file-text-o fa-fw"></i></a>');
				} else {
					array_unshift($v['tools'], '<i class="fa fa-check fa-fw" data-toggle="fks-tooltip" title="Page Files Exist"></i>');
				}
				
			} else {
				if($v['is_external'] == 1) {
					array_unshift($v['tools'], '<i class="fa fa-external-link fa-fw" data-toggle="fks-tooltip" title="External Link"></i>');
				} else {
					array_unshift($v['tools'], '<i class="fa fa-times fa-fw" data-toggle="fks-tooltip" title="No Content"></i>');
				}
			}
			
			$v['tools'] = '<span class="pull-right">' . implode('&nbsp;', $v['tools']) . '</span>';
		}
		
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Edit Menu Item -------------------- \\
	public function editMenuItem($data) {
		// Check for read access
		if($this->access < 1){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$menus = array();
		$current_icon = '';
		$readonly = ($this->access == 1);
		$current_parent = 0;
		
		if(!$Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => 'SELECT * FROM fks_menu_items WHERE id = :id'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		if($Database->r['found'] == 1 ) {
			$menu_item = $Database->r['row'];
			$current_parent = $menu_item['parent_id'];
			$title = ($readonly ? 'View' : 'Edit') . ' Item: ' . $menu_item['title'];
			$button = 'Update Item';
		} else {
			$title = 'Add Item';
			$button = 'Add Item';
		}
		
		// Grab all menus from the database
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menus WHERE deleted = 0'
		))) {
			$menus = $Database->r['rows'];
			$menus_options = '';
			
			foreach($menus as $k => $v){
				$selected = (isset($menu_item) && $k == $menu_item['menu_id']) ? ' selected' : '';
				$menus_options .= '<option value="' . $k . '"' . $selected . '>' . $v['title'] . '</option>';
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all menu items from the database that can have children
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT id,menu_id,parent_id,title FROM fks_menu_items WHERE is_parent = 1 AND deleted = 0 ORDER BY title'
		))) {
			$menu_items = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Loop through menu items and format parent names
		foreach($menu_items as $k => &$v) {
			// Set parents array
			$_parents = array();
			
			// Set _item to self
			$_item = $v;
			
			// While loop
			while(
				key_exists($_item['parent_id'], $menu_items)	// Parent exists
				&& $_item['parent_id'] != $k					// Parent is not self
			) {
				// Set _item to parent
				$_item = $menu_items[$_item['parent_id']];
				
				// Prepend _item title to _parents
				array_unshift($_parents, $_item['title']);
			}
			
			// Build parent_title
			$v['parent_title'] = (empty($_parents) ? '' : implode('/', $_parents) . '/') . $v['title'];
		}
		
		$current_icon = (isset($menu_item['icon']) ? $menu_item['icon'] : '');
		
		$body = '<form id="editMenuItemForm" role="form" action="javascript:void(0);">
			<input type="hidden" name="id" value="' . (isset($menu_item['id']) ? $menu_item['id'] : '+') . '"/>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="title" class="form-control-label">Title</label>
						<input type="text" class="form-control form-control-sm" id="title" name="title" aria-describedby="title_help" value="' . (isset($menu_item['title']) ? $menu_item['title'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="title_help" class="form-text text-muted">The title is displayed in the menu for this item.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="icon" class="form-control-label">Icon</label>
						<div class="input-group input-group-sm">
							<div class="input-group-addon" id="icon_preview"><i class="fa fa-' . $current_icon . ' fa-fw"></i></div>
							<select class="form-control form-control-sm select2" id="icon" name="icon" aria-describedby="icon_help"' . ($readonly ? ' disabled' : '') . '>
								<option value="">-</option>
							</select>
						</div>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="icon_help" class="form-text text-muted">The icon is displayed next to the  title in the menu.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="title_data" class="form-control-label">Title Data</label>
						<input type="text" class="form-control form-control-sm" id="title_data" name="title_data" aria-describedby="title_data_help" value="' . (isset($menu_item['title_data']) ? $menu_item['title_data'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="title_data_help" class="form-text text-muted">If you want to display DB data.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="has_separator" class="form-control-label">Separator</label>
						<select class="form-control form-control-sm" id="has_separator" name="has_separator" aria-describedby="has_separator_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . ((isset($menu_item['has_separator']) && $menu_item['has_separator'] == 0) || !isset($menu_item['has_separator']) ? ' selected' : '') . '>Disabled</option>
							<option value="1"' . (isset($menu_item['has_separator']) && $menu_item['has_separator'] == 1 ? ' selected' : '') . '>Before</option>
							<option value="2"' . (isset($menu_item['has_separator']) && $menu_item['has_separator'] == 2 ? ' selected' : '') . '>After</option>
							<option value="3"' . (isset($menu_item['has_separator']) && $menu_item['has_separator'] == 3 ? ' selected' : '') . '>Before & After</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="has_separator_help" class="form-text text-muted">For creating a separator around this item.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="url" class="form-control-label">URL</label>
						<input type="text" class="form-control form-control-sm" id="url" name="url" aria-describedby="url_help" value="' . (isset($menu_item['url']) ? $menu_item['url'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="url_help" class="form-text text-muted">What the URL will display as while on this page.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="label" class="form-control-label">Label</label>
						<input type="text" class="form-control form-control-sm" id="label" name="label" aria-describedby="label_help" value="' . (isset($menu_item['label']) ? $menu_item['label'] : '') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="label_help" class="form-text text-muted">Unique identifier used for page access.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="menu_id" class="form-control-label">Menu</label>
						<select class="form-control form-control-sm select2" id="menu_id" name="menu_id" aria-describedby="menu_id_help"' . ($readonly ? ' disabled' : '') . '>
							' . $menus_options . '
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="menu_id_help" class="form-text text-muted">The menu item\'s menu container.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="parent_id" class="form-control-label">Parent</label>
						<select class="form-control form-control-sm select2" id="parent_id" name="parent_id" aria-describedby="parent_id_help"' . ($readonly ? ' disabled' : '') . '>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="parent_id_help" class="form-text text-muted">The menu item\'s parent item.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="is_parent" class="form-control-label">Has Children</label>
						<select class="form-control form-control-sm" id="is_parent" name="is_parent" aria-describedby="is_parent_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . ((isset($menu_item['is_parent']) && $menu_item['is_parent'] == 0) || !isset($menu_item['is_parent']) ? ' selected' : '') . '>No</option>
							<option value="1"' . (isset($menu_item['is_parent']) && $menu_item['is_parent'] == 1 ? ' selected' : '') . '>Yes</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="is_parent_help" class="form-text text-muted">Menu item has children items.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="is_external" class="form-control-label">External Link</label>
						<select class="form-control form-control-sm" id="is_external" name="is_external" aria-describedby="is_external_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . ((isset($menu_item['is_external']) && $menu_item['is_external'] == 0) || !isset($menu_item['is_external']) ? ' selected' : '') . '>No</option>
							<option value="1"' . (isset($menu_item['is_external']) && $menu_item['is_external'] == 1 ? ' selected' : '') . '>Yes</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="is_external_help" class="form-text text-muted">URL links to an external site.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="has_content" class="form-control-label">Has Content</label>
						<select class="form-control form-control-sm" id="has_content" name="has_content" aria-describedby="has_content_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="1"' . (isset($menu_item['has_content']) && $menu_item['has_content'] == 1 ? ' selected' : '') . '>Yes</option>
							<option value="0"' . ((isset($menu_item['has_content']) && $menu_item['has_content'] == 0) ? ' selected' : '') . '>No</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="has_content_help" class="form-text text-muted">Whether or not the menu item should have content pages.</small>
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label for="pos" class="form-control-label">Position</label>
						<input type="number" class="form-control form-control-sm" id="pos" name="pos" aria-describedby="pos_help" value="' . (isset($menu_item['pos']) ? $menu_item['pos'] : '0') . '"' . ($readonly ? ' disabled' : '') . '>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="pos_help" class="form-text text-muted">The position of this menu item in its menu container.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="hidden" class="form-control-label">Display</label>
						<select class="form-control form-control-sm" id="hidden" name="hidden" aria-describedby="hidden_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . ((isset($menu_item['hidden']) && $menu_item['hidden'] == 0) || !isset($menu_item['hidden']) ? ' selected' : '') . '>Visible</option>
							<option value="1"' . (isset($menu_item['hidden']) && $menu_item['hidden'] == 1 ? ' selected' : '') . '>Hidden</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="hidden_help" class="form-text text-muted">The display state of this menu item.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="active" class="form-control-label">Status</label>
						<select class="form-control form-control-sm" id="active" name="active" aria-describedby="active_help"' . ($readonly ? ' disabled' : '') . '>
							<option value="0"' . (isset($menu_item['active']) && $menu_item['active'] == 0 ? ' selected' : '') . '>Disabled</option>
							<option value="1"' . ((isset($menu_item['active']) && $menu_item['active'] == 1) || !isset($menu_item['active']) ? ' selected' : '') . '>Active</option>
						</select>
						<div class="form-control-feedback" style="display: none;"></div>
						<small id="active_help" class="form-text text-muted">The status of this menu item.</small>
					</div>
				</div>
			</div>
		</form>';
		
		$pattern = '/\.(fa-(?:\w+(?:-)?)+):before{content:"(.+?)"}/';
		$subject = file_get_contents(parent::ROOT_DIR . '/scripts/css/font-awesome.min.css');

		preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER);

		$icons = array();

		foreach($matches as $match){
			$name = str_replace('fa-', '', $match[1]);
			array_push($icons, array('id' => $name, 'text' => $name));
		}
		
		usort($icons, function($a, $b){
			return strcmp($a['id'], $b['id']);
		});
		
		$parts = array(
			'title' => $title,
			'size' => 'lg',
			'body' => $body,
			'footer' => ''
				. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> ' . ($readonly ? 'Close' : 'Cancel') . '</button>'
				. ($readonly ? '' : '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#editMenuItemForm"><i class="fa fa-undo fa-fw"></i> Reset</button>')
				. ($readonly ? '' : '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editMenuItemForm"><i class="fa fa-save fa-fw"></i> ' . $button . '</button>'),
			'callbackData' => array(
				'onOpen' => array(
					'icons' => $icons, 
					'current_icon' => $current_icon,
					'parents' => $menu_items,
					'current_parent' => $current_parent
				)
			)
		);
		
		return array('result' => 'success', 'parts' => $parts);
	}
	
	// -------------------- Save Menu Item -------------------- \\
	public function saveMenuItem($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set Vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		$menus = array();
		$menu_items = array();
		
		// Grab all menus from database
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menus'
		))) {
			return array('result' => 'failure', 'message' => 'Failed to grab all menus from DB!');
		}
		$menus = $Database->r['rows'];
		
		// Grab all menu items from database
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menu_items'
		))) {
			return array('result' => 'failure', 'message' => 'Failed to grab all menu items from DB!');
		}
		$menu_items = $Database->r['rows'];
		if($data['id'] != '+') { unset($menu_items[$data['id']]); }
		
		// Pre-Validate
		$Validator->validate('id', array('required' => true));
		$Validator->validate('menu_id', array('required' => true, 'numeric' => true, 'values' => array_keys($menus)));
		$Validator->validate('parent_id', array('required' => true, 'numeric' => true, 'values' => array_keys($menu_items)));
		$Validator->validate('pos', array('numeric' => true));
		$Validator->validate('title', array('required' => true, 'max_length' => 40));
		$Validator->validate('title_data', array('max_length' => 45));
		$Validator->validate('has_separator', array('required' => true, 'values' => array(0,1,2,3)));
		$Validator->validate('is_external', array('bool' => true));
		$Validator->validate('is_parent', array('bool' => true));
		$Validator->validate('has_content', array('bool' => true));
		$Validator->validate('url', array('max_length' => 255, 'required' => true));
		$Validator->validate('icon', array('max_length' => 20));
		$Validator->validate('label', array('required' => true, 'max_length' => 40));
		$Validator->validate('active', array('bool' => true));
		$Validator->validate('hidden', array('bool' => true));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// See if the menu item exists
		if($Database->Q(array(
			'params' => array(
				'id' => $form['id']
			),
			'query' => 'SELECT id FROM fks_menu_items WHERE id = :id'
		))) {
			if($Database->r['found'] == 1) {
			// Found menu item
				// Check Diffs
				$diff = $this->compareQueryArray($form['id'], 'fks_menu_items', $form, false);
				
				if($diff) {
					// Update menu item
					if(!$Database->Q(array(
						'params' => array(
							':id' => $form['id'],
							':menu_id' => $form['menu_id'],
							':parent_id' => $form['parent_id'],
							':pos' => $form['pos'],
							':title' => $form['title'],
							':title_data' => $form['title_data'],
							':has_separator' => $form['has_separator'],
							':is_external' => $form['is_external'],
							':is_parent' => $form['is_parent'],
							':has_content' => $form['has_content'],
							':url' => $form['url'],
							':icon' => $form['icon'],
							':label' => $form['label'],
							':active' => $form['active'],
							':hidden' => $form['hidden'],
							':date_modified' => gmdate('Y-m-d H:i:s'),
							':modified_by' => $_SESSION['id']
						),
						'query' => '
							UPDATE
								fks_menu_items
							
							SET
								menu_id = :menu_id,
								parent_id = :parent_id,
								pos = :pos,
								title = :title,
								title_data = :title_data,
								has_separator = :has_separator,
								is_external = :is_external,
								is_parent = :is_parent,
								has_content = :has_content,
								url = :url,
								icon = :icon,
								label = :label,
								active = :active,
								hidden = :hidden,
								date_modified = :date_modified,
								modified_by = :modified_by
							
							WHERE
								id = :id
						'
					))) {
						// Return error message with error code
						return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
					}
				}
				
				// Save member log
				if($diff && !empty($diff)) {
					$MemberLog = new \MemberLog(\Enums\LogActions::MENU_ITEM_MODIFIED, $_SESSION['id'], $form['id'], json_encode($diff));
				} else {
					// Return No Changes
					return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.', 'diff' => $diff, 'form' => $form);
				}
				
				return array('result' => 'success', 'title' => 'Menu Item Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			} else {
			// Create new menu item
				// Save new menu item to database
				if(!$Database->Q(array(
					'params' => array(
						':menu_id' => $form['menu_id'],
						':parent_id' => $form['parent_id'],
						':pos' => $form['pos'],
						':title' => $form['title'],
						':title_data' => $form['title_data'],
						':has_separator' => $form['has_separator'],
						':is_external' => $form['is_external'],
						':is_parent' => $form['is_parent'],
						':has_content' => $form['has_content'],
						':url' => $form['url'],
						':icon' => $form['icon'],
						':label' => $form['label'],
						':active' => $form['active'],
						':hidden' => $form['hidden'],
						':date_created' => gmdate('Y-m-d H:i:s'),
						':created_by' => $_SESSION['id']
					),
					'query' => '
						INSERT INTO
							fks_menu_items
							
						SET
							menu_id = :menu_id,
							parent_id = :parent_id,
							pos = :pos,
							title = :title,
							title_data = :title_data,
							has_separator = :has_separator,
							is_external = :is_external,
							is_parent = :is_parent,
							has_content = :has_content,
							url = :url,
							icon = :icon,
							label = :label,
							active = :active,
							hidden = :hidden,
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
					$MemberLog = new \MemberLog(\Enums\LogActions::MENU_ITEM_CREATED, $_SESSION['id'], $last_id, json_encode($form));
				}
				
				return array('result' => 'success', 'title' => 'Menu Item Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
	}
	
	// -------------------- Create Menu Item Pages -------------------- \\
	public function createMenuItemPages($menu_id) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$parent = '';
		$parent_id = 0;
		$breadcrumb = '';
		$window_title = '';
		$paths = array();
		
		// Grab menu data
		if($Database->Q(array(
			'params' => array(
				':id' => $menu_id
			),
			'query' => 'SELECT * FROM fks_menu_items WHERE id = :id'
		))) {
			if($Database->r['found'] != 1) {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			$menu_data = $Database->r['row'];
			$parent_id = $menu_data['parent_id'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Return if 
		if($menu_data['has_content'] == 0 || $menu_data['is_external'] == 1) {
			return array('result' => 'info', 'message' => $menu_data['title'] . ' doesn\'t need pages created for it!');
		}
		
		// Get parent URL
		get_parents:
		if($parent_id != 0) {
			if($Database->Q(array(
				'params' => array(':id' => $parent_id),
				'query' => 'SELECT parent_id,title,url FROM fks_menu_items WHERE id = :id'
			))) {
				$parent_data = $Database->r['row'];
				$parent = '/' . $parent_data['url'] . $parent;
				$breadcrumb = $parent_data['title'] . PHP_EOL . "\t\t\t" . '<i class="fa fa-angle-right fa-fw"></i>' . PHP_EOL . "\t\t\t" . $breadcrumb;
				$window_title = $parent_data['title'] . '/' . $window_title;
				
				if($parent_data['parent_id'] != 0) {
					$parent_id = $parent_data['parent_id'];
					goto get_parents;
				}
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
		}
		
		// Create replacement array
		$replace_array = array(
			'%TITLE%' => $menu_data['title'],
			'%LABEL%' => $menu_data['label'],
			'%BREADCRUMB%' => $breadcrumb . $menu_data['title'],
			'%FULL_URL%' => ($parent_id != 0 ? substr_replace($parent, '', 0, 1) . '/' : '') . $menu_data['url'],
			'%WINDOW_TITLE%' => ' : ' . implode(' : ', array_filter(explode('/', $window_title))) . (!empty($window_title) ? ' : ' : '') . $menu_data['title']
		);
		
		// Check for view file
		if(!is_file(parent::ROOT_DIR . '/views' . $parent . '/' . $menu_data['url'] . '.php')) {
			if(!is_dir(parent::ROOT_DIR . '/views' . $parent)) {mkdir(parent::ROOT_DIR . '/views' . $parent, 0777, true);}
			$file = file_get_contents(parent::ROOT_DIR . '/scripts/js/plugins/fks/templates/view.php');
			foreach($replace_array as $k => $v) {
				$file = str_replace($k, $v, $file);
			}
			$path = '/views' . $parent . '/' . $menu_data['url'] . '.php';
			file_put_contents(parent::ROOT_DIR . $path, $file);
			array_push($paths, $path);
		}

		// Check for script file
		if(!is_file(parent::ROOT_DIR . '/scripts/js/views' . $parent . '/' . $menu_data['url'] . '.js')) {
			if(!is_dir(parent::ROOT_DIR . '/scripts/js/views' . $parent)) {mkdir(parent::ROOT_DIR . '/scripts/js/views' . $parent, 0777, true);}
			$file = file_get_contents(parent::ROOT_DIR . '/scripts/js/plugins/fks/templates/script.js');
			foreach($replace_array as $k => $v) {
				$file = str_replace($k, $v, $file);
			}
			$path = '/scripts/js/views' . $parent . '/' . $menu_data['url'] . '.js';
			file_put_contents(parent::ROOT_DIR . $path, $file);
			array_push($paths, $path);
		} 
		
		// Check for functions file
		if(!is_file(parent::ROOT_DIR . '/scripts/php/views' . $parent . '/' . $menu_data['url'] . '/functions.php')) {
			if(!is_dir(parent::ROOT_DIR . '/scripts/php/views' . $parent . '/' . $menu_data['url'])) {mkdir(parent::ROOT_DIR . '/scripts/php/views' . $parent . '/' . $menu_data['url'], 0777, true);}
			$file = file_get_contents(parent::ROOT_DIR . '/scripts/js/plugins/fks/templates/functions.php');
			foreach($replace_array as $k => $v) {
				$file = str_replace($k, $v, $file);
			}
			$path = '/scripts/php/views' . $parent . '/' . $menu_data['url'] . '/functions.php';
			file_put_contents(parent::ROOT_DIR . $path, $file);
			array_push($paths, $path);
		}
		
		if(count($paths) > 0) {
			$MemberLog = new \MemberLog(\Enums\LogActions::MENU_ITEM_PAGES_CREATED, $_SESSION['id'], $menu_id, json_encode($paths));
		}
		
		return array('result' => 'success', 'message' => 'Created ' . count($paths) . ' page' . (count($paths) == 1 ? '' : 's') . ' for ' . $menu_data['title']);
	}
	
	// -------------------- Load Menu History -------------------- \\
	public function loadMenuHistory($data) {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_menus',
			'id' => $data,
			'title' => 'Menu History: ',
			'select' => 'title',
			'actions' => array(
				\Enums\LogActions::MENU_CREATED,
				\Enums\LogActions::MENU_MODIFIED
			)
		));
		
		return $history;
	}
	
	// -------------------- Load Menu Item History -------------------- \\
	public function loadMenuItemHistory($data) {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_menu_items',
			'id' => $data,
			'title' => 'Menu Item History: ',
			'select' => 'title',
			'actions' => array(
				\Enums\LogActions::MENU_ITEM_CREATED,
				\Enums\LogActions::MENU_ITEM_MODIFIED,
				\Enums\LogActions::MENU_ITEM_PAGES_CREATED
			)
		));
		
		return $history;
	}
}
?>