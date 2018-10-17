<?PHP
class DataTypes {
	private $Database = null;
	private $Enums = null;
	
	public $error = false;
	
	public function __construct() {
		require_once('Database.php');
		require_once('Enums.php');
		$this->Database = new \Database(array('persist' => true));
		$this->Enums = \Enums\DataTypes::flip();
	}
	
	public function getData($constants = array(), $member = 0) {
		// Return false if empty or not an array
		if(empty($constants) || !is_array($constants)) { return false; }
		
		$ids = array();
		
		foreach($constants as $k => $v) {
			if(!isset($v['id']) || !is_numeric($v['id']) || !in_array($v['id'], array_keys($this->Enums))) { return false; }
			array_push($ids, $v['id']);
		}

		if($this->Database->Q(array(
			'assoc' => 'id',
			'params' => array(
				':member' => $member
			),
			'query' => '
				SELECT
					id,
					data
				
				FROM
					fks_member_data
					
				WHERE
					id IN (' . implode(',', $ids) . ')
						AND
					member_id = :member
			'
		))) {
			/* OLD WAY
			if($this->Database->r['found'] == 0) { return false; }
			$out = array();
			foreach($this->Database->r['rows'] as $k => $v) {
				$out[$k] = $v['data'];
			}
			*/

			$out = array();
			$rows = $this->Database->r['rows'];
			foreach($ids as $id) {
				$out[$this->Enums[$id]] = (isset($rows[$id]) ? $rows[$id]['data'] : false);
			}
			//if(count($ids) == 1) { return reset($out); }
			
			return $out;
		}
		return false;
	}
	
	public function setData($values = array(), $member = 0, $server = false) {
		// Convert values to array if not an array
		if(!is_array($values)) { $values = array($values); }
		
		$this->error = false;
		foreach($values as $k => $v) { if(!is_numeric($k)) { return false; } }
		if(!$server && (!isset($_SESSION['guest']) || $_SESSION['guest'])) { return false; }
		foreach($values as $id => $data){
			$queryOK = false;
			if(empty($data)) {
				$queryOK = $this->Database->Q(array(
					'params' => array(
						':id' => $id,
						':member' => $member
					),
					'query' => '
						DELETE FROM
							fks_member_data
						
						WHERE
							id = :id
								AND
							member_id = :member
					'
				));
			} else {
				$queryOK = $this->Database->Q(array(
					'params' => array(
						':id' => $id,
						':member' => $member,
						':data' => $data
					),
					'query' => '
						INSERT INTO
							fks_member_data
						
						(id, member_id, data)
							VALUES
						(:id, :member, :data)
							
						ON DUPLICATE KEY UPDATE
							data = :data
					'
				));
			}
			if(
				$queryOK
				&& (
					!empty($data)
					|| (
						empty($data)
						&& $this->Database->r['row_count'] > 0
					)
				)
			) {
				$this->Database->Q(array(
					'params' => array(
						':id' => $member,
						':date_modified' => gmdate('Y-m-d H:i:s'),
						':modified_by' => ($server ? 0 : $_SESSION['id'])
					),
					'query' => '
						UPDATE
							fks_members
						
						SET
							date_modified = :date_modified,
							modified_by = :modified_by
							
						WHERE
							id = :id
					'
				));
			} else {
				$this->error = array(
					'queryOK' => $queryOK,
					'data' => $data,
					'row_count' => isset($this->Database->r['row_count']) ? $this->Database->r['row_count'] : null
				);
				if(!$queryOK) { return false; }
			}
		}
		return true;
	}
	
	// -------------------- Check Data -------------------- \\
	// Checks for unique data in Member Data
	// Created: 	11/24/2017 @ 1:35 AM
	// Modified: 	-
	public function checkData($values = array()) {
		// Return false if empty or not an array
		if(empty($values) || !is_array($values)) { return false; }
		
		// Loop through values and check for uniqueness
		foreach($values as $k => &$v) {
			if($this->Database->Q(array(
				'assoc' => 'id',
				'params' => array(
					':id' => $k,
					':data' => $v
				),
				'query' => '
					SELECT
						d.member_id
					
					FROM
						fks_member_data AS d
						
					INNER JOIN
						fks_members AS m
							ON
						d.member_id = m.id
						
					WHERE
						d.id = :id
							AND
						d.data = :data
							AND
						m.deleted = 0
				'
			))) {
				if($this->Database->r['found'] > 0) {
					// Set the member id of who has this data
					$v = $this->Database->r['row']['member_id'];
				} else {
					// Set to false if no one is using it
					$v = false;
				}
			} else {
				// Return false if the query failed
				return false;
			}
		}
		
		// Return the new values
		return $values;
	}
}
?>