<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
// ini_set('display_errors',1);
// ini_set('display_startup_errors',1);
// error_reporting(-1);

require_once('functions.php');

class Handler extends CoreFunctions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $response;
	
	private $_POST;
	private $script_time;
	
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
		if(isset($this->_POST['wait']) && $this->_POST['wait'] == 'true') {
		// Wait
			usleep(1000000);
		}
		
		// Start script timer
		$this->scriptTimer();
		
		if(!isset($this->_POST['action'])) {
		// Action is not set
			$this->response = array(
				'result' => 'failure',
				'message' => 'No action found.'
			);
			return false;
		}
		
		if(isset($this->_POST['src']) && !empty($this->_POST['src'])) {
		// Source was passed, load functions
			$src = str_replace(array('.', 'php', '/views/'), array('', '', ''), $this->_POST['src']);
			require_once(__DIR__  . parent::SLASH . $src . parent::SLASH . 'functions.php');
			$PageFunctions = new PageFunctions;
			if(!method_exists($PageFunctions, $this->_POST['action'])) {
			// Function (passed through action) does not exist
				$this->response = array(
					'result' => 'failure',
					'message' => 'No such function found.',
					'action' => $this->_POST['action']
				);
				return false;
			}
			$this->response = $PageFunctions->{$this->_POST['action']}(isset($this->_POST['data']) ? $this->_POST['data'] : false);
			return true;
		}
		
		if(!method_exists($this, $this->_POST['action'])) {
		// Function (passed through action) does not exist
			$this->response = array(
				'result' => 'failure',
				'message' => 'No such function found.',
				'action' => $this->_POST['action']
			);
			return false;
		}
		$this->response = $this->{$this->_POST['action']}(isset($this->_POST['data']) ? $this->_POST['data'] : false);
		return true;
	}
	
	public function scriptTimer($start = true) {
		if($start) {
			$this->script_time = microtime(true);
		} else {
			return round(microtime(true) - $this->script_time, 4);
		}
	}
	
	public function getDateTime() {
		// Set current date time
		$dateTime = gmdate('Y-m-d H:i:s');
		
		// Format to users preference
		$dateTime = $this->formatDateTime($dateTime);
		
		// Return
		return $dateTime;
	}
}

$Handler = new Handler();
$Handler->response['script_time'] = $Handler->scriptTimer(false);
$Handler->response['date_time'] = $Handler->getDateTime();
echo json_encode($Handler->response);
?>