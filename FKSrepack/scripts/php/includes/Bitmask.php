<?PHP
// Load "bitmask.php" if it exists
if(is_file(__DIR__ . '/../config/bitmask.php')) {
	include(__DIR__ . '/../config/bitmask.php');
}

class Bitmask {
	private $mask = 0;
	
	public function __construct($m = null) {
        if($m) { $this->setMask($m); }
    }
	
	public function setMask($m = null){
	// Sets the mask value
		$this->mask = ($m == '*' ? self::MAX : $m);
	}
	
	public function getMask($m = null){
	// Returns the mask value
		return $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
	}
	
	public function addValue($value, $m = null) {
	// Adds a value to the mask (if it doesn't exist)
		$return = false;
		if($m) { $return = true; }
		$m = $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
		if($this->hasValue($value, $m)) {
			if($return) { return $m; }
		}
		$m += $value;
		if($return) { return $m; }
		$this->mask = $m;
	}
	
	public function removeValue($value, $m = null) {
	// Removes a value from the mask (if it exists)
		$return = false;
		if($m) { $return = true; }
		$m = $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
		if($this->hasValue($value, $m)) {
			$m -= $value;
			if($return) { return $m; }
			$this->mask = $m;
		}
		if($return) { return $m; }
	}
	
	public function mergeMasks($merge, $m = null) {
	// Merge masks together, adds missing values
		$return = false;
		if($m) { $return = true; }
		$merge = $merge == '*' ? self::MAX : $merge;
		$m = $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
		$new_mask = array_sum(array_unique(array_merge($this->bitArray($merge), $this->bitArray($m))));
		if($return) { return $new_mask; }
		$this->mask = $new_mask;
	}
	
	public function hasValue($value, $m = null) {
	// Returns true/false if a value exists in the mask
		$m = $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
		return (($m & $value) == $value);
	}
	
	public function bitArray($m = null) {
	// Returns an array of all values in the mask
		$m = $m ? ($m == '*' ? self::MAX : $m) : $this->mask;
		$bitArray = array_reverse(str_split(base_convert($m, 10, 2)));
		$return = array();
		foreach($bitArray as $k => $v) {
			if($v) { array_push($return, pow(2, $k)); }
		}
		return $return;
	}
}
?>