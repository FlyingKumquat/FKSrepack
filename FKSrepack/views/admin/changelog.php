<?PHP
	$data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
	if(!isset($data['depth']) || !is_numeric($data['depth']) || $data['depth'] < 1) {
		die((isset($data['die']) ? $data['die'] : 'Unable to load page.'));
	}
	require_once(__DIR__ . str_repeat('/..', $data['depth']) . '/scripts/php/views/manager.php');
	$Manager->checkAccess('changelog', 1, true);
?>
<div id="crumbs">
	<div class="bar">
		<div class="trail">
			<a href="#"><?=$Manager->siteSettings('SITE_TITLE')?></a>
			<i class="fa fa-angle-right fa-fw"></i>
			Admin
			<i class="fa fa-angle-right fa-fw"></i>
			Changelog
		</div>
		<div class="actions">
			<a class="btn" fks-action="loadPageChangelog"><i class="fa fa-list-alt fa-fw"></i><span class="d-none d-lg-inline"> Changelog</span></a>
		</div>
	</div>
</div>
<div class="fks-page-title">
	<span class="title">Changelog</span>
	<span class="subtitle d-none d-md-inline">what happened to this site...</span>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="changelog_panel" class="fks-panel tabs">
			<div class="header">
				<span class="title">
					<ul class="nav nav-tabs">
						<li class="nav-item" fks-tabcontrol-flags="ignore_all">
							<a class="nav-link active" data-toggle="tab" href="#tab0" role="tab" changelog-id="0" draggable="false"><i class="fa fa-list fa-fw"></i><span class="d-none d-md-inline"> All Changelogs</span></a>
						</li>
					</ul>
				</span>
				<span class="actions">
					<a fks-access="3" class="btn add-table"><i class="fa fa-plus"></i><span class="d-none d-lg-inline"> Add Changelog</span></a>
					<a class="btn reload-table"><i class="fa fa-refresh"></i><span class="d-none d-lg-inline"> Reload</span></a>
					<div class="btn-group">
						<a class="btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-columns"></i><span class="d-none d-lg-inline"> Columns</span> <i class="fa fa-angle-down"></i></a>
						<ul class="dropdown-menu dropdown-menu-right column-toggler"></ul>
					</div>
					<a class="btn panel-fullscreen" fks-action="panelFullscreen"></a>
				</span>
			</div>
			<div class="body">
				<div class="tab-content">
					<div class="tab-pane active" id="tab0" role="tabpanel">
						<table class="table table-striped table-hover table-border table-sm dataTable no-footer"><thead class="thead-dark"></thead></table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<template name="changelog">
	<div class="row">
		<div class="col-lg-5">
			<h5>Changelog Settings</h5>
			<form id="changelogForm_%CHANGELOG_ID%" role="form" action="javascript:void(0);">
				<input type="hidden" name="id" value="%CHANGELOG_ID%">
				<div class="row">
					<div class="col-md-6 form-group">
						<label for="change_version_%CHANGELOG_ID%" class="form-control-label">Version</label>
						<input type="text" class="form-control form-control-sm" id="change_version_%CHANGELOG_ID%" name="version" aria-describedby="version_help_%CHANGELOG_ID%" value="%VERSION%">
						<div class="form-control-feedback"></div>
						<small id="version_help_%CHANGELOG_ID%" class="form-text text-muted">The changelog version.</small>
					</div>

					<div class="col-md-6 form-group">
						<label for="change_title_%CHANGELOG_ID%" class="form-control-label">Title</label>
						<input type="text" class="form-control form-control-sm" id="change_title_%CHANGELOG_ID%" name="title" aria-describedby="title_help_%CHANGELOG_ID%" value="%TITLE%">
						<div class="form-control-feedback"></div>
						<small id="title_help_%CHANGELOG_ID%" class="form-text text-muted">The changelog title.</small>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 form-group">
						<label for="change_active_%CHANGELOG_ID%" class="form-control-label">Status</label>
						<select type="text" class="form-control form-control-sm" id="change_active_%CHANGELOG_ID%" name="active" aria-describedby="active_help_%CHANGELOG_ID%">
							<option value="0">Disabled</option>
							<option value="1">Active</option>
						</select>
						<div class="form-control-feedback"></div>
						<small id="active_help_%CHANGELOG_ID%" class="form-text text-muted">Starts disabled so you can add notes first.</small>
					</div>

					<div class="col-md-6 form-group">
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 form-group">
						<label for="change_summary_%CHANGELOG_ID%" class="form-control-label">Summary</label>
						<textarea class="form-control" rows="8" id="change_summary_%CHANGELOG_ID%" name="notes" aria-describedby="summary_help_%CHANGELOG_ID%">%NOTES%</textarea>
						<div class="form-control-feedback"></div>
						<small id="summary_help_%CHANGELOG_ID%" class="form-text text-muted">Short explaination of this changelog.</small>
					</div>
				</div>
			</form>
		</div>
		
		<div class="col-lg-7">
			<h5>Changelog Notes</h5>
			<table class="table table-striped table-hover table-border table-sm notes-table">
				<thead class="thead-dark">
				</thead>
			</table>
		</div>
	</div>
	
	<div class="row d-block d-lg-none" style="margin-bottom:10px;"></div>
	
	<div class="row">
		<div class="col-md-12 btn-container">
			<div class="btn-left">
				<button class="btn fks-btn-success btn-sm" fks-action="submitForm" fks-target="#changelogForm_%CHANGELOG_ID%"><i class="fa fa-save fa-fw"></i> Save</button>
				<button class="btn fks-btn-warning btn-sm" fks-action="resetForm" fks-target="#changelogForm_%CHANGELOG_ID%"><i class="fa fa-undo fa-fw"></i> Reset</button>
				<button class="btn fks-btn-danger btn-sm close-tab-btn"><i class="fa fa-times fa-fw"></i> Close</button>
			</div>
			
			<div class="btn-right">
				<button class="btn fks-btn-success btn-sm add-note-btn"><i class="fa fa-plus fa-fw"></i> Add Note</button>
			</div>
		</div>
	</div>
</template>