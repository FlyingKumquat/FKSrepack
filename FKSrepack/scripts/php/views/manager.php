<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

require_once('functions.php');

class Manager extends CoreFunctions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $page;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		parent::__construct();

		// Check fks and site versions
		if(isset($_SESSION['site_settings'])) {
			$fks_version_session = $this->siteSettings('FKS_VERSION');
			$site_version_session = $this->siteSettings('SITE_VERSION');
			$fks_version_database = $this->siteSettings('FKS_VERSION', true);
			$site_version_database = $this->siteSettings('SITE_VERSION', true);
			if(($fks_version_session != $fks_version_database) || ($site_version_session != $site_version_database)) {
				$this->Session->destroy();
			}
		}
		
		if(!$this->Session->active()) {
			$this->Session->start(array(
				'id' => 0,
				'username' => 'Guest',
				'ip' => $this->Session->getIP(),
				'timeout' => 0,
				'last_action' => time(),
				'started' => gmdate('Y-m-d h:i:s'),
				'guest' => true
			));
		}
		$this->updateSessionAccess();
		
		$this->page = str_replace(array('/views/','.php'), array('',''), strtok($_SERVER['REQUEST_URI'],'?'));
		$this->navigate();
	}
	
/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function navigate() {
		if(
			$this->page != '/login'
			&& $this->page != '/views/403'
			&& $this->page != '/views/404'
			&& $this->page != '/views/500'
			&& $this->Session->guest()
			&& $this->siteSettings('REQUIRE_LOGIN') == 1
		) {
			if(
				$this->page == '/'
				|| $this->page == '/index'
			) {
				header('location: /login.php');
			} else {
				die('<span style="display: none;">Require Login</span>');
			}
		}
		
		if(
			$this->page == '/login'
			&& !$this->Session->guest()
		) {
			header('location: /');
		}
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	
}

$Manager = new Manager();
?>