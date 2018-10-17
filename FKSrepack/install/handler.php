<?PHP namespace FKS\Install;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

require_once('functions.php');

class Handler extends \FKS\Install\Functions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $response;
	
	private $_POST;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		parent::__construct();
		$this->_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		$this->handle();
	}
	
/*----------------------------------------------
	Handle Function
----------------------------------------------*/
	private function handle() {
		if(!isset($this->_POST['action'])) {
		// Action is not set
			$this->response = array(
				'result' => 'failure',
				'message' => 'No action found.'
			);
			return false;
		}
		if(!method_exists($this, $this->_POST['action'])) {
		// Function (passed through action) does not exist
			$this->response = array(
				'result' => 'failure',
				'message' => 'No such function found.'
			);
			return false;
		}
		$this->response = $this->{$this->_POST['action']}(isset($this->_POST['data']) ? $this->_POST['data'] : false);
	}
}

$Handler = new Handler();
echo json_encode($Handler->response);
?>