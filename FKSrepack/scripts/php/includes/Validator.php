<?PHP
/*##############################################
	Validator Form Validation
	Version: 2.3.20191024
	Updated: 10/24/2019
##############################################*/

/*
	base64_decode
	not_empty
	nullify
	required
	set
	unset
	urldecode
	
	alphanumeric
	bool
	date
	datetime
	email
	file
	ip
	json
	length
	mag
	match
	max_length
	max_value
	min_length
	min_value
	no_spaces
	not_values
	not_values_i
	not_values_csv
	not_values_csv_i
	numeric
	regex
	time
	time_zone
	url
	urlmag
	values
	values_i
	values_csv
	values_csv_i
*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class Validator {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $result = true;
	private $output = array();
	private $input = null;
	private $options = array();
	private $form = array();
	private $missing = null;
	private $required = null;
	private $not_empty = null;
	
	private $current_title = null;

/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($input = null) {
		$this->input = $input;
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	public function getOptions() { return $this->options; }
	
	public function getResult() { return $this->result; }
	
	public function getInput() { return $this->input; }
	
	public function getOutput() { return $this->output; }
	
	public function getForm() { return $this->form; }
	
	public function getDebug($parts) {
		$out = array();
		foreach($parts as $p) {
			switch($p) {
				case 'input':
					array_push($out, '<b>Input</b>');
					array_push($out, print_r(self::getInput(), true));
					break;
					
				case 'options':
					array_push($out, '<b>Options</b>');
					array_push($out, print_r(self::getOptions(), true));
					break;
					
				case 'result':
					array_push($out, '<b>Result</b>');
					array_push($out, print_r(var_export(self::getResult(), true) . '<br>', true));
					break;
					
				case 'output':
					array_push($out, '<b>Output</b>');
					array_push($out, print_r(self::getOutput(), true));
					break;
					
				case 'form':
					array_push($out, '<b>Form</b>');
					array_push($out, print_r(self::getForm(), true));
					break;
					
				default:
					array_push($out, '<b>UNKNOWN</b>');
					array_push($out, $p);
					break;
			}
		}
		echo implode('<br>', $out);
	}
	
	public function validate($options, $args = array()) {
		// Convert options if not array
		if(!is_array($options)) { $options = array($options => $args); }
		
		// Loop through all options
		foreach($options as $title => $validators) {
			// Set current title
			$this->current_title = $title;
			
			// Reset missing
			$this->missing = false;
			
			// Check to see if input for title is missing
			if(!key_exists($title, $this->input)) {
				$this->missing = true;
				$this->form[$title] = null;
			}
			
			// JSON decode validators if not an array
			if(!is_array($validators)) { $validators = json_decode($validators, true); }
			
			// Set options
			$this->options[$title] = $validators;
			
			// Check for set
			if(key_exists('set', $validators)) {
				$this->missing = false;
				$this->form[$title] = $validators['set'];
				unset($validators['set']);
			}
			
			// Check for base64_decode
			if(key_exists('base64_decode', $validators)) {
				if(self::trueFalse($validators['base64_decode'])) {
					$this->form[$title] = base64_decode($this->input[$title]);
				}
				unset($validators['base64_decode']);
			}
			
			// Check for urldecode
			if(key_exists('urldecode', $validators)) {
				if(self::trueFalse($validators['urldecode'])) {
					$this->form[$title] = str_replace('&plus;', '+', urldecode($this->input[$title]));
				}
				unset($validators['urldecode']);
			}
			
			// Check for value nullification
			if(key_exists('nullify', $validators)) {
				if(self::trueFalse($validators['nullify'])) {
					$this->form[$title] = null;
				}
				unset($validators['nullify']);
			}

			// Set required
			$this->required = (key_exists('required', $validators) ? self::trueFalse($validators['required']) : false);
			unset($validators['required']);
			
			// Set not_empty
			$this->not_empty = (key_exists('not_empty', $validators) ? self::trueFalse($validators['not_empty']) : false);
			unset($validators['not_empty']);
			
			// Check to see if input for title is missing and required
			if($this->missing && $this->required) {
				self::addError($title, '_required', 'Input is missing.');
			}
			
			// Set temp input
			$_input = key_exists($title, $this->form) ? $this->form[$title] : $this->input[$title];
			
			// Check for empty input
			if(!$this->missing && $this->required && $this->not_empty && self::is_empty($_input)) {
				self::addError($title, '_not_empty', 'Input must not be empty.');
			}
			
			// Validate if not missing and empty is not allowed or empty is allowed but input is not empty
			if(
				!$this->missing
					&&
				(
					$this->not_empty
						||
					(
						!$this->not_empty
							&&
						!self::is_empty($_input)
					)
				)
			) {
				// Loop through all validators
				foreach($validators as $k => $v) {
					// Skip special
					if($k == 'unset') { continue; }
					
					// Set _func to function name
					$_func = '_' . $k;
					
					// Make sure class function exists and is callable
					if(method_exists(__CLASS__, $_func) && is_callable(__CLASS__, $_func)) {
						// Call the class function
						self::{$_func}($title, $_input, $v);
					} else {
						// Add an error
						self::addError($title, $_func, 'Unknown validation option "' . $k . '".');
					}
				}
			}
			
			// Set form data, change empty to null
			$this->form[$title] = ($_input == '' ? null : $_input);
			
			// Check for unset
			if(key_exists('unset', $validators)) {
				if(self::trueFalse($validators['unset'])) {
					unset($this->form[$title]);
				}
			}
		}
		
		return $this->result;
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function _alphanumeric($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!ctype_alnum($input)) {
			self::addError($title, __FUNCTION__, 'Must consist of only letters and numbers.');
		}
	}

	private function _bool($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if($input !== false && $input !== true && (string)$input != '0' && (string)$input != '1') {
			self::addError($title, __FUNCTION__, 'Non-bool value in a bool-only field.');
		}
	}
	
	private function _date($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		$proper = true;
		preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/m', $input, $matches, PREG_OFFSET_CAPTURE, 0);
		if(count($matches) != 1) { $proper = false; }
		if($proper) {
			$parts = explode(' ', str_replace('-', ' ', $input));
			if($parts[1] < 1 || $parts[1] > 12) { $proper = false; }
			if($parts[2] < 1 || $parts[2] > date('t', strtotime($parts[0] . '-' . $parts[1]))) { $proper = false; }
		}
		if(!$proper) {
			self::addError($title, __FUNCTION__, 'Must be in MySQL date format.');
		}
	}
	
	private function _datetime($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		$proper = true;
		preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/m', $input, $matches, PREG_OFFSET_CAPTURE, 0);
		if(count($matches) != 1) { $proper = false; }
		if($proper) {
			$parts = explode(' ', str_replace(array('-', ':'), ' ', $input));
			if($parts[1] < 1 || $parts[1] > 12) { $proper = false; }
			if($parts[2] < 1 || $parts[2] > date('t', strtotime($parts[0] . '-' . $parts[1]))) { $proper = false; }
			if($parts[3] < 0 || $parts[3] > 23) { $proper = false; }
			if($parts[4] < 0 || $parts[4] > 59) { $proper = false; }
			if($parts[5] < 0 || $parts[5] > 59) { $proper = false; }
		}
		if(!$proper) {
			self::addError($title, __FUNCTION__, 'Must be in MySQL datetime format.');
		}
	}
	
	private function _email($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!filter_var($input, FILTER_VALIDATE_EMAIL)) {
			self::addError($title, __FUNCTION__, 'Invalid email address format.');
		}
	}
	
	private function _file($title, $input, $value) {
		$image_types = array(
			'image/png',
			'image/jpeg',
			'image/gif',
			'image/bmp'
		);
		if($value && !empty($input)) {
			$size = 0;
			if(isset($value['max_files']) && count($input) > $value['max_files']) {
				self::addError($title, __FUNCTION__, 'Must select ' . $value['max_files'] . ' or fewer files.');
				return false;
			}
			foreach($input as $v) {
				if(isset($value['image']) && $value['image'] && !in_array($v['type'], $image_types)) {
					self::addError($title, __FUNCTION__, 'File is not a valid image type.');
					return false;
				}
				if(isset($value['max_file_size']) && $v['size'] > $value['max_file_size']) {
					self::addError($title, __FUNCTION__, 'File size must be ' . $this->formatBytes($value['max_file_size']) . ' or smaller.');
					return false;
				}
				$size += $v['size'];
			}
			if(isset($value['max_total_size']) && $size > $value['max_total_size']) {
				self::addError($title, __FUNCTION__, 'File sizes must total ' . $this->formatBytes($value['max_total_size']) . ' or smaller.');
				return false;
			}
		}
	}
	
	private function _ip($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!filter_var($input, FILTER_VALIDATE_IP)) {
			self::addError($title, __FUNCTION__, 'Invalid IP Address.');
		}
	}
	
	private function _json($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!is_string($value)) {
			self::addError($title, __FUNCTION__, 'Invalid JSON format.');
		} else {
			$json = json_decode($input);
			if($json === null && json_last_error() !== JSON_ERROR_NONE) {
				self::addError($title, __FUNCTION__, 'Invalid JSON format.');
			}
		}
	}
	
	private function _length($title, $input, $value) {
		if(strlen($input) != $value) {
			self::addError($title, __FUNCTION__, 'Must be exactly ' . $value . ' characters long.');
		}
	}
	
	private function _mag($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(substr($input, 0, 8) != 'magnet:?') {
			self::addError($title, __FUNCTION__, 'Invalid magnet.');
		}
	}
	
	private function _match($title, $input, $value) {
		if($input != $this->input[$value]) {
			self::addError($title, __FUNCTION__, 'Values do not match.');
			self::addError($value, __FUNCTION__, 'Values do not match.');
		}
	}
	
	private function _max_length($title, $input, $value) {
		if(strlen($input) > $value) {
			self::addError($title, __FUNCTION__, 'Must be no more than ' . $value . ' characters long.');
		}
	}
	
	private function _max_value($title, $input, $value) {
		if($input > $value) {
			self::addError($title, __FUNCTION__, 'Must not be greater than ' . $value . '.');
		}
	}
	
	private function _min_length($title, $input, $value) {
		if(strlen($input) < $value) {
			self::addError($title, __FUNCTION__, 'Must be at least ' . $value . ' characters long.');
		}
	}
	
	private function _min_value($title, $input, $value) {
		if($input < $value) {
			self::addError($title, __FUNCTION__, 'Must not be less than ' . $value . '.');
		}
	}
	
	private function _no_spaces($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(strpos($input, ' ') !== false) {
			self::addError($title, __FUNCTION__, 'Must not contain spaces.');
		}
	}
	
	private function _not_values($title, $input, $value) {
		if(in_array($input, $value)) {
			self::addError($title, __FUNCTION__, 'Unacceptable value.');
		}
	}
	
	private function _not_values_i($title, $input, $value) {
		if($this->in_arrayi($input, $value)) {
			self::addError($title, __FUNCTION__, 'Unacceptable value.');
		}
	}
	
	private function _not_values_csv($title, $input, $value) {
		foreach(explode(',', $input) as $v) {
			if(in_array($v, $value)) {
				self::addError($title, __FUNCTION__, 'Unacceptable value.');
				break;
			}
		}
	}
	
	private function _not_values_csv_($title, $input, $value) {
		foreach(explode(',', $input) as $v) {
			if($this->in_arrayi($v, $value)) {
				self::addError($title, __FUNCTION__, 'Unacceptable value.');
				break;
			}
		}
	}
	
	private function _numeric($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!is_int($input * 1) || !is_numeric($input)) {
			self::addError($title, __FUNCTION__, 'Must be a numeric value.');
		}
	}
	
	private function _regex($title, $input, $value) {
		preg_match('/^' . $value . '$/m', $input, $matches, PREG_OFFSET_CAPTURE, 0);
		if(count($matches) == 0) {
			self::addError($title, __FUNCTION__, 'Does not match pattern.');
		}
	}
	
	private function _time($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		$proper = true;
		preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/m', $input, $matches, PREG_OFFSET_CAPTURE, 0);
		if(count($matches) != 1) { $proper = false; }
		if($proper) {
			$parts = explode(' ', str_replace(':', ' ', $input));
			if($parts[0] < 0 || $parts[0] > 23) { $proper = false; }
			if($parts[1] < 0 || $parts[1] > 59) { $proper = false; }
			if($parts[2] < 0 || $parts[2] > 59) { $proper = false; }
		}
		if(!$proper) {
			self::addError($title, __FUNCTION__, 'Must be in MySQL time format.');
		}
	}
	
	private function _time_zone($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!in_array($input, DateTimeZone::listIdentifiers(DateTimeZone::ALL))) {
			self::addError($title, __FUNCTION__, 'Invalid Time Zone.');
		}
	}
	
	private function _url($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!filter_var($input, FILTER_VALIDATE_URL)) {
			self::addError($title, __FUNCTION__, 'Invalid URL.');
		}
	}

	private function _urlmag($title, $input, $value) {
		if(!self::trueFalse($value)) { return; }
		if(!filter_var($input, FILTER_VALIDATE_URL) && substr($input, 0, 8) != 'magnet:?') {
			self::addError($title, __FUNCTION__, 'Invalid URL or magnet.');
		}
	}
	
	private function _values($title, $input, $value) {
		if(!is_array($input)) { $input = array($input); }
		foreach($input as $i) {
			if(!in_array($i, $value)) {
				self::addError($title, __FUNCTION__, 'Invalid value.');
				break;
			}
		}
	}
	
	private function _values_i($title, $input, $value) {
		if(!is_array($input)) { $input = array($input); }
		foreach($input as $i) {
			if(!in_arrayi($i, $value)) {
				self::addError($title, __FUNCTION__, 'Invalid value.');
				break;
			}
		}
	}
	
	private function _values_csv($title, $input, $value) {
		foreach(explode(',', $input) as $i) {
			if(!in_array($i, $value)) {
				self::addError($title, __FUNCTION__, 'Invalid value.');
				break;
			}
		}
	}
	
	private function _values_csv_i($title, $input, $value) {
		foreach(explode(',', $input) as $i) {
			if(!$this->in_arrayi($i, $value)) {
				self::addError($title, __FUNCTION__, 'Invalid value.');
				break;
			}
		}
	}

/*----------------------------------------------
	Utility Functions
----------------------------------------------*/
	private function reset() {
		$this->result = true;
		$this->output = array();
		$this->options = array();
		$this->form = array();
		$this->missing = null;
		$this->required = null;
		$this->not_empty = null;
		$this->current_title = null;
	}

	private function is_empty($value) {
		if(is_array($value)) { return empty($value); }
		if(strlen($value) != 0) { return false; }
		if($value === false) { return false; }
		return true;
	}

	private function addError($title, $function, $error) {
		if(!isset($this->output[$title])) { $this->output[$title] = array(); }
		$this->output[$title][substr($function, 1, strlen($function) - 1)] = $error;
		$this->result = false;
	}

	private function formatBytes($size, $precision = 2) {
		if(!is_numeric($size)) { $size = 0; }
		$base = log($size, 1024);
		$suffixes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$newSize = round(pow(1024, $base - floor($base)), $precision);
		return (is_nan($newSize) ? '-' : $newSize . ' ' . $suffixes[floor($base)]);
	}
	
	private function in_arrayi($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	private function trueFalse($args) {
		// Return if bool
		if(is_bool($args)) { return $args; }
		
		// Return false if not array
		if(!is_array($args)) { return false; }
		
		// Setup options
		$options = array(
			'conditions' => key_exists('conditions', $args) ? $args['conditions'] : $args,
			'inverse' => key_exists('inverse', $args) ? $args['inverse'] : false,
			'operator' => key_exists('operator', $args) ? strtoupper($args['operator']) : 'AND'
		);
		
		// Set errors
		$errors = false;
		
		// Loop through all conditions for missing inputs
		foreach($options['conditions'] as $title => $value) {
			// Input does not exist
			if(!key_exists($title, $this->input)) {
				self::addError($this->current_title, '_input_missing', 'Input not found. (' . $title . ')');
				$errors = true;
			}
		}
		
		// Return false if errors
		if($errors) { return false; }
		
		// Loop through all conditions to check values
		foreach($options['conditions'] as $title => $value) {
			// Set temp input
			$_input = key_exists($title, $this->form) ? $this->form[$title] : $this->input[$title];
			
			// All values must match
			if($options['operator'] == 'AND') {
				// Value is not what we want
				if($_input != $value) {
					return $options['inverse'];
				}
			}
			
			// At least one value must match
			if($options['operator'] == 'OR') {
				// Value is what we want
				if($_input == $value) {
					return !$options['inverse'];
				}
			}
		}
		
		return (!$options['inverse'] && $options['operator'] == 'AND');
	}
}
?>