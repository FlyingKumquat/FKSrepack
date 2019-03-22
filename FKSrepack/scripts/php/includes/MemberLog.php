<?PHP
/*----------------------------------------------------------------------------------------------------
	Debug / Error reporting
----------------------------------------------------------------------------------------------------*/
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

// Load "log.php" if it exists
if(is_file(__DIR__ . '/../config/log.php')) {
	include(__DIR__ . '/../config/log.php');
}

// Create class if it doesn't exist
if(!class_exists('MemberLogX'))	{ class MemberLogX {}; }

class MemberLog extends MemberLogX {
/*----------------------------------------------------------------------------------------------------
	Global Variables
----------------------------------------------------------------------------------------------------*/
	public $logCache = array();
	
/*----------------------------------------------------------------------------------------------------
	Construct
----------------------------------------------------------------------------------------------------*/
	public function __construct($action = null, $member_id = null, $target_id = null, $misc = null) {
		if(
			isset($action)
			&& isset($member_id)
		){
			$this->addMemberLog($action, $member_id, $target_id, $misc);
		}
	}

/*----------------------------------------------------------------------------------------------------
	Private Functions
----------------------------------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------------------------------
	Public Functions
----------------------------------------------------------------------------------------------------*/
	
	/*----------------------------------------------------------------------------------------------------
		Add Member Log
	----------------------------------------------------------------------------------------------------*/
	/*
		addMemberLog(
			action,
			member_id,
			target_id,
			misc
		);
	*/
	public function addMemberLog($action, $member_id, $target_id = null, $misc = null) {
		$Database = new \Database();
			
		if(!$Database->Q(array(
			'params' => array(
				':action' => (is_array($action) ? $action['id'] : $action),
				':member_id' => $member_id,
				':target_id' => $target_id,
				':misc' => $misc,
				':date_created' => gmdate('Y-m-d H:i:s')
			),
			'query' => '
				INSERT INTO
					fks_member_logs
				
				SET
					action = :action,
					member_id = :member_id,
					target_id = :target_id,
					misc = :misc,
					date_created = :date_created
			'
		))) {
			return false;
		} else {
			return true;
		}
	}
	
	/*----------------------------------------------------------------------------------------------------
		Grab Member Log
	----------------------------------------------------------------------------------------------------*/
	/*
		### EXAMPLE ###
		Grab all logs for member id 1
		grabMemberLogs(array(
			'member_id' => 1
		));
		
		### EXAMPLE ###
		Grab all LOGIN_FAILURE logs for members 1 and 2
		grabMemberLogs(array(
			'action' => \Enums\LogActions::LOGIN_FAILURE['id']
			'member_id' => array(
				1,
				2
			)
		));
		
		### EXAMPLE ###
		Grab all MAIL_GRAB and MAIL_RETURN logs for target id '012e372bebb1be8eb10a505a4a18c614' and order desc
		grabMemberLogs(array(
			'action' => array(
				\Enums\LogActions::MAIL_GRAB['id'],
				\Enums\LogActions::MAIL_RETURN['id']
			),
			'target_id' => '012e372bebb1be8eb10a505a4a18c614',
			'also' => 'ORDER BY created DESC'
		));
	*/
	public function grabMemberLogs($params) {
		$Database = new \Database();
		
		if(isset($params['action']) && is_array($params['action'])) {
			if(isset($params['action']['id'])) {
				$params['action'] = $params['action']['id'];
			} else {
				foreach($params['action'] as $k => $v) {
					if(is_array($v) && isset($v['id'])) {
						$params['action'][$k] = $v['id'];
					}
				}
			}
		}
		
		$options = array(
			'id',
			'action',
			'member_id',
			'target_id'
		);
		
		$error = false;
		$query_params = array();
		$conditions = array();
		
		foreach($options as $o) {
			if($error) { break; }
			if(isset($params[$o])) {
				if(is_array($params[$o])) {
				// is an array, create condition
					foreach($params[$o] as $v){
						if(!is_int($v)) {
							$error = array('result' => 'failure', 'message' => 'Non-number found in ' . $o);
							break;
						}
					}
					array_push($conditions, 'ml.' . $o . ' IN ("' . implode('", "', $params[$o]) . '")');
				} else {
				// is not an array, create query parameter and condition
					$query_params[':' . $o] = $params[$o];
					array_push($conditions, 'ml.' . $o . ' = :' . $o);
				}
			}
		}
		if($error) { return $error; }
		
		$db_type = $Database->db[$Database->db['default']]['type'];
		
		$args = array( 
			'params' => $query_params, 
			'query' => '
				SELECT' . (isset($params['limit']) && ($db_type == 'sqlsrv' || $db_type == 'mssql') ? ' TOP ' . $params['limit'] : '') . '
					ml.*,
					m.username AS username
					
				FROM
					fks_member_logs AS ml
					
				LEFT OUTER JOIN
					fks_members AS m
						ON
					ml.member_id = m.id
				
				WHERE
					' . implode(' AND ', $conditions)
			. (isset($params['also']) ? ' ' . $params['also'] : '')
			. (isset($params['limit']) && $db_type == 'mysql' ? ' LIMIT ' . $params['limit'] : '')
		);

		if($Database->Q($args)) {
			$return = $Database->r['rows'];
			foreach($return as $k => $v) {
				//$return[$k]['created_formatted'] = date('m/d/Y h:i A', $v['created']);
				$return[$k]['action_title'] = \Enums\LogActions::title($v['action']);
				$return[$k]['misc_formatted'] = $this->parseLogMisc(array('action' => $v['action'], 'misc' => $v['misc'], 'full' => $v));
				$return[$k]['misc_formatted_detailed'] = $this->parseLogMiscDetailed(array('action' => $v['action'], 'misc' => $v['misc'], 'full' => $v));
			}
		} else {
			return array('result' => 'failure', 'message' => 'Failed to load logs from DB.');
		}
		
		return array('result' => 'success', 'history' => $return);
	}
	
	/*----------------------------------------------------------------------------------------------------
		Parse Log Misc // Type Formatter
	----------------------------------------------------------------------------------------------------*/
	public function formatType($cloumn, $misc) {
		$out = '<div class="history-title">' . $cloumn['title'] . '</div>';
		$out .= '<div class="fks-blockquote">';
		switch($cloumn['type']) {
			case 'code':
				if(is_array($misc)) {
					if($misc[0] == null) { $out .= 'Set to <span class="fks-text-success">' . htmlentities($misc[1]) . '</span>.'; }
					else { $out .= 'Changed from <span class="fks-text-success">' . htmlentities($misc[0]) . '</span> to ' . (!empty($misc[1]) ? '<span class="fks-text-success">' . htmlentities($misc[1]) . '</span>' : 'blank') . '.'; }
				} else {
					$out .= 'Set to ' . ($misc == null ? 'none' : '<span class="fks-text-success">' . htmlentities($misc) . '</span>') . '.';
				}
				break;
				
			case 'date':
				if(is_array($misc)) {
					if($misc[0] == null) { $out .= 'Set to <span class="fks-text-success">' . date('M j, Y', $misc[1]) . '</span>.'; }
					else { $out .= 'Changed from <span class="fks-text-success">' . date('M j, Y', $misc[0]) . '</span> to <span class="fks-text-success">' . date('M j, Y', $misc[1]) . '</span>.'; }
				} else {
					$out .= 'Set to ' . ($misc == null ? 'never' : '<span class="fks-text-success">' . date('M j, Y', $misc) . '</span>') . '.';
				}
				break;
				
			case 'icon':
				if(is_array($misc)) {
					if($misc[0] == null){ $out .= 'Set to <span class="fks-text-success"><i class="fks-text-info fa fa-' . $misc[1] . ' fa-fw"></i> ' . $misc[1] . '</span>.'; }
					else{
						if($misc[1] != null) {
							$out .= 'Changed from <span class="fks-text-success"><i class="fks-text-info fa fa-' . $misc[0] . ' fa-fw"></i> ' . $misc[0] . '</span> to <span class="fks-text-success"><i class="fks-text-info fa fa-' . $misc[1] . ' fa-fw"></i> ' . $misc[1] . '</span>.';
						} else {
							$out .= 'Changed from <span class="fks-text-success"><i class="fks-text-info fa fa-' . $misc[0] . ' fa-fw"></i> ' . $misc[0] . '</span> to none.';
						}
					}
				} else { $out .= 'Set to <span class="fks-text-success"><i class="fks-text-info fa fa-' . $misc . ' fa-fw"></i> ' . $misc . '</span>.'; }
				break;
				
			case 'values':
			case 'bool':
				if(is_array($misc)) {
					if($misc[0] == null) { $out .= 'Set to <span class="fks-text-success">' . $cloumn['values'][$misc[1]] . '</span>.'; }
					else { $out .= 'Changed from <span class="fks-text-success">' . $cloumn['values'][$misc[0]] . '</span> to <span class="fks-text-success">' . $cloumn['values'][$misc[1]] . '</span>.'; }
				} else { $out .= 'Set to <span class="fks-text-success">' . $cloumn['values'][$misc] . '</span>.'; }
				break;
				
			case 'csv':
				if(is_array($misc)) {
					if($misc[0] == null) {
						$exploded = explode(',', $misc[1]);
						foreach($exploded as $k => $v) { $exploded[$k] = $cloumn['values'][$v]; }
						$out .= 'Set to <span class="fks-text-success">' . implode(', ', $exploded) . '</span>.';
					} else {
						if(empty($misc[1])) { $misc[1] = 0; }
						$exploded = array(
							explode(',', $misc[0]),
							explode(',', $misc[1])
						);
						foreach($exploded[0] as $k => $v) { $exploded[0][$k] = $cloumn['values'][$v]; }
						foreach($exploded[1] as $k => $v) { $exploded[1][$k] = $cloumn['values'][$v]; }
						$out .= 'Changed from <span class="fks-text-success">' . implode(', ', $exploded[0]) . '</span> to <span class="fks-text-success">' . implode(', ', $exploded[1]) . '</span>.';
					}
				} else {
					if(empty($misc)) { $misc = 0; }
					$exploded = explode(',', $misc);
					foreach($exploded as $k => $v) { $exploded[$k] = $cloumn['values'][$v]; }
					$out .= 'Set to <span class="fks-text-success">' . implode(', ', $exploded) . '</span>.';
				}
				break;

			default:
				if(is_array($misc)) {
					if($misc[0] == null) { $out .= 'Set to <span class="fks-text-success">' . $misc[1] . '</span>.'; }
					else { $out .= 'Changed from <span class="fks-text-success">' . $misc[0] . '</span> to ' . (!empty($misc[1]) ? '<span class="fks-text-success">' . $misc[1] . '</span>' : 'blank') . '.'; }
				} else {
					$out .= 'Set to ' . ($misc == null ? 'none' : '<span class="fks-text-success">' . $misc . '</span>') . '.';
				}
				break;
		}
		$out .= '</div>';
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Parse Log Misc
	----------------------------------------------------------------------------------------------------*/
	public function parseLogMisc($log) {
		$out = '';
		switch($log['action']) {
			case \Enums\LogActions::LOGIN['id']:
			case \Enums\LogActions::LOGIN_FAILURE['id']:
			case \Enums\LogActions::LOGIN_FAILURE_VERIFICATION['id']:
			case \Enums\LogActions::LOGOUT_MANUAL['id']:
			case \Enums\LogActions::LOGOUT_INACTIVE['id']:
			case \Enums\LogActions::LOGOUT_UNKNOWN['id']:
				$out .= $this->parseMemberLoginLogout($out, $log);
				break;
				
			case \Enums\LogActions::MEMBER_CREATED['id']:
			case \Enums\LogActions::MEMBER_MODIFIED['id']:
				$out .= $this->parseMemberCreateModify($out, $log, true);
				break;
				
			case \Enums\LogActions::MENU_CREATED['id']:
			case \Enums\LogActions::MENU_MODIFIED['id']:
				$out .= $this->basicParse($out, $log);
				break;
				
			case \Enums\LogActions::MENU_ITEM_CREATED['id']:
			case \Enums\LogActions::MENU_ITEM_MODIFIED['id']:
				$out .= $this->basicParse($out, $log);
				break;
				
			case \Enums\LogActions::ACCESS_GROUP_CREATED['id']:
			case \Enums\LogActions::ACCESS_GROUP_MODIFIED['id']:
				$out .= $this->parseAccessGroupCreateModify($out, $log);
				break;
				
			case \Enums\LogActions::SITE_SETTINGS_MODIFIED['id']:
				$out .= $this->parseSiteSettingsModify($out, $log, true);
				break;
				
			case \Enums\LogActions::MENU_ITEM_PAGES_CREATED['id']:
				$out .= $this->parseMenuItemPagesCreated($out, $log, true);
				break;
				
			case \Enums\LogActions::ANNOUNCEMENT_CREATED['id']:
			case \Enums\LogActions::ANNOUNCEMENT_MODIFIED['id']:
				$out .= $this->basicParse($out, $log);
				break;
				
			case \Enums\LogActions::CHANGELOG_CREATED['id']:
			case \Enums\LogActions::CHANGELOG_MODIFIED['id']:
				$out .= $this->basicParse($out, $log);
				break;
				
			case \Enums\LogActions::CHANGELOG_NOTE_CREATED['id']:
			case \Enums\LogActions::CHANGELOG_NOTE_MODIFIED['id']:
			case \Enums\LogActions::CHANGELOG_NOTE_DELETED['id']:
				$out .= $this->parseChangelogNoteCreateModifyDelete($out, $log);
				break;
				
			case \Enums\LogActions::SITE_ERROR_DELETED['id']:
				$out .= $this->parseSiteErrorDeleted($out, $log, true);
				break;
				
			default:
				if(method_exists($this, 'parseLogMiscExtended')) {
					$out .= $this->parseLogMiscExtended($log);
				}
				break;
		}
		return $out == '' ? htmlentities($log['full']['misc']) : $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Parse Log Misc Detailed
	----------------------------------------------------------------------------------------------------*/
	public function parseLogMiscDetailed($log) {
		$out = '';
		switch($log['action']) {
			case \Enums\LogActions::LOGIN['id']:
			case \Enums\LogActions::LOGIN_FAILURE['id']:
			case \Enums\LogActions::LOGIN_FAILURE_VERIFICATION['id']:
			case \Enums\LogActions::LOGOUT_MANUAL['id']:
			case \Enums\LogActions::LOGOUT_INACTIVE['id']:
			case \Enums\LogActions::LOGOUT_UNKNOWN['id']:
				$out .= $this->parseMemberLoginLogoutDetailed($out, $log);
				break;
				
			case \Enums\LogActions::MEMBER_CREATED['id']:
			case \Enums\LogActions::MEMBER_MODIFIED['id']:
				$out .= $this->parseMemberCreateModify($out, $log);
				break;
				
			case \Enums\LogActions::MENU_CREATED['id']:
			case \Enums\LogActions::MENU_MODIFIED['id']:
				$out .= $this->parseMenuCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::MENU_ITEM_CREATED['id']:
			case \Enums\LogActions::MENU_ITEM_MODIFIED['id']:
				$out .= $this->parseMenuItemCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::ACCESS_GROUP_CREATED['id']:
			case \Enums\LogActions::ACCESS_GROUP_MODIFIED['id']:
				$out .= $this->parseAccessGroupCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::SITE_SETTINGS_MODIFIED['id']:
				$out .= $this->parseSiteSettingsModify($out, $log);
				break;
				
			case \Enums\LogActions::MENU_ITEM_PAGES_CREATED['id']:
				$out .= $this->parseMenuItemPagesCreated($out, $log);
				break;
				
			case \Enums\LogActions::ANNOUNCEMENT_CREATED['id']:
			case \Enums\LogActions::ANNOUNCEMENT_MODIFIED['id']:
				$out .= $this->parseAnnouncementCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::CHANGELOG_CREATED['id']:
			case \Enums\LogActions::CHANGELOG_MODIFIED['id']:
				$out .= $this->parseChangelogCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::CHANGELOG_NOTE_CREATED['id']:
			case \Enums\LogActions::CHANGELOG_NOTE_MODIFIED['id']:
				$out .= $this->parseChangelogNoteCreateModifyDetailed($out, $log);
				break;
				
			case \Enums\LogActions::CHANGELOG_NOTE_DELETED['id']:
				$out .= $this->parseChangelogNoteDeleteDetailed($out, $log);
				break;
				
			case \Enums\LogActions::SITE_ERROR_DELETED['id']:
				$out .= $this->parseSiteErrorDeleted($out, $log);
				break;
				
			default:
				if(method_exists($this, 'parseLogMiscDetailedExtended')) {
					$out .= $this->parseLogMiscDetailedExtended($log);
				}
				break;
		}
		return $out == '' ? htmlentities($log['full']['misc']) : $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Cache Keeper
	----------------------------------------------------------------------------------------------------*/
	public function cacheKeeper($get) {
		if(!isset($this->logCache['database'])) { $this->logCache['database'] = new \Database(); }
		if(!isset($this->logCache['queries'])) { $this->logCache['queries'] = array(); }
		
		if(!isset($this->logCache['queries'][$get])) {
			$this->logCache['queries'][$get] = array();
			if(!$this->logCache['database']->Q($get)) {
				return false;
			}
			$this->logCache['database']->assoc('id');
			$this->logCache['queries'][$get] = $this->logCache['database']->r['rows'];
			return true;
		} else {
			return true;
		}
	}
	
	/*----------------------------------------------------------------------------------------------------
		Basic Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function basicParse($out, $log) {
		$misc = json_decode($log['misc'], true);
		if(!is_array($misc)) { return $out; }

		$columns = array(
			'username'			=>		'Username',
			'type'				=>		'Type',
			'title'				=>		'Title',
			'version'			=>		'Version',
			'notes'				=>		'Notes',
			'menu_id'			=>		'Menu',
			'parent_id'			=>		'Parent',
			'pos'				=>		'Position',
			'title_data'		=>		'Title Data',
			'has_separator'		=>		'Separator',
			'is_external'		=>		'External Link',
			'is_parent'			=>		'Has Children',
			'has_content'		=>		'Has Content',
			'url'				=>		'URL',
			'icon'				=>		'Icon',
			'label'				=>		'Label',
			'hierarchy'			=>		'Hierarchy',
			'announcement'		=>		'Announcement',
			'sticky'			=>		'Sticky',
			'pages'				=>		'Page Access',
			'access_groups'		=>		'Access Groups',
			'active'			=>		'Status',
			'hidden'			=>		'Hidden',
			'deleted'			=>		'Deleted'
		);

		
		$build = array();
		
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){ array_push($build, $columns[$k]); }
			else { array_push($build, 'UKNOWN: ' . $k); }
		}
		
		$out .= implode(', ', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Member Login/Logout Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMemberLoginLogout($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$out .= $misc['ip_address'];
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Member Login/Logout Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMemberLoginLogoutDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$out .= '
			<div class="history-title">Platform</div>
			<div class="fks-blockquote">' . $misc['platform'] . '</div>
			
			<div class="history-title">Browser</div>
			<div class="fks-blockquote">' . $misc['browser'] . ' v' . $misc['version'] . '</div>
			
			<div class="history-title">IP Address</div>
			<div class="fks-blockquote">' . $misc['ip_address'] . '</div>
		';
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Member Create/Modify Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMemberCreateModify($out, $log, $simple = false) {
		$misc = json_decode($log['misc'], true);
		
		$data_types = \Enums\DataTypes::flip();
		
		$access_groups = array();
		if(isset($misc[\Enums\DataTypes::ACCESS_GROUPS['id']])) {
			$get = 'SELECT * FROM fks_access_groups';
			if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 2'; }
			$access_groups = $this->logCache['queries'][$get];
			foreach($access_groups as $k => $v) { $access_groups[$k] = $v['title']; }
			$access_groups[0] = '<i>none</i>';
		}
		
		$columns = array(
			'username' => array(
				'title' => 'Username',
				'type' => 'string'
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			),
			'deleted' => array(
				'title' => 'Deleted',
				'type' => 'bool',
				'values' => array(
					0 => 'Not Deleted',
					1 => 'Deleted'
				)
			)
		);
		
		$build = array();
		
		// Make simple
		if($simple) {
			foreach($misc as $k => $v) {
				$temp = '';
				if($k == 'username') { $temp = 'Username'; }
				if($k == 'active') { $temp = 'Status'; }
				if($k == 'deleted') { $temp = 'Deleted'; }
				if(isset($data_types[$k])) {
					$temp = constant("\Enums\DataTypes::$data_types[$k]")['title'];
				}
				if(empty($temp)) { $temp = 'UNKNOWN: ' . $k; }
				array_push($build, $temp);
			}
			$out .= implode(', ', $build);
			return $out;
		}
		
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
			if(isset($data_types[$k])) {
				// Don't show password, silly
				if($k == \Enums\DataTypes::PASSWORD['id']) {
					array_push($build, '
						<div class="history-title">Password</div>
						<div class="fks-blockquote">' . (is_array($v) ? 'Changed' : 'Set') . '.</div>
					');
					continue;
				}
				
				$format = array(
					'title' => constant("\Enums\DataTypes::$data_types[$k]")['title'],
					'type' => constant("\Enums\DataTypes::$data_types[$k]")['input_type']
				);
				
				if($k == \Enums\DataTypes::ACCESS_GROUPS['id']) {
					$format['type'] = 'csv';
					$format['values'] = $access_groups;
				}
				
				array_push($build, $this->formatType($format, $v));
				continue;
			}
		}
		
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Menu Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMenuCreateModifyDetailed($out, $log) {		
		$misc = json_decode($log['misc'], true);
		
		$columns = array(
			'title' => array(
				'title' => 'Title',
				'type' => 'string'
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			),
			'deleted' => array(
				'title' => 'Deleted',
				'type' => 'bool',
				'values' => array(
					0 => 'Not Deleted',
					1 => 'Deleted'
				)
			)
		);
		
		$build = array();
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Menu Item Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMenuItemCreateModifyDetailed($out, $log) {		
		$misc = json_decode($log['misc'], true);
		
		$menus = array();
		$get = 'SELECT id, title FROM fks_menus';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$menus = $this->logCache['queries'][$get];
		foreach($menus as $k => $v) { $menus[$k] = $v['title']; }
		$menus[0] = '<i>none</i>';
		
		$menu_items = array();
		$get = 'SELECT id, title FROM fks_menu_items';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 2'; }
		$menu_items = $this->logCache['queries'][$get];
		foreach($menu_items as $k => $v) { $menu_items[$k] = $v['title']; }
		$menu_items[0] = '<i>none</i>';
		
		$columns = array(
			'menu_id' => array(
				'title' => 'Menu',
				'type' => 'values',
				'values' => $menus
			),
			'parent_id' => array(
				'title' => 'Parent',
				'type' => 'values',
				'values' => $menu_items
			),
			'pos' => array(
				'title' => 'Position',
				'type' => 'number'
			),
			'title' => array(
				'title' => 'Title',
				'type' => 'string'
			),
			'title_data' => array(
				'title' => 'Title Data',
				'type' => 'string'
			),
			'has_separator' => array(
				'title' => 'Separator',
				'type' => 'values',
				'values' => array(
					0 => 'Disabled',
					1 => 'Before',
					2 => 'After',
					3 => 'Before & After'
				)
			),
			'is_external' => array(
				'title' => 'External Link',
				'type' => 'bool',
				'values' => array(
					0 => 'No',
					1 => 'Yes'
				)
			),
			'is_parent' => array(
				'title' => 'Has Children',
				'type' => 'bool',
				'values' => array(
					0 => 'No',
					1 => 'Yes'
				)
			),
			'has_content' => array(
				'title' => 'Has Content',
				'type' => 'bool',
				'values' => array(
					0 => 'No',
					1 => 'Yes'
				)
			),
			'url' => array(
				'title' => 'URL',
				'type' => 'string'
			),
			'icon' => array(
				'title' => 'Icon',
				'type' => 'icon'
			),
			'label' => array(
				'title' => 'Label',
				'type' => 'string'
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			),
			'hidden' => array(
				'title' => 'Display',
				'type' => 'bool',
				'values' => array(
					0 => 'Visible',
					1 => 'Hidden'
				)
			),
			'deleted' => array(
				'title' => 'Deleted',
				'type' => 'bool',
				'values' => array(
					0 => 'Not Deleted',
					1 => 'Deleted'
				)
			)
		);
		
		$build = array();
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Access Group Create/Modify Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseAccessGroupCreateModify($out, $log) {
		$misc = json_decode($log['misc'], true);
		if(!is_array($misc)) { return $out; }

		$columns = array(
			'title'				=>		'Title',
			'data'				=>		'Menu Access',
			'hierarchy'			=>		'Hierarchy',
			'active'			=>		'Status'
		);

		$build = array();
		
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){ array_push($build, $columns[$k]); }
		}
		
		$out .= implode(', ', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Access Group Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseAccessGroupCreateModifyDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$menu_items = array();
		$get = 'SELECT id, title FROM fks_menu_items';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$menu_items = $this->logCache['queries'][$get];
		foreach($menu_items as $k => $v) { $menu_items[$k] = $v['title']; }
		$menu_items[0] = '<i>none</i>';
		
		$access_types = array(
			0 => 'None',
			1 => 'Read',
			2 => 'Write',
			3 => 'Admin',
		);
		
		$columns = array(
			'title' => array(
				'title' => 'Title',
				'type' => 'string'
			),
			'hierarchy' => array(
				'title' => 'Hierarchy',
				'type' => 'number'
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			),
			'deleted' => array(
				'title' => 'Deleted',
				'type' => 'bool',
				'values' => array(
					0 => 'Not Deleted',
					1 => 'Deleted'
				)
			)
		);
		
		$build = array();
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
			if($k == 'data'){
				$tmp = '<div class="history-title">Menu Access</div>';
				$tmp .= '<div class="fks-blockquote">';
				$build_tmp = array();
				foreach($v as $ck => $cv) {
					if(is_array($cv)) {
						if($cv[0] == null) {
							array_push($build_tmp, '<span class="fks-text-info">' . $menu_items[$ck] . '</span> set to <span class="fks-text-success">' . $access_types[$cv[1]] . '</span>.');
						} else {							
							array_push($build_tmp, '<span class="fks-text-info">' . $menu_items[$ck] . '</span> changed from <span class="fks-text-success">' . $access_types[$cv[0]] . '</span> to <span class="fks-text-success">' . $access_types[$cv[1]] . '</span>.');
						}
					} else {
						array_push($build_tmp, '<span class="fks-text-info">' . $menu_items[$ck] . '</span> set to <span class="fks-text-success">' . $access_types[$cv] . '</span>.');
					}
				}
				$tmp .= implode('</br>', $build_tmp);
				$tmp .= '</div>';
				array_push($build, $tmp);
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Site Settings Modify Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseSiteSettingsModify($out, $log, $simple = false) {
		$misc = json_decode($log['misc'], true);
		
		$access_groups = array();
		$get = 'SELECT * FROM fks_access_groups';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$access_groups = $this->logCache['queries'][$get];
		foreach($access_groups as $k => $v) { $access_groups[$k] = $v['title']; }
		$access_groups[0] = '<i>none</i>';
		
		$site_settings = array();
		$get = 'SELECT id, title, type FROM fks_site_settings';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 2'; }
		$_ss = $this->logCache['queries'][$get];
		foreach($_ss as $v) {
			$site_settings[$v['id']] = $v;
		}
		
		$menu_items = array();
		$get = 'SELECT id, parent_id, url FROM fks_menu_items';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 3'; }
		$_mi = $this->logCache['queries'][$get];
		$menu_items[0] = '<i>none</i>';
		
		foreach($_mi as $k => $v) {			
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $_mi[$v['parent_id']]['url']);
				if($_mi[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $_mi[$_mi[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$menu_items[$k] = implode('/', $url);
		}
		
		$build = array();
		
		// Make simple
		if($simple) {
			foreach($misc as $k => $v) {
				if(isset($site_settings[$k])) { array_push($build, $site_settings[$k]['title']); }
				else { array_push($build, 'UKNOWN: ' . $k); }
			}
			
			$out .= implode(', ', $build);
			return $out;
		}
		
		foreach($misc as $k => $v) {
			if(!isset($site_settings[$k])) {
				array_push($build, $this->formatType(array(
					'title' => $k,
					'type' => 'string'
				), $v));
				continue;
			}
			
			$_skip = false;
			$_setting = $site_settings[$k];
			$_part = array(
				'title' => $_setting['title'],
				'type' => 'string'
			);
			
			switch($_setting['type']) {
				case 'bool':
					$_part['type'] = 'bool';
					$_part['values'] = array(
						0 => 'Disabled',
						1 => 'Enabled'
					);
					break;
					
				case 'number':
					if(in_array($k, array(
						'DEFAULT_ACCESS_GUEST',
						'DEFAULT_ACCESS_LDAP',
						'DEFAULT_ACCESS_LOCAL'
					))) {
						$_part['type'] = 'csv';
						$_part['values'] = $access_groups;
					}
					break;
					
				case 'div':
					$_part['type'] = 'code';
					break;
					
				case 'password':
					array_push($build, '
						<div class="history-title">' . $_setting['title'] . '</div>
						<div class="fks-blockquote">' . (is_array($v) ? 'Changed' : 'Set') . '.</div>
					');
					$_skip = true;
					break;
					
				case 'web_page':
					$_part['type'] = 'values';
					$_part['values'] = $menu_items;
					break;
			}
			
			// Add _part to build
			if(!$_skip) {
				array_push($build, $this->formatType($_part, $v));
			}
		}
		
		$out .= implode('', $build);
		
		return $out;
	}

	/*----------------------------------------------------------------------------------------------------
		Menu Item Pages Created Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseMenuItemPagesCreated($out, $log, $simple = false) {
		$misc = json_decode($log['misc'], true);
		
		// Make simple
		if($simple) {
			$out .= 'Pages Created';
		} else {
			$out .= '
				<div class="history-title">Pages Created</div>
				<div class="fks-blockquote">' . implode('<br/>', $misc) . '</div>
			';
		}
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Announcement Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseAnnouncementCreateModifyDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$access_groups = array();
		$get = 'SELECT * FROM fks_access_groups';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$access_groups = $this->logCache['queries'][$get];
		foreach($access_groups as $k => $v) { $access_groups[$k] = $v['title']; }
		$access_groups[0] = '<i>none</i>';
		
		$menu_items = array();
		$menu_items_struct = array();
		$get = 'SELECT id, title, parent_id, url FROM fks_menu_items';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$menu_items = $this->logCache['queries'][$get];
		
		// Create menu item url structures
		foreach($menu_items as $k => $v) {
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $menu_items[$v['parent_id']]['url']);
				if($menu_items[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $menu_items[$menu_items[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$menu_items_struct[$k] = implode('/', $url);
		}
		
		$menu_items_struct[0] = '<i>none</i>';

		$columns = array(
			'title' => array(
				'title' => 'Title',
				'type' => 'string'
			),
			'announcement' => array(
				'title' => 'Announcement',
				'type' => 'code'
			),
			'sticky' => array(
				'title' => 'Sticky',
				'type' => 'bool',
				'values' => array(
					0 => 'No',
					1 => 'Yes'
				)
			),
			'pages' => array(
				'title' => 'Page Access',
				'type' => 'csv',
				'values' => $menu_items_struct
			),
			'access_groups' => array(
				'title' => 'Access Groups',
				'type' => 'csv',
				'values' => $access_groups
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			),
			'deleted' => array(
				'title' => 'Deleted',
				'type' => 'bool',
				'values' => array(
					0 => 'Not Deleted',
					1 => 'Deleted'
				)
			)
		);
		
		$build = array();
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Changelog Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseChangelogCreateModifyDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);

		$columns = array(
			'title' => array(
				'title' => 'Title',
				'type' => 'string'
			),
			'version' => array(
				'title' => 'Version',
				'type' => 'string'
			),
			'notes' => array(
				'title' => 'Notes',
				'type' => 'string'
			),
			'active' => array(
				'title' => 'Status',
				'type' => 'bool',
				'values' => array(
					0 => 'Disabled',
					1 => 'Active'
				)
			)
		);
		
		$build = array();
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Changelog Note Create/Modify/Delete Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseChangelogNoteCreateModifyDelete($out, $log) {
		$misc = json_decode($log['misc'], true);
		if(!is_array($misc)) { return $out; }

		$columns = array(
			'type'				=>		'Type',
			'data'				=>		'Message',
			'pages'				=>		'Page Access',
			'page_id'			=>		'Pages'
		);
		
		$build = array();
		
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){ array_push($build, $columns[$k]); }
		}
		
		$out .= implode(', ', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Changelog Note Create/Modify Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseChangelogNoteCreateModifyDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$menu_items = array();
		$menu_items_struct = array();
		$get = 'SELECT id, title, parent_id, url FROM fks_menu_items';
		if(!$this->cacheKeeper($get)) { return __FUNCTION__ . ' - error 1'; }
		$menu_items = $this->logCache['queries'][$get];
		
		// Create menu item url structures
		foreach($menu_items as $k => $v) {
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $menu_items[$v['parent_id']]['url']);
				if($menu_items[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $menu_items[$menu_items[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$menu_items_struct[$k] = implode('/', $url);
		}
		
		$menu_items_struct[0] = '<i>none</i>';
		
		$build = array();
		
		array_push($build, '
			<div class="history-title">Target Note ID</div>
			<div class="fks-blockquote">' . $misc['note_id'] . '</div>
		');

		$columns = array(
			'type' => array(
				'title' => 'Type',
				'type' => 'string'
			),
			'data' => array(
				'title' => 'Message',
				'type' => 'string'
			),
			'page_id' => array(
				'title' => 'Pages',
				'type' => 'csv',
				'values' => $menu_items_struct
			)
		);
		
		foreach($misc as $k => $v) {
			if(isset($columns[$k])){
				array_push($build, $this->formatType($columns[$k], $v));
				continue;
			}
		}
		$out .= implode('', $build);
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Changelog Note Delete Detailed Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseChangelogNoteDeleteDetailed($out, $log) {
		$misc = json_decode($log['misc'], true);
		
		$out .= '
			<div class="history-title">Target Note ID</div>
			<div class="fks-blockquote">' . $misc['note_id'] . '</div>
		';
		
		return $out;
	}
	
	/*----------------------------------------------------------------------------------------------------
		Site Error Delete Parse Function
	----------------------------------------------------------------------------------------------------*/
	public function parseSiteErrorDeleted($out, $log, $simple = false) {
		$misc = json_decode($log['misc'], true);
		
		// Make simple
		if($simple) {
			$out .= $misc['error_code'];
		} else {
			$out .= '
				<div class="history-title">Error Code</div>
				<div class="fks-blockquote">' . $misc['error_code'] . '</div>
				
				<div class="history-title">Member ID</div>
				<div class="fks-blockquote">' . $misc['error_member'] . '</div>
			';
		}
		
		return $out;
	}
}
?>