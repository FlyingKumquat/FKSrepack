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
		$this->access = $this->getAccess('account_settings');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function test() {
		
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Load Account Activity -------------------- \\
	public function loadAccountActivity() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Get member logs
		$MemberLog = new \MemberLog();
		$logs = $MemberLog->grabMemberLogs(array(
			'member_id' => $_SESSION['id'],
			'limit' => 100,
			'also' => 'ORDER BY date_created DESC'
		));
		
		// Return data for the table
		if($logs['result'] == 'success') {
			// Loop through logs and make changes
			foreach($logs['history'] as $k => &$v) {
				// Format create date
				$v['date_created'] = $this->formatDateTime($v['date_created']);
				
				// Add view button
				$v['tools'] = '<span class="pull-right">
					<a class="view" href="javascript:void(0);" data-toggle="fks-tooltip" title="Detailed View"><i class="fa fa-eye fa-fw"></i><span class="d-none d-lg-inline"> View</span></a>
				</span>';
			}
			
			// Return
			return array('result' => 'success', 'data' => $logs['history']);
		} else {
			// Return
			return array('result' => 'failure', 'message' => $logs['message']);
		}
	}
	
	// -------------------- Load Account Data -------------------- \\
	public function loadAccountData() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		$return = '<tr><th>Username</th><td>' . $_SESSION['username'] . '</td></tr>';
		
		// Create Data Handler for remote site
		$DataHandler = new \DataHandler(array(
			'members' => array(
				'base' => 'fks_members',						// Base Table
				'data' => 'fks_member_data',					// Data Table
				'data_types' => 'fks_member_data_types',		// Data Type Table
				'base_column' => 'member_id',					// Column name (data table link to base table)
				'data_types_column' => 'id'						// Column name (data table link to data types table)
			)
		));
		
		// Get remote member data
		$member_data = $DataHandler->getData('remote', 'members', $_SESSION['id'], array('columns' => false, 'data' => array(
			'FIRST_NAME',
			'LAST_NAME',
			'FULL_NAME',
			'EMAIL_ADDRESS',
			'DATE_FORMAT',
			'TIMEZONE',
			'EMAIL_VERIFIED'
		)));
		
		// Loop through member data and print them out
		foreach($member_data['data'] as $k => $v) {
			$title = $v['title'];
			$v = $v['value'];
			
			// Special cases
			if($k == 'DATE_FORMAT') {$v = ($v ? $v : 'Site Default');}
			if($k == 'TIMEZONE') {$v = ($v ? $v : 'Site Default');}
			if($k == 'EMAIL_VERIFIED') {$v = ($v ? 'Yes' : 'No');}
			
			// Add row to table
			$return .= '<tr><th>' . $title . '</th><td>' . ($v ? $v : '-') . '</td></tr>';
		}
		
		// Return member data for the table
		return array('result' => 'success', 'data' => $return);
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