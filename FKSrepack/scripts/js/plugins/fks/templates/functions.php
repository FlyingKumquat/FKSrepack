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