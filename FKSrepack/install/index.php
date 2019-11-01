<?PHP
require_once('functions.php');
$Functions = new FKS\Install\Functions;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="">
		<meta name="author" content="FKSrepack">
		<link rel="icon" href="../img/_favicon.ico">

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
					<img src="../img/_favicon.ico" width="30" height="30" class="d-inline-block align-top" alt="">
					<b>FKS</b><span style="color: #36e3fd;">repack <small style="color:white;font-size:60%"><?=$Functions->fks_version;?></small></span>
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
										<a class="nav-link active" data-toggle="tab" href="#tabConnect" role="tab" draggable="false"><i class="fa fa-plug fa-fw"></i><span class="d-none d-sm-inline"> Connect</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tabVersions" role="tab" draggable="false"><i class="fa fa-github fa-fw"></i><span class="d-none d-sm-inline"> Versions</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tabCreate" role="tab" draggable="false"><i class="fa fa-plus fa-fw"></i><span class="d-none d-sm-inline"> Create</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tabAdmin" role="tab" draggable="false"><i class="fa fa-user fa-fw"></i><span class="d-none d-sm-inline"> Admin</span></a>
									</li>
									<li class="nav-item">
										<a class="nav-link disabled" data-toggle="tab" href="#tabComplete" role="tab" draggable="false"><i class="fa fa-check fa-fw"></i><span class="d-none d-sm-inline"> Complete</span></a>
									</li>
								</ul>
							</span>
						</div>
						<div class="body">
							<div class="tab-content">
								<!---------- Connect ---------->
								<div class="tab-pane active" id="tabConnect" role="tabpanel">
									<h5>Database Connection</h5>
									<p>Click on the button below to attempt a connection to each unique database required for all of the tables configured. This includes the default connection used by FKSrepack for the core tables.</p>
									<p>The connection details are stored at /scripts/php/config/connections.php.</p>
									<p>The table configs are stored at /scripts/php/config/tables.php.</p>
									<div role="tablist">
										<div class="fks-card">
											<div class="header">
												<span class="title">Connection Results <span class="badge-placeholder"></span></span>
											</div>
											<div class="body-container" style="display:none;">
												<div class="body"><pre>Results will be displayed here...</pre></div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="ajax({action:'testConnection',block:'#site_settings',callback:testConnection})" class="btn fks-btn-info btn-sm go-btn"><i class="fa fa-plug"></i> Test Connection</button>
										<button type="button" onClick="nextTab('#tabVersions')" class="btn fks-btn-success btn-sm float-sm-right next-btn disabled"><i class="fa fa-arrow-right"></i> Next Tab</button>
									</div>
								</div>
								<!---------- Versions ---------->
								<div class="tab-pane" id="tabVersions" role="tabpanel">
									<h5>FKSrepack Versions</h5>
									<p>Below will show you your current version and the installer version. If you have an internet connection you can check the latest version off GitHub as well as the release notes.</p>
									<div class="row">
										<div class="col-lg-4">
											<table class="table table-striped table-bordered table-sm" style="margin-bottom:10px;">
												<tr><th>Your Version</th><td class="current">0.0.000000</td></tr>
												<tr><th>Installer Version</th><td class="installer">0.0.000000</td></tr>
												<tr><th>Latest Version</th><td class="latest">...</td></tr>
											</table>
										</div>
									</div>
									<div class="latest-hidden" style="display:none;">
										<h5>FKSrepack Releases</h5>
										<p>Below are all the releases that you are missing with their release notes.</p>
										<div class="fks-panel tabs tabs-versions">
											<div class="header">
												<span class="title">
													<ul class="nav nav-tabs"></ul>
												</span>
											</div>
											<div class="body" style="padding-bottom:0px;">
												<div class="tab-content"></div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="ajax({action:'getLatestVersion',block:'#site_settings',callback:getLatestVersion})" class="btn fks-btn-info btn-sm go-btn"><i class="fa fa-github"></i> Check GitHub</button>
										<button type="button" onClick="nextTab('#tabCreate')" class="btn fks-btn-success btn-sm float-sm-right next-btn disabled"><i class="fa fa-arrow-right"></i> Next Tab</button>
									</div>
								</div>
								<!---------- Create ---------->
								<div class="tab-pane" id="tabCreate" role="tabpanel">
									<h5>Create Database Tables</h5>
									<p>Below are the list of tables that FKSrepack requires. If there is a check next to the name then it is either out of date or doesn't exist and needs to be (re)created.</p>
									<ul>
										<li><b>no action</b> - This will drop, create, and then fill the table with default values if it has any.</li>
										<li><b>backup</b> - Same as "no action" but will also create a backup of any stored data.</li>
										<li><b>backup & restore</b> - Same as "backup" but will attempt to restore any data that was backup up.</li>
									</ul>
									<p>You have to click the blue button at the bottom even if you did not select any tables. This updates the FKS version.</p>
									<div class="data"></div>
									<div role="tablist">
										<div class="fks-card">
											<div class="header">
												<span class="title">Creation Results <span class="badge-placeholder"></span></span>
											</div>
											<div class="body-container" style="display:none;">
												<div class="body"><pre>Results will be displayed here...</pre></div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="createTables()" class="btn fks-btn-info btn-sm"><i class="fa fa-plus"></i> Create Selected Tables</button>
										<button type="button" onClick="nextTab('#tabAdmin')" class="btn fks-btn-success btn-sm float-sm-right next-btn disabled"><i class="fa fa-arrow-right"></i> Next Tab</button>
									</div>
								</div>
								<!---------- Admin ---------->
								<div class="tab-pane" id="tabAdmin" role="tabpanel">
									<h5>Create Admin Account</h5>
									<p>Fill in the below form for the main local admin account for this site. This is optional but if there aren't any accounts created then you will not be able to log in and change any settings.</p>
									<p>This account should probably be disabled after the site has been setup if you plan on using LDAP.</p>
									<form id="formAccount" role="form" action="javascript:void(0);" style="margin-bottom:10px;">
										<?PHP
											require_once('../scripts/php/includes/Utilities.php');
											$Utilities = new \Utilities();
											echo $Utilities->buildFormGroups(array(
												array(
													'title' => 'Username',
													'type' => 'text',
													'name' => 'username',
													'help' => 'Pick a username that won\'t be used by LDAP.',
													'width' => 6
												),array(
													'title' => 'Password',
													'type' => 'password',
													'name' => 'password',
													'help' => 'Make a complex password that can\'t be guessed.',
													'width' => 6
												),array(
													'title' => 'Overwrite Existing User',
													'type' => 'checkbox',
													'name' => 'overwrite',
													'value' => 1,
													'help' => 'Overwrites the password if the username already exists.',
													'width' => 6
												),array(
													'width' => 6
												)
											));
										?>
									</form>
									<div id="tab3_results" role="tablist">
										<div class="fks-card">
											<div class="header">
												<span class="title">Creation Results <span class="badge-placeholder"></span></span>
											</div>
											<div class="body-container" style="display:none;">
												<div class="body"><pre>Results will be displayed here...</pre></div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="createAccount()" class="btn fks-btn-info btn-sm"><i class="fa fa-plus"></i> Create Account</button>
										<button type="button" onClick="nextTab('#tabComplete')" class="btn fks-btn-success btn-sm float-sm-right next-btn disabled"><i class="fa fa-arrow-right"></i> Next Tab</button>
									</div>
								</div>
								<!---------- Complete ---------->
								<div class="tab-pane" id="tabComplete" role="tabpanel">
									<h5>Done Installing</h5>
									<p>You have finished setting up the site.</p>
									<p>Make sure to remove or rename the install directory so that the site can load.</p>
									<div role="tablist">
										<div class="fks-card">
											<div class="header">
												<span class="title">Renaming Results <span class="badge-placeholder"></span></span>
											</div>
											<div class="body-container" style="display:none;">
												<div class="body"><pre>Results will be displayed here...</pre></div>
											</div>
										</div>
									</div>
									<div class="footer">
										<button type="button" onClick="ajax({action:'deleteInstall',block:'#site_settings',callback:deleteInstall})" class="btn fks-btn-danger btn-sm"><i class="fa fa-trash"></i> Rename Install Directory</button>
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