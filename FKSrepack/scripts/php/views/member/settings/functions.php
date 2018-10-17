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
		$DataTypes = new \DataTypes();
		$return = '<tr><th>Username</th><td>' . $_SESSION['username'] . '</td></tr>';
		$constants = \Enums\DataTypes::constants();
		
		// Get member data
		$data = $DataTypes->getData(array(
				\Enums\DataTypes::FIRST_NAME,
				\Enums\DataTypes::LAST_NAME,
				\Enums\DataTypes::FULL_NAME,
				\Enums\DataTypes::EMAIL_ADDRESS,
				\Enums\DataTypes::DATE_FORMAT,
				\Enums\DataTypes::TIMEZONE,
				\Enums\DataTypes::EMAIL_VERIFIED
			),
			$_SESSION['id']
		);
		
		// Loop through member data and print them out
		foreach($data as $k => $v) {
			// Special cases
			if($k == 'DATE_FORMAT') {$v = ($v ? $v : 'Site Default');}
			if($k == 'TIMEZONE') {$v = ($v ? $v : 'Site Default');}
			if($k == 'EMAIL_VERIFIED') {$v = ($v ? 'Yes' : 'No');}
			
			// Add row to table
			$return .= '<tr><th>' . $constants[$k]['title'] . '</th><td>' . ($v ? $v : '-') . '</td></tr>';
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