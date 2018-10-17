<?PHP
/*##############################################
	Database Simplistic PDO Wrapper
	Version: 1.8.20180627
	Updated: 06/27/2018
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class Database {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	public $r;
	public $pdo;
	public $type;
	public $db;
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct($args = null) {
		
		// Load "connections.php" if it exists otherwise set default settings
		if(is_file(__DIR__ . '/../config/connections.php')) {
			include(__DIR__ . '/../config/connections.php');
			foreach($db as $k => $v) {
				$this->db[$k] = $v;
			}
		} else {
            // Note: These settings should not be changed!
            // Add your own connection to config/connections.php
            $this->db = array(
                'persist' => false,
                'default' => 'my_connection',
                'my_connection' => array(
                    'type' => 'mysql',
                    'host' => '127.0.0.1',
                    'user' => 'user_name',
                    'pass' => 'awesome-password',
                    'port' => '3306',
                    'name' => 'db_name',
                    'charset' => 'utf8'
                )
            );
        }
		
		if(isset($args['db'])) { $this->db['default'] = $args['db']; }
		else { $args['db'] = $this->db['default']; }
		$this->db['persist'] = isset($args['persist']) ? $args['persist'] : $this->db['persist'];
		if($this->db['persist']) {
			$this->con($args);
		}
	}
/*----------------------------------------------
	Destruct
----------------------------------------------*/
	public function __destruct() {
		$this->pdo = null;
	}
/*----------------------------------------------
	Connect
----------------------------------------------*/
	public function con($args = null) {
		if(!isset($args['db'])) { $args['db'] = $this->db['default']; }
		try {
			$this->type = $this->db[$args['db']]['type'];
			if($this->type == 'mysql') {
			// MySQL
				$this->pdo = new PDO(
					'mysql'
					. ':host=' . $this->db[$args['db']]['host']
					. ';port=' . $this->db[$args['db']]['port']
					. ';dbname=' . $this->db[$args['db']]['name']
					. (isset($this->db[$args['db']]['charset']) ? ';charset=' . $this->db[$args['db']]['charset'] : ''),
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'sqlsrv' || $this->type == 'mssql') {
			// MSSQL
				$this->pdo = new PDO(
					'sqlsrv'
					. ':Server=' . $this->db[$args['db']]['host']
					. ',' . $this->db[$args['db']]['port']
					. ';Database=' . $this->db[$args['db']]['name']
					. (isset($this->db[$args['db']]['charset']) ? ';charset=' . $this->db[$args['db']]['charset'] : ''),
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'sqlite') {
			// SQLite
				$this->pdo = new PDO(
					'sqlite:' . $this->db[$args['db']]['file'],
					null,
					null,
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			} else if($this->type == 'pgsql') {
			// PostgreSQL
				$this->pdo = new PDO(
					'pgsql'
					. ':host=' . $this->db[$args['db']]['host']
					. ';port=' . $this->db[$args['db']]['port']
					. ';dbname=' . $this->db[$args['db']]['name'],
					$this->db[$args['db']]['user'],
					$this->db[$args['db']]['pass'],
					array(PDO::ATTR_PERSISTENT => $this->db['persist'])
				);
			}
		} catch(PDOException $e) {
			$this->r['error'] = $e->getMessage();
			return false;
		}
		return true;
	}
/*----------------------------------------------
	Query Function
----------------------------------------------*/
	public function Q($args) {
	// Unset 'last_id' and 'row_count'
		unset($this->r['last_id']);
		unset($this->r['row_count']);
		
	// Adjust for straight query
		if(!is_array($args)) { $args = array('query' => $args); }
		
	// Adjust args for missing parameters
		$args['db'] = isset($args['db']) ? $args['db'] : $this->db['default'];
		$args['params'] = isset($args['params']) ? $args['params'] : array();
		$args['assoc'] = isset($args['assoc']) ? $args['assoc'] : false;
		$args['fetchAll'] = isset($args['fetchAll']) && is_long($args['fetchAll']) ? $args['fetchAll'] : PDO::FETCH_ASSOC;
	
	// Add order to query if it exists
		if(isset($args['order'])) {
			$order = '';
			$count = 0;
			foreach($args['order'] as $k => $v) {
				$v = strtoupper($v);
				if($v == 'ASC' || $v == 'DESC') {
					$order .= $count > 0 ? ',' : ' ORDER BY';
					$order .= ' ' . $k . ' ' . $v;
					$count++;
				}
			}
			$args['query'] .= $order;
		}
	
	// Clean up query string for friendly appearance
		$args['query'] = trim(preg_replace("/\s\s+/", " ", $args['query']));
	
	// Clear result for new data
		$this->r = array('args' => $args);
	
	// Open connection if not open/persistent
		if(!$this->db['persist']) {
			if(!$this->con($args)) { return false; }
		}
		
	// Reconnect if the persistent connection has gone away
		if($this->db['persist'] && !$this->ping()) {
			if(!$this->con($args)) { return false; }
		}

	// Bind the params and run the query
		try {
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $this->pdo->prepare($args['query']);
			$stmt->execute($args['params']);
		} catch(PDOException $e) {
			$this->r['error'] = $e->getMessage();
			return false;
		}
		
	// Explode query to find type
		$type = explode(' ', str_replace('(', '', $args['query']));
		switch($type[0]) {
			case 'SELECT':
			case 'SHOW':
			// Grab all data from query if SELECT
				$rows = $stmt->fetchAll($args['fetchAll']);
				$this->r['found'] = count($rows);
				$this->r['rows'] = $rows;
				$this->r['row'] = ($this->r['found'] > 0 ? $rows[0] : array());
				if($args['fetchAll'] == PDO::FETCH_ASSOC && $args['assoc']) {
					$this->assoc($args['assoc']);
				}
				break;
				
			case 'INSERT':
			// Set the 'last_id' if INSERT
				$this->r['last_id'] = $this->pdo->lastInsertId();
				break;
				
			case 'CREATE':
				break;
				
			case 'DELETE':
			// Set the 'row_count' if DELETE
				$this->r['row_count'] = $stmt->rowCount();
				break;
		}
	
	// Close connection if not persistent
		if(!$this->db['persist']) {
			$stmt = null;
			$this->pdo = null;
		}
	
	// At this point, everything was a success!
		return true;
	}
/*----------------------------------------------
	Associate Rows Function
----------------------------------------------*/
	public function assoc($column) {
		$rows = array();
		if(is_array($this->r['rows']) && isset($this->r['rows'][0][$column])) {
			foreach($this->r['rows'] as $r) {
				$rows[$r[$column]] = $r;
			}
			$this->r['rows'] = $rows;
		} else {
			return false;
		}
		return true;
    }
/*----------------------------------------------
	Ping Function
----------------------------------------------*/
	public function ping() {
		if(!isset($this->pdo)) { $this->con(); }
        try {
            return (bool) $this->pdo->query('SELECT 1+1');
        } catch(PDOException $e) {
            return false;
        }
    }
/*----------------------------------------------
	Table Exists Function
----------------------------------------------*/
	public function tableExists($table) {
        if($this->Q("SHOW TABLES LIKE '" . $table . "'")) {
			if($this->r['found'] > 0) {
				return true;
			}
		}
		return false;
	}
}
?>