<?PHP
/*##############################################
	Simple Crypter Class
	Version: 1.0.20171116
	Updated: 11/16/2017
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class Crypter {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $key = '3Z+.M]`5YhKm-`Q<';					// Default Key
	private $iv = '23460079704784079345634065872349';	// Default Initialization Vector

/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($key = false, $iv = false) {
		if($key) { $this->key = $key; }
		if($iv) { $this->iv = $iv; }
	}

/*----------------------------------------------
	RJ256
----------------------------------------------*/
	public function toRJ256($text) {
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$padding = $block - (strlen($text) % $block);
		$text .= str_repeat(chr($padding), $padding);

		$cryptText = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_CBC, $this->iv);
		$cryptText64 = base64_encode($cryptText);
		
		return $cryptText64;
	}
	
	public function fromRJ256($encrypted) {
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($encrypted), MCRYPT_MODE_CBC, $this->iv), "\x00..\x1F");
	}

/*----------------------------------------------
	Blowfish
----------------------------------------------*/
	public function toBlowfish($data){
		$e_time = base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $this->key, gmdate('Y-m-d H:i:s'), MCRYPT_MODE_ECB));
		$e_data = base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $this->key, $data, MCRYPT_MODE_ECB));
		$e_both = base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $this->key, $e_time . ',' . $e_data, MCRYPT_MODE_ECB));
		return $e_both;
	}
	
	public function fromBlowfish($e_both, $returnTime = false){
		$e_both = rtrim(mcrypt_decrypt(MCRYPT_BLOWFISH, $this->key, base64_decode($e_both), MCRYPT_MODE_ECB));
		$e_both = explode(',', $e_both);
		$e_time = rtrim(mcrypt_decrypt(MCRYPT_BLOWFISH, $this->key, base64_decode($e_both[0]), MCRYPT_MODE_ECB));
		$e_data = rtrim(mcrypt_decrypt(MCRYPT_BLOWFISH, $this->key, base64_decode($e_both[1]), MCRYPT_MODE_ECB));
		if($returnTime) {
			return array('data' => $e_data, 'time' => $e_time);
		}
		return $e_data;
	}
}
?>