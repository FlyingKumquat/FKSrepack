<?PHP
/*##############################################
	Curl Wrapper
	Version: 1.0.20190301
	Updated: 03/01/2019
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
// ini_set('display_errors',1);
// ini_set('display_startup_errors',1);
// error_reporting(-1);

class Curl {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $r;
	private $defaults = array(
		CURLOPT_RETURNTRANSFER		=>	true,
		CURLOPT_HEADER				=>	false,
		CURLOPT_USERAGENT			=>	'CurlWrapper',
		CURLOPT_FOLLOWLOCATION		=>	true,
		CURLOPT_SSL_VERIFYHOST 		=>	false,
		CURLOPT_SSL_VERIFYPEER 		=>	false,
		CURLOPT_POST				=>	false
	);
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
	}
	
/*----------------------------------------------
	Get
----------------------------------------------*/
	public function get($args) {
		// Set options
		$_options = (is_string($args) ? array(CURLOPT_URL => $args) : $args);
		
		// Make request
		return $this->request($_options);
	}
	
/*----------------------------------------------
	Post
----------------------------------------------*/
	public function post($args) {
		// Set POST defaults
		$_options = array(
			CURLOPT_POST => true
		);
		
		// Set options
		if(is_string($args)) {
			$_options[CURLOPT_URL] = $args;
		} else {
			$_options = array_replace($_options, $args);
		}
		
		// Make request		
		return $this->request($_options);
	}
	
/*----------------------------------------------
	Reset Result
----------------------------------------------*/
	private function resetResult() {
		$this->r = array(
			'return' => false,
			'json' => false,
			'error' => false,
			'options' => false
		);
	}
	
/*----------------------------------------------
	Request
----------------------------------------------*/
	private function request($_options) {
		// Reset result values
		$this->resetResult();
		
		// Merge defaults with passed options
		$_options = array_replace($this->defaults, $_options);
		
		// Add options to result
		$this->r['options'] = $_options;
		
		// Initialize curl
		$ch = curl_init();
		
		// Set curl options
		curl_setopt_array($ch, $_options);

		// Execute curl and return if it fails
		if(!$this->r['return'] = curl_exec($ch)) {
			$this->r['error'] = curl_error($ch);
			curl_close($ch);
			return false;
		}

		// Close the connection
		curl_close($ch);
		
		// JSON decode
		$this->r['json'] = json_decode($this->r['return'], true);

		// Return
		return true;
	}
}
?>