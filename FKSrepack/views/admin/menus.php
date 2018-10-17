<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('menus', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			Admin
			<i class="fa fa-angle-right fa-fw"></i>
			Menus
		</div>
		<div class="actions">
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">Menus</span>
	<span class="subtitle d-none d-md-inline">list of all menus and menu items</span>
</div>
<div class="row">
	<div class="col-xl-2">
		<div class="fks-panel">
			<div class="body">
				<ul class="nav panel-nav">
					<li class="nav-item">
						<a href="#menus_panel" data-toggle="tab" role="tab"><i class="fa fa-list-ul fa-fw"></i> Menus</a>
					</li>
					<li class="nav-item">
						<a href="#menu_items_panel" class="active" data-toggle="tab" role="tab"><i class="fa fa-cubes fa-fw"></i> Menu Items</a>
					</li>
				</ul>
			</div>
		</div>
		<div id="menu_layouts" class="fks-panel" style="display: none;">
			<div class="header">
				<span class="title">
					Menu Layouts
				</span>
			</div>
			<div class="body">
				<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Menus...
			</div>
		</div>
	</div>
	<div class="col-xl-10 tab-content">
		<div id="menus_panel" class="fks-panel tab-pane" role="tabpanel">
			<div class="header">
				<span class="title">
					Menus
				</span>
				<span class="actions">
					<a class="btn add-table" fks-access="2"><i class="fa fa-plus"></i><span class="d-none d-lg-inline"> Add Menu</span></a>
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
		<div id="menu_items_panel" class="fks-panel tab-pane active" role="tabpanel">
			<div class="header">
				<span class="title">
					Menu Items
				</span>
				<span class="actions">
					<a class="btn add-table" fks-access="2"><i class="fa fa-plus"></i><span class="d-none d-lg-inline"> Add Item</span></a>
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