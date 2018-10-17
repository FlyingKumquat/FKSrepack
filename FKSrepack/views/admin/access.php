<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('access_groups', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			Admin
			<i class="fa fa-angle-right fa-fw"></i>
			Access Groups
		</div>
		<div class="actions">
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">Access Groups</span>
	<span class="subtitle d-none d-md-inline">set menu access for each group</span>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="access_groups_panel" class="fks-panel">
			<div class="header">
				<span class="title">
					Access Groups Table
				</span>
				<span class="actions">
					<a class="btn add-table" fks-access="2"><i class="fa fa-plus"></i><span class="d-none d-lg-inline"> Add Group</span></a>
					<a class="btn reload-table"><i class="fa fa-refresh"></i><span class="d-none d-lg-inline"> Reload</span></a>
					<div class="btn-group">
						<a class="btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-columns"></i><span class="d-none d-lg-inline"> Columns</span> <i class="fa fa-angle-down"></i></a>
						<ul class="dropdown-menu dropdown-menu-right column-toggler"></ul>
					</div>
					<a class="btn panel-fullscreen" fks-action="panelFullscreen"></a>
				</span>
			</div>
			<div class="body">
				<table  class="table table-striped table-hover table-border table-sm dataTable no-footer"><thead class="thead-dark"></thead></table>
			</div>
		</div>
	</div>
</div>