<?PHP
/***********************************************
	Custom Member Log parsing to use
	- rename to "log.php" to use
***********************************************/
/*----------------------------------------------
	Member Log (Extended)
----------------------------------------------*/
class MemberLogX {
	/*----------------------------------------------
		Parse Log Misc (Extended)
	----------------------------------------------*/
	public function parseLogMiscExtended($log) {
		$out = '';
		switch($log['action']) {
			case \Enums\LogActions::ENUM_NAME['id']:
				$out .= $this->parseEnum($out, $log);
				break;
		}
		return $out;
	}
	
	/*----------------------------------------------
		Parse Log Misc Detailed (Extended)
	----------------------------------------------*/
	public function parseLogMiscDetailedExtended($log) {
		$out = '';
		switch($log['action']) {
			case \Enums\LogActions::ENUM_NAME['id']:
				$out .= $this->parseEnumDetailed($out, $log);
				break;
		}
		return $out;
	}
	
	/*----------------------------------------------
		Enum Parse Function
	----------------------------------------------*/
	public function parseEnum($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$out .= '';
		
		return $out;
	}
	
	/*----------------------------------------------
		Enum Parse Detailed Function
	----------------------------------------------*/
	public function parseEnumDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$out .= '';
		
		return $out;
	}
}
?>