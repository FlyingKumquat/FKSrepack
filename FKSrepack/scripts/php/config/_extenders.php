<?PHP namespace Extenders;
/***********************************************
	Custom PHP functions to run at key points
	- rename to "extenders.php" to use
***********************************************/

/*----------------------------------------------
	Triggers during the Login process
----------------------------------------------*/
class Login {
	// Before the Login process starts
	public function before(&$form) {
		return array(true, 'before success');
	}
	
	// After the Login process ends
	public function after(&$form) {
		return array(true, 'after success');
	}
}

/*----------------------------------------------
	Triggers during the Registration process
----------------------------------------------*/
class Register {
	// Before the Registration Validation process starts
	public function beforePreValidate(&$data) {
		return array(true, 'beforePreValidate success');
	}
	
	// During the Registration Validation process
	public function beforeMidValidate(&$Validator) {
		return array(true, 'beforeMidValidate success');
	}
	
	// After the Registration Validation process ends
	public function beforePostValidate(&$form) {
		return array(true, 'beforePostValidate success');
	}
	
	// After the Registration process ends
	public function after(&$form) {
		return array(true, 'after success');
	}
}

/*----------------------------------------------
	Triggers during the Logout process
----------------------------------------------*/
class Logout {
	// Before the Logout process starts
	public function before($type, $session_data) {
		return array(true, 'before success');
	}
	
	// After the Logout process ends
	public function after($type, $session_data) {
		return array(true, 'after success');
	}
}
?>