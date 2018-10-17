<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('%LABEL%', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			%BREADCRUMB%
		</div>
		<div class="actions">
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">%TITLE%</span>
	<span class="subtitle d-none d-md-inline">small text for this page</span>
</div>
<div class="row">
	<div class="col-lg-2">
		<div id="panel_id_1" class="fks-panel">
			<div class="header">
				<span class="title">
					Panel Title
				</span>
			</div>
			<div class="body"></div>
		</div>
	</div>
	<div class="col-lg-10">
		<div id="panel_id_2" class="fks-panel">
			<div class="header">
				<span class="title">
					Panel Title
				</span>
				<span class="actions">
					<a class="btn add-table"><i class="fa fa-plus" fks-access="2"></i><span class="d-none d-lg-inline"> Add Button</span></a>
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