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
		$this->access = $this->getAccess('errors');
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
	public function loadErrorsTable() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create database connection
		$Database = new \Database();
		
		// Query the database
		if($Database->Q(array(
			'query' => '
				SELECT
					e.*,
					m.username AS member_name
				
				FROM
					fks_site_errors AS e
					
				LEFT OUTER JOIN
					fks_members AS m
						ON
					e.error_member = m.id
			'
		))) {
			// Set data
			$data = $Database->r['rows'];
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Format the table rows
		foreach($data as &$v) {
			// File name
			$t = explode('\\', $v['error_file']);
			$v['file_name'] = $t[count($t) - 2] . '/' . $t[count($t) - 1];
			
			// Format date
			$v['error_created'] = $this->formatDateTime($v['error_created']);
			
			// Tools
			$v['tools'] = '<span class="pull-right">';
				// View
				if($this->access > 0) { $v['tools'] .= '<a class="view" href="javascript:void(0);" data-toggle="fks-tooltip" title="View"><i class="fa fa-eye fa-fw"></i> View</a>'; }
			$v['tools'] .= '</span>';
		}
		
		// Return data for the table
		return array('result' => 'success', 'data' => $data);
	}
	
	// -------------------- Load Example Panel -------------------- \\
	public function loadErrorModal($data) {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create database connection
		$Database = new \Database();
		
		// Query the database
		if(!$Database->Q(array(
			'params' => array(
				':error_code' => $data
			),
			'query' => '
				SELECT
					e.*,
					m.username AS member_name
				
				FROM
					fks_site_errors AS e
					
				LEFT OUTER JOIN
					fks_members AS m
						ON
					e.error_member = m.id
				
				WHERE
					error_code = :error_code
			'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Did we find anything?
		if($Database->r['found'] == 1) {
			// Set data
			$data = $Database->r['row'];
			$data['error_message'] = json_decode($data['error_message'], true);
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Error Code not found.');
		}
		
		// Set return data
		$body = '<form id="modalForm" role="form" action="javascript:void(0);"><input type="hidden" name="error_code" value="' . $data['error_code'] . '"/></form>
		<h5>Error Data</h5>
		<table class="table table-sm table-striped table-hover table-border" style="word-break:break-word;"><tbody>
			<tr><th>Error Code</th><td>' . $data['error_code'] . '</td></tr>
			<tr><th>File Path</th><td>' . $data['error_file'] . '</td></tr>
			<tr><th>Function Name</th><td>' . $data['error_function'] . '</td></tr>
			<tr><th>Line Number</th><td>' . $data['error_line'] . '</td></tr>
			<tr><th>Class Name</th><td>' . $data['error_class'] . '</td></tr>
			<tr><th>Member ID</th><td>' . $data['error_member'] . '</td></tr>
			<tr><th>Member Name</th><td>' . $data['member_name'] . '</td></tr>
			<tr><th>Created Date</th><td>' . $this->formatDateTime($data['error_created']) . '</td></tr>
		</tbody></table>
		<h5>Error Message</h5>
		<pre>' . json_encode($data['error_message'], JSON_PRETTY_PRINT) . '</pre>';
		
		// Return parts
		return array(
			'result' => 'success',
			'parts' => array(
				'title' => 'View Error',
				'size' => 'lg',
				'body' => $body,
				'footer' => array(
					'<button class="btn fks-btn-warning btn-sm" fks-action="submitForm" fks-target="#modalForm"><i class="fa fa-trash fa-fw"></i> Delete</button>',
					'<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
				)
			)
		);
	}
	
	// -------------------- Delete Error -------------------- \\
	public function deleteError($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Create database connection
		$Database = new \Database();
		
		// Grab the member
		if($Database->Q(array(
			'params' => array(
				':error_code' => $data['error_code']
			),
			'query' => 'SELECT error_member FROM fks_site_errors WHERE error_code = :error_code'
		))) {
			if($Database->r['found'] == 1) {
				// Store the member for the log
				$error_member = $Database->r['row']['error_member'];
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Delete the error
		if(!$Database->Q(array(
			'params' => array(
				':error_code' => $data['error_code']
			),
			'query' => 'DELETE FROM fks_site_errors WHERE error_code = :error_code'
		))) {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Create member log
		$MemberLog = new \MemberLog(\Enums\LogActions::SITE_ERROR_DELETED, $_SESSION['id'], null, json_encode(array('error_code' => $data['error_code'], 'error_member' => $error_member)));
		
		// Return parts
		return array('result' => 'success', 'message' => 'Deleted error from DB.');
	}
	
	// -------------------- Load Site Settings History -------------------- \\
	public function loadSiteErrorsHistory() {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'title' => 'Site Errors History',
			'id' => null,
			'actions' => array(
				\Enums\LogActions::SITE_ERROR_DELETED
			)
		));
		
		return $history;
	}
}
?>