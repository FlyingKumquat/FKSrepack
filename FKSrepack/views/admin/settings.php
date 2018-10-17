<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('site_settings', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			Admin
			<i class="fa fa-angle-right fa-fw"></i>
			Settings
		</div>
		<div class="actions">
			<a class="btn history" fks-access="3"><i class="fa fa-history fa-fw"></i><span class="d-none d-lg-inline"> View History</span></a>
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">Site Settings</span>
	<span class="subtitle d-none d-md-inline">these settings affect the whole site</span>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="site_settings" class="fks-panel tabs">
			<div class="header">
				<span class="title"></span>
				<span class="actions">
					<a class="btn panel-fullscreen" fks-action="panelFullscreen"></a>
				</span>
			</div>
			
			<div class="body pb-0" style="display:none">
				
			</div>
			
			<div class="footer buttons" style="display:none">
				<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#editSiteSettingsForm" fks-access="2"><i class="fa fa-undo fa-fw"></i> Reset</button>
				<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#editSiteSettingsForm" fks-access="2"><i class="fa fa-save fa-fw"></i> Save Settings</button>
			</div>
		</div>
	</div>
</div>