<?PHP
/*##############################################
	Database Session Manager
	Version: 1.1.20171109
	Updated: 11/09/2017
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
// ini_set('display_errors',1);
// ini_set('display_startup_errors',1);
// error_reporting(-1);

class Session {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $cache = null;

/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($keep_alive = true) {
		$this->cont($keep_alive);
	}

/*----------------------------------------------
	Start
----------------------------------------------*/
	public function start($data) {
		// Start Session
		$GUID = $this->getGUID();
		session_id($GUID);
		ini_set('session.cookie_lifetime', ((60 * 60) * 24) * 100);
		ini_set('session.gc_maxlifetime', ((60 * 60) * 24) * 100);
		session_start();
		
		// Set Session Data
		$this->update($data);
	}
	
/*----------------------------------------------
	Continue
----------------------------------------------*/
	public function cont($keep_alive = true) {
		ini_set('session.cookie_lifetime', ((60 * 60) * 24) * 100);
		ini_set('session.gc_maxlifetime', ((60 * 60) * 24) * 100);
		session_start();
		
		if(!$this->active()) { $this->destroy(); return; }
		
		$this->cache = array(
			'_SESSION' => $_SESSION,
			'session_id' => session_id()
		);
		
		if($_SESSION['timeout'] > 0) {
			$time = time();
			if(($time - $_SESSION['last_action']) > $_SESSION['timeout']) {
			// Last action was longer than timeout allows
				$this->destroy();
				return;
			} else {
			// Last action was within the time allowed for timeout
				// Update last_action if keep_alive is TRUE
				if($keep_alive) { $this->keepAlive(); }
			}
		} else {
			// Session never expires
		}
		
		session_write_close();
	}
	
/*----------------------------------------------
	Destroy
----------------------------------------------*/
	public function destroy($logout = false) {
		if($logout) {
			ini_set('session.cookie_lifetime', ((60*60)*24)*100);
			ini_set('session.gc_maxlifetime', ((60*60)*24)*100);
			session_start();
		} else {
			$status = (session_status() == PHP_SESSION_ACTIVE);
			if(!$status) { session_start(); }
		}
		session_unset();
		session_destroy();
		session_write_close();
	}
	
/*----------------------------------------------
	Keep Alive
----------------------------------------------*/
	public function keepAlive() {
		$status = (session_status() == PHP_SESSION_ACTIVE);
		if(!$status) { session_start(); }
		
		$_SESSION['last_action'] = time();
		
		if(!$status) { session_write_close(); }
	}
	
/*----------------------------------------------
	Update
----------------------------------------------*/
	public function update($data) {
		$status = (session_status() == PHP_SESSION_ACTIVE);
		if(!$status) { session_start(); }
		
		$_SESSION = array_merge($_SESSION, $data);
		
		if(!$status) { session_write_close(); }
	}
	
/*----------------------------------------------
	Remove
----------------------------------------------*/
	public function remove($data) {
		if(empty($data)) { return false; }
		
		$status = (session_status() == PHP_SESSION_ACTIVE);
		if(!$status) { session_start(); }

		if(!is_array($data)) {
			unset($_SESSION[$data]);
		} else {
			foreach($data as $k => $v) {
				unset($_SESSION[$v]);
			}
		}
		
		if(!$status) { session_write_close(); }
	}
	
/*----------------------------------------------
	Active
----------------------------------------------*/
	public function active() {
		if(!isset($_SESSION['id']) || !isset($_SESSION['timeout']) || !isset($_SESSION['last_action'])) { return false; }
		if($_SESSION['timeout'] > 0) {
			if((time() - $_SESSION['last_action']) > $_SESSION['timeout']) {
				return false;
			}
		}
		return true;
	}

/*----------------------------------------------
	Guest
----------------------------------------------*/
	public function guest() {
		if(!isset($_SESSION['guest'])) { return true; }
		return $_SESSION['guest'];
	}

/*----------------------------------------------
	Get GUID
----------------------------------------------*/
	public function getGUID() {
		mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid, 12, 4).$hyphen
			.substr($charid, 16, 4).$hyphen
			.substr($charid, 20, 12);
		return $uuid;
	}

/*----------------------------------------------
	Get IP
----------------------------------------------*/
	public function getIP() {
	// Returns the IP of the Client
		if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

/*----------------------------------------------
	Parse User Agent
----------------------------------------------*/
/**
 * Parses a user agent string into its important parts
 *
 * @author Jesse G. Donat <donatj@gmail.com>
 * @link https://github.com/donatj/PhpUserAgent
 * @link http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
 * @param string|null $u_agent User agent string to parse or null. Uses $_SERVER['HTTP_USER_AGENT'] on NULL
 * @throws InvalidArgumentException on not having a proper user agent to parse.
 * @return string[] an array with browser, version and platform keys
 */
	public function parse_user_agent( $u_agent = null ) {
		if( is_null($u_agent) ) {
			if( isset($_SERVER['HTTP_USER_AGENT']) ) {
				$u_agent = $_SERVER['HTTP_USER_AGENT'];
			} else {
				throw new \InvalidArgumentException('parse_user_agent requires a user agent');
			}
		}

		$platform = null;
		$browser  = null;
		$version  = null;

		$empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );

		if( !$u_agent ) return $empty;

		if( preg_match('/\((.*?)\)/im', $u_agent, $parent_matches) ) {

			preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS)|Xbox(\ One)?)
					(?:\ [^;]*)?
					(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);

			$priority           = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'CrOS', 'Linux', 'X11' );
			$result['platform'] = array_unique($result['platform']);
			if( count($result['platform']) > 1 ) {
				if( $keys = array_intersect($priority, $result['platform']) ) {
					$platform = reset($keys);
				} else {
					$platform = $result['platform'][0];
				}
			} elseif( isset($result['platform'][0]) ) {
				$platform = $result['platform'][0];
			}
		}

		if( $platform == 'linux-gnu' || $platform == 'X11' ) {
			$platform = 'Linux';
		} elseif( $platform == 'CrOS' ) {
			$platform = 'Chrome OS';
		}

		preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|Safari|MSIE|Trident|AppleWebKit|TizenBrowser|Chrome|
					Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|
					Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
					Valve\ Steam\ Tenfoot|
					NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
					(?:\)?;?)
					(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
			$u_agent, $result, PREG_PATTERN_ORDER);

		// If nothing matched, return null (to avoid undefined index errors)
		if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
			if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
				return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
			}

			return $empty;
		}

		if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $u_agent, $rv_result) ) {
			$rv_result = $rv_result['version'];
		}

		$browser = $result['browser'][0];
		$version = $result['version'][0];

		$lowerBrowser = array_map('strtolower', $result['browser']);

		$find = function ( $search, &$key ) use ( $lowerBrowser ) {
			$xkey = array_search(strtolower($search), $lowerBrowser);
			if( $xkey !== false ) {
				$key = $xkey;

				return true;
			}

			return false;
		};

		$key  = 0;
		$ekey = 0;
		if( $browser == 'Iceweasel' ) {
			$browser = 'Firefox';
		} elseif( $find('Playstation Vita', $key) ) {
			$platform = 'PlayStation Vita';
			$browser  = 'Browser';
		} elseif( $find('Kindle Fire', $key) || $find('Silk', $key) ) {
			$browser  = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
			$platform = 'Kindle Fire';
			if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
				$version = $result['version'][array_search('Version', $result['browser'])];
			}
		} elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
			$browser = 'NintendoBrowser';
			$version = $result['version'][$key];
		} elseif( $find('Kindle', $key) ) {
			$browser  = $result['browser'][$key];
			$platform = 'Kindle';
			$version  = $result['version'][$key];
		} elseif( $find('OPR', $key) ) {
			$browser = 'Opera Next';
			$version = $result['version'][$key];
		} elseif( $find('Opera', $key) ) {
			$browser = 'Opera';
			$find('Version', $key);
			$version = $result['version'][$key];
		} elseif( $find('Midori', $key) ) {
			$browser = 'Midori';
			$version = $result['version'][$key];
		} elseif( $browser == 'MSIE' || ($rv_result && $find('Trident', $key)) || $find('Edge', $ekey) ) {
			$browser = 'MSIE';
			if( $find('IEMobile', $key) ) {
				$browser = 'IEMobile';
				$version = $result['version'][$key];
			} elseif( $ekey ) {
				$version = $result['version'][$ekey];
			} else {
				$version = $rv_result ?: $result['version'][$key];
			}

			if( version_compare($version, '12', '>=') ) {
				$browser = 'Edge';
			}
		} elseif( $find('Vivaldi', $key) ) {
			$browser = 'Vivaldi';
			$version = $result['version'][$key];
		} elseif( $find('Valve Steam Tenfoot', $key) ) {
			$browser = 'Valve Steam Tenfoot';
			$version = $result['version'][$key];
		} elseif( $find('Chrome', $key) || $find('CriOS', $key) ) {
			$browser = 'Chrome';
			$version = $result['version'][$key];
		} elseif( $browser == 'AppleWebKit' ) {
			if( ($platform == 'Android' && !($key = 0)) ) {
				$browser = 'Android Browser';
			} elseif( strpos($platform, 'BB') === 0 ) {
				$browser  = 'BlackBerry Browser';
				$platform = 'BlackBerry';
			} elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
				$browser = 'BlackBerry Browser';
			} elseif( $find('Safari', $key) ) {
				$browser = 'Safari';
			} elseif( $find('TizenBrowser', $key) ) {
				$browser = 'TizenBrowser';
			}

			$find('Version', $key);

			$version = $result['version'][$key];
		} elseif( $key = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser'])) ) {
			$key = reset($key);

			$platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
			$browser  = 'NetFront';
		}

		return array( 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null );
	}
}
?>