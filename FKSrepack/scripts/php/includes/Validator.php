<?PHP
/*##############################################
	Validator Form Validation
	Version: 1.3.02222019
	Updated: 02/22/2019
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

/*
	required - true/false
		If true, the validator checks that the passed value is not empty
	
	number - true/false
		If true, the validator checks that the passed value is a number
		
	bool - true/false
		If true, the validator checks that the passed value (to string) is either '0' or '1'
		
	min_length - (numeric value)
		If set, the validator checks that the passed value is at least #min_length characters long
		
	max_length - (numeric value)
		If set, the validator checks that the passed value is no more than #max_length characters long
		
	min_value - (numeric value)
		If set, the validator checks that the passed value is no less than #min_value
		
	max_value - (numeric value)
		If set, the validator checks that the passed value is no more than #max_value
		
	email - true/false
		If true, the validator checks that the passed value is a valid email address format
		
	date - true/false
		If true, the validator checks that the passed value is a valid date
		
	date_to_epoch - true/false
		If true, the validator checks that the passed value is a valid date and coverts it to epoch
	
	ip - true/false
		If true, the validator checks that the passed value is a valid ip address format
		
	timezone - true/false
		If true, the validator checks that the passed value is a valid timezone
		
	url - true/false
		If true, the validator checks that the passed value is a valid url format
		
	mag - true/false
		If true, the validator checks that the passed value is a valid magnet link format
		
	urlmag - true/false
		If true, the validator checks that the passed value is a valid url or magnet link format
		
	values - (array of acceptable values)
	values_i - (array of acceptable values)[case-insensitive]
		If set, the validator checks that the passed value is in the array of acceptable values
		
	values_csv - (array of acceptable values - comma separated)
	values_csv_i - (array of acceptable values - comma separated)[case-insensitive]
		If set, the validator checks that the passed value is in the array of acceptable values
		
	not_values - (array of unacceptable values)
	not_values_i - (array of unacceptable values) [case-insensitive]
		If set, the validator checks that the passed value is not in the array of unacceptable values
		
	not_values_csv - (array of unacceptable values - comma separated)
	not_values_csv_i - (array of unacceptable values - comma separated)[case-insensitive]
		If set, the validator checks that the passed value is not in the array of unacceptable values
		
	match - (title)
		If set, the validator checks that the passed value matches the value of $data[title]
		
	no_spaces - true/false
		If set, the validator checks that the passed value does not contain spaces
		
	alphanumeric - true/false
		If set, the validator checks that the passed value is alphanumeric
		
	file - (options)
		image - true/false
			If set, the validator checks that all the files passed are images
		max_files - (numeric value)
			If set, the validator checks that the number of files passed are no more than #max_files
		max_file_size - (numeric value)
			If set, the validator checks that the size of files passed are no larger than #max_file_size
		max_total_size - (numeric value)
			If set, the validator checks that the total size of files passed is no larger than #max_total_size
	
EXAMPLE
	$Validator = new \Validator($data);
	$Validator->validate('id', array('required' => true, 'number' => true));
	$Validator->validate('artist', array('max_length' => 255));
	$Validator->validate('title', array('required' => true, 'max_length' => 255));
	$Validator->validate('song_id', array('required' => true));
	$Validator->validate('post_url', array('url' => true));
	$Validator->validate('type', array('required' => true, 'values' => array('YouTube', 'Soundcloud')));
	$Validator->validate('active', array('required' => true, 'bool' => true));
	$Validator->validate('posted', array('date_to_epoch' => true));
	$Validator->validate('info', array());
	
	// JSON is also acceptable instead of an array (useful for validating directly from a databse)
	// ex. $Validator->validate('id', '{"required":true,"number":true}');
	
	if(!$Validator->getResult()) {
		return array('result' => 'failure', 'validation' => $Validator->getOutput());
	}
	
	// Filters out anything passed in $data that was not put through the validator
	$form = $Validator->getForm();
*/

class Validator {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $result = true;
	private $output = array();
	private $data = null;
	private $form = array();

/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($data = null) {
		if($data != null) {
			$this->data = $data;
		}
	}
	
/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function _required($data, $value, $title) {
		if($value && empty($data) && (string)$data != '0') {
			$this->output[$title] = 'Input is missing or blank.';
			$this->result = false;
		}
	}
	
	private function _number($data, $value, $title) {
		if($value && (!is_int($data * 1) || !is_numeric($data)) && !empty($data)) {
			$this->output[$title] = 'Must be a numeric value.';
			$this->result = false;
		}
	}
	
	private function _bool($data, $value, $title) {
		$data = (string)$data;
		if($value && $data != '0' && $data != '1' && !empty($data)) {
			$this->output[$title] = 'Non-bool value in a bool-only field.';
			$this->result = false;
		}
	}
	
	private function _min_length($data, $value, $title) {
		if(strlen($data) < $value && !empty($data)) {
			$this->output[$title] = 'Must be at least ' . $value . ' characters long.';
			$this->result = false;
		}
	}
	
	private function _max_length($data, $value, $title) {
		if(strlen($data) > $value && !empty($data)) {
			$this->output[$title] = 'Must be no more than ' . $value . ' characters long.';
			$this->result = false;
		}
	}
	
	private function _min_value($data, $value, $title) {
		if($data < $value && strlen($data) > 0) {
			$this->output[$title] = 'Must not be less than ' . $value . '.';
			$this->result = false;
		}
	}
	
	private function _max_value($data, $value, $title) {
		if($data > $value && strlen($data) > 0) {
			$this->output[$title] = 'Must not be greater than ' . $value . '.';
			$this->result = false;
		}
	}
	
	private function _email($data, $value, $title) {
		if($value && !filter_var($data, FILTER_VALIDATE_EMAIL) && !empty($data)) {
			$this->output[$title] = 'Invalid email address.';
			$this->result = false;
		}
	}
	
	private function _date($data, $value, $title) {
		if($value && !strtotime($data) && !empty($data)) {
			$this->output[$title] = 'Invalid date.';
			$this->result = false;
		}
	}
	
	private function _date_to_epoch($data, $value, $title) {
		if($value && !strtotime($data) && !empty($data)) {
			$this->output[$title] = 'Invalid date.';
			$this->result = false;
		} else {
			if(isset($this->data[$title])) {
				$this->data[$title] = strtotime($data);
			}
		}
	}
	
	private function _ip($data, $value, $title) {
		if($value && !filter_var($data, FILTER_VALIDATE_IP) && !empty($data)) {
			$this->output[$title] = 'Invalid ip address.';
			$this->result = false;
		}
	}
	
	private function _timezone($data, $value, $title) {
		if($value && !in_array($data, DateTimeZone::listIdentifiers(DateTimeZone::ALL)) && !empty($data)) {
			$this->output[$title] = 'Invalid timezone.';
			$this->result = false;
		}
	}
	
	private function _url($data, $value, $title) {
		if($value && !filter_var($data, FILTER_VALIDATE_URL) && !empty($data)) {
			$this->output[$title] = 'Invalid url.';
			$this->result = false;
		}
	}
	
	private function _mag($data, $value, $title) {
		if($value && substr($data, 0, 8) != 'magnet:?' && !empty($data)) {
			$this->output[$title] = 'Invalid magnet.';
			$this->result = false;
		}
	}
	
	private function _urlmag($data, $value, $title) {
		if($value && !empty($data) && !filter_var($data, FILTER_VALIDATE_URL) && substr($data, 0, 8) != 'magnet:?') {
			$this->output[$title] = 'Invalid url/magnet.';
			$this->result = false;
		}
	}
	
	private function _values($data, $value, $title) {
		if($value && !empty($data) && !in_array($data, $value)) {
			$this->output[$title] = 'Invalid value.';
			$this->result = false;
		}
	}
	
	private function _values_i($data, $value, $title) {
		if($value && !empty($data) && !$this->in_arrayi($data, $value)) {
			$this->output[$title] = 'Invalid value.';
			$this->result = false;
		}
	}
	
	private function _values_csv($data, $value, $title) {
		if($value && !empty($data)) {
			foreach(explode(',', $data) as $v) {
				if(!in_array($v, $value)) {
					$this->output[$title] = 'Invalid value.';
					$this->result = false;
				}
			}
		}
	}
	
	private function _values_csv_i($data, $value, $title) {
		if($value && !empty($data)) {
			foreach(explode(',', $data) as $v) {
				if(!$this->in_arrayi($v, $value)) {
					$this->output[$title] = 'Invalid value.';
					$this->result = false;
				}
			}
		}
	}
	
	private function _not_values($data, $value, $title) {
		if($value && !empty($data) && in_array($data, $value)) {
			$this->output[$title] = 'Unacceptable value.';
			$this->result = false;
		}
	}
	
	private function _not_values_i($data, $value, $title) {
		if($value && !empty($data) && $this->in_arrayi($data, $value)) {
			$this->output[$title] = 'Unacceptable value.';
			$this->result = false;
		}
	}
	
	private function _not_values_csv($data, $value, $title) {
		if($value && !empty($data)) {
			foreach(explode(',', $data) as $v) {
				if(in_array($v, $value)) {
					$this->output[$title] = 'Unacceptable value.';
					$this->result = false;
				}
			}
		}
	}
	
	private function _not_values_csv_i($data, $value, $title) {
		if($value && !empty($data)) {
			foreach(explode(',', $data) as $v) {
				if($this->in_arrayi($v, $value)) {
					$this->output[$title] = 'Unacceptable value.';
					$this->result = false;
				}
			}
		}
	}
	
	private function _match($data, $value, $title) {
		if($value && !empty($data) && $data != $this->data[$value]) {
			$this->output[$title] = 'Values do not match.';
			$this->output[$value] = 'Values do not match.';
			$this->result = false;
		}
	}
	
	private function _no_spaces($data, $value, $title) {
		if($value && !empty($data) && strpos($data, ' ') !== false) {
			$this->output[$title] = 'Must not contain spaces.';
			$this->result = false;
		}
	}
	
	private function _alphanumeric($data, $value, $title) {
		if($value && !empty($data) && !ctype_alnum($data)) {
			$this->output[$title] = 'Must consist of only letters and numbers.';
			$this->result = false;
		}
	}
	
	private function _file($data, $value, $title) {
		$image_types = array(
			'image/png',
			'image/jpeg',
			'image/gif',
			'image/bmp'
		);
		if($value && !empty($data)) {
			$size = 0;
			if(isset($value['max_files']) && count($data) > $value['max_files']) {
				$this->output[$title] = 'Must select ' . $value['max_files'] . ' or fewer files.';
				$this->result = false;
				return false;
			}
			foreach($data as $v) {
				if(isset($value['image']) && $value['image'] && !in_array($v['type'], $image_types)) {
					$this->output[$title] = 'File is not a valid image type.';
					$this->result = false;
					return false;
				}
				if(isset($value['max_file_size']) && $v['size'] > $value['max_file_size']) {
					$this->output[$title] = 'File size must be ' . $this->formatBytes($value['max_file_size']) . ' or smaller.';
					$this->result = false;
					return false;
				}
				$size += $v['size'];
			}
			if(isset($value['max_total_size']) && $size > $value['max_total_size']) {
				$this->output[$title] = 'File sizes must total ' . $this->formatBytes($value['max_total_size']) . ' or smaller.';
				$this->result = false;
				return false;
			}
		}
	}
	
/*----------------------------------------------
	Utility Functions
----------------------------------------------*/
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
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	public function getResult() { return $this->result; }
	
	public function getOutput() { return $this->output; }
	
	public function getForm() { return $this->form; }
	
	public function validate($title = null, $validators = null, $data = null) {
		if($data == null && isset($this->data[$title])) { $data = $this->data[$title]; }
		if($data == null && $data != '') {
			$this->output['UNKNOWN'] = 'No data supplied.';
			$this->result = false;
			return $this->result;
		}
		
		if($validators == null) {
			//array_push($this->output, array('UNKNOWN' => 'No validators supplied.'));
			//$this->result = false;
			return $this->result;
		}
		
		if(!is_array($validators)) {
			$validators = json_decode($validators, true);
		}
		
		foreach($validators as $k => $v) {
			switch($k) {
				case 'required':
					$this->_required($data, $v, $title);
					break;
					
				case 'number':
					$this->_number($data, $v, $title);
					break;
					
				case 'bool':
					$this->_bool($data, $v, $title);
					break;
					
				case 'min_length':
					$this->_min_length($data, $v, $title);
					break;
					
				case 'max_length':
					$this->_max_length($data, $v, $title);
					break;
					
				case 'min_value':
					$this->_min_value($data, $v, $title);
					break;
					
				case 'max_value':
					$this->_max_value($data, $v, $title);
					break;
					
				case 'email':
					$this->_email($data, $v, $title);
					break;
					
				case 'date':
					$this->_date($data, $v, $title);
					break;
					
				case 'date_to_epoch':
					$this->_date_to_epoch($data, $v, $title);
					break;
					
				case 'ip':
					$this->_ip($data, $v, $title);
					break;
					
				case 'timezone':
					$this->_timezone($data, $v, $title);
					break;
					
				case 'url':
					$this->_url($data, $v, $title);
					break;
					
				case 'mag':
					$this->_mag($data, $v, $title);
					break;
					
				case 'urlmag':
					$this->_urlmag($data, $v, $title);
					break;
					
				case 'values':
					$this->_values($data, $v, $title);
					break;
					
				case 'values_i':
					$this->_values_i($data, $v, $title);
					break;
					
				case 'values_csv':
					$this->_values_csv($data, $v, $title);
					break;
					
				case 'values_csv_i':
					$this->_values_csv_i($data, $v, $title);
					break;
					
				case 'not_values':
					$this->_not_values($data, $v, $title);
					break;
					
				case 'not_values_i':
					$this->_not_values_i($data, $v, $title);
					break;
					
				case 'not_values_csv':
					$this->_not_values_csv($data, $v, $title);
					break;
					
				case 'not_values_csv_i':
					$this->_not_values_csv_i($data, $v, $title);
					break;
					
				case 'match':
					$this->_match($data, $v, $title);
					break;
					
				case 'no_spaces':
					$this->_no_spaces($data, $v, $title);
					break;
					
				case 'alphanumeric':
					$this->_alphanumeric($data, $v, $title);
					break;
					
				case 'file':
					$this->_file($data, $v, $title);
					break;
			}
		}
		
		if($this->result && isset($this->data[$title])) {
			if($this->data[$title] == '') { $this->data[$title] = null; }
			$this->form[$title] = $this->data[$title];
		}
		
		return $this->result;
	}
}
?>