<?PHP
/*##############################################
	_soap Connection
	Version: 0.0.061016
	Updated: 06/10/2016
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

class _soap{
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $r;
	protected $soap;
	public $conn = array(
		'default' => 'wotlk',			// Default connection
		'wotlk' => array(
			'host' => '127.0.0.1', 		// Host Address
			'user' => 'user', 		// User's Name
			'pass' => 'pass', 		// User's Password
			'port' => '7878', 			// Connection Port
			'keep_alive' => false,		// Keep alive only works in php 5.4 or >
		)
	);
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($args = null){
		if(isset($args['conn'])){
			// Sets default connection
			$this->conn['default'] = $args['conn'];
		}
		
		if(isset($args['user'])){
			// Sets username
			$this->conn[$this->conn['default']]['user'] = $args['user'];
		}
		if(isset($args['pass'])){
			// Sets password
			$this->conn[$this->conn['default']]['pass'] = $args['pass'];
		}
		
		if( !$this->con( $this->conn['default'] ) ){ $this->r = 'Could not make connection';return false; }
	}
/*----------------------------------------------
	Destruct
----------------------------------------------*/
	public function __destruct(){
		
	}
/*----------------------------------------------
	Connect
----------------------------------------------*/
	public function con($conn){
		$this->soap = new SoapClient(NULL, Array(
            'location'=> 'http://'. $this->conn[$conn]['host'] .':'. $this->conn[$conn]['port'] .'/',
            'uri' => 'urn:TC',
            'style' => SOAP_RPC,
            'login' => $this->conn[$conn]['user'],
            'password' => $this->conn[$conn]['pass'],
            'keep_alive' => $this->conn[$conn]['keep_alive']
        ));
		return true;
	}
/*----------------------------------------------
	Send Command
----------------------------------------------*/	
	public function command($command)
    {
        $this->r = $this->soap->executeCommand(new SoapParam($command, 'command'));
        return true;
    }
}
?>