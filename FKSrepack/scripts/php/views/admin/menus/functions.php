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
		
		// Configure form groups
		$form_groups = array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => (isset($m['id']) ? $m['id'] : '+')
			),
			array(
				'title' => 'Title',
				'type' => 'text',
				'name' => 'title',
				'value' => (isset($m['title']) ? $m['title'] : ''),
				'help' => 'The title of this menu.',
				'required' => true
			),
			array(
				'title' => 'Status',
				'type' => 'select',
				'name' => 'active',
				'value' => (isset($m['active']) ? $m['active'] : 1),
				'help' => 'The status of this menu.',
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
		
		// Create the body
		$body = '<form id="editMenuForm" class="fks-form fks-form-sm" role="form" action="javascript:void(0);">
			' . $this->buildFormGroups($form_groups, array('width' => 6)) . '
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
		$Validator->validate(array(
			'id' => array('required' => true),
			'title' => array('required' => true, 'not_empty' => true, 'max_length' => 40),
			'active' => array('required' => true, 'bool' => true)
		));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Create Data Handler
		$DataHandler = new \DataHandler(array(
			'fks_menus' => array(
				'base' => 'fks_menus',
				'log_actions' => array(
					'created' => \Enums\LogActions::MENU_CREATED,
					'modified' => \Enums\LogActions::MENU_MODIFIED
				)
			)
		));
		
		// Diff, Set, Log
		$DSL = $DataHandler->DSL(array(
			'type' => 'local',
			'table' => 'fks_menus',
			'target_id' => $form['id'],
			'values' => array(
				'columns' => $form,
				'data' => false
			)
		));

		// Return
		if($DSL['result'] == 'success') {
			if($DSL['diff']['log_type'] == 'created') {
				return array('result' => 'success', 'title' => 'Menu Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			} else {
				return array('result' => 'success', 'title' => 'Menu Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			}
		} else {
			return $DSL;
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
			$menus_options = array();
			
			foreach($menus as $k => $v){
				array_push($menus_options, array('title' => $v['title'], 'value' => $k));
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
		
		// Configure form groups
		$form_groups = array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => (isset($menu_item['id']) ? $menu_item['id'] : '+')
			),
			array(
				'title' => 'Title',
				'type' => 'text',
				'name' => 'title',
				'value' => (isset($menu_item['title']) ? $menu_item['title'] : ''),
				'help' => 'The title is displayed in the menu for this item.',
				'required' => true
			),
			array(
				'title' => 'Icon',
				'type' => 'select',
				'name' => 'icon',
				'value' => (isset($menu_item['title']) ? $menu_item['title'] : ''),
				'help' => 'The icon is displayed next to the title in the menu.',
				'attributes' => array('class' => 'select2'),
				'group' => array('before' => '<div class="input-group-addon" id="icon_preview"><i class="fa fa-' . $current_icon . ' fa-fw"></i></div>'),
				'options' => array(array('title' => '-', 'value' => ''))
			),
			array(
				'title' => 'Title Data',
				'type' => 'text',
				'name' => 'title_data',
				'value' => (isset($menu_item['title_data']) ? $menu_item['title_data'] : ''),
				'help' => 'If you want to display DB data.'
			),
			array(
				'title' => 'Separator',
				'type' => 'select',
				'name' => 'has_separator',
				'value' => (isset($menu_item['has_separator']) ? $menu_item['has_separator'] : 0),
				'help' => 'For creating a separator around this item.',
				'options' => array(
					array('title' => 'Disabled', 'value' => 0),
					array('title' => 'Before', 'value' => 1),
					array('title' => 'After', 'value' => 2),
					array('title' => 'Before & After', 'value' => 3)
				)
			),
			array(
				'title' => 'URL',
				'type' => 'text',
				'name' => 'url',
				'value' => (isset($menu_item['url']) ? $menu_item['url'] : ''),
				'help' => 'What the URL will display as while on this page.',
				'group' => array('after' => '<div class="input-group-append"><button type="button" class="btn btn-sm fks-btn-info gen-url"><i class="fa fa-hashtag fa-fw"></i></button></div>')
			),
			array(
				'title' => 'Label',
				'type' => 'text',
				'name' => 'label',
				'value' => (isset($menu_item['label']) ? $menu_item['label'] : ''),
				'help' => 'Unique identifier used for page access.',
				'group' => array('after' => '<div class="input-group-append"><button type="button" class="btn btn-sm fks-btn-info gen-label"><i class="fa fa-hashtag fa-fw"></i></button></div>'),
				'required' => true
			),
			array(
				'title' => 'Menu',
				'type' => 'select',
				'name' => 'menu_id',
				'value' => (isset($menu_item['menu_id']) ? $menu_item['menu_id'] : 0),
				'help' => 'The menu item\'s menu container.',
				'attributes' => array('class' => 'select2'),
				'options' => $menus_options
			),
			array(
				'title' => 'Parent',
				'type' => 'select',
				'name' => 'parent_id',
				'help' => 'The menu item\'s parent item.',
				'attributes' => array('class' => 'select2')
			),
			array(
				'title' => 'Has Children',
				'type' => 'select',
				'name' => 'is_parent',
				'value' => (isset($menu_item['is_parent']) ? $menu_item['is_parent'] : 0),
				'help' => 'Menu item has children items.',
				'options' => array(
					array('title' => 'No', 'value' => 0),
					array('title' => 'Yes', 'value' => 1)
				)
			),
			array(
				'title' => 'External Link',
				'type' => 'select',
				'name' => 'is_external',
				'value' => (isset($menu_item['is_external']) ? $menu_item['is_external'] : 0),
				'help' => 'URL links to an external site.',
				'options' => array(
					array('title' => 'No', 'value' => 0),
					array('title' => 'Yes', 'value' => 1)
				)
			),
			array(
				'title' => 'Has Content',
				'type' => 'select',
				'name' => 'has_content',
				'value' => (isset($menu_item['has_content']) ? $menu_item['has_content'] : 1),
				'help' => 'Whether or not the menu item should have content pages.',
				'options' => array(
					array('title' => 'No', 'value' => 0),
					array('title' => 'Yes', 'value' => 1)
				)
			),
			array(
				'title' => 'Position',
				'type' => 'text',
				'name' => 'pos',
				'value' => (isset($menu_item['pos']) ? $menu_item['pos'] : 0),
				'help' => 'The position of this menu item in its menu container.'
			),
			array(
				'title' => 'Display',
				'type' => 'select',
				'name' => 'hidden',
				'value' => (isset($menu_item['hidden']) ? $menu_item['hidden'] : 0),
				'help' => 'The display state of this menu item.',
				'options' => array(
					array('title' => 'Visible', 'value' => 0),
					array('title' => 'Hidden', 'value' => 1)
				)
			),
			array(
				'title' => 'Status',
				'type' => 'select',
				'name' => 'active',
				'value' => (isset($menu_item['active']) ? $menu_item['active'] : 1),
				'help' => 'The status of this menu.',
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
		
		// Create the body
		$body = '<form id="editMenuItemForm" class="fks-form fks-form-sm" role="form" action="javascript:void(0);">
			' . $this->buildFormGroups($form_groups, array('width' => 6)) . '
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
		
		// Allow no parent
		$menu_items[0] = 'None';
		
		// Pre-Validate
		$Validator->validate(array(
			'id' => array('required' => true),
			'menu_id' => array('required' => true, 'numeric' => true, 'values' => array_keys($menus)),
			'parent_id' => array('required' => true, 'numeric' => true, 'values' => array_keys($menu_items)),
			'pos' => array('numeric' => true),
			'title' => array('required' => true, 'not_empty' => true, 'max_length' => 40),
			'title_data' => array('max_length' => 45),
			'has_separator' => array('required' => true, 'values' => array(0, 1, 2, 3)),
			'is_external' => array('bool' => true),
			'is_parent' => array('bool' => true),
			'has_content' => array('bool' => true),
			'url' => array('max_length' => 255, 'required' => true),
			'icon' => array('max_length' => 20),
			'label' => array('required' => true, 'not_empty' => true, 'max_length' => 40),
			'active' => array('bool' => true),
			'hidden' => array('bool' => true)
		));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Create Data Handler
		$DataHandler = new \DataHandler(array(
			'fks_menu_items' => array(
				'base' => 'fks_menu_items',
				'log_actions' => array(
					'created' => \Enums\LogActions::MENU_ITEM_CREATED,
					'modified' => \Enums\LogActions::MENU_ITEM_MODIFIED
				)
			)
		));
		
		// Diff, Set, Log
		$DSL = $DataHandler->DSL(array(
			'type' => 'local',
			'table' => 'fks_menu_items',
			'target_id' => $form['id'],
			'values' => array(
				'columns' => $form,
				'data' => false
			)
		));

		// Return
		if($DSL['result'] == 'success') {
			if($DSL['diff']['log_type'] == 'created') {
				return array('result' => 'success', 'title' => 'Menu Item Created', 'message' => '\'' . $form['title'] . '\' has been created.');
			} else {
				return array('result' => 'success', 'title' => 'Menu Item Updated', 'message' => '\'' . $form['title'] . '\' has been updated.');
			}
		} else {
			return $DSL;
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