<?PHP namespace FKS\Views;
/*----------------------------------------------
	Debug / Error reporting
----------------------------------------------*/

class PageFunctions extends CoreFunctions {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	private $access;
	
/*----------------------------------------------
	Construct
----------------------------------------------*/
	public function __construct() {
		parent::__construct();
		$this->access = $this->getAccess('site_settings');
	}

/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	private function formGroup($formData = null) {
		// Return blank if anything is missing
		if(is_null($formData)) {return '(NULL)';}
		
		// JSON decode misc settings
		$json = json_decode($formData['misc'], true);
		
		// Start the form group
		$return = '<div class="form-group"><label for="' . $formData['id'] . '" class="form-control-label">' . $formData['title'] . (isset($json['required']) && $json['required'] == true ? ' <span style="color:red;">*</span>' : '') . '</label>';
		
		switch($formData['type'])
		{
            case 'bool':
				$return .= '<select class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>
					<option value="0"' . (isset($formData['data']) && $formData['data'] == 0 ? ' selected' : '') . '>Disabled</option>
					<option value="1"' . (isset($formData['data']) && $formData['data'] == 1 ? ' selected' : '') . '>Enabled</option>
				</select>';
				break;
			
            case 'email':
            case 'text':
            case 'number':
            case 'password':
				$return .= '<input type="' . $formData['type'] . '" class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP" value="' . (isset($formData['data']) ? $formData['data'] : '') . '" autocomplete="off"' . ($this->access == 3 ? '' : ' disabled') . '>';
				break;
				
			case 'dropdown':
				// Start the select
				$return .= '<select class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>';
				
				// Loop through options if set
				if( isset($json['options']) ) { 
					foreach($json['options'] as $k => $v) {
						$return .= '<option value="' . $v . '"' . ($formData['data'] == $v ? ' selected' : '') . '>' . $v . '</option>';
					}
				} else {
					$return .= '<option>Options Not Defined!</option>';
				}
				
				// End the select
				$return .= '</select>';
				break;
				
            case 'timezone':
				// Start the select
				$return .= '<select class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>';
				
				// Create the default option
				$return .= '<option value="">Use Default (' . date_default_timezone_get() . ')</option>';
				
				// Select specific time regions
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
				
				// loop through each time regions
				foreach ($regions as $name => $mask)
				{
					$zones = \DateTimeZone::listIdentifiers($mask);
					foreach($zones as $timezone)
					{
						$return .= '<option value="' . $timezone . '"' . ($formData['data'] == $timezone ? ' selected' : '') . '>' . $timezone . '</option>';
					}
				}
				
				// End the select
				$return .= '</select>';
				break;
				
			case 'div':
				$return .= '<div id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP" ' . ($this->access == 3 ? '' : ' disabled') . '>' . (isset($formData['data']) ? $formData['data'] : '') . '</div>';
				break;
				
			case 'textarea':
				$return .= '<textarea class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" ' . (isset($json['attributes']) ? $json['attributes'] : '') . ' aria-describedby="' . $formData['id'] . '_HELP" ' . ($this->access == 3 ? '' : ' disabled') . '>' . (isset($formData['data']) ? $formData['data'] : '') . '</textarea>';
				break;
				
			case 'web_page':
				// Start the select
				$return .= '<select class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>';
				
				// Default NULL
				$return .= '<option value="">-</option>';
				
				// Loop through options 
				foreach($this->getMenuItemStructures(false, true) as $k => $v) {
					$return .= '<option value="' . $k . '"' . ($formData['data'] == $k ? ' selected' : '') . '>' . $v . '</option>';
				}
				
				// End the select
				$return .= '</select>';
				break;
				
			case 'connection':
				// Start the select
				$return .= '<select class="form-control form-control-sm" id="' . $formData['id'] . '" name="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>';
				
				// Default NULL
				$return .= '<option value="">-</option>';
				
				// Make a database connection
				$Database = new \Database();
				
				// Loop through options 
				foreach($Database->db as $k => $v) {
					// Skip things
					if( $k == 'persist' || $k == 'default' || $k == $Database->db['default'] ){ continue; }
					
					// Add option
					$return .= '<option value="' . $k . '"' . ($formData['data'] == $k ? ' selected' : '') . '>' . $k . '</option>';
				}
				
				// End the select
				$return .= '</select>';
				break;
				
            default:
				$return .= '<input type="text" class="form-control form-control-sm" id="' . $formData['id'] . '" aria-describedby="' . $formData['id'] . '_HELP" value="There was an issue with this form (' . $formData['type'] . ')" disabled>';
				break;
		}
		
		// End the form group
		$return .= '<div class="form-control-feedback"></div>
			<small id="' . $formData['id'] . '_HELP" class="form-text text-muted">' . $formData['help_text'] . '</small>
		</div>';
		
		// Return form group
		return $return;
	}
	
	// -------------------- General Tab Settings -------------------- \\
	private function tabGeneral($site_settings) {
		$return = '<div class="row">
			<div class="col-xl-7">		
				<p>These are general settings for the site, anything that doesn\'t belong in their own tab.</p>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['SITE_TITLE']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['MEMBER_REGISTRATION']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['REQUIRE_LOGIN']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['SITE_USERNAME']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['TIMEZONE']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['DATE_FORMAT']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['SITE_LAYOUT']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['SITE_HOME_PAGE']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-12">' . $this->formGroup($site_settings['PROTECTED_USERNAMES']) . '</div>
				</div>
			</div>
			<div class="col-xl-5">
				<h6>' . $site_settings['SITE_TITLE']['title'] . '</h6>
				<p>' . $site_settings['SITE_TITLE']['description'] . '</p>
				<h6>' . $site_settings['MEMBER_REGISTRATION']['title'] . '</h6>
				<p>' . $site_settings['MEMBER_REGISTRATION']['description'] . '</p>
				<h6>' . $site_settings['REQUIRE_LOGIN']['title'] . '</h6>
				<p>' . $site_settings['REQUIRE_LOGIN']['description'] . '</p>
				<h6>' . $site_settings['SITE_USERNAME']['title'] . '</h6>
				<p>' . $site_settings['SITE_USERNAME']['description'] . '</p>
				<h6>' . $site_settings['TIMEZONE']['title'] . '</h6>
				<p>' . $site_settings['TIMEZONE']['description'] . '</p>
				<h6>' . $site_settings['DATE_FORMAT']['title'] . '</h6>
				<p>' . $site_settings['DATE_FORMAT']['description'] . '</p>
				<h6>' . $site_settings['SITE_LAYOUT']['title'] . '</h6>
				<p>' . $site_settings['SITE_LAYOUT']['description'] . '</p>
				<h6>' . $site_settings['SITE_HOME_PAGE']['title'] . '</h6>
				<p>' . $site_settings['SITE_HOME_PAGE']['description'] . '</p>
				<h6>' . $site_settings['PROTECTED_USERNAMES']['title'] . '</h6>
				<p>' . $site_settings['PROTECTED_USERNAMES']['description'] . '</p>
			</div>
		</div>';
		
		return $return;
	}
	
	// -------------------- reCaptcha Tab Settings -------------------- \\
	private function tabCaptcha($site_settings) {
		$return = '<div class="row">
			<div class="col-xl-7">		
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['CAPTCHA']) . '</div>
					<div class="col-xl-6"></div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['CAPTCHA_PRIVATE']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['CAPTCHA_PUBLIC']) . '</div>
				</div>
				
			</div>
			<div class="col-xl-5">
				<h6>' . $site_settings['CAPTCHA']['title'] . '</h6>
				<p>' . $site_settings['CAPTCHA']['description'] . '</p>
				<h6>' . $site_settings['CAPTCHA_PRIVATE']['title'] . '</h6>
				<p>' . $site_settings['CAPTCHA_PRIVATE']['description'] . '</p>
				<h6>' . $site_settings['CAPTCHA_PUBLIC']['title'] . '</h6>
				<p>' . $site_settings['CAPTCHA_PUBLIC']['description'] . '</p>
			</div>
		</div>';
		
		return $return;
	}
	
	// -------------------- Email Tab Settings -------------------- \\
	private function tabEmail($site_settings) {
		// Panel start
		$return = '<div class="fks-panel tabs mar-bot-0">';
		
		// Email tabs
		$return .= '<div class="header">
			<ul class="nav nav-tabs">
				<li class="nav-item">
					<a class="nav-link active" data-toggle="tab" href="#tab3-1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> General</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tab3-2" role="tab" draggable="false"><i class="fa fa-handshake-o fa-fw"></i> Verification</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tab3-3" role="tab" draggable="false"><i class="fa fa-question fa-fw"></i> Forgot Password</a>
				</li>
			</ul>
		</div>';
		
		// Email tab bodies
		$return .= '<div class="body">
			<div class="tab-content">
				<div class="tab-pane active" id="tab3-1" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_AUTH']) . '</div>
								<div class="col-xl-6"></div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_USERNAME']) . '</div>
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_PASSWORD']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_FROM_ADDRESS']) . '</div>
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_REPLY_TO_ADDRESS']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_HOSTNAME']) . '</div>
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_PORT']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_SECURE']) . '</div>
								<div class="col-xl-6"></div>
							</div>
							<div class="row">
								<div class="col-xl-12">
									<div class="form-group">
										<button type="button" class="btn fks-btn-info btn-sm test-email-btn"><i class="fa fa-paper-plane-o fa-fw"></i> Send Test Email</button>
									</div>
								</div>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['EMAIL_AUTH']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_AUTH']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_USERNAME']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_USERNAME']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_PASSWORD']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_PASSWORD']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_FROM_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_FROM_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_REPLY_TO_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_REPLY_TO_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_HOSTNAME']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_HOSTNAME']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_PORT']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_PORT']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_SECURE']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_SECURE']['description'] . '</p>
						</div>
					</div>
				</div>
				
				<div class="tab-pane" id="tab3-2" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_VERIFICATION']) . '</div>
								<div class="col-xl-6"></div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_VERIFICATION_FROM_ADDRESS']) . '</div>
								<div class="col-xl-6">' . $this->formGroup($site_settings['EMAIL_VERIFICATION_REPLY_TO_ADDRESS']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-12">' . $this->formGroup($site_settings['EMAIL_VERIFICATION_SUBJECT']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-12">' . $this->formGroup($site_settings['EMAIL_VERIFICATION_TEMPLATE']) . '</div>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['EMAIL_VERIFICATION']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_VERIFICATION']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_VERIFICATION_FROM_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_VERIFICATION_FROM_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_VERIFICATION_REPLY_TO_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_VERIFICATION_REPLY_TO_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_VERIFICATION_SUBJECT']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_VERIFICATION_SUBJECT']['description'] . '</p>
							<h6>' . $site_settings['EMAIL_VERIFICATION_TEMPLATE']['title'] . '</h6>
							<p>' . $site_settings['EMAIL_VERIFICATION_TEMPLATE']['description'] . '</p>
						</div>
					</div>
				</div>
				
				<div class="tab-pane" id="tab3-3" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['FORGOT_PASSWORD']) . '</div>
								<div class="col-xl-6"></div>
							</div>
							<div class="row">
								<div class="col-xl-6">' . $this->formGroup($site_settings['FORGOT_PASSWORD_FROM_ADDRESS']) . '</div>
								<div class="col-xl-6">' . $this->formGroup($site_settings['FORGOT_PASSWORD_REPLY_TO_ADDRESS']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-12">' . $this->formGroup($site_settings['FORGOT_PASSWORD_SUBJECT']) . '</div>
							</div>
							<div class="row">
								<div class="col-xl-12">' . $this->formGroup($site_settings['FORGOT_PASSWORD_TEMPLATE']) . '</div>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['FORGOT_PASSWORD']['title'] . '</h6>
							<p>' . $site_settings['FORGOT_PASSWORD']['description'] . '</p>
							<h6>' . $site_settings['FORGOT_PASSWORD_FROM_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['FORGOT_PASSWORD_FROM_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['FORGOT_PASSWORD_REPLY_TO_ADDRESS']['title'] . '</h6>
							<p>' . $site_settings['FORGOT_PASSWORD_REPLY_TO_ADDRESS']['description'] . '</p>
							<h6>' . $site_settings['FORGOT_PASSWORD_SUBJECT']['title'] . '</h6>
							<p>' . $site_settings['FORGOT_PASSWORD_SUBJECT']['description'] . '</p>
							<h6>' . $site_settings['FORGOT_PASSWORD_TEMPLATE']['title'] . '</h6>
							<p>' . $site_settings['FORGOT_PASSWORD_TEMPLATE']['description'] . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';
		
		// Panel end
		$return .= '</div>';
		
		return $return;
	}
	
	// -------------------- Access Tab Settings -------------------- \\
	private function tabAccess($site_settings) {
		// Set vars
		$Database = new \Database();
		$hierarchy = $this->getHierarchy($_SESSION['id']);
		$access = array(
			'guest' => array(
				'options' => '',
				'values' => explode(',', $this->siteSettings('DEFAULT_ACCESS_GUEST'))
			),
			'ldap' => array(
				'options' => '',
				'values' => explode(',', $this->siteSettings('DEFAULT_ACCESS_LDAP'))
			),
			'local' => array(
				'options' => '',
				'values' => explode(',', $this->siteSettings('DEFAULT_ACCESS_LOCAL'))
			)
		);
		
		// Grab all access groups and create options
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_access_groups WHERE active = 1 AND deleted = 0'
		))) {
			foreach($Database->r['rows'] as $k => $v) {
				foreach($access as $ak => $av) {
					$access[$ak]['options'] .= '<option value="'. $k .'"'. (in_array($k, $access[$ak]['values']) ? ' selected' : '') . ($hierarchy < $v['hierarchy'] ? ' disabled' : '') . '>' . $v['title'] . '</option>';
				}
			}
		} else {
			return array('result' => 'failure', 'message' => 'Failed to grab settings from DB!');
		}
		
		// Panel start
		$return = '<div class="fks-panel tabs mar-bot-0">';
		
		// Email tabs
		$return .= '<div class="header">
			<ul class="nav nav-tabs">
				<li class="nav-item">
					<a class="nav-link active" data-toggle="tab" href="#tab4-1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> Guest</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tab4-2" role="tab" draggable="false"><i class="fa fa-handshake-o fa-fw"></i> Local</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#tab4-3" role="tab" draggable="false"><i class="fa fa-question fa-fw"></i> LDAP</a>
				</li>
			</ul>
		</div>';
		
		// Email tab bodies
		$return .= '<div class="body">
			<div class="tab-content">
				<div class="tab-pane active" id="tab4-1" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="form-group">
								<label for="DEFAULT_ACCESS_GUEST" class="form-control-label">Guest Default Access Group</label>
								<select class="form-control form-control-sm access-lists" id="DEFAULT_ACCESS_GUEST" name="DEFAULT_ACCESS_GUEST" multiple="multiple" aria-describedby="DEFAULT_ACCESS_GUEST_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>
									' . $access['guest']['options'] . '
								</select>
								<div class="form-control-feedback"></div>
								<small id="DEFAULT_ACCESS_GUEST_HELP" class="form-text text-muted">Default Access Group(s) for accounts that aren\'t logged in.</small>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['DEFAULT_ACCESS_GUEST']['title'] . '</h6>
							<p>' . $site_settings['DEFAULT_ACCESS_GUEST']['description'] . '</p>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="tab4-2" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="form-group">
								<label for="DEFAULT_ACCESS_LOCAL" class="form-control-label">Local Default Access Group</label>
								<select class="form-control form-control-sm access-lists" id="DEFAULT_ACCESS_LOCAL" name="DEFAULT_ACCESS_LOCAL" multiple="multiple" aria-describedby="DEFAULT_ACCESS_LOCAL_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>
									' . $access['local']['options'] . '
								</select>
								<div class="form-control-feedback"></div>
								<small id="DEFAULT_ACCESS_LOCAL_HELP" class="form-text text-muted">Default Access Group(s) for accounts that are created through registration.</small>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['DEFAULT_ACCESS_LOCAL']['title'] . '</h6>
							<p>' . $site_settings['DEFAULT_ACCESS_LOCAL']['description'] . '</p>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="tab4-3" role="tabpanel">
					<div class="row">
						<div class="col-xl-7">
							<div class="form-group">
								<label for="DEFAULT_ACCESS_LDAP" class="form-control-label">LDAP Default Access Group</label>
								<select class="form-control form-control-sm access-lists" id="DEFAULT_ACCESS_LDAP" name="DEFAULT_ACCESS_LDAP" multiple="multiple" aria-describedby="DEFAULT_ACCESS_LDAP_HELP"' . ($this->access == 3 ? '' : ' disabled') . '>
									' . $access['ldap']['options'] . '
								</select>
								<div class="form-control-feedback"></div>
								<small id="DEFAULT_ACCESS_LDAP_HELP" class="form-text text-muted">Default Access Group(s) for accounts that are created using LDAP.</small>
							</div>
						</div>
						<div class="col-xl-5">
							<h6>' . $site_settings['DEFAULT_ACCESS_LDAP']['title'] . '</h6>
							<p>' . $site_settings['DEFAULT_ACCESS_LDAP']['description'] . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';
		
		// Panel end
		$return .= '</div>';
		
		// Return
		return $return;
	}
	
	// -------------------- LDAP Tab Settings -------------------- \\
	private function tabLDAP($site_settings) {
		$return = '<div class="row">
			<div class="col-xl-7">		
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['ACTIVE_DIRECTORY']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_LOGIN_SELECTOR']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_SERVER']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_RDN']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_BASE_DN']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_ACCOUNT_CREATION']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_PREFERRED']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['AD_FAILOVER']) . '</div>
				</div>
				
			</div>
			<div class="col-xl-5">
				<h6>' . $site_settings['ACTIVE_DIRECTORY']['title'] . '</h6>
				<p>' . $site_settings['ACTIVE_DIRECTORY']['description'] . '</p>
				<h6>' . $site_settings['AD_LOGIN_SELECTOR']['title'] . '</h6>
				<p>' . $site_settings['AD_LOGIN_SELECTOR']['description'] . '</p>
				<h6>' . $site_settings['AD_SERVER']['title'] . '</h6>
				<p>' . $site_settings['AD_SERVER']['description'] . '</p>
				<h6>' . $site_settings['AD_RDN']['title'] . '</h6>
				<p>' . $site_settings['AD_RDN']['description'] . '</p>
				<h6>' . $site_settings['AD_BASE_DN']['title'] . '</h6>
				<p>' . $site_settings['AD_BASE_DN']['description'] . '</p>
				<h6>' . $site_settings['AD_ACCOUNT_CREATION']['title'] . '</h6>
				<p>' . $site_settings['AD_ACCOUNT_CREATION']['description'] . '</p>
				<h6>' . $site_settings['AD_PREFERRED']['title'] . '</h6>
				<p>' . $site_settings['AD_PREFERRED']['description'] . '</p>
				<h6>' . $site_settings['AD_FAILOVER']['title'] . '</h6>
				<p>' . $site_settings['AD_FAILOVER']['description'] . '</p>
			</div>
		</div>';
		
		// Return
		return $return;
	}
	
	// -------------------- Error Tab Settings -------------------- \\
	private function tabError($site_settings) {
		$return = '<div class="row">
			<div class="col-xl-7">		
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['ERROR_TO_DB']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['ERROR_TO_DISK']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['ERROR_EMAIL']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['ERROR_EMAIL_ADDRESS']) . '</div>
				</div>
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['ERROR_MESSAGE']) . '</div>
					<div class="col-xl-6"></div>
				</div>
			</div>
			<div class="col-xl-5">
				<h6>' . $site_settings['ERROR_TO_DB']['title'] . '</h6>
				<p>' . $site_settings['ERROR_TO_DB']['description'] . '</p>
				<h6>' . $site_settings['ERROR_TO_DISK']['title'] . '</h6>
				<p>' . $site_settings['ERROR_TO_DISK']['description'] . '</p>
				<h6>' . $site_settings['ERROR_EMAIL']['title'] . '</h6>
				<p>' . $site_settings['ERROR_EMAIL']['description'] . '</p>
				<h6>' . $site_settings['ERROR_EMAIL_ADDRESS']['title'] . '</h6>
				<p>' . $site_settings['ERROR_EMAIL_ADDRESS']['description'] . '</p>
				<h6>' . $site_settings['ERROR_MESSAGE']['title'] . '</h6>
				<p>' . $site_settings['ERROR_MESSAGE']['description'] . '</p>
			</div>
		</div>';
		
		return $return;
	}
	
	// -------------------- Remote Site Tab Settings -------------------- \\
	private function tabRemote($site_settings) {
		$return = '<div class="row">
			<div class="col-xl-7">		
				<div class="row">
					<div class="col-xl-6">' . $this->formGroup($site_settings['REMOTE_SITE']) . '</div>
					<div class="col-xl-6">' . $this->formGroup($site_settings['REMOTE_DATABASE']) . '</div>
				</div>
				<div class="row remote-validate">
					<div class="col-xl-6">
						<div class="form-group">
							<button type="button" class="btn fks-btn-info btn-sm remote-test-btn" style="width:100%;margin-top:26px;"><i class="fa fa-question fa-fw"></i> Validate</button>
						</div>
					</div>
					<div class="col-xl-6"></div>
				</div>
				<div style="display:none;" class="remote-user-search">
					<div class="row">
						<div class="col-xl-6">' . $this->buildFormGroup(array('title' => 'Member Search','type' => 'text','id' => 'REMOTE_SEARCH','help' => 'Search for a user on the remote DB.',
							'group' => array(
								'after' => '<btn class="input-group-addon" id="icon_preview"><i class="fa fa-search fa-fw"></i></btn>'
							))) . '</div>
						<div class="col-xl-6"><button type="button" class="btn fks-btn-info btn-sm remote-search-btn" style="width:100%;margin-top:26px;"><i class="fa fa-search fa-fw"></i> Search</button></div>
					</div>
					<div class="row">
						<div class="col-xl-12">
							<table class="table table-sm table-striped table-hover remote-admins">
								<thead class="thead-dark">
									<tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Access</th><th style="width:50px;text-align:center;padding-right:11px;"><i class="fa fa-trash fa-fw"></i></th></tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xl-5">
				<h6>' . $site_settings['REMOTE_SITE']['title'] . '</h6>
				<p>' . $site_settings['REMOTE_SITE']['description'] . '</p>
				<h6>' . $site_settings['REMOTE_DATABASE']['title'] . '</h6>
				<p>' . $site_settings['REMOTE_DATABASE']['description'] . '</p>
			</div>
		</div>';
		
		return $return;
	}
	
/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	// -------------------- Grab All Site Settings -------------------- \\
	public function loadSiteSettings() {
		// Check for read access
		if($this->access < 1) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Set vars
		$Database = new \Database();
		
		// Grab all site settings
		if($Database->Q(array(
			'assoc' => 'id',
			'query' => 'SELECT * FROM fks_site_settings'
		))) {
			$d = $Database->r['rows'];
			foreach($d as $k => $v) {
				if( empty($v['description']) ) { $d[$k]['description'] = '(NULL)'; }
				if( empty($v['help_text']) ) { $d[$k]['help_text'] = '&nbsp;'; }
			}
		} else {
			// Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
		}
		
		// Custom stuff
		if(!empty($d['EMAIL_PASSWORD']['data'])) {$d['EMAIL_PASSWORD']['data'] = '-[NOCHANGE]-';}
		
		// Tabs
		$tabs = '<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" data-toggle="tab" href="#tab1-1" role="tab" draggable="false"><i class="fa fa-gears fa-fw"></i> General</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-2" role="tab" draggable="false"><i class="fa fa-google fa-fw"></i> reCaptcha</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-3" role="tab" draggable="false"><i class="fa fa-envelope fa-fw"></i> Email</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-4" role="tab" draggable="false"><i class="fa fa-lock fa-fw"></i> Access</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-5" role="tab" draggable="false"><i class="fa fa-address-card-o fa-fw"></i> Active Directory</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-6" role="tab" draggable="false"><i class="fa fa-exclamation-triangle fa-fw"></i> Error</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#tab1-7" role="tab" draggable="false"><i class="fa fa-link fa-fw"></i> Remote Site</a>
			</li>
		</ul>';
		
		// Tab Content
		$body = '<form id="editSiteSettingsForm" class="fks-form fks-form-sm" role="form" action="javascript:void(0);" autocomplete="off">
			<div class="tab-content">
				<div class="tab-pane active" id="tab1-1" role="tabpanel">
					' . $this->tabGeneral($d) . '
				</div>
				<div class="tab-pane" id="tab1-2" role="tabpanel">
					' . $this->tabCaptcha($d) . '
				</div>
				<div class="tab-pane" id="tab1-3" role="tabpanel">
					' . $this->tabEmail($d) . '
				</div>
				<div class="tab-pane" id="tab1-4" role="tabpanel">
					' . $this->tabAccess($d) . '
				</div>
				<div class="tab-pane" id="tab1-5" role="tabpanel">
					' . $this->tabLDAP($d) . '
				</div>
				<div class="tab-pane" id="tab1-6" role="tabpanel">
					' . $this->tabError($d) . '
				</div>
				<div class="tab-pane" id="tab1-7" role="tabpanel">
					' . $this->tabRemote($d) . '
				</div>
			</div>
		</form>';
		
		// Return form
		return array('result' => 'success', 'tabs' => $tabs, 'body' => $body);
	}
	
	// -------------------- Save Site Settings -------------------- \\
	public function saveSiteSettings($data) {
		// Check for write access
		if($this->access < 2) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Decode Summernote Values
		$data['EMAIL_VERIFICATION_TEMPLATE'] = str_replace('&plus;', '+', urldecode($data['EMAIL_VERIFICATION_TEMPLATE']));
		$data['FORGOT_PASSWORD_TEMPLATE'] = str_replace('&plus;', '+', urldecode($data['FORGOT_PASSWORD_TEMPLATE']));
		
		// Remove "blank" Summernote values
		if($data['EMAIL_VERIFICATION_TEMPLATE'] == '<p><br></p>') {$data['EMAIL_VERIFICATION_TEMPLATE'] = '';}
		if($data['FORGOT_PASSWORD_TEMPLATE'] == '<p><br></p>') {$data['FORGOT_PASSWORD_TEMPLATE'] = '';}
		
		// Decode email password
		$data['EMAIL_PASSWORD'] = base64_decode($data['EMAIL_PASSWORD']);
		
		// Set variables
		$Validator = new \Validator($data);
		$Database = new \Database();
		$failed = array();
		$updated = array();
		$connections = array();
		$do_log = true;							// Whether we create a member log when saving
		
		// Get allowed connections
		foreach($Database->db as $k => $v) {
			if( $k != 'persist' && $k != 'default' && $k != $Database->db['default'] ){ array_push($connections, $k); }
		}

        // Grab Current Settings
        if($Database->Q(array(
            'assoc' => 'id',
            'query' => 'SELECT * FROM fks_site_settings'
        ))){
            $site_settings = $Database->r['rows'];
        }else{
            // Return error message with error code
			return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
        }
		
		// Validation
		$Validator->validate('SITE_HOME_PAGE', array('required' => false));
		$Validator->validate('SITE_LAYOUT', array('required' => true, 'values' => json_decode($site_settings['SITE_LAYOUT']['misc'], true)['options']));
		$Validator->validate('SITE_TITLE', array('required' => true, 'min_length' => 3, 'max_length' => 15));
		$Validator->validate('SITE_USERNAME', array('required' => true));
		
		$Validator->validate('MEMBER_REGISTRATION', array('required' => true, 'bool' => true));
		$Validator->validate('REQUIRE_LOGIN', array('required' => true, 'bool' => true));
		$Validator->validate('TIMEZONE', array('timezone' => true));
		$Validator->validate('DATE_FORMAT', array('required' => true));
		$Validator->validate('PROTECTED_USERNAMES', array('max_length' => 255));
		
		$Validator->validate('CAPTCHA', array('required' => true, 'bool' => true));
		$Validator->validate('CAPTCHA_PRIVATE', array('required' => ($data['CAPTCHA'] == 1), 'max_length' => 100));
		$Validator->validate('CAPTCHA_PUBLIC', array('required' => ($data['CAPTCHA'] == 1), 'max_length' => 100));

        $Validator->validate('EMAIL_AUTH', array('required' => true, 'bool' => true));
        $Validator->validate('EMAIL_USERNAME', array('required' => ($data['EMAIL_AUTH'] == 1)));
        $Validator->validate('EMAIL_PASSWORD', array('required' => ($data['EMAIL_AUTH'] == 1)));
        $Validator->validate('EMAIL_HOSTNAME', array('min_length' => 3, 'required' => ($data['EMAIL_AUTH'] == 1 || $data['EMAIL_VERIFICATION'] == 1 || $data['FORGOT_PASSWORD'] == 1)));
        $Validator->validate('EMAIL_PORT', array('number' => true));
        $Validator->validate('EMAIL_SECURE', array('required' => true, 'values' => json_decode($site_settings['EMAIL_SECURE']['misc'], true)['options']));
        $Validator->validate('EMAIL_FROM_ADDRESS', array('email' => true));
        $Validator->validate('EMAIL_REPLY_TO_ADDRESS', array('email' => true));

		$Validator->validate('EMAIL_VERIFICATION', array('required' => true, 'bool' => true));
		$Validator->validate('EMAIL_VERIFICATION_FROM_ADDRESS', array('required' => ($data['EMAIL_VERIFICATION'] == 1 && empty($data['EMAIL_FROM_ADDRESS'])), 'email' => true));
		$Validator->validate('EMAIL_VERIFICATION_REPLY_TO_ADDRESS', array('email' => true));
		$Validator->validate('EMAIL_VERIFICATION_SUBJECT', array('required' => ($data['EMAIL_VERIFICATION'] == 1)));
		$Validator->validate('EMAIL_VERIFICATION_TEMPLATE', array('required' => ($data['EMAIL_VERIFICATION'] == 1), 'min_length' => 15));

        $Validator->validate('ERROR_EMAIL', array('required' => true, 'bool' => true));
        $Validator->validate('ERROR_EMAIL_ADDRESS', array('required' => ($data['ERROR_EMAIL'] == 1), 'email' => true));
        $Validator->validate('ERROR_MESSAGE', array('required' => true));
        $Validator->validate('ERROR_TO_DB', array('required' => true, 'bool' => true));
        $Validator->validate('ERROR_TO_DISK', array('required' => true, 'values' => json_decode($site_settings['ERROR_TO_DISK']['misc'], true)['options']));
		
        $Validator->validate('FORGOT_PASSWORD', array('required' => true, 'bool' => true));
        $Validator->validate('FORGOT_PASSWORD_FROM_ADDRESS', array('required' => ($data['FORGOT_PASSWORD'] == 1 && empty($data['EMAIL_FROM_ADDRESS'])), 'email' => true));
        $Validator->validate('FORGOT_PASSWORD_REPLY_TO_ADDRESS', array('email' => true));
		
		$Validator->validate('DEFAULT_ACCESS_GUEST', array('required' => true));
		$Validator->validate('DEFAULT_ACCESS_LOCAL', array('required' => true));
		$Validator->validate('DEFAULT_ACCESS_LDAP', array('required' => true));
		
		$Validator->validate('REMOTE_SITE', array('required' => true, 'values' => json_decode($site_settings['REMOTE_SITE']['misc'], true)['options']));
		$Validator->validate('REMOTE_DATABASE', array('required' => ($data['REMOTE_SITE'] == 'Secondary'), 'values' => $connections));
		$Validator->validate('REMOTE_ADMINS', array('required' => ($data['REMOTE_SITE'] == 'Secondary')));
		
		$Validator->validate('ACTIVE_DIRECTORY', array('required' => true, 'bool' => true));
		$Validator->validate('AD_ACCOUNT_CREATION', array('required' => true, 'bool' => true));
		$Validator->validate('AD_FAILOVER', array('required' => true, 'bool' => true));
		$Validator->validate('AD_LOGIN_SELECTOR', array('required' => true, 'values' => ($data['ACTIVE_DIRECTORY'] ? array(0,1) : array(0) )));
		$Validator->validate('AD_PREFERRED', array('required' => true, 'values' => ($data['ACTIVE_DIRECTORY'] ? array('LDAP', 'Local') : array('Local'))));
		$Validator->validate('AD_RDN', array('required' => ($data['ACTIVE_DIRECTORY'] == 1), 'min_length' => 1));
		$Validator->validate('AD_SERVER', array('required' => ($data['ACTIVE_DIRECTORY'] == 1), 'min_length' => 1));
		$Validator->validate('AD_BASE_DN', array('required' => ($data['ACTIVE_DIRECTORY'] == 1), 'min_length' => 1));
		
		if( !$Validator->getResult() ){ return array('result' => 'validate', 'message' => 'There were issues with the form.', 'validation' => $Validator->getOutput()); }
		
		$form = $Validator->getForm();
		
		// Unset email password if no change
		if($form['EMAIL_PASSWORD'] == '-[NOCHANGE]-') {
			unset($form['EMAIL_PASSWORD']);
		} else {
			if( !empty($form['EMAIL_PASSWORD']) ) {
				$Crypter = new \Crypter();
				$form['EMAIL_PASSWORD'] = $Crypter->toRJ256($form['EMAIL_PASSWORD']);
			}
		}
		
		// Return failure if Remote Site AND Active Directory is enabled
		if($form['ACTIVE_DIRECTORY'] == 1 && $form['REMOTE_SITE'] != 'disabled') {
			return array('result' => 'failure', 'message' => 'You can not have both Active Directory and Remote Site enabled!');
		}
		
		// TESTING the activation of Remote Site settings
		if(($site_settings['REMOTE_SITE']['data'] == 'Disabled' || $site_settings['REMOTE_SITE']['data'] == 'Primary') && $form['REMOTE_SITE'] == 'Secondary') {
			// We do not want to create a member log
			$do_log = false;

			// Connect to Primary site to generate an ID
			if($Database->Q(array(
				'db' => $form['REMOTE_DATABASE'],
				'query' => 'SELECT data FROM fks_site_settings WHERE id = "REMOTE_SITE_IDS"'
			))){
				// Check for the record
				if($Database->r['found'] == 0){
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Remote database is missing data!');
				}
				
				// Explode ID's if not null
				$_ids = is_null($Database->r['row']['data']) ? array() : explode(',', $Database->r['row']['data']);
				
				// Loop through known ID's and generate a new one
				while(true){
					$_key = $this->makeKey(4);
					if(!in_array($_key, $_ids)){break;}
				}
				
				// Add key to the known list
				array_push($_ids, $_key);
			} else {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Update remote sites list of ID's
			if(!$Database->Q(array(
				'db' => $form['REMOTE_DATABASE'],
				'params' => array(
					':data' => implode(',', $_ids)
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = "REMOTE_SITE_IDS"'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Update local site's current ID
			if(!$Database->Q(array(
				'params' => array(
					':data' => $_key
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = "REMOTE_ID"'
			))){
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
			}
			
			// Reset tables
			if(!$this->resetLocalTables()) {
				// Return error message with error code
				return array('result' => 'failure', 'title' => 'Database Error', 'message' => 'Failed to reset tables!');
			}
			
			// Add passed member access group
			foreach($form['REMOTE_ADMINS'] as $k => $v) {
				if(!$Database->Q(array(
					'params' => array(
						':id' => 8,
						':member_id' => $v['id'],
						':data' => $v['access_id']
					),
					'query' => 'INSERT INTO fks_member_data SET id = :id, member_id = :member_id, data = :data'
				))){
					// Return error message with error code
					return array('result' => 'failure', 'title' => 'Database Error', 'message' => $this->createError($Database->r));
				}
			}
			
			// Do not save this data
			unset($form['REMOTE_ADMINS']);
			
			// Log out current user
			$this->Session->destroy();
		}
		
		// Set the Remote ID if setting REMOTE_SITE to Primary
		if($form['REMOTE_SITE'] == 'Primary') {
			if(!$Database->Q('UPDATE fks_site_settings SET data = 0 WHERE id = "REMOTE_ID"')){
				$failed['REMOTE_SITE'] = 'Could not save to the DB!';
				unset($form['REMOTE_SITE']);
				
				// TODO - set tables auto increment back to 1?
			}
		}
		
		// Set the Remote ID if setting REMOTE_SITE to Disabled
		if($form['REMOTE_SITE'] == 'Disabled') {
			if(!$Database->Q('UPDATE fks_site_settings SET data = NULL WHERE id = "REMOTE_ID"')){
				$failed['REMOTE_SITE'] = 'Could not save to the DB!';
				unset($form['REMOTE_SITE']);
				
				// TODO - set tables auto increment back to 1?
			}
		}
		
		// Check For Changes/Failures
		foreach($form as $k => $v) {
			// Change blank values to NULL
		    if($v == ''){$v = NULL;}

		    // Ignore values that have not changed
			if($v == $site_settings[$k]['data']){continue;}

			// Update the value in the DB
			if(!$Database->Q(array(
				'params' => array(
					':id' => $k,
					':data' => $v
				),
				'query' => 'UPDATE fks_site_settings SET data = :data WHERE id = :id'
			))){
				$failed[$k] = 'Could not save to the DB!';
			}else{
				$updated[$k] = array($site_settings[$k]['data'], $v);
			}
		}
		
		// Add Member Log
		if(count($updated) > 0 && $do_log) {
			$MemberLog = new \MemberLog(\Enums\LogActions::SITE_SETTINGS_MODIFIED, $_SESSION['id'], NULL, json_encode($updated));
		}
		
		// Return Status
		if(count($failed) > 0) {
			return array('result' => 'validate', 'message' => 'Some settings were not saved!', 'validation' => $failed, 'do_log' => $do_log);
		} else {
		    if( count($updated) > 0 ) {
                return array('result' => 'success', 'message' => 'Settings have been updated!', 'updated_count' => count($updated), 'reload' => (isset($updated['SITE_LAYOUT']) ? 'true' : 'false'), 'do_log' => $do_log);
            } else {
                return array('result' => 'info', 'message' => 'No changes detected!', 'updated_count' => count($updated), 'do_log' => $do_log);
            }
		}
	}
	
	// -------------------- Reset Locak Tables -------------------- \\
	private function resetLocalTables() {
		// Set variables
		$Database = new \Database();
		$return = true;
		
		// Clear member_data table
		if(!$Database->Q('DELETE FROM fks_member_data')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Reset member_data table auto increment
		if(!$Database->Q('ALTER TABLE fks_member_data AUTO_INCREMENT = 1')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Clear member_logs table
		if(!$Database->Q('DELETE FROM fks_member_logs')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Reset member_logs table auto increment
		if(!$Database->Q('ALTER TABLE fks_member_logs AUTO_INCREMENT = 1')){
			$this->createError($Database->r);
			$return = false;
		}
		
		// Return
		return $return;
	}
	
	// -------------------- Load Site Settings History -------------------- \\
	public function loadSiteSettingsHistory() {
		// Check for admin access
		if($this->access < 3) { return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$history = $this->loadHistory(array(
			'title' => 'Site Settings History',
			'id' => null,
			'actions' => array(
				\Enums\LogActions::SITE_SETTINGS_MODIFIED
			)
		));
		
		return $history;
	}

    // -------------------- Edit Member -------------------- \\
    public function sendEmailForm() {
		// Check for write access
        if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }

        $body = '<form id="editModalForm" role="form" action="javascript:void(0);">
			<div class="row">
				<div class="col-md-12">
				    <p>This will use the current saved site settings so make sure your settings have been saved before attempting to send a test message.</p>
					<div class="form-group">
						<label for="to_address" class="form-control-label">To Address</label>
						<input type="email" class="form-control form-control-sm" id="to_address" name="to_address" aria-describedby="to_address_HELP" value="' . (isset($m['username']) ? $m['username'] : '') . '"' . ($this->access > 2 ? '' : $readonly) . '>
						<div class="form-control-feedback"></div>
						<small id="to_address_HELP" class="form-text text-muted">&nbsp;</small>
					</div>
				</div>
			</div>
		</form>';

        $parts = array(
            'title' => 'Send Test Email Form',
            'body' => $body,
            'footer' => ''
                . '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
                . '<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editModalForm"><i class="fa fa-paper-plane-o fa-fw"></i> Send Email</button>'
        );

        return array('result' => 'success', 'parts' => $parts);
    }

    // -------------------- Function For Sending Test Email -------------------- \\
    public function sendTestMail($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		$mail = $this->sendEmail(array(
			'to_address' => $data['to_address'],
			'subject' => 'Test Email',
			'body' => 'Hello<br><br>This is a test message!'
		));
		
		if($mail['result'] == 'success') {
			return array('result' => 'success', 'message' => 'Test email has been sent!');
		} else {
			return array('result' => 'failure', 'message' => $mail['message']);
		}
	}
	
	// -------------------- Remote Database Validator -------------------- \\
    public function validateRemoteDatabase($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Check for data
	    if( !isset($data) || is_null($data) ){ return array('result' => 'failure', 'message' => 'Data was not passed...'); }
		
		// Create database class
		$Database = new \Database(array('db' => $data));
		
		// Check for valid connection
		if( !isset($Database->db[$data]) ){ return array('result' => 'failure', 'message' => 'Not a valid connection.'); }
		
		// Attempt a connection
		if( !$Database->con() ){ return array('result' => 'failure', 'message' => $Database->r['error']); }
		
		// Return
		return array('result' => 'success', 'message' => 'Successfully validated!', 'data' => $data);
	}
	
	// -------------------- Remote Database Search -------------------- \\
    public function searchRemoteDatabase($data) {
        // Check for write access
	    if( $this->access < 2 ){ return array('result' => 'failure', 'message' => 'Access Denied!'); }
		
		// Check for data
	    if( !isset($data) || is_null($data) ){ return array('result' => 'failure', 'message' => 'Data was not passed...'); }
		
		// Create local database class
		$Database = new \Database();
		
		// Grab local access groups
		if($Database->Q('SELECT id, title FROM fks_access_groups WHERE active = 1 AND deleted = 0 ORDER BY hierarchy DESC')) {
			$access = '';
			foreach($Database->r['rows'] as $k => $v) {
				$access .= '<option value="' . $v['id'] . '">' . $v['title'] . '</option>';
			}
		} else {
			return array('result' => 'failure', 'message' => 'Unable to load access groups!');
		}
		
		// Search for remote member
		if($Database->Q(array(
			'db' => $data['database'],
			'params' => array(
				'username' => '%' . $data['search'] . '%'
			),
			'query' => 'SELECT id, username FROM fks_members WHERE username LIKE :username AND active = 1 AND deleted = 0'
		))) {
			$table = '';
			foreach($Database->r['rows'] as $k => $v) {
				$table .= '<tr><td>' . $v['id'] . '</td><td class="username">' . $v['username'] . '</td><td><select member-id="' . $v['id'] . '" class="form-control form-control-sm member-access">' . $access . '</select></td><td><button type="button" class="btn fks-btn-success btn-sm member-add-btn"><i class="fa fa-plus fa-fw"></i></button></td></tr>';
			}
		} else {
			return array('result' => 'failure', 'message' => 'Database failure!');
		}
		
		// Create the modal body
		$body = '<table class="table table-striped table-hover table-sm"><thead class="thead-dark"><tr><th>ID</th><th>Username</th><th>Access</th><th style="width:50px;text-align:center;padding-right:11px;"><i class="fa fa-plus fa-fw"></i></th></tr></thead><tbody>' . $table . '</tbody></table>';
		
		// Return parts
        return array(
			'result' => 'success',
			'parts' => array(
				'title' => 'Member Lookup',
				'size' => 'lg',
				'body' => $body,
				'footer' => '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times fa-fw"></i> Close</button>'
			)
		);
	}
}
?>