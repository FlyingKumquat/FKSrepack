<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('account_settings', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			Member Menu
			<i class="fa fa-angle-right fa-fw"></i>
			Account Settings
		</div>
		<div class="actions">
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">Account Settings</span>
	<span class="subtitle d-none d-md-inline">account overview and settings</span>
</div>
<div class="row">
	<div class="col-xl-3">
		<div id="member_data" class="fks-panel">
			<div class="header">
				<span class="title">
					Account Data
				</span>
				<span class="actions">
					<!--<a class="btn reload-table"><i class="fa fa-pencil"></i><span class="d-none d-xl-inline"> Edit</span></a>-->
				</span>
			</div>
			<div class="body">
				<table class="table table-striped table-border table-sm"><tbody></tbody></table>
			</div>
		</div>
	</div>
	<div class="col-xl-9">
		<div class="fks-panel">
			<div class="header">
				<span class="title">
					Empty Panel
				</span>
				<span class="actions">
					<!--<a class="btn reload-table"><i class="fa fa-pencil"></i><span class="d-none d-xl-inline"> Edit</span></a>-->
				</span>
			</div>
			<div class="body">
				<p>A panel left blank for future use.</p>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-xl-12">
		<div id="panel_id" class="fks-panel">
			<div class="header">
				<span class="title">
					Account Activity <small>Last 100</small>
				</span>
				<span class="actions">
					<a class="btn reload-table"><i class="fa fa-refresh"></i><span class="d-none d-lg-inline"> Reload</span></a>
					<div class="btn-group">
						<a class="btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-columns"></i><span class="d-none d-lg-inline"> Columns</span> <i class="fa fa-angle-down"></i></a>
						<ul class="dropdown-menu dropdown-menu-right column-toggler"></ul>
					</div>
					<a class="btn panel-fullscreen" fks-action="panelFullscreen"></a>
				</span>
			</div>
			<div class="body">
				<table class="table table-striped table-hover table-border table-sm dataTable no-footer"><thead class="thead-dark"></thead></table>
			</div>
		</div>
	</div>
</div>