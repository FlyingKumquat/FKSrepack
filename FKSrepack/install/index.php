<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="">
		<meta name="author" content="FKSrepack">
		<link rel="icon" href="../img/favicon.ico">

		<title>FKSrepack : Installer</title>

		<link href="../scripts/css/font-awesome.min.css" rel="stylesheet">
		
		<link href="../scripts/js/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="../scripts/js/plugins/iCheck/skins/minimal/_all.css" rel="stylesheet">
		<link href="../scripts/js/plugins/pace/themes/silver/pace-theme-minimal.css" rel="stylesheet">
		<link href="../scripts/js/plugins/datatables/datatables.min.css" rel="stylesheet">
		<link href="../scripts/js/plugins/toastr/toastr.min.css" rel="stylesheet">
		<link href="../scripts/js/plugins/select2/css/select2.min.css" rel="stylesheet">
		<link href="../scripts/js/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		<link href="../scripts/js/plugins/fks/fks.css" rel="stylesheet">

		<link href="../scripts/css/themes/black.css" rel="stylesheet">
		<link href="./install.css" rel="stylesheet">
	</head>

	<body style="background: #eef1f5;overflow: auto;">
		<!-- Modal -->
		<div class="modal" id="fks_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">No Title</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					
					<div class="modal-body">
						No Body
					</div>
					
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		
		
		<nav class="navbar navbar-dark bg-dark" style="margin-bottom:10px;">
			<div class="container">	
				<a class="navbar-brand" href="/">
					<img src="../img/favicon.ico" width="30" height="30" class="d-inline-block align-top" alt="">
					FKSrepack
				</a>
			</div>
		</nav>
		
		<div class="container">	
			<div class="fks-page-title">
				<span class="title">Site Installation</span>
				<span class="subtitle d-none d-sm-inline">step by step installation guide</span>
			</div>
			
			<div class="row">
				<div class="col-md-12">
					<div id="site_settings" class="fks-panel tabs tabs-fill">
						<div class="header">
							<span class="title">
								<ul class="nav nav-tabs">
									<li class="nav-item">
										<a class="nav-link active" data-toggle="tab" href="#tab1" role="tab" draggable="false"><i class="fa fa-plug fa-fw"></i><span class="d-none d-sm-inline"> Connect</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tab2" role="tab" draggable="false"><i class="fa fa-plus fa-fw"></i><span class="d-none d-sm-inline"> Create</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tab3" role="tab" draggable="false"><i class="fa fa-user fa-fw"></i><span class="d-none d-sm-inline"> Admin</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tab4" role="tab" draggable="false"><i class="fa fa-check fa-fw"></i><span class="d-none d-sm-inline"> Complete</span></a>
									</li>
								</ul>
							</span>
							<span class="actions">
								
							</span>
						</div>
						
						<div class="body">
							
							<div class="tab-content">
								<div class="tab-pane active" id="tab1" role="tabpanel">
									<h5>Database Connection</h5>
									<p>Click on the button below to attempt a connection to the database that is setup as the default connection in /scripts/php/config/connections.php.</p>
									
									<div id="tab1_results" role="tablist">
										<div class="card">
											<div class="card-header" role="tab" id="tab1_results_heading" data-toggle="collapse" href="#tab1_results_body" aria-expanded="true" aria-controls="tab1_results_body">
												<h6 class="mb-0">Connection Results <span class="badge-placeholder"></span></h6>
											</div>

											<div id="tab1_results_body" class="collapse" role="tabpanel" aria-labelledby="tab1_results_heading" data-parent="#tab1_results">
												<div class="card-body">
													<div class="data"><pre>Results will be displayed here...</pre></div>
												</div>
											</div>
										</div>
									</div>
									
									<div class="footer">
										<button type="button" onClick="testDatabase()" class="btn fks-btn-success btn-sm go-btn"><i class="fa fa-plug"></i> Test Connection</button>
										<button type="button" onClick="fks.editModal({handler:'handler.php', action:'helpModal', data:'connect'})" class="btn fks-btn-info btn-sm float-sm-right help-gtn"><i class="fa fa-info"></i> Help</button>
									</div>
								</div>
								
								<div class="tab-pane" id="tab2" role="tabpanel">
									<h5>Create Database Tables</h5>
									<p>Below are the list of tables that FKSrepack requires. If there is a check next to the name then it is either out of date or doesn't exist and needs to be (re)created. If you want a clean install you may check the unselected tables.</p>
									<div class="data"></div>
									<div id="tab2_results" role="tablist">
										<div class="card">
											<div class="card-header" role="tab" id="tab2_results_heading" data-toggle="collapse" href="#tab2_results_body" aria-expanded="true" aria-controls="tab2_results_body">
												<h6 class="mb-0">Creation Results <span class="badge-placeholder"></span></h6>
											</div>

											<div id="tab2_results_body" class="collapse" role="tabpanel" aria-labelledby="tab2_results_heading" data-parent="#tab2_results">
												<div class="card-body">
													<div class="pre"><pre>Results will be displayed here...</pre></div>
												</div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="createTables()" class="btn fks-btn-success btn-sm"><i class="fa fa-plus"></i> Create Selected Tables</button>
										<button type="button" onClick="fks.editModal({handler:'handler.php', action:'helpModal', data:'tables'})" class="btn fks-btn-info btn-sm float-sm-right"><i class="fa fa-info"></i> Help</button>
									</div>
								</div>
								
								<div class="tab-pane" id="tab3" role="tabpanel">
									<h5>Create Admin Account</h5>
									<p>Fill in the below form for the main local admin account for this site.</p>
									<p>This account should probably be disabled after the site has been setup if you plan on using LDAP.</p>
									<form id="formAccount" role="form" action="javascript:void(0);" style="margin-bottom:10px;">
										<div class="row">
											<div class="col-md-6">
												<div class="form-group" >
													<label for="formUsername"><b>Username</b></label>
													<input type="text" class="form-control form-control-sm" id="formUsername" name="username" aria-describedby="usernameHelp" required="required">
													<small id="usernameHelp" class="form-text text-muted">Pick a username that won't be used by LDAP.</small>
												</div>
											</div>
											
											<div class="col-md-6">
												<div class="form-group" >
													<label for="formPassword"><b>Password</b></label>
													<input type="password" class="form-control form-control-sm" id="formPassword" name="password" aria-describedby="passwordHelp" required="required">
													<small id="passwordHelp" class="form-text text-muted">Make a complex password that can't be guessed.</small>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<div class="form-group" style="margin-top:5px">
													<input type="checkbox" class="table-checkbox"  name="skip" value="1" id="formSkip" aria-describedby="formSkipHelp">
													<label for="formSkip" style="margin:0px"><b>Skip Account Creation</b></label><br>
													<small id="formSkipHelp" class="form-text text-muted">Don't create a new admin account.</small>
												</div>
											</div>
										</div>
									</form>
									<div id="tab3_results" role="tablist">
										<div class="card">
											<div class="card-header" role="tab" id="tab3_results_heading" data-toggle="collapse" href="#tab3_results_body" aria-expanded="true" aria-controls="tab3_results_body">
												<h6 class="mb-0">Creation Results <span class="badge-placeholder"></span></h6>
											</div>

											<div id="tab3_results_body" class="collapse" role="tabpanel" aria-labelledby="tab3_results_heading" data-parent="#tab3_results">
												<div class="card-body">
													<div class="pre"><pre>Results will be displayed here...</pre></div>
												</div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="createAccount()" class="btn fks-btn-success btn-sm"><i class="fa fa-plus"></i> Create Account</button>
										<button type="button" onClick="fks.editModal({handler:'handler.php', action:'helpModal', data:'account'})" class="btn fks-btn-info btn-sm float-sm-right"><i class="fa fa-info"></i> Help</button>
									</div>
								</div>
								
								<div class="tab-pane" id="tab4" role="tabpanel">
									<h5>Done Installing</h5>
									<p>You have finished setting up the site.</p>
									<p>Make sure to remove or rename the install directory so that the site can load.</p>
									<div id="tab4_results" role="tablist">
										<div class="card">
											<div class="card-header" role="tab" id="tab4_results_heading" data-toggle="collapse" href="#tab4_results_body" aria-expanded="true" aria-controls="tab4_results_body">
												<h6 class="mb-0">Renaming Results <span class="badge-placeholder"></span></h6>
											</div>

											<div id="tab4_results_body" class="collapse" role="tabpanel" aria-labelledby="tab4_results_heading" data-parent="#tab4_results">
												<div class="card-body">
													<div class="pre"><pre>Results will be displayed here...</pre></div>
												</div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="deleteInstall()" class="btn fks-btn-danger btn-sm"><i class="fa fa-trash"></i> Rename Install Directory</button>
									</div>
								</div>
								
							</div>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<script src="../scripts/js/plugins/jquery/jquery-3.2.1.min.js"></script>
		<script src="../scripts/js/plugins/popper/popper.min.js"></script>
		<script src="../scripts/js/plugins/iCheck/icheck.min.js"></script>
		<script src="../scripts/js/plugins/bootstrap/bootstrap.min.js"></script>
		<script src="../scripts/js/plugins/fks/fks.js"></script>
		<script src="../scripts/js/ie10-viewport-bug-workaround.js"></script>
		
		<script src="./install.js"></script>
	</body>
</html>