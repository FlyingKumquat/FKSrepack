<?PHP if(!isset($Manager)) { die(); } ?>
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

		<title><?=$Manager->siteSettings('SITE_TITLE')?></title>

		<link href="scripts/css/main.css" rel="stylesheet">
		
		<link href="scripts/css/layouts/admin.css" rel="stylesheet">
		<link href="scripts/css/themes/black.css" rel="stylesheet">
		
		<?PHP if(is_file(__DIR__ . '/../scripts/css/autoloader.css')) { echo '<link href="scripts/css/autoloader.css" rel="stylesheet">';} ?>
	</head>

	<body>		
		<!-- Modal -->
		<div class="modal" id="fks_modal">
			<div class="modal-loader" style="width: 250px; margin: 60px auto;">
				<div class="progress">
					<div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
						<span class="sr-only"> Loading Modal... </span>
					</div>
				</div>
			</div>
			<div class="modal-dialog" style="display: none;">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title"></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span>&times;</span>
						</button>
					</div>
					<div class="modal-body"></div>
					<div class="modal-footer"></div>
				</div>
			</div>
		</div>
		
		<div id="top_nav" fks-nav="top">
			<a class="site-logo" href="/">
				<?PHP
					if(empty($Manager->siteSettings('SITE_LOGO_MAIN'))) {
						echo '<img src="img/_favicon.ico" /><span class="d-none d-sm-inline"><b>FKS</b><span class="fks-text-signature">repack</span></span>';
					} else {
						echo $Manager->siteSettings('SITE_LOGO_MAIN');
					}
				?>
			</a>
			<div class="actions">
				<div onclick="fks.sideMenuToggle();" class="action toggle-nav"></div>
				<div onclick="fks.fullscreen();" class="action toggle-fullscreen"></div>
			</div>
			<div fks-menu="2" autoclose></div>
			<div class="notifiers">
				<ul class="nav-list">
				</ul>
			</div>
		</div>
		
		<div id="side_nav" fks-nav="side">
			<div class="scroll" fks-menu="1" autocollapse></div>
		</div>
		
		<div id="content_container">
			<div id="content" class="slimscroll"></div>
		</div>
		
		<div id="exit_fullscreen" onclick="fks.fullscreen();" ><i class="fa fa-compress fa-fw"></i></div>
		
		<div id="footer">
			<div class="footer-left">
				<small>
					<?=$Manager->siteSettings('SITE_TITLE')?> v<?=$Manager->siteSettings('SITE_VERSION')?>
				</small>
			</div>
			<div class="footer-center">
				<span class="d-none d-sm-inline">Copyright </span>&copy; <?=date('Y')?> <?=$Manager->siteSettings('SITE_TITLE')?>
			</div>
			<div class="footer-right">
				<small>
					<a class="fks-link-light" href="http://fksrepack.com/" target="_blank">FKSrepack v<?=$Manager->siteData('fks_version')?></a>
				</small>
			</div>
		</div>
		
		<script src="scripts/js/plugins/require/require.min.js" type="text/javascript" data-main="scripts/js/app.js?bust=<?=time()?>"></script>
		<script src="scripts/js/ie10-viewport-bug-workaround.js"></script>
	</body>
</html>