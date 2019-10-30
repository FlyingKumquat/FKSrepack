<?php
/*##############################################
	Google Two-Factor Authentication
	Version: 1.0.20191020
	Updated: 10/20/2019
##############################################*/

class GTFA {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/	
	private $length = 6;	// Code Length
	private $timeout = 30;	// Code Timeout (seconds)
	private $flex = 1;		// Code Flexibility
	private $map = array(
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',	//  7
		'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',	// 15
		'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',	// 23
		'Y', 'Z', '2', '3', '4', '5', '6', '7',	// 31
		'='	// padding char
	);
	
/*----------------------------------------------
	Base 32 Decode
----------------------------------------------*/
	protected function b32Decode($secret) {
		if(empty($secret)) { return ''; }

		$base32charsFlipped = array_flip($this->map);
		$paddingCharCount = substr_count($secret, $this->map[32]);
		$allowedValues = array(6, 4, 3, 1, 0);
		
		if(!in_array($paddingCharCount, $allowedValues)) { return false; }
		
		for($i = 0; $i < 4; $i++) {
			if($paddingCharCount == $allowedValues[$i] && substr($secret, -($allowedValues[$i])) != str_repeat($this->map[32], $allowedValues[$i])) { return false; }
		}
		
		$secret = str_replace('=', '', $secret);
		$secret = str_split($secret);
		$binaryString = '';
		
		for($i = 0; $i < count($secret); $i = $i + 8) {
			$x = '';
			
			if(!in_array($secret[$i], $this->map)) { return false; }
			
			for($j = 0; $j < 8; $j++) {
				$x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
			}
			
			$eightBits = str_split($x, 8);
			
			for($z = 0; $z < count($eightBits); $z++) {
				$binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
			}
		}
		
		return $binaryString;
    }
	
/*----------------------------------------------
	Base 32 Encode
----------------------------------------------*/
	protected function b32Encode($secret, $padding = true) {
		if(empty($secret)) { return ''; }

		$secret = str_split($secret);
		$binaryString = '';
		
		for($i = 0; $i < count($secret); $i++) {
			$binaryString .= str_pad(base_convert(ord($secret[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
		}
		
		$fiveBitBinaryArray = str_split($binaryString, 5);
		$base32 = '';
		$i = 0;
		
		while($i < count($fiveBitBinaryArray)) {
			$base32 .= $this->map[base_convert(str_pad($fiveBitBinaryArray[$i], 5, '0'), 2, 10)];
			$i++;
		}
		
		if($padding && ($x = strlen($binaryString) % 40) != 0) {
			if($x == 8) { $base32 .= str_repeat($this->map[32], 6); }
			elseif($x == 16) { $base32 .= str_repeat($this->map[32], 4); }
			elseif($x == 24) { $base32 .= str_repeat($this->map[32], 3); }
			elseif($x == 32) { $base32 .= $this->map[32]; }
		}
		
		return $base32;
	}	
	
/*----------------------------------------------
	Misc Functions
----------------------------------------------*/
	public function generateSecret($length = 16) {
		$chars = $this->map;
		unset($chars[32]);

		$secret = '';
		
		for($i = 0; $i < $length; $i++) {
			$secret .= $chars[array_rand($chars)];
		}
		
		return $secret;
	}
	
	public function getCode($secret, $timeSlice = null) {
		if($timeSlice === null) { $timeSlice = floor(time() / $this->timeout); }

		$time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
		$hm = hash_hmac('SHA1', $time, $this->b32Decode($secret), true);
		$offset = ord(substr($hm, -1)) & 0x0F;
		$hashpart = substr($hm, $offset, 4);

		$value = unpack('N', $hashpart);
		$value = $value[1];
		$value = $value & 0x7FFFFFFF;

		$modulo = pow(10, $this->length);
		
		return str_pad($value % $modulo, $this->length, '0', STR_PAD_LEFT);
	}
	
	public function verifyCode($secret, $code) {
		$currentTimeSlice = floor(time() / $this->timeout);

		for($i = -$this->flex; $i <= $this->flex; $i++) {
			$calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
			
			if($calculatedCode == $code) {
				return true;
			}
		}
		
		return false;
	}	
	
	public function qrURL($name, $secret, $size = '150x150') {
        $urlencoded = urlencode('otpauth://totp/' . $name . '?secret=' . $secret);
        return 'https://chart.googleapis.com/chart?chs=' . $size . '&chld=M|0&cht=qr&chl=' . $urlencoded;
    }
}
?>