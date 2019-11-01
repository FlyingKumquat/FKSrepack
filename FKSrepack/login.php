<?PHP
	if(is_dir('install')) {
		header('Location:splash.php');
	}
	require_once(__DIR__ . '/scripts/php/views/manager.php');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="">
		<meta name="author" content="FKSrepack">
		<link rel="icon" href="<?=(empty($Manager->siteSettings('SITE_FAVICON_URL')) ? 'img/_favicon.ico' : $Manager->siteSettings('SITE_FAVICON_URL'))?>">

		<title><?=$Manager->siteSettings('SITE_TITLE')?> : Login</title>

		<link href="scripts/css/font-awesome.min.css" rel="stylesheet">
		
		<link href="scripts/js/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="scripts/js/plugins/iCheck/skins/minimal/_all.css" rel="stylesheet">
		<link href="scripts/js/plugins/fks/fks.css" rel="stylesheet">
		
		<?PHP if(is_file(__DIR__ . '/scripts/css/autoloader.css')) { echo '<link href="scripts/css/autoloader.css" rel="stylesheet">';} ?>
	</head>

	<body class="fks-login">
		<div class="row justify-content-center title">
			<?PHP
				if(empty($Manager->siteSettings('SITE_LOGO_LOGIN'))) {
					echo '<img src="img/_favicon.ico" /><span><b>FKS</b>repack</span>';
				} else {
					echo $Manager->siteSettings('SITE_LOGO_LOGIN');
				}
			?>
		</div>
		<div class="row justify-content-center">
			<div class="col-lg-6">
				<!------- Account Login Panel ------->
				<div class="fks-panel login">
					<div class="header">
						<span class="title">
							Account Login
						</span>
					</div>
					<div class="body" style="padding-bottom: 0px;">
						<?PHP
							$login_inputs = array(
								'username' => array(
									'title' => 'Username',
									'type' => 'text',
									'name' => 'username',
									'attributes' => array(
										'placeholder' => 'Username'
									)
								),
								'password' => array(
									'title' => 'Password',
									'type' => 'password',
									'name' => 'password',
									'attributes' => array(
										'placeholder' => 'Password'
									)
								),
								'verify_code' => array(
									'title' => 'Verify Code',
									'type' => 'text',
									'name' => 'verify_code',
									'attributes' => array(
										'placeholder' => '1234'
									)
								),
								'selector' => array(
									'title' => 'Account Type',
									'type' => 'select',
									'name' => 'selector',
									'options' => array(
										array('title' => 'Automatic (Default)', 'value' => 'default'),
										array('title' => 'Active Directory', 'value' => 'ldap'),
										array('title' => 'Local', 'value' => 'local')
									)
								)
							);
						
							$login_form = '
								<div class="alert fks-alert-danger" role="alert"></div>
								<div class="alert fks-alert-info" role="alert"></div>
								<div class="alert fks-alert-success" role="alert"></div>
								<button type="submit" style="display: none;"></button>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($login_inputs['username']) . '
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($login_inputs['password']) . '
									</div>
								</div>
								<div class="row verify-code" style="display: none;">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($login_inputs['verify_code']) . '
									</div>
								</div>
							';
							
							if($Manager->siteSettings('AD_LOGIN_SELECTOR') == 1) {
								$login_form .= '
									<div class="row">
										<div class="col-md-12">
											' . $Manager->buildFormGroup($login_inputs['selector']) . '
										</div>
									</div>
								';	
							}
							
							echo '<form id="login_form" class="fks-form" action="javascript:void(0);">' . $login_form . '</form>';
						?>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success" fks-action="submitForm" fks-target="#login_form"><i class="fa fa-sign-in fa-fw"></i> Login</button>
						<?PHP
							if($Manager->siteSettings('MEMBER_REGISTRATION') == 0) {
								if($Manager->siteSettings('FORGOT_PASSWORD') == 1) {
									echo '<small><a href="javascript:void(0);">Forgot Password</a></small>';
								}
							} else {
								echo '<small><a href="javascript:void(0);" fks-action="toggle-forms" fks-target="register">Register</a>' . ($Manager->siteSettings('FORGOT_PASSWORD') == 1 ? '&nbsp;&nbsp;|&nbsp;&nbsp;<a href="javascript:void(0);" fks-action="toggle-forms" fks-target="forgot">Forgot Password</a>' : '') . '</small>';
							}
						?>
						
					</div>
				</div>

				<?PHP if($Manager->siteSettings('MEMBER_REGISTRATION') == 0) { goto skipMemberRegistration; } ?>
				<!------- Member Registration Panel ------->
				<div class="fks-panel register" style="display: none;">
					<div class="header">
						<span class="title">
							Account Registration
						</span>
					</div>
					<div class="body" style="padding-bottom: 0px;">
						<?PHP
							$register_inputs = array(
								'username' => array(
									'title' => 'Username',
									'type' => 'text',
									'name' => 'username_register',
									'required' => true,
									'attributes' => array(
										'placeholder' => 'Username'
									)
								),
								'email' => array(
									'title' => 'Email',
									'type' => 'email',
									'name' => 'email_register',
									'required' => $Manager->siteSettings('EMAIL_VERIFICATION') == 1,
									'attributes' => array(
										'placeholder' => 'Email'
									)
								),
								'password' => array(
									'title' => 'Password',
									'type' => 'password',
									'name' => 'password_register',
									'required' => true,
									'attributes' => array(
										'placeholder' => 'Password'
									)
								),
								'repeat_password' => array(
									'title' => 'Repeat Password',
									'type' => 'password',
									'name' => 'repeat_password_register',
									'required' => true,
									'attributes' => array(
										'placeholder' => 'Repeat Password'
									)
								)
							);
							
							$register_form = '
								<div class="alert fks-alert-danger" role="alert"></div>
								<button type="submit" style="display: none;"></button>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($register_inputs['username']) . '
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($register_inputs['email']) . '
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($register_inputs['password']) . '
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($register_inputs['repeat_password']) . '
									</div>
								</div>
							';
							
							if($Manager->siteSettings('CAPTCHA') == 1) {
								$register_form .= '
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<script src="https://www.google.com/recaptcha/api.js"></script>
												<label for="g-recaptcha-response" class="form-control-label">Captcha</label>
												<div class="col-12">
													<div class="g-recaptcha" data-sitekey="' . $Manager->siteSettings('CAPTCHA_PUBLIC') . '"></div>
													<div class="form-control-feedback"></div>
												</div>
											</div>
										</div>
									</div>
								';	
							}
							
							echo '<form id="register_form" class="fks-form" action="javascript:void(0);">' . $register_form . '</form>';
						?>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success" fks-action="submitForm" fks-target="#register_form"><i class="fa fa-edit fa-fw"></i> Register</button>
						<small><a href="javascript:void(0);" fks-action="toggle-forms" fks-target="login">Login</a></small>
					</div>
				</div>
				<?PHP skipMemberRegistration: ?>
				
				<?PHP if($Manager->siteSettings('FORGOT_PASSWORD') == 0) { goto skipForgotPassword; } ?>
				<!------- Forgot Password Panel ------->
				<div class="fks-panel forgot" style="display: none;">
					<div class="header">
						<span class="title">
							Forgot Password
						</span>
					</div>
					<div class="body" style="padding-bottom: 0px;">
						<?PHP
							$forgot_inputs = array(
								'email' => array(
									'title' => 'Email',
									'type' => 'email',
									'name' => 'email_register',
									'attributes' => array(
										'placeholder' => 'Username'
									)
								)
							);
							
							$forgot_form = '
								<div class="alert fks-alert-danger" role="alert"></div>
								<button type="submit" style="display: none;"></button>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($forgot_inputs['email']) . '
									</div>
								</div>
							';
							
							echo '<form id="forgot_form" class="fks-form" action="javascript:void(0);">' . $forgot_form . '</form>';
						?>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success" fks-action="submitForm" fks-target="#forgot_form"><i class="fa fa-undo fa-fw"></i> Reset</button>
						<small><a href="javascript:void(0);" fks-action="toggle-forms" fks-target="login">Login</a></small>
					</div>
				</div>
				<?PHP skipForgotPassword: ?>
				
				<!------- Add Email Panel ------->
				<div class="fks-panel add-email" style="display: none;">
					<div class="header">
						<span class="title">
							Add Email Address
						</span>
					</div>
					<div class="body" style="padding-bottom: 0px;">
						<?PHP
							$email_inputs = array(
								'email' => array(
									'title' => 'Email',
									'type' => 'email',
									'name' => 'email',
									'attributes' => array(
										'id' => 'email_add',
										'placeholder' => 'Username'
									)
								)
							);
							
							$email_form = '
								<div class="alert fks-alert-danger" role="alert"></div>
								<div class="alert fks-alert-info" role="alert"></div>
								<button type="submit" style="display: none;"></button>
								<div class="row">
									<div class="col-md-12">
										' . $Manager->buildFormGroup($email_inputs['email']) . '
									</div>
								</div>
							';
							
							echo '<form id="email_form" class="fks-form" action="javascript:void(0);">' . $email_form . '</form>';
						?>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success" fks-action="submitForm" fks-target="#email_form"><i class="fa fa-plus fa-fw"></i> Add</button>
					</div>
				</div>
			</div>
		</div>
		<script src="scripts/js/plugins/jquery/jquery-3.2.1.min.js"></script>
		<script src="scripts/js/plugins/fks/fks.js"></script>
		<script src="scripts/js/ie10-viewport-bug-workaround.js"></script>
		<script>
			$(document).ready(function() {
				fks.submitForm();
				
				fks.debug.ajax = true;
				
				$('[fks-action="toggle-forms"]').on('click', function() {
					togglePanels($(this).attr('fks-target'));
				});
				
				$('#login_form [name="username"]').focus();
				
				// ---------- Login Form ---------- //
				$('#login_form').on('submit', function() {
					var form = $(this),
						data = fks.superSerialize(form);
					data.password = btoa(data.password);
					$('input, button', form).prop('disabled', true);
					form.children('.alert').html('');
					fks.block( form.parents('.fks-panel') );
					$.post(fks.handler, {wait: true, action: 'accountLogin', data: data})
					.done(function(data) {
						// Catch server errors
						try {
							var response = JSON.parse(data);
							if(fks.debug.ajax) { console.log(response); }
						} catch(e) {
							if(fks.debug.ajax) { console.log(data); }
							$('#login_form .fks-alert-danger').html('Server error!');
							return;
						}
						switch(response.result) {
							case 'success':
								if(response.alerts.length > 0) {
									localStorage.setItem('alerts', JSON.stringify(response.alerts));
								}
								
								window.location = '/' + window.location.hash;
								break;
								
							case 'verify':
								$('#login_form .verify-code').show();
								$('#login_form .fks-alert-info').html(response.message);
								break;
								
							case 'email':
								togglePanels('add-email');
								$('#email_form .fks-alert-info').html(response.message);
								break;
								
							case 'failure':
								$('#login_form .fks-alert-danger').html(response.message);
								break;
								
							default:
								break;
						}
					})
					.always(function(data){
						$('input, button', form).prop('disabled', false);
						fks.unblock( form.parents('.fks-panel') );
						<?PHP if($Manager->siteSettings('CAPTCHA') == 1) { echo 'grecaptcha.reset();'; } ?>
					});
				});
				
				// ---------- Add Email Form ---------- //
				$('#email_form').on('submit', function() {
					var form = $(this),
						data = fks.superSerialize(form);
					$('input, button', form).prop('disabled', true);
					form.children('.alert').html('');
					fks.block( form.parents('.fks-panel') );
					$.post(fks.handler, {wait: true, action: 'accountAddEmail', data: data})
					.done(function(data) {
						// Catch server errors
						try {
							var response = JSON.parse(data);
							if(fks.debug.ajax) { console.log(response); }
						} catch(e) {
							if(fks.debug.ajax) { console.log(data); }
							$('#email_form .fks-alert-danger').html('Server error!');
							return;
						}
						switch(response.result) {
							case 'success':
								togglePanels('login');
								$('#login_form .alert').html('');
								$('#login_form .fks-alert-success').html(response.message);
								if( response.verify ) {
									$('#login_form .verify-code').show();
								}
								break;
								
							case 'login':
								togglePanels('login');
								$('#login_form .alert').html('');
								$('#login_form .fks-alert-danger').html(response.message);
								$('#login_form .verify-code').hide();
								break;
								
							case 'failure':
								$('#email_form .fks-alert-danger').html(response.message);
								break;
								
							default:
								break;
						}
					})
					.always(function(data){
						$('input, button', form).prop('disabled', false);
						fks.unblock( form.parents('.fks-panel') );
						<?PHP if($Manager->siteSettings('CAPTCHA') == 1) { echo 'grecaptcha.reset();'; } ?>
					});
				});
				
				<?PHP if($Manager->siteSettings('MEMBER_REGISTRATION') == 0) { goto skipRegistrationFunction; } ?>
				$('#register_form').on('submit', function() {
					var form = $(this),
						data = fks.superSerialize(form);
					$('input, button', form).prop('disabled', true);
					form.children('.alert').html('');
					fks.formValidate(form);
					fks.block( form.parents('.fks-panel') );
					$.post(fks.handler, {wait: true, action: 'accountRegister', data: data})
					.done(function(data) {
						// Catch server errors
						try {
							var response = JSON.parse(data);
							if(fks.debug.ajax) { console.log(response); }
						} catch(e) {
							if(fks.debug.ajax) { console.log(data); }
							$('#register_form .fks-alert-danger').html('Server error!');
							return;
						}
						switch(response.result) {
							case 'success':
								togglePanels('login');
								$('#login_form .alert').html('');
								$('#login_form .fks-alert-success').html(response.message);
								if( response.verify == '1' ) {
									$('#login_form .verify-code').show();
								}
								break;
								
							case 'failure':
								$('#register_form .fks-alert-danger').html(response.message);
								break;
								
							case 'validate':
								$('#register_form .fks-alert-danger').html(response.message);
								fks.formValidate(form, response.validation);
								break;
								
							default:
								break;
						}
					})
					.always(function(data){
						$('input, button', form).prop('disabled', false);
						fks.unblock( form.parents('.fks-panel') );
						<?PHP if($Manager->siteSettings('CAPTCHA') == 1) { echo 'grecaptcha.reset();'; } ?>
					});
				});
				<?PHP skipRegistrationFunction: ?>
				
				<?PHP if($Manager->siteSettings('FORGOT_PASSWORD') == 0) { goto skipForgotFunction; } ?>
				$('#forgot_form').on('submit', function() {
					var form = $(this),
						data = fks.superSerialize(form);
					$('input, button', form).prop('disabled', true);
					form.children('.alert').html('');
					fks.formValidate(form);
					fks.block( form.parents('.fks-panel') );
					$.post(fks.handler, {wait: true, action: 'forgotPassword', data: data})
					.done(function(data) {
						// Catch server errors
						try {
							var response = JSON.parse(data);
							if(fks.debug.ajax) { console.log(response); }
						} catch(e) {
							if(fks.debug.ajax) { console.log(data); }
							form.children('.alert').html('Server error!');
							return;
						}
						switch(response.result) {
							case 'success':
								togglePanels('login');
								$('.fks-panel.login .alert').html('');
								$('.fks-panel.login .fks-alert-success').html(response.message);
								break;
								
							case 'failure':
								form.children('.alert').html(response.message);
								break;
								
							case 'validate':
								form.children('.alert').html(response.message);
								fks.formValidate(form, response.validation);
								break;
								
							default:
								break;
						}
					})
					.always(function(data){
						$('input, button', form).prop('disabled', false);
						fks.unblock( form.parents('.fks-panel') );
						<?PHP if($Manager->siteSettings('CAPTCHA') == 1) { echo 'grecaptcha.reset();'; } ?>
					});
				});
				<?PHP skipForgotFunction: ?>
			});
			
			// ---------- Toggle Between Panels ---------- //
			function togglePanels(panel_id) {
				$('.fks-panel').each(function() {
					$(this).hide();
				});
				
				if($('.fks-panel.' + panel_id).length > 0) {
					$('.fks-panel.' + panel_id).show();
				} else {
					$('.fks-panel.login').show();
				}
				
				$('.fks-panel.' + panel_id + ' [name]:visible:first').focus();
			}
		</script>
	</body>
</html>