<?PHP require_once(__DIR__ . '/scripts/php/views/manager.php'); ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="">
		<meta name="author" content="FKSrepack">
		<link rel="icon" href="img/favicon.ico">

		<title><?=$Manager->siteSettings('SITE_TITLE')?> : Login</title>

		<link href="scripts/css/font-awesome.min.css" rel="stylesheet">
		
		<link href="scripts/js/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="scripts/js/plugins/iCheck/skins/minimal/_all.css" rel="stylesheet">
		<link href="scripts/js/plugins/fks/fks.css" rel="stylesheet">
		
		<link href="scripts/css/themes/black.css" rel="stylesheet">
	</head>

	<body class="fks-login">
		<div class="row justify-content-center title">
			<img src="img/favicon.ico" /><span><b>FKS</b>repack</span>
		</div>
		<div class="row justify-content-center">
			<div class="col-md-6">
				<!------- Login Panel ------->
				<div class="fks-panel login">
					<div class="header">
						<span class="title">
							Account Login
						</span>
					</div>
					<div class="body">
						<form id="login_form" action="javascript:void(0);">
							<div class="alert fks-alert-danger" role="alert"></div>
							<div class="alert fks-alert-info" role="alert"></div>
							<div class="alert fks-alert-success" role="alert"></div>
							<button type="submit" style="display: none;"></button>
							<div class="form-group row">
								<label for="username" class="col-3 form-control-label">Username</label>
								<div class="col-9">
									<input type="text" class="form-control form-control-sm" id="username" name="username" placeholder="Username">
								</div>
							</div>
							<div class="form-group row">
								<label for="password" class="col-3 form-control-label">Password</label>
								<div class="col-9">
									<input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password">
								</div>
							</div>
							<div class="form-group row verify-code" style="display:none;">
								<label for="verify_code" class="col-3 form-control-label">Verify Code</label>
								<div class="col-9">
									<input type="number" class="form-control form-control-sm" id="verify_code" name="verify_code" placeholder="1234">
								</div>
							</div>
							<?PHP
								if($Manager->siteSettings('AD_LOGIN_SELECTOR') == 1) {
								//if($Manager->siteSettings('AD_LOGIN_SELECTOR') == 1 && $Manager->siteSettings('AD_FAILOVER') == 1) {
									echo '<div class="form-group row">
										<label for="selector" class="col-3 form-control-label">Account Type</label>
										<div class="col-9">
											<select class="form-control form-control-sm" id="selector" name="selector">
												<option value="default">Automatic (Default)</option>
												<option value="ldap">Active Directory</option>
												<option value="local">Local</option>
											</select>
										</div>
									</div>';	
								}
							?>
						</form>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#login_form"><i class="fa fa-sign-in fa-fw"></i> Login</button>
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
				
				<!------- Registration Panel ------->
				<?PHP if($Manager->siteSettings('MEMBER_REGISTRATION') == 0) { goto skipRegistration; } ?>
				<div class="fks-panel register" style="display: none;">
					<div class="header">
						<span class="title">
							Account Registration
						</span>
					</div>
					<div class="body">
						<form id="register_form" action="javascript:void(0);">
							<div class="alert fks-alert-danger" role="alert"></div>
							<button type="submit" style="display: none;"></button>
							<div class="form-group row">
								<label for="username_register" class="col-3 form-control-label">Username</label>
								<div class="col-9">
									<input type="text" class="form-control form-control-sm" id="username_register" name="username_register" placeholder="Username">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
							<div class="form-group row">
								<label for="email_register" class="col-3 form-control-label">Email</label>
								<div class="col-9">
									<input type="email" class="form-control form-control-sm" id="email_register" name="email_register" placeholder="Email">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
							<div class="form-group row">
								<label for="password_register" class="col-3 form-control-label">Password</label>
								<div class="col-9">
									<input type="password" class="form-control form-control-sm" id="password_register" name="password_register" placeholder="Password">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
							<div class="form-group row">
								<label for="repeat_password_register" class="col-3 form-control-label">Repeat</label>
								<div class="col-9">
									<input type="password" class="form-control form-control-sm" id="repeat_password_register" name="repeat_password_register" placeholder="Repeat Password">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
							<?PHP if($Manager->siteSettings('CAPTCHA') == 0) { goto skipCaptcha; } ?>
							<div class="form-group row">
								<script src='https://www.google.com/recaptcha/api.js'></script>
								<label for="g-recaptcha-response" class="col-3 form-control-label">Captcha</label>
								<div class="col-9">
									<div class="g-recaptcha" data-sitekey="<?=$Manager->siteSettings('CAPTCHA_PUBLIC')?>"></div>
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
							<?PHP skipCaptcha: ?>
						</form>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#register_form"><i class="fa fa-edit fa-fw"></i> Register</button>
						<small><a href="javascript:void(0);" fks-action="toggle-forms" fks-target="login">Login</a></small>
					</div>
				</div>
				<?PHP skipRegistration: ?>
				
				<!------- Forgot Panel ------->
				<div class="fks-panel forgot" style="display: none;">
					<div class="header">
						<span class="title">
							Forgot Password
						</span>
					</div>
					<div class="body">
						<form id="forgot_form" action="javascript:void(0);">
							<div class="alert fks-alert-danger" role="alert"></div>
							<button type="submit" style="display: none;"></button>
							<div class="form-group row">
								<label for="email_forgot" class="col-3 form-control-label">Email</label>
								<div class="col-9">
									<input type="email" class="form-control form-control-sm" id="email_forgot" name="email_forgot" placeholder="Email">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
						</form>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#forgot_form"><i class="fa fa-undo fa-fw"></i> Reset</button>
						<small><a href="javascript:void(0);" fks-action="toggle-forms" fks-target="login">Login</a></small>
					</div>
				</div>
				
				<!------- Verification Panel ------->
				<div class="fks-panel add-email" style="display: none;">
					<div class="header">
						<span class="title">
							Add Email Address
						</span>
					</div>
					<div class="body">
						<form id="email_form" action="javascript:void(0);">
							<div class="alert fks-alert-danger" role="alert"></div>
							<div class="alert fks-alert-info" role="alert"></div>
							<button type="submit" style="display: none;"></button>
							<div class="form-group row">
								<label for="email_add" class="col-3 form-control-label">Email</label>
								<div class="col-9">
									<input type="email" class="form-control form-control-sm" id="email_add" name="email" placeholder="Email">
									<div class="form-control-feedback" style="display: none;"></div>
								</div>
							</div>
						</form>
					</div>
					<div class="footer">
						<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#email_form"><i class="fa fa-plus fa-fw"></i> Add</button>
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
				
				$('[fks-action="toggle-forms"]').on('click', function() {
					togglePanels($(this).attr('fks-target'));
				});
				
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
						if(fks.debug.ajax) { console.log(data); }
						var response = JSON.parse(data);
						switch(response.result) {
							case 'success':
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
						if(fks.debug.ajax) { console.log(data); }
						var response = JSON.parse(data);
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
						if(fks.debug.ajax) { console.log(data); }
						try { var response = JSON.parse(data); } catch(e) { form.children('.alert').html('Server error!'); return; }
						switch(response.result) {
							case 'success':
								togglePanels('login');
								$('#login_form .alert').html('');
								$('#login_form .fks-alert-success').html(response.message);
								if( response.verify ) {
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
						if(fks.debug.ajax) { console.log(data); }
						var response = JSON.parse(data);
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
			}
		</script>
	</body>
</html>