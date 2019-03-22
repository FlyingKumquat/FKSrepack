<?PHP
/*##############################################
	Utilities
	Version: 1.3.02252019
	Updated: 02/25/2019
##############################################*/

/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

class Utilities {
	/*----------------------------------------------
		Load Page Changelog
	----------------------------------------------*/
	public function loadPageChangelog($data) {
		// Get page access
		$page_access = $this->getAccess($data);
		
		// Check for read access to the page
		if($page_access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set database connection
		$Database = new \Database;
		
		// Set variables
		$note_ids = array();
		$notes = array();
		$changelog_ids = array();
		$changelogs = array();
		
		// Grab menu item
		if(!$Database->Q(array(
			'assoc' => 'id',
			'params' => array(':label' => $data),
			'query' => 'SELECT id, title FROM fks_menu_items WHERE label = :label AND active = 1 AND deleted = 0'
		))) {
			// Create error code
			$error_code = $this->createError(json_encode($Database->r));
			
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Error Code: ' . $error_code);
		}
		
		// Set menu item from row
		$menu_item = $Database->r['row'];
		
		// No menu item found
		if(empty($menu_item)) { return array('result' => 'failure', 'message' => 'Unable to load Changelog.'); }
		
		// Grab changelog note_ids from pages
		if(!$Database->Q(array(
			'assoc' => 'note_id',
			'params' => array(':page_id' => $menu_item['id']),
			'query' => 'SELECT note_id FROM fks_changelog_pages WHERE page_id = :page_id'
		))) {
			// Create error code
			$error_code = $this->createError(json_encode($Database->r));
			
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Error Code: ' . $error_code);
		}
		
		// Set note_ids from rows
		$note_ids = $Database->r['rows'];
		
		// No note_ids found
		if(empty($note_ids)) { goto skip; }
		
		// Grab changelog notes
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_changelog_notes WHERE id IN (' . (implode(',', array_keys($note_ids))) . ')'
		))) {
			// Create error code
			$error_code = $this->createError(json_encode($Database->r));
			
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Error Code: ' . $error_code);
		}
		
		// Set notes from rows
		$notes = $Database->r['rows'];
		
		// No notes found
		if(empty($notes)) { goto skip; }
		
		// Create array of changelog_ids
		foreach($notes as $note) { array_push($changelog_ids, $note['changelog_id']); }
		
		// No changelog_ids found (somehow)
		if(empty($changelog_ids)) { goto skip; }
		
		// Grab changelogs
		if(!$Database->Q(array(
			'query' => 'SELECT * FROM fks_changelog WHERE id IN (' . (implode(',', $changelog_ids)) . ') AND active = 1 AND deleted = 0 ORDER BY date_created DESC'
		))) {
			// Create error code
			$error_code = $this->createError(json_encode($Database->r));
			
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Error Code: ' . $error_code);
		}
		
		// Set changelogs from rows
		$changelogs = $Database->r['rows'];
	
		// Skip goes to here
		skip:
		
		// Format the changelog
		$formatted_changelog = $this->formatChangelog($changelogs, $notes);
		
		// Return parts
		return array(
			'result' => 'success',
			'parts' => array(
				'size' => 'lg',
				'title' => 'Page Changelog: ' . $menu_item['title'],
				'body' => $formatted_changelog,
				'footer' => '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
			)
		);
	}
	
	/*----------------------------------------------
		Format Changelog
	----------------------------------------------*/
	public function formatChangelog($changelogs, $notes, $header = true) {
		// Set note_types
		$note_types = array(
			'Added',
			'Changed',
			'Fixed',
			'Removed'
		);
		
		// Set variables
		$build = array();
		$menu_item_structures = array();
		
		$build['changelog'] = '';
		// Loop through changelogs
		foreach($changelogs as $changelog) {
			$build['types'] = '';
			// Loop through note_types
			foreach($note_types as $type) {
				$build['notes'] = '';
				// Loop through notes
				foreach($notes as $note) {
					$build['pages'] = '';
					// Skip if note is not part of the changelog or not the right type
					if($note['changelog_id'] != $changelog['id'] || $note['type'] != $type) { continue; }
					if(isset($note['pages'])) {
						// Loop through pages
						foreach($note['pages'] as $page) {
							// Get menu item structures if needed
							if(empty($menu_item_structures)) { $menu_item_structures = $this->getMenuItemStructures(false); }
							
							// Skip if note is not part of the changelog or not the right type
							$build['pages'] .= '<li class="changelog-page">' . $menu_item_structures[$page] . '</li>';
						}
					}
					$build['notes'] .= '<li class="changelog-note">' . $note['data'] . (!empty($build['pages']) ? '<ul class="changelog-page-list">' . $build['pages'] . '</ul>' : '') . '</li>';
				}
				// Skip if no notes added
				if(empty($build['notes'])) { continue; }
				$build['types'] .= '<div class="changelog-notes">
					<div class="changelog-header">' . $type . '</div>
					<ul class="changelog-note-list">' . $build['notes'] . '</ul>
				</div>';
			}
			// Skip if type was not created and changelog notes are empty
			if(empty($build['types']) && empty($changelog['notes'])) { continue; }
			$build['changelog'] .= '<div class="changelog-container' . (!$header ? ' no-header' : '') . '">
				<div class="changelog-header">
					<div class="changelog-version">' . $changelog['version'] . '</div>
					' . (!empty($changelog['title']) ? '<div class="changelog-title">' . $changelog['title'] . '</div>' : '') . '
					<div class="changelog-created">' . $this->formatDateTime($changelog['date_created']) . '</div>
				</div>
				' . (!empty($changelog['notes']) ? '<div class="fks-alert-signature changelog-notes">' . $changelog['notes'] . '</div>' : '') . '
				' . (!empty($build['types']) ? '<div class="changelog-body">' . $build['types'] . '</div>' : '') . '
			</div>';
		}
		
		if(empty($build['changelog'])) {
			$build['changelog'] = '<div class="alert fks-alert-warning">No changelog notes found</div>';
		}
		
		return $build['changelog'];
	}
	
	/*----------------------------------------------
		Format GitHub Changelog
	----------------------------------------------*/
	public function formatGitHubChangelog($link = false) {
		if(empty($link)) { return false; }
		
		$Curl = new \Curl();
		if(!$Curl->get($link)) {
			return array('result' => 'failure', 'message' => 'Failed to load changelog from GitHub.');
		}
		
		if(!isset($Curl->r['json']['body'])) { 
			return array('result' => 'failure', 'message' => 'Changelog has no body.');
		}
		
		$_body = $Curl->r['json']['body'];
		$_lines = explode("\r\n", $_body);
		$_out = '';
		
		foreach($_lines as $_line) {
			
		}
		
		return array('result' => 'success', 'body' => $_body, 'lines' => $_lines, 'out' => $_out);
	}
	
	/*----------------------------------------------
		Get Menu Item Structures
	----------------------------------------------*/
	public function getMenuItemStructures($check_access = false, $content_only = false) {
		// Set database connection
		$Database = new \Database;
		
		// Set variables
		$menu_items = array();

		// Grab all menu items
		if(!$Database->Q(array(
			'assoc' => 'id',
			'query' => ' SELECT * FROM fks_menu_items'
		))) {
			return array('result' => 'failure', 'message' => 'DB Error loading menu items.');
		}
		
		// Create menu item url structures
		$rows = $Database->r['rows'];
		foreach($rows as $k => $v) {
			// Continue if no access
			if($check_access && !$this->checkAccess($v['label'], 1)) { continue; }
			
			// Continue if no content
			if($content_only && !$v['has_content']) { continue; }
			
			$url = array();
			array_unshift($url, $v['url']);
			if($v['parent_id'] > 0) {
				array_unshift($url, $rows[$v['parent_id']]['url']);
				if($rows[$v['parent_id']]['parent_id'] > 0) {
					array_unshift($url, $rows[$rows[$v['parent_id']]['parent_id']]['url']);
				}
			}
			$menu_items[$k] = implode('/', $url);
		}
		
		asort($menu_items);
		
		return $menu_items;
	}
	
	/*----------------------------------------------
		Get Home Page
	----------------------------------------------*/
	public function getHomePage() {
		return 'home';
	}
	
	/*----------------------------------------------
		Send Email
	----------------------------------------------*/
	public function sendEmail($mail_settings) {
		
		/*
		// Example mail settings
		$mail_settings = array(
			'to_address' => '',
			'subject' => '',
			'body' => '',
			'attachment' => array(),	// optional
			'from_address' => '',		// optional (if set in site settings)
			'from_name' => '',			// optional
			'reply_address' => '',		// optional
			'to_name' => '',			// optional
			'cc_address' => '',			// optional
			'bcc_address' => '',		// optional
		);
		
		// Example attachments
		$attachments = array(
			'/path/to/file.txt',			// filepath
			array(
				'/path/to/other/file.txt',	// filepath
				'Rename.txt'				// optional name
			)
		);
		*/
		
		$Database = new \Database;
		$Crypter = new \Crypter;
		
		// Grab site settings from the DB
        if(!$Database->Q(array(
            'assoc' => 'id',
            'query' => 'SELECT id, data FROM fks_site_settings'
        ))) {
			return array('result' => 'failure', 'message' => 'Failed to grab settings from DB!');
        }
		
		$site_settings = $Database->r['rows'];
		
		foreach($site_settings as &$setting) {
			$setting = $setting['data'];
		}
		
		require_once('PHPMailer/PHPMailerAutoload.php');
		
		try {
			$mail = new \PHPMailer(true);
			$mail->IsSMTP();
			$mail->CharSet = 'UTF-8';

			$mail->Host = $site_settings['EMAIL_HOSTNAME'];
			$mail->SMTPDebug = 0;
			$mail->SMTPAuth = $site_settings['EMAIL_AUTH'];
			$mail->SMTPSecure = ($site_settings['EMAIL_SECURE'] == 'None' ? false : $site_settings['EMAIL_SECURE']);
			$mail->Port = ($site_settings['EMAIL_PORT'] ? $site_settings['EMAIL_PORT'] : 25);
			$mail->Username = $site_settings['EMAIL_USERNAME'];
			$mail->Password = $Crypter->fromRJ256($site_settings['EMAIL_PASSWORD']);
			
			if(!isset($mail_settings['from_address']) || $mail_settings['from_address'] == null) { $mail_settings['from_address'] =  $site_settings['EMAIL_FROM_ADDRESS']; }
			if(!isset($mail_settings['reply_address']) || $mail_settings['reply_address'] == null) { $mail_settings['reply_address'] =  $site_settings['EMAIL_REPLY_TO_ADDRESS']; }

			$mail->setFrom($mail_settings['from_address'], (isset($mail_settings['from_name']) && !is_null($mail_settings['from_name']) ? $mail_settings['from_name'] : '' ));
			$mail->addAddress($mail_settings['to_address'], (isset($mail_settings['to_name']) && !is_null($mail_settings['to_name']) ? $mail_settings['to_name'] : '' ));
			$mail->addReplyTo((isset($mail_settings['reply_address']) ? $mail_settings['reply_address'] : $mail_settings['from_address'] ));
			if( isset($mail_settings['cc_address']) ) { $mail->addCC($mail_settings['cc_address']); }
			if( isset($mail_settings['bcc_address']) ) { $mail->addBCC($mail_settings['bcc_address']); }

			$mail->isHTML(true);

			$mail->Subject = $mail_settings['subject'];
			$mail->Body = $mail_settings['body'];
			
			if(isset($mail_settings['attachment']) && !empty($mail_settings['attachment'])) {
				foreach($mail_settings['attachment'] as $attachment) {
					if(is_array($attachment)) {
						$mail->addAttachment($attachment[0], $attachment[1]);
					} else {
						$mail->addAttachment($attachment);
					}
				}
			}
			
			$mail->send();
		} catch (phpmailerException $e) {
			return array('result' => 'failure', 'message' => $e->errorMessage());
		} catch (Exception $e) {
			return array('result' => 'failure', 'message' => $e->getMessage());
		}
		
		return array('result' => 'success', 'message' => 'Email sent!');
	}
	
	/*----------------------------------------------
		Load History
	----------------------------------------------*/
	public function loadHistory($options) {
		$title = 'UNKNOWN';
		$MemberLog = new \MemberLog();
		
		if(isset($options['table'])) {
			$Database = new \Database();
			if(!$Database->Q(array(
				'params' => array('id' => $options['id']),
				'query' => 'SELECT ' . $options['select'] . ' FROM ' . $options['table'] . ' WHERE id = :id'
			))) {
				return array('result' => 'failure', 'message' => 'Error loading history.', 'code' => 1);
			}
			
			if($Database->r['found'] != 1) {
				return array('result' => 'failure', 'message' => 'Error loading history.', 'code' => 2);
			}
			
			$title = $Database->r['row'][$options['select']];
		}
		
		$logs = $MemberLog->grabMemberLogs(array(
			'action' => $options['actions'],
			'target_id' => $options['id'],
			'also' => 'ORDER BY date_created DESC'
		));
		if($logs['result'] == 'failure') { return $logs; }
		
		foreach($logs['history'] as $k => $v) {
			$logs['history'][$k] = array(
				'id' => $v['id'],
				'target_id' => $v['target_id'],
				'date_created' => $this->formatDateTime($v['date_created']),
				'action_title' => $v['action_title'],
				'username' => $v['username'],
				'misc_formatted' => $v['misc_formatted'],
				'misc_formatted_detailed' => $v['misc_formatted_detailed'],
				'tools' => '<span class="pull-right"><a class="view" href="javascript:void(0);" data-toggle="fks-tooltip" title="Detailed View"><i class="fa fa-eye fa-fw"></i><span class="d-none d-xl-inline"> View</span></a></span>'
			);
		}
		
		return array(
			'result' => 'success',
			'parts' => array(
				'size' => 'xl',
				'title' => $options['title'] . (isset($options['select']) ? $title : ''),
				'footer' => '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>',
				'callbackData' => array(
					'onOpen' => $logs
				)
			)
		);
	}
	
	/*----------------------------------------------
		Format Table Rows
	----------------------------------------------*/
	public function formatTableRows($rows, $access = false, $tool_container = true) {
		
		// Create tool function
		$_tool = function($class, $title, $icon) {
			return '<a class="' . $class . '" href="javascript:void(0);" data-toggle="fks-tooltip" title="' . $title . '"><i class="fa fa-' . $icon . ' fa-fw"></i></a>';
		};
		
		foreach($rows as $k => &$v) {			
			// Format status
			if(isset($v['active'])) { $v['status'] = $v['active'] == 1 ? '<span style="color:green">Active</span>' : '<span style="color:red">Disabled</span>'; }
			if(isset($v['deleted'])) { if($v['deleted'] == 1) { $v['status'] = '<span style="color:red">Deleted</span>'; } }
			
			// Format date times into correct timezones 
			if(isset($v['date_created'])) { $v['date_created'] = $this->formatDateTime($v['date_created']); }
			if(isset($v['date_modified'])) { $v['date_modified'] = $this->formatDateTime($v['date_modified']); }
			
			// Set '0' usernames to the site username
			if(isset($v['created_by']) && $v['created_by'] != NULL && $v['created_by'] == 0) { $v['created_name'] = $this->siteSettings('SITE_USERNAME'); }
			if(isset($v['modified_by']) && $v['modified_by'] != NULL && $v['modified_by'] == 0) { $v['modified_name'] = $this->siteSettings('SITE_USERNAME'); }
			
			// Make Tools
			if($access) {
				$tools = array();
				// History
				if($access > 2) { array_push($tools, $_tool('history', 'History', 'history')); }
				// Edit
				if($access > 1) { array_push($tools, $_tool('edit', 'Edit', 'edit')); }
				// View
				if($access == 1) { array_push($tools, $_tool('view', 'View', 'eye')); }
				
				// Tool container, or stay array
				if($tool_container) { $v['tools'] = '<span class="pull-right">' . implode('&nbsp;', $tools) . '</span>'; }
				else { $v['tools'] = $tools; }
			}
			
			// Turn blanks into dashes
			foreach($v as $vk => $vv) {
				if($vv == NULL && $vv != '0') {
					$v[$vk] = '-';
				}
			}
		}
		return $rows;
	}
	
	/*----------------------------------------------
		Build Form Inputs
	----------------------------------------------*/
	public function buildFormInputs($dataType, $data = '', $readonly = false) {
		if( !is_array($dataType) ){ return false; }
		$names =  \Enums\DataTypes::flip();
		$dataType['const'] = $names[$dataType['id']];
		
		switch($dataType['const'])
		{
			case 'ACCESS_GROUPS':
				// Set Vars
				$Database = new \Database;
				$options = '';
				$data = explode(',', $data);
				$hierarchy = 0;
				
				// Grab All Access Groups
				if($Database->Q(array(
					'assoc' => 'id',
					'query' => 'SELECT * FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY hierarchy ASC'
				))) {
					$access_groups = $Database->r['rows'];
				} else {
					return false;
				}
				
				// Figure out what the current user's highest Access Group is
				foreach( $_SESSION['access_groups'] as $k => $v ){
					if( $access_groups[$k]['hierarchy'] > $hierarchy ){ $hierarchy = $access_groups[$k]['hierarchy']; }
				}
				
				foreach( $access_groups as $v ){
					if( in_array($v['id'], $data) ){ $sel = ' selected'; }else{ $sel = ''; }
					if( $hierarchy < $v['hierarchy'] ){ $dis = ' disabled'; }else{ $dis = ''; }
					$options .= '<option value="' . $v['id'] . '"' . $sel . '' . $dis . '>' . $v['title'] . ' - ' . $v['hierarchy'] . '</option>';
				}
				
				// Create Input
				$input = '<div class="form-group">
					<label for="' . $dataType['const'] . '" class="form-control-label">' . $dataType['title'] . ' - ' . $hierarchy . '</label>
					<select id="' . $dataType['const'] . '" name="' . $dataType['const'] . '" aria-describedby="' . $dataType['const'] . '_HELP" multiple="multiple"' . ($readonly ? ' disabled' : '') . '>' . $options . '</select>
					<div class="form-control-feedback"></div>
					<small id="' . $dataType['const'] . '_HELP" class="form-text text-muted">' . ($dataType['help_text'] != null ? $dataType['help_text'] : '&nbsp;') . '</small>
				</div>';
				break;
				
			case 'TIMEZONE':
				// If on the site settings page the default should be server default
				// If anywhere else the default should be the site settings default
				if( $_SESSION['site_settings']['TIMEZONE'] != null ) {
					$options = '<option value="">Use Default (' . $_SESSION['site_settings']['TIMEZONE'] . ')</option>';
				} else {
					$options = '<option value="">Use Default (' . date_default_timezone_get() . ')</option>';
				}
				
				
				$regions = array(
					//'Africa' => \DateTimeZone::AFRICA,
					'America' => \DateTimeZone::AMERICA,
					//'Antarctica' => \DateTimeZone::ANTARCTICA,
					//'Asia' => \DateTimeZone::ASIA,
					'Atlantic' => \DateTimeZone::ATLANTIC,
					//'Europe' => \DateTimeZone::EUROPE,
					//'Indian' => \DateTimeZone::INDIAN,
					'Pacific' => \DateTimeZone::PACIFIC
				);
				foreach ($regions as $name => $mask)
				{
					$zones = \DateTimeZone::listIdentifiers($mask);
					foreach($zones as $timezone)
					{
						if( $data == $timezone ){ $sel = ' selected'; }else{ $sel = ''; }
						$options .= '<option value="' . $timezone . '"' . $sel . '>' . $timezone . '</option>';
					}
				}
				$input = '<div class="form-group">
					<label for="' . $dataType['const'] . '" class="form-control-label">' . $dataType['title'] . '</label>
					<select class="form-control form-control-sm" id="' . $dataType['const'] . '" name="' . $dataType['const'] . '" aria-describedby="' . $dataType['const'] . '_HELP"' . ($readonly ? ' disabled' : '') . '>' . $options . '</select>
					<div class="form-control-feedback"></div>
					<small id="' . $dataType['const'] . '_HELP" class="form-text text-muted">' . ($dataType['help_text'] != null ? $dataType['help_text'] : '&nbsp;') . '</small>
				</div>';
				break;
				
			case 'AVATAR':
				$input = '<div class="form-group">
					<label for="' . $dataType['const'] . '" class="form-control-label">' . $dataType['title'] . '</label>
					<input type="file" class="form-control form-control-sm" id="' . $dataType['const'] . '" name="' . $dataType['const'] . '" aria-describedby="' . $dataType['const'] . '_HELP" value="' . $data . '">
					<div class="form-control-feedback"></div>
					<small id="' . $dataType['const'] . '_HELP" class="form-text text-muted">' . ($dataType['help_text'] != null ? $dataType['help_text'] : '&nbsp;') . '</small>
				</div>';
				break;
				
			case 'SITE_LAYOUT':
				// Create database connection
				$Database = new \Database;
				
				// Grab data and misc (options) for SITE_LAYOUT
				if($Database->Q('SELECT data,misc FROM fks_site_settings WHERE id = "SITE_LAYOUT"')) {
					$t = $Database->r['row'];
					$t['misc'] = json_decode($t['misc'], true);
				} else {
					return false;
				}
				
				$options = '<option value=""' . (empty($data) ? ' selected' : '' ) . '>Use Default (' . $t['data'] . ')</option>';
				
				foreach($t['misc']['options'] as $o) {
					$options .= '<option value="' . $o . '"' . ($data == $o ? ' selected' : '' ) . '>' . $o . '</option>';
				}
				
				$input = '<div class="form-group">
					<label for="' . $dataType['const'] . '" class="form-control-label">' . $dataType['title'] . '</label>
					<select class="form-control form-control-sm" id="' . $dataType['const'] . '" name="' . $dataType['const'] . '" aria-describedby="' . $dataType['const'] . '_HELP"' . ($readonly ? ' disabled' : '') . '>' . $options . '</select>
					<div class="form-control-feedback"></div>
					<small id="' . $dataType['const'] . '_HELP" class="form-text text-muted">' . ($dataType['help_text'] != null ? $dataType['help_text'] : '&nbsp;') . '</small>
				</div>';
				break;
				
			default:
				$input = '<div class="form-group">
					<label for="' . $dataType['const'] . '" class="form-control-label">' . $dataType['title'] . '</label>
					<input type="' . $dataType['input_type'] . '" class="form-control form-control-sm" id="' . $dataType['const'] . '" name="' . $dataType['const'] . '" aria-describedby="' . $dataType['const'] . '_HELP" value="' . $data . '"' . ($readonly ? ' disabled' : '') . '>
					<div class="form-control-feedback"></div>
					<small id="' . $dataType['const'] . '_HELP" class="form-text text-muted">' . ($dataType['help_text'] != null ? $dataType['help_text'] : '&nbsp;') . '</small>
				</div>';
				break;
		}
		
		return $input;
	}
	
	/*----------------------------------------------
		Compare Query Array
	----------------------------------------------*/
	public function compareQueryArray($id, $table, $new, $json = false, $data = false) {
		$Database = new \Database;
		
		// Table doesn't exist, return false
		if(!$Database->tableExists($table)) { return false; }
		
		if(!$data) {
		// Data is false, do not grab from data table
			$args = array(
				'params' => array(':id' => (is_array($id) ? $id[1] : $id)),
				'query' => 'SELECT * FROM ' . $table . ' WHERE ' . (is_array($id) ? $id[0] : 'id') . ' = :id'
			);
			if($Database->Q($args)) {
				$old = array(); 
				if($Database->r['found'] == 1) {
					$old = $Database->r['row'];
				} else if($Database->r['found'] > 1) {
					foreach($Database->r['rows'] as $row) {
						foreach($row as $column_name => $column_value) {
							if(!isset($old[$column_name])) {
								$old[$column_name] = array();
							} else {
								$old[$column_name] = explode(',', $old[$column_name]);
							}
							array_push($old[$column_name], $column_value);
							sort($old[$column_name]);
							$old[$column_name] = implode(',', $old[$column_name]);
						}
					}
				}
			} else {
			// Query failed for some reason
				print_r($Database->r);
				return false;
			}
		} else {
		// Data is NOT false, grab data from data table
			if($Database->Q(array(
				'params' => array(':id' => $id),
				'query' => '
					SELECT
						t.*,
						td.id AS data_id,
						td.data AS data
						
					FROM
						' . $table . ' AS t
						
					LEFT OUTER JOIN
						fks_member_data AS td
							ON
						t.id = td.member_id
						
					WHERE
						t.id = :id
				'
			))) {
				$tmp = array();
				foreach($Database->r['rows'] as $k => $v) {
				// Associate data_ids to data values
					if(!isset($tmp[$v['id']])) { $tmp[$v['id']] = $v; }
					else {
						$tmp[$v['id']]['data_id'] = $v['data_id'];
						$tmp[$v['id']]['data'] = $v['data'];
					}
					if(isset($v['data_id'])) {
						unset($tmp[$v['id']]['data_id']);
						unset($tmp[$v['id']]['data']);
						$tmp[$v['id']][$v['data_id']] = $v['data'];
					}
				}
				if(count($tmp) == 1) {
				// Make sure only 1 company was found
					$old = reset($tmp);
				} else {
				// If more or less than 1 company was found, set old data to blank
					$old = array(); 
				}
				foreach($new as $k => $v) {
					if(!isset($old[$k]) && empty($v)) {
						unset($new[$k]);
					}
				}
			} else {
			// Query failed for some reason
				print_r($Database->r);
				return false;
			}
		}
		
		$hold = array();
		if($json){
		// Check json encoded values
			foreach($json as $j) {
			// Loop through each json value as j
				if(!isset($hold[$j])) {
				// If the json value is not set in hold yet, create new/old arrays in hold
					$hold[$j] = array(
						'new' => array(),
						'old' => array()
					);
				}
				if(array_key_exists($j, $new)) {
				// If the json value exists in the new array, update the new hold array and unset the value from the new array
					$hold[$j]['new'] = $new[$j] == null ? array() : json_decode($new[$j], true);
					unset($new[$j]);
				}
				if(array_key_exists($j, $old)) {
				// If the json value exists in the old array, update the old hold array and unset the value from the old array
					$hold[$j]['old'] = $old[$j] == null ? array() : json_decode($old[$j], true);
					unset($old[$j]);
				}
			}
		}
		
		// Find differences between the new and old arrays
		$diff = array_diff_assoc($new, $old);
		
		foreach($diff as $k => $v) {
		// Loop through each difference
			// Set the value to null if it's blank
			$v = $v == '' ? null : $v;
			if(array_key_exists($k, $old)) {
				$diff[$k] = array($old[$k], $v);
			} else {
				$diff[$k] = $v;
			}
		}
		
		foreach($hold as $hk => $hv) {
			$new_diff = array_diff_assoc($hv['new'], $hv['old']);
			$old_diff = array_diff_assoc($hv['old'], $hv['new']);
			
			foreach($new_diff as $k => $v) {
				if(!array_key_exists($hk, $diff)) { $diff[$hk] = array(); }
				if(array_key_exists($k, $hv['old'])) {
					$diff[$hk][$k] = array($hv['old'][$k], $v);
				} else {
					$diff[$hk][$k] = empty($hv['old']) ? $v : array(null, $v);
				}
			}
			
			foreach($old_diff as $k => $v) {
				if(!array_key_exists($hk, $diff)) { $diff[$hk] = array(); }
				if(!array_key_exists($k, $hv['new'])) {
					$diff[$hk][$k] = array($hv['old'][$k], null);
				}
			}
		}
		
		// Return False if no changes
		if(empty($diff)) { return false; }
		
		// Return changes array if changes detected
		return $diff;
	}
	
	/*----------------------------------------------
		Create Error
	----------------------------------------------*/
	public function createError($message) {
		// JSON encode the message
		$message = json_encode($message);
		
		// Set database connection
		$Database = new \Database;
		
		// Set variables
		$backtrace = debug_backtrace();
		$backtrace = (count($backtrace) == 1 ? $backtrace[0] : $backtrace[1]);
		$error = array(
			'code' => $this->makeKey(16),
			'file' => debug_backtrace()[0]['file'],
			'line' => debug_backtrace()[0]['line'],
			'function' => $backtrace['function'],
			'class' => $backtrace['class'],
			'member' => (isset($_SESSION) && isset($_SESSION['id']) ? $_SESSION['id'] : -1),
			'message' => $message,
			'created' => gmdate('Y-m-d h:i:s')
		);
		$saved_error = false;
		$out = '';
		
		// Grab site settings from the DB
        if(!$Database->Q(array(
            'assoc' => 'id',
            'query' => 'SELECT id, data FROM fks_site_settings WHERE id IN ("ERROR_EMAIL", "ERROR_EMAIL_ADDRESS", "ERROR_MESSAGE", "ERROR_TO_DB", "ERROR_TO_DISK")'
        ))) {
			return 'Error Code: ' . $error['code'];
        }
		$site_settings = $Database->r['rows'];
		foreach($site_settings as &$setting) { $setting = $setting['data']; }

		// Save error to database if enabled
		if($site_settings['ERROR_TO_DB'] == 1) {
			if($Database->Q(array(
				'params' => array(
					':error_code' => $error['code'],
					':error_file' => $error['file'],
					':error_line' => $error['line'],
					':error_function' => $error['function'],
					':error_class' => $error['class'],
					':error_member' => $error['member'],
					':error_message' => $error['message'],
					':error_created' => $error['created']
				),
				'query' => '
					INSERT INTO
						fks_site_errors
						
					SET
						error_code = :error_code,
						error_file = :error_file,
						error_line = :error_line,
						error_function = :error_function,
						error_class = :error_class,
						error_member = :error_member,
						error_message = :error_message,
						error_created = :error_created
				'
			))) {
			// Query success, set saved_error
				$saved_error = true;
			}
		}
		
		// Save error to disk if enabled or database failed and is set to fallback
		if($site_settings['ERROR_TO_DISK'] == 'Yes' || (!$saved_error && $site_settings['ERROR_TO_DISK'] == 'Fallback')) {
			$file = __DIR__ . '/../../../errors/' . gmdate('Y.m.d') . '/' . $error['code'];
		
			if(!file_exists(dirname($file))) {
				mkdir(dirname($file), 0777, true);
			}
			
			$file = fopen($file . '.txt', 'w');
			fwrite($file, json_encode($error));
			fclose($file);
		}

		// Send email if enabled
		if($site_settings['ERROR_EMAIL'] == 1) {
			$mail = $this->sendEmail(array(
				'to_address' => $site_settings['ERROR_EMAIL_ADDRESS'],
				'subject' => $this->siteSettings('SITE_TITLE') . ' Error: ' . $error['code'],
				'body' => 'An error was encountered:<br><br>
					Code: ' . $error['code'] . '<br>
					File: ' . $error['file'] . '<br>
					Class: ' . $error['class'] . '<br>
					Function: ' . $error['function'] . '<br>
					Line: ' . $error['line'] . '<br>
					Member: ' . $error['member'] . '<br>
					Message: ' . $error['message'] . '<br>'
			));
		}
		
		// Do error message replacements
		$out = str_replace(
			array(
				'%CLASS%',
				'%CODE%',
				'%FILE%',
				'%FUNCTION%',
				'%LINE%'
			),
			array(
				$error['class'],
				$error['code'],
				$error['file'],
				$error['function'],
				$error['line']
			),
			$site_settings['ERROR_MESSAGE']
		);
		
		return $out;
	}
	
	/*----------------------------------------------
		Make Key
	----------------------------------------------*/
	public function makeKey($length, $alphabet = false) {
	// Returns a random key with a given length
		$key = '';
		if(!$alphabet) {
			$codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
			$codeAlphabet .= '0123456789';
		} else {
			$codeAlphabet = $alphabet;
		}
		for($i=0; $i < $length; $i++) {
			$key .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
		}
		return $key;
	}
	
	/*----------------------------------------------
		crypto_rand_secure
	----------------------------------------------*/
	private function crypto_rand_secure($min, $max) {
		$range = $max - $min;
		if ($range < 0) return $min;
		$log = log($range, 2);
		$bytes = (int) ($log / 8) + 1;
		$bits = (int) $log + 1;
		$filter = (int) (1 << $bits) - 1;
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter;
		} while ($rnd >= $range);
		return $min + $rnd;
	}

	/*----------------------------------------------
		Format Date Time
	----------------------------------------------*/
	public function formatDateTime($time = null) {
		if(empty($time)) { return $time; }
		$timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : $this->siteSettings('TIMEZONE');
		$date_format = isset($_SESSION['date_format']) ? $_SESSION['date_format'] : $this->siteSettings('DATE_FORMAT');
		if(empty($timezone)) { $timezone = date_default_timezone_get(); }
		return date_format(date_create($time, new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone($timezone)), $date_format);
	}
	
	/*----------------------------------------------
		Site Settings
	----------------------------------------------*/
	public function siteSettings($title, $from_db = false) {

		if(isset($this->Session) && $this->Session->active()) {
			if(!$from_db && isset($_SESSION['site_settings']) && isset($_SESSION['site_settings'][$title])) {
				return $_SESSION['site_settings'][$title];
			}
		}
		
		$Database = new \Database;
		if($Database->Q(array(
			'params' => array(':id' => $title),
			'query' => 'SELECT data FROM fks_site_settings WHERE id = :id'
		))) {
			if($Database->r['found'] > 0) {
				return $Database->r['row']['data'];
			}
		}
		
		return false;
	}
	
	/*----------------------------------------------
		Encrypt User
	----------------------------------------------*/
	public function encryptUser() {
		if($this->Session->active()) {
			$Crypter = new \Crypter;
			return array('result' => 'success', 'message' => $Crypter->toBlowfish($_SESSION['id']));
		} else {
			return array('result' => 'failure');
		}
	}
	
	/*----------------------------------------------
		Associative Array Sort
	----------------------------------------------*/
	public function aasort(&$arr, $col, $dir = SORT_ASC) {
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}

		array_multisort($sort_col, $dir, $arr);
	}
	
	/*----------------------------------------------
		Seconds to Time (00d 00h 00m 00s)
	----------------------------------------------*/
	public function sec2time($s) {
		if(!is_numeric($s)) { return $s; }
		
		$out = '';
		
		$day = intval($s / 86400);
		$hour = intval($s / 3600) % 24;
		$min = intval($s / 60) % 60;
		$sec = $s % 60;

		if($day > 0) { $out .= $day . 'd '; }
		if($hour > 0 || $day > 0) { $out .= ($hour < 10 && ($day > 0) ? '0' : '') . $hour . 'h '; }
		if($min > 0 || $hour > 0 || $day > 0) { $out .= ($min < 10 && ($hour > 0 || $day > 0) ? '0' : '') . $min . 'm '; }
		if($sec <= 0) { $sec = 0; }
		
		$out .= ($sec < 10 && ($min > 0 || $hour > 0 || $day > 0) ? '0' : '') . $sec . 's';
		
		return $out;
	}
	
	/*----------------------------------------------
		Build Form Group
	----------------------------------------------*/
	public function buildFormGroup($params) {
		// Set variables
		$out = '';
		$parts = array();
		$content = array();
		$classes = array();
		$name_set = false;
		$attributes = array();
		$required = ' <span style="color: red;">*</span>';
		
		// Add
		array_push($classes, 'form-control');
		
		// Add id to attributes array
		array_push($attributes, 'id="' . $params['id'] . '"');
		
		// Add all attributes
		if(isset($params['attributes'])) {
			foreach($params['attributes'] as $k => $v) {
				if($k == 'name') { $name_set = true; }
				if($k == 'class') { 
					array_push($classes, $v);
					continue;
				}
				array_push($attributes, $k . '="' . $v . '"');
			}
		}
		
		// See if name was set
		if(!$name_set) {
			// Add name to attributes array using id value
			array_push($attributes, 'name="' . $params['id'] . '"');
		}
		
		// Add all properties
		if(isset($params['properties'])) {
			foreach($params['properties'] as $v) {
				array_push($attributes, $v);
			}
		}
		
		// Add aria-describedby
		array_push($attributes, 'aria-describedby="' . $params['id'] . '_help"');
		
		// See if input is hidden
		if($params['type'] == 'hidden') {
			$out = '<input type="hidden" ' . implode(' ', $attributes) . (isset($params['value']) ? ' value="' . $params['value'] . '"' : '') . '>';
			return $out;
		}
		
		// Add label to parts if not a checkbox
		if($params['type'] != 'checkbox') {
			array_push($parts, '<label for="' . $params['id'] . '" class="form-control-label">' . $params['title'] . (isset($params['required']) && $params['required'] ? $required : '') . '</label>');
		}

		// Attributes / Classes / Properties
		$_acp = 'class="' . implode(' ', $classes) . '" ' . implode(' ', $attributes);
		
		switch($params['type']) {
			case 'text':
			case 'number':
				array_push($content, '<input type="' . $params['type'] . '" ' . $_acp . (isset($params['value']) ? ' value="' . $params['value'] . '"' : '') . ' />');
				break;
				
			case 'textarea':
				array_push($content, '<textarea ' . $_acp . '>' . (isset($params['value']) ? $params['value'] : '') . '</textarea>');
				break;
				
			case 'select':
				$_options = array();
				if(isset($params['options'])) {
					foreach($params['options'] as $o) {
						$_a = (isset($o['value']) ? ' value="' . $o['value'] . '"' : '')
							. ((isset($o['selected']) && $o['selected']) || (isset($o['value']) && isset($params['value']) && $o['value'] == $params['value']) ? ' selected' : '')
							. (isset($o['disabled']) && $o['disabled'] ? ' disabled' : '');
						array_push($_options, '<option' . $_a . '>' . $o['title'] . '</option>');
					}
				}
				array_push($content, '<select ' . $_acp . '>' . implode('', $_options) . '</select>');
				break;
				
			case 'checkbox':
				$_checkbox = '<input type="checkbox" ' . $_acp . ' />'
					. $params['title']
					. (isset($params['required']) && $params['required'] ? $required : '');
				array_push($content, '<label class="form-checkbox">' . $_checkbox . '</label>');
				break;
		}
		
		// Check for input grouping
		if(
			isset($params['group'])
				&&
			(
				(
					isset($params['group']['before'])
						&&
					!empty($params['group']['before'])
				) 
					||
				(
					isset($params['group']['after'])
						&&
					!empty($params['group']['after'])
				)
			)
		) {
			// Grouping
			array_push($parts, '<div class="input-group">');
			if(isset($params['group']['before'])) { array_push($parts, $params['group']['before']); }
			array_push($parts, implode('', $content));
			if(isset($params['group']['after'])) { array_push($parts, $params['group']['after']); }
			array_push($parts, '</div>');
		} else {
			// No grouping
			array_push($parts, implode('', $content));
		}
		
		// Add form-control-feedback to parts
		array_push($parts, '<div class="form-control-feedback"></div>');
		
		// Add help to parts
		array_push($parts, '<small id="' . $params['id'] . '_help" class="form-text text-muted">' . (isset($params['help']) ? $params['help'] : '&nbsp;') . '</small>');
		
		// Return the form group
		return '<div class="form-group">' . implode('', $parts) . '</div>';
	}
}





























