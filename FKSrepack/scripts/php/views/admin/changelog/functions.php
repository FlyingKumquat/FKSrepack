<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/

class PageFunctions extends CoreFunctions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $access;
	private $types = array('Added', 'Changed', 'Removed', 'Fixed');
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		parent::__construct();
		$this->access = $this->getAccess('changelog');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function updateChangelogModify($changelog_id) {
		// Set vars
		$Database = new \Database();
		
		// Grab changelogs
		$Database->Q(array(
			'params' => array(
				':date_modified' => gmdate('Y-m-d H:i:s'),
				':modified_by' => $_SESSION['id'],
				':id' => $changelog_id
			),
			'query' => 'UPDATE fks_changelog SET date_modified = :date_modified, modified_by = :modified_by WHERE id = :id'
		));
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Load Changelog Table -------------------- \\
	public function loadChangelogTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		
		// Grab changelogs
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => '
				SELECT
					c.*,
					(SELECT username FROM fks_members WHERE id = c.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = c.modified_by) AS modified_name
				
				FROM
					fks_changelog AS c
					
				ORDER BY version DESC'
		))){
			// Format and store rows
			$data = $this->formatTableRows($Database->r['rows']);
		}else{
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// loop through all changelogs found
		foreach( $data as $k => &$v ) {
			// Tools
			$v['tools'] = '<span class="pull-right">';
				// View
				if($this->access > 0) { $v['tools'] .= '<a class="view" href="javascript:void(0);" data-toggle="fks-tooltip" title="View"><i class="fa fa-eye fa-fw"></i></a>&nbsp;'; }
				// History
				if($this->access > 2) { $v['tools'] .= '<a class="history" href="javascript:void(0);" data-toggle="fks-tooltip" title="History"><i class="fa fa-history fa-fw"></i></a>&nbsp;'; }
				// Edit
				if($this->access > 1) { $v['tools'] .= '<a class="edit" href="javascript:void(0);" data-toggle="fks-tooltip" title="Edit"><i class="fa fa-edit fa-fw"></i></a>'; }
			$v['tools'] .= '</span>';
		}
		
		// Return data
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Add Changelog Modal -------------------- \\
	public function addChangelog() {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Get current site version from the DB
		$version = $this->siteSettings('SITE_VERSION', true);
		
		// Body
		$body = '<form id="modalForm" role="form" action="javascript:void(0);">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_version" class="form-control-label">Changelog Version</label>
						<input type="text" class="form-control form-control-sm" id="modal_version" name="version" aria-describedby="version_help" value="' . ($version ? $version : '') . '" autocomplete="off">
						<div class="form-control-feedback"></div>
						<small id="version_help" class="form-text text-muted">Set the changelog version.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_settings" class="form-control-label">Update Site Version?</label>
						<select class="form-control form-control-sm" id="modal_settings" name="settings" aria-describedby="settings_help">
							<option value="1">Yes</option>
							<option value="0">No</option>
						</select>
						<div class="form-control-feedback"></div>
						<small id="settings_help" class="form-text text-muted">Update the site version with this version.</small>
					</div>
				</div>
			</div>
		</form>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => 'Add Changelog',
				'size' => 'sm',
				'body' => $body,
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
					. '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-save fa-fw"></i> Create</button>'
			)
		);
	}
	
	// -------------------- Create Changelog -------------------- \\
	public function createChangelog($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Pre-Validate
		$Validator->validate('version', array('required' => true, 'max_length' => 45));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Check for existing version
		if(!$Database->Q(array(
			'params' => array(':version' => $form['version']),
			'query' => 'SELECT * FROM fks_changelog WHERE version = :version AND deleted = 0'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Found existing version
		if(!empty($Database->r['rows'])) {
			return array('result' => 'failure', 'message' => 'Version already exists.');
		}
		
		// Create changelog in DB
		if(!$Database->Q(array(
			'params' => array(
				':version' => $form['version'],
				':date_created' => gmdate('Y-m-d H:i:s'),
				':created_by' => $_SESSION['id']
			),
			'query' => '
				INSERT INTO
					fks_changelog
					
				SET 
					version = :version,
					date_created = :date_created,
					created_by = :created_by
				'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		$last_id = $Database->r['last_id'];
		
		// Add log action for creating changelog
		$MemberLog = new \MemberLog(\Enums\LogActions::CHANGELOG_CREATED, $_SESSION['id'], $Database->r['last_id'], json_encode($form));
		
		// Update Site Version in DB if set to true
		if( $data['settings'] == 1 ) {
			if(!$Database->Q(array(
				'params' => array(
					':version' => $form['version']
				),
				'query' => 'UPDATE fks_site_settings SET data = :version WHERE id = "SITE_VERSION"'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
		}
		
		// Return success
		return array('result' => 'success', 'message' => 'Created changelog.', 'last_id' => $last_id);
	}
	
	// -------------------- View Changelog Modal -------------------- \\
	public function viewChangelog($data) {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set database connection
		$Database = new \Database;
		
		// Set variables
		$changelog = array();
		$notes = array();
		$pages = array();
		
		// Grab changelog
		if(!$Database->Q(array(
			'params' => array(':id' => $data),
			'query' => 'SELECT * FROM fks_changelog WHERE id = :id AND deleted = 0 ORDER BY date_created DESC'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Set changelog from row
		$changelog = $Database->r['row'];
		
		// No changelog found
		if(empty($changelog)) { return array('result' => 'failure', 'message' => 'Changelog not found.'); }
		
		// Grab changelog notes
		if(!$Database->Q(array(
			'assoc' => 'id',
			'params' => array(':changelog_id' => $data),
			'query' => 'SELECT * FROM fks_changelog_notes WHERE changelog_id = :changelog_id'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Set notes from rows
		$notes = $Database->r['rows'];
		
		// Create pages array
		foreach($notes as &$note) { $note['pages'] = array(); }
		
		// Grab changelog note pages
		if(!empty($notes)) {
			// Grab changelog pages
			if(!$Database->Q(array(
				'query' => 'SELECT * FROM fks_changelog_pages WHERE note_id IN (' . implode(',', array_keys($notes)) . ')'
			))) {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Set pages from rows
			$pages = $Database->r['rows'];
			
			// Add pages to notes
			foreach($pages as $v) { array_push($notes[$v['note_id']]['pages'], $v['page_id']); }
		}
		
		// Format the changelog
		$formatted_changelog = $this->formatChangelog(array($changelog), $notes, false);
		
		$title = $changelog['version'] . (!empty($changelog['title']) ? ' <small style="color: rgba(0, 0, 0, 0.5);">' . $changelog['title'] . '</small>' : '');
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'size' => 'lg',
				'title' => $title,
				'body' => $formatted_changelog,
				'footer' => '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
			)
		);
	}
	
	// -------------------- Edit Changelog -------------------- \\
	public function editChangelog($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		
		// Grab changelog data
		if($Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => 'SELECT * FROM fks_changelog WHERE id = :id'
		))){
			$changelog = $Database->r['row'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Check to see if changelog is empty
		if(empty($changelog)) {
			// Return error message
			return array('result' => 'failure', 'title' => 'Changelog Not Found', 'message' => 'Unable to load Changelog.');
		}
		
		// Grab changelog notes
		if($Database->Q(array(
			'params' => array(
				':id' => $data
			),
			'query' => '
				SELECT
					n.*,
					(SELECT COUNT(page_id) FROM fks_changelog_pages WHERE note_id = n.id) AS pages
				
				FROM
					fks_changelog_notes AS n
					
				WHERE
					n.changelog_id = :id'
		))){
			$notes = $Database->r['rows'];
			
			foreach( $notes as $k => &$v ) {
				if( $v['pages'] == 0 ) { $v['pages'] = '*'; }
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Return changelog stuffs
		return array('result' => 'success', 'data' => $changelog, 'notes' => $notes);
	}
	
	// -------------------- Update Changelog -------------------- \\	
	public function updateChangelog($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Pre-Validate
		$Validator->validate('id', array('required' => true, 'number' => true));
		$Validator->validate('version', array('required' => true, 'max_length' => 45));
		$Validator->validate('title', array('min_length' => 1, 'max_length' => 45));
		$Validator->validate('active', array('required' => true, 'bool' => true));
		$Validator->validate('notes', array('min_length' => 1));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Check Diffs
		$diff = $this->compareQueryArray($form['id'], 'fks_changelog', $form);

		// Update the changelog
		if( $diff ) {
			if(!$Database->Q(array(
				'params' => array(
					':id' => $form['id'],
					':version' => $form['version'],
					':title' => $form['title'],
					':notes' => $form['notes'],
					':active' => $form['active']
				),
				'query' => '
					UPDATE
						fks_changelog 
						
					SET 
						version = :version,
						title = :title,
						notes = :notes,
						active = :active
						
					WHERE
						id = :id
					'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
		}
	
		// Add log action for modifying changelog
		if( $diff ) {
			$MemberLog = new \MemberLog(\Enums\LogActions::CHANGELOG_MODIFIED, $_SESSION['id'], $form['id'], json_encode($diff));
		} else {
			// Return No Changes
			return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.');
		}
		
		// Update modified info for changelog
		$this->updateChangelogModify($form['id']);
		
		// Return changelog stuffs
		return array('result' => 'success', 'message' => 'Updated changelog');
	}
	
	// -------------------- Add/Edit Changelog Note Modal -------------------- \\
	public function addChangelogNote($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$readonly = ($this->access == 1);
		$Database = new \Database();
		$type_options = '';
		$content_pages_options = '';
		$menus = array();
		$menu_items = array();
		$pages_array = array();
		
		// Grab current note details
		if($Database->Q(array(
			'params' => array(
				':note_id' => $data['note_id']
			),
			'query' => 'SELECT * FROM fks_changelog_notes WHERE id = :note_id LIMIT 1'
		))) {
			if( $Database->r['found'] == 1 ) {
				$note = $Database->r['row'];
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab all pages for this note
		if( isset($note['id']) ) {
			if($Database->Q(array(
				'assoc' => 'page_id',
				'params' => array(
					':note_id' => $note['id']
				),
				'query' => 'SELECT * FROM fks_changelog_pages WHERE note_id = :note_id'
			))) {
				if( $Database->r['found'] > 0 ) {
					$pages_array = array_keys($Database->r['rows']);
				}
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
		}
		
		// Create type options
		foreach($this->types as $t) {
			$type_options .= '<option value="' . $t . '"' . (isset($note['type']) && $note['type'] == $t ? ' selected' : '') . '>' . $t . '</option>';
		}
		
		// Grab all menus
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => ' SELECT * FROM fks_menus WHERE deleted = 0 ORDER BY title ASC'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		$menus = $Database->r['rows'];
		
		// Grab all menu items
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_menu_items WHERE deleted = 0'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		$menu_items = $Database->r['rows'];
		
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
				
				if(isset($pages_array) && in_array($item['id'], $pages_array)) {
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
		
		// Modal body
		$body = '<form id="modalForm" role="form" action="javascript:void(0);">
			<input type="hidden" name="changelog_id" value="' . $data['changelog_id'] . '">
			<input type="hidden" name="note_id" value="' . $data['note_id'] . '">
			<div class="row">
				<div class="col-md-4 form-group">
					<label for="modal_type" class="form-control-label">Type</label>
					<select class="form-control form-control-sm" id="modal_type" name="type" aria-describedby="type_help">
						' . $type_options . '
					</select>
					<div class="form-control-feedback"></div>
					<small id="type_help" class="form-text text-muted">What did this message do.</small>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-12 form-group">
					<label for="modal_data" class="form-control-label">Message</label>
					<input type="text" class="form-control form-control-sm " id="modal_data" name="data" aria-describedby="data_help" value="' . (isset($note['data']) ? $note['data'] : ''). '" autocomplete="off">
					<div class="form-control-feedback"></div>
					<small id="data_help" class="form-text text-muted">Explain what was done.</small>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-12 form-group">
					<label for="modal_pages" class="form-control-label">Pages</label>
					<select class="form-control form-control-sm" id="modal_pages" name="pages" aria-describedby="pages_help" multiple="multiple">
						' . $content_pages_options . '
					</select>
					<div class="form-control-feedback"></div>
					<small id="pages_help" class="form-text text-muted">What pages were affected by this.</small>
				</div>
			</div>
		</form>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => ($data['note_id'] == '+' ? 'Add' : 'Edit') . ' Changelog Note',
				'size' => 'lg',
				'body' => $body,
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
					. '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-save fa-fw"></i> ' . ($data['note_id'] == '+' ? 'Add' : 'Update') . '</button>'
			)
		);
	}
	
	public function createChangelogNote($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$Validator = new \Validator($data);
		
		// Pre-Validate
		$Validator->validate('changelog_id', array('required' => true));
		$Validator->validate('type', array('required' => true, 'values' => $this->types));
		$Validator->validate('data', array('required' => true));
		$Validator->validate('pages', array('min_length' => 1));
		
		// Check for failures
		if(!$Validator->getResult()) { return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		// Get updated form
		$form = $Validator->getForm();
		
		// Check for missing values
		if( empty($form['pages']) ){ $form['pages'] = null; }
		
		if( $data['note_id'] == '+' ) {
		// Create changelog note in DB
			// Add the note
			if(!$Database->Q(array(
				'params' => array(
					':changelog_id' => $form['changelog_id'],
					':type' => $form['type'],
					':data' => $form['data']
				),
				'query' => '
					INSERT INTO
						fks_changelog_notes 
						
					SET 
						changelog_id = :changelog_id,
						type = :type,
						data = :data
					'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Manipulate data for member logs
			$note_id = $Database->r['last_id'];
			$changelog_id = $form['changelog_id'];
			unset($form['changelog_id']);
			$form['note_id'] = $note_id;
			
			// Add the page(s)
			if( isset($form['pages']) && !empty($form['pages']) ) {
				$page_array = explode(',', $form['pages']);
				foreach( $page_array as $page_id ) {
					$Database->Q(array(
						'params' => array(
							':page_id' => $page_id,
							':note_id' => $note_id
						),
						'query' => '
							INSERT INTO
								fks_changelog_pages
								
							SET 
								page_id = :page_id,
								note_id = :note_id
							'
					));
				}
			}
			
			// Add log action for creating changelog notes
			$MemberLog = new \MemberLog(\Enums\LogActions::CHANGELOG_NOTE_CREATED, $_SESSION['id'], $changelog_id, json_encode($form));
			
			// Update modified info for changelog
			$this->updateChangelogModify($changelog_id);
			
			// Return success
			return array('result' => 'success', 'message' => 'Added new note.');
		} else {
		// Update changelog note in DB
			// Manipulate data for member logs
			if( !empty($form['pages']) ) {
				$form['pages'] = explode(',', $form['pages']);
				sort($form['pages']);
				$form['pages'] = implode(',', $form['pages']);
			}
			
			// Check Diffs
			$diff1 = $this->compareQueryArray($data['note_id'], 'fks_changelog_notes', array('type' => $form['type'], 'data' => $form['data']));
			$diff2 = $this->compareQueryArray(array('note_id', $data['note_id']), 'fks_changelog_pages', array('page_id' => $form['pages']));
			
			// If we aren't making changes to a note that doesn't have pages
			// Since diff2 will always return what it's passed
			if( empty($diff2['page_id']) ) { $diff2 = false;  }
				
			// Update the note
			if( $diff1 ) {
				if(!$Database->Q(array(
					'params' => array(
						':note_id' => $data['note_id'],
						':changelog_id' => $form['changelog_id'],
						':type' => $form['type'],
						':data' => $form['data']
					),
					'query' => '
						UPDATE
							fks_changelog_notes 
							
						SET 
							changelog_id = :changelog_id,
							type = :type,
							data = :data
							
						WHERE
							id = :note_id
						'
				))){
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
				}
			}
			
			// Pages stuff
			if( $diff2 ) {
				// Convert old and new values into arrays
				if( is_array($diff2['page_id']) ) {
				// If we get back an array of data then there was already data found in the DB
					$parts[0] = (empty($diff2['page_id'][0]) ? array() : explode(',', $diff2['page_id'][0]));
					$parts[1] = (empty($diff2['page_id'][1]) ? array() : explode(',', $diff2['page_id'][1]));
				} else {
				// If we get back a single value then we are adding the first value in the DB
					$parts[0] = array();
					$parts[1] = explode(',', $diff2['page_id']);
				}
				
				// Make arrays of what needs to be done with pages
				$removed = array_diff($parts[0], $parts[1]);
				$added = array_diff($parts[1], $parts[0]);
				
				// Remove pages if there are pages to remove
				if( count($removed) > 0 ) {
					if(!$Database->Q(array(
						'params' => array(
							':note_id' => $data['note_id']
						),
						'query' => '
							DELETE FROM
								fks_changelog_pages
								
							WHERE
								note_id = :note_id
									AND
								page_id IN (' . implode(',', $removed) . ')
							'
					))){
						// Return error message with error code
						return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
					}
				}
				
				// Add pages if there are pages to add
				if( count($added) > 0 ) {
					foreach( $added as $a ) {
						if(!$Database->Q(array(
							'params' => array(
								':note_id' => $data['note_id'],
								':page_id' => $a
							),
							'query' => 'INSERT INTO fks_changelog_pages SET note_id = :note_id, page_id = :page_id'
						))){
							// Return error message with error code
							return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
						}
					}
				}
			}
			
			// Prepare member log
			$diff3 = ($diff1 && $diff2 ? $diff1 + $diff2 : ($diff1 ? $diff1 : ($diff2 ? $diff2 : false)));
			$diff3['note_id'] = $data['note_id'];
			
			// Add log action for modifying changelog
			if( $diff1 || $diff2 ) {
				$MemberLog = new \MemberLog(\Enums\LogActions::CHANGELOG_NOTE_MODIFIED, $_SESSION['id'], $form['changelog_id'], json_encode($diff3));
			} else {
				// Return No Changes
				return array('result' => 'info', 'title' => 'No Changes Detected', 'message' => 'Nothing was saved.');
			}
			
			// Update modified info for changelog
			$this->updateChangelogModify($form['changelog_id']);
			
			// Return success
			return array('result' => 'success', 'message' => 'Updated note!');
		}
	}
	
	public function loadChangelogNotesTable($changelog_id) {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		
		// Grab changelogs
		if($Database->Q(array(
			'params' => array(
				':changelog_id' => $changelog_id
			),
			'query' => '
				SELECT
					*
				
				FROM
					fks_changelog_notes
					
				WHERE
					changelog_id = :changelog_id
					
				ORDER BY type'
		))){
			$data = $Database->r['rows'];
		}else{
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// loop through all changelogs found
		foreach( $data as $k => &$v ) {
			// Tools
			$v['tools'] = '<span class="pull-right">';
				if($this->access > 1) {
					$v['tools'] .= '<a class="edit" href="javascript:void(0);" data-toggle="fks-tooltip" title="Edit"><i class="fa fa-edit fa-fw"></i></a>';
					$v['tools'] .= '<a class="delete" href="javascript:void(0);" data-toggle="fks-tooltip" title="Delete"><i class="fa fa-ban fa-fw"></i></a>';
				}
			$v['tools'] .= '</span>';
		}
		
		// Return data
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Delete Changelog Note -------------------- \\
	public function deleteChangelogNote($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$note = array();
		$page_ids = array();
		
		// Grab note from notes
		if($Database->Q(array(
			'params' => array(':id' => $data['note_id']),
			'query' => 'SELECT id AS note_id, type, data FROM fks_changelog_notes WHERE id = :id'
		))) {
			// Set note from row
			$note = $Database->r['row'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Grab changelog page_ids from pages
		if($Database->Q(array(
			'assoc' => 'page_id',
			'params' => array(':note_id' => $data['note_id']),
			'query' => 'SELECT page_id FROM fks_changelog_pages WHERE note_id = :note_id'
		))) {
			// Set note pages from row keys
			$note['pages'] = array_keys($Database->r['rows']);
			
			// Set to null if no pages
			if(empty($note['pages'])) { $note['pages'] = null; }
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Delete changelog pages
		if(!$Database->Q(array(
			'params' => array(
				':id' => $data['note_id']
			),
			'query' => 'DELETE FROM fks_changelog_pages WHERE note_id = :id'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Delete changelog Note
		if(!$Database->Q(array(
			'params' => array(
				':id' => $data['note_id']
			),
			'query' => 'DELETE FROM fks_changelog_notes WHERE id = :id'
		))){
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Add memberlog for deleting pages and note_id
		$MemberLog = new \MemberLog(\Enums\LogActions::CHANGELOG_NOTE_DELETED, $_SESSION['id'], $data['changelog_id'], json_encode($note));
		
		// Update modified info for changelog
		$this->updateChangelogModify($data['changelog_id']);
		
		// Return success
		return array('result' => 'success', 'message' => 'Deleted changelog note!');
	}
	
	// -------------------- Load History -------------------- \\
	public function loadChangelogHistory($data) {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'table' => 'fks_changelog',
			'id' => $data,
			'title' => 'Changelog History: ',
			'select' => 'version',
			'actions' => array(
				\Enums\LogActions::CHANGELOG_CREATED,
				\Enums\LogActions::CHANGELOG_MODIFIED,
				\Enums\LogActions::CHANGELOG_NOTE_CREATED,
				\Enums\LogActions::CHANGELOG_NOTE_MODIFIED,
				\Enums\LogActions::CHANGELOG_NOTE_DELETED
			)
		));
		
		return $history;
	}
}
?>