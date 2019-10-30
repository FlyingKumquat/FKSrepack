<?PHP namespace FKS\Views;

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
		$this->access = $this->getAccess('%LABEL%');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function test() {
		
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Load Example Table -------------------- \\
	public function loadExampleTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create database connection
		$Database = new \Database();
		
		// Hide deleted if not at least admin access
		$deleted = $this->access < 3 ? ' WHERE deleted = 0' : '';
		
		// Query the database
		if(!$Database->Q(array(
			'query' => '
				SELECT
					m.*,
					(SELECT username FROM fks_members WHERE id = m.created_by) AS created_name,
					(SELECT username FROM fks_members WHERE id = m.modified_by) AS modified_name
				
				FROM
					fks_menus AS m
			' . $deleted
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Format the table rows (Utilities.php function)
		$data = $this->formatTableRows($Database->r['rows'], $this->access);
		
		// Return data for the table
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Load Example Panel -------------------- \\
	public function loadExamplePanel() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set return data
		$data = '<p>Return data from an AJAX call.</p>';
		
		// Return data for the table
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Add Function Modal -------------------- \\
	public function addFunction($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create modal body
		$body = '<input type="hidden" name="id" value=""/>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_title" class="form-control-label">Text Input</label>
						<input type="text" class="form-control form-control-sm" id="modal_title" name="title" aria-describedby="modal_title_help" value="" autocomplete="off">
						<div class="form-control-feedback"></div>
						<small id="modal_title_help" class="form-text text-muted">This is a text input..</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_notes" class="form-control-label">Textarea</label>
						<textarea class="form-control form-control-sm" id="modal_notes" name="notes" aria-describedby="modal_notes_help"></textarea>
						<div class="form-control-feedback"></div>
						<small id="modal_notes_help" class="form-text text-muted">This is a textarea input.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_active" class="form-control-label">Select</label>
						<select class="form-control form-control-sm" id="modal_active" name="active" aria-describedby="modal_active_help">
							<option value="1">Enabled</option>
							<option value="0">Disabled</option>
						</select>
						<div class="form-control-feedback"></div>
						<small id="modal_active_help" class="form-text text-muted">This is a select input.</small>
					</div>
				</div>
			</div>';
		
		// Return modal parts
		return array(
			'result' => 'success',																		// Required (success,failure)
			'parts' => array(
				'title' => 'Add Function',																// Required - Title of the modal
				'size' => 'md',																			// Optional (sm,md,lg,xl) - Defaults to md
				'body_before' => '<form id="modalForm" role="form" action="javascript:void(0);">',		// Optional - Gets put before the body text, useful for forms
				'body' => $body,																		// Required - Body text of the modal
				'body_after' => '</form>',																// Optional - Gets put after the body text, useful for forms
				'footer' => ''																			// Optional - Footer text. Defaults to a Cancel and Save button
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
					. '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#modalForm"><i class="fa fa-undo fa-fw"></i> Reset</button>'
					. '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-plus fa-fw"></i> Add/Update</button>'
			)
		);
	}
	
	// -------------------- Edit Modal Tabs -------------------- \\
	public function editModalTabs($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create modal body 1
		$body1 = '<input type="hidden" name="id" value=""/>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_title" class="form-control-label">Text Input</label>
						<input type="text" class="form-control form-control-sm" id="modal_title" name="title" aria-describedby="modal_title_help" value="" autocomplete="off">
						<div class="form-control-feedback"></div>
						<small id="modal_title_help" class="form-text text-muted">This is a text input..</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_notes" class="form-control-label">Textarea</label>
						<textarea class="form-control form-control-sm" id="modal_notes" name="notes" aria-describedby="modal_notes_help"></textarea>
						<div class="form-control-feedback"></div>
						<small id="modal_notes_help" class="form-text text-muted">This is a textarea input.</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="modal_active" class="form-control-label">Select</label>
						<select class="form-control form-control-sm" id="modal_active" name="active" aria-describedby="modal_active_help">
							<option value="1">Enabled</option>
							<option value="0">Disabled</option>
						</select>
						<div class="form-control-feedback"></div>
						<small id="modal_active_help" class="form-text text-muted">This is a select input.</small>
					</div>
				</div>
			</div>';
		
		// Create modal body 2
		$body2 = '<p>This is text being displayed on the second tab of this modal...</p>';
		
		// Return modal parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => array('<i class="fa fa-cogs"></i> Tab 1', '<i class="fa fa-list"></i> Tab 2'),	// Array of all the tab titles
				'size' => 'md',
				'body_before' => '<form id="modalForm" role="form" action="javascript:void(0);">',
				'body' => array($body1, $body2),															// Array of all the tab bodies
				'body_after' => '</form>',
				'footer' => ''
					. '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
					. '<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#modalForm"><i class="fa fa-undo fa-fw"></i> Reset</button>'
					. '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-plus fa-fw"></i> Add/Update</button>'
			)
		);
	}
	
	// -------------------- Load Example History -------------------- \\
	public function loadExampleHistory($data) {
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
}
?>