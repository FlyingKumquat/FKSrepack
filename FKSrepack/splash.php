<?PHP if(!is_dir('install')) { header('Location:index.php'); }?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="Splash page for the site">
		<meta name="author" content="FKS">
		<link rel="icon" href="./img/_favicon.ico">

		<title>FKSrepack Splash Page</title>

		<link href="./scripts/css/font-awesome.min.css" rel="stylesheet">
		
		<link href="./scripts/js/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="./scripts/js/plugins/iCheck/skins/minimal/_all.css" rel="stylesheet">
		
		<link href="./scripts/js/plugins/fks/fks.css" rel="stylesheet">
	</head>

	<body style="background: #eef1f5;overflow: auto;">
		<nav class="navbar navbar-dark bg-dark" style="margin-bottom:10px;">
			<div class="container">	
				<a class="navbar-brand" href="/">
					<img src="../img/_favicon.ico" width="30" height="30" class="d-inline-block align-top" alt="">
					<b>FKS</b><span style="color: #36e3fd;">repack</span>
				</a>
			</div>
		</nav>
		
		<div class="container">	
		
			<div class="row">
			
				<div class="col-md-12">
				
					<div class="card">
						<div class="card-body">
							<div class="alert fks-alert-danger" role="alert">
								<strong>Warning!</strong> The installation directory has been detected!
							</div>
							<p class="card-text">The main site can not be loaded until the installation directory has been removed or renamed!</p>
							<p class="card-text">This is to prevent other people from being able make changes to your site.</p>
							<p class="card-text">If this your first time loading this site please run the installation. Otherwise remove the installation directory and try reloading the site.</p>
							
							<button class="btn fks-btn-primary" onclick="window.location = '/';"><i class="fa fa-refresh fa-fw"></i> Reload Site</a>
						</div>
					</div>
					
				</div>
				
			</div>
			
		</div>
		
		<script src="./scripts/js/plugins/jquery/jquery-3.2.1.min.js"></script>
		<script src="./scripts/js/plugins/popper/popper.min.js"></script>
		<script src="./scripts/js/plugins/bootstrap/bootstrap.min.js"></script>
		<script src="./scripts/js/ie10-viewport-bug-workaround.js"></script>
	</body>
</html>