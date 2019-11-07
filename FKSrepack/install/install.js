$( document ).ready(function() {
    fks.fksActions([
		'panelToggle',
		'cardToggle'
	]);
	
	// Activate iCheck
	$('input[type="checkbox"]').iCheck({
		checkboxClass: 'icheckbox_minimal'
	});
	
	//
	fks.debug.ajax = true;
	default_connection = null;
});

// Generic ajax function
function ajax(args) {
	// Set defaults if missing
	args = $.extend({
		action: false,
		data: null,
		block: false,
		callback: false
	}, args);
	
	// Block element if one was passed
	if(args.block) { fks.block($(args.block)); }
	
	// Set default result
	var response = {result: 'error', message: '<br/>Ajax error!'};
	
	// Call PHP function
	$.ajax({
		type: 'POST',
		url: 'handler.php',
		data: {action: args.action, data: args.data}
	}).done(function(data) {
		// Catch server errors
		try {
			response = JSON.parse(data);
			if(fks.debug.ajax) { console.log(response); }
		} catch(e) {
			if(fks.debug.ajax) { console.log(data); }
		}
		
		// Callback function
		if(args.callback){args.callback(response);}
		
		//
		return response;
	}).always(function() {
		// Unblock element if one was passed
		if(args.block) { fks.unblock($(args.block)); }
	});
}

// Expand the card
function expandCard(cardID) {
	if( $('.fks-card .body-container:hidden', cardID).length ) {
		$('.fks-card .header', cardID).click();
	}
}

// Unlock tab
function unlockTab(tabID, switchto = false, delay = 750) {
	// Activate tab
	$('a[href="' + tabID + '"]').removeClass('disabled');
	
	// Switch to tab
	if(switchto) {
		setTimeout(function(){ $('a[href="' + tabID + '"]').tab('show'); }, delay);
	}
}

// Unlock tab
function nextTab(tabID) {
	// Switch to tab
	$('a[href="' + tabID + '"]').tab('show');
}

// Resets tab back to default
function resetTabs() {
	$('.fks-card .badge-placeholder').html('');
	$('.fks-card .body pre').html('Results will be displayed here...');
	$('#site_settings a.nav-link:not(.active)').addClass('disabled');
}

// Tab - Connect
function testConnection(data) {
	// Reset tabs
	resetTabs()
	
	//
	$('#tabConnect .fks-card .body pre').html('Testing Connection...<br/>');
	
	switch(data.result) {
		case 'success':
			$('#tabConnect .fks-card .body pre').append(data.message);
			$('#tabConnect .fks-card .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
			
			// Unlock next tab
			$('.next-btn', '#tabConnect').removeClass('disabled');
			unlockTab('#tabVersions');
			
			// Load the tables for the Create tab
			ajax({action:'getTables',callback:getTables});
			ajax({action:'getVersions',callback:getVersions});
			break;
			
		case 'error':
		case 'failure':
			$('#tabConnect .fks-card .body pre').append(data.message);
			$('#tabConnect .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			break;
	}
	
	// Expand card
	expandCard('#tabConnect');
}

// Tab - Versions
function getVersions(data) {
	switch(data.result) {
		case 'success':
			// Set versions
			$('.current', '#tabVersions').html(data.versions.current);
			$('.installer', '#tabVersions').html(data.versions.installer);
			$('.next-btn', '#tabVersions').removeClass('disabled');
			break;
			
		case 'error':
		case 'failure':
			$('.tabs-versions .nav-tabs', '#tabVersions').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#000000" role="tab" draggable="false"><i class="fa fa-tag fa-fw"></i> Error</a></li>');
			$('.tabs-versions .tab-content', '#tabVersions').append('<div class="tab-pane" id="000000" role="tabpanel"><pre>There was an error grabbing versions!</pre></div>');
			break;
	}
}

// Tab - Latest Version
function getLatestVersion(data) {
	switch(data.result) {
		case 'success':
			// Set versions
			$('.current', '#tabVersions').html(data.versions.current);
			$('.installer', '#tabVersions').html(data.versions.installer);
			$('.latest', '#tabVersions').html(data.versions.latest);
			
			// Clear out the tabs and bodies
			$('.tabs-versions .nav-tabs', '#tabVersions').html('');
			$('.tabs-versions .tab-content', '#tabVersions').html('');
			
			// Show hidden data
			$('.latest-hidden', '#tabVersions').show();
			
			// Loop through the releases and add them to the tab panel
			$.each(data.releases, function(k, v){
				// Add tab
				$('.tabs-versions .nav-tabs', '#tabVersions').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#' + v.id + '" role="tab" draggable="false"><i class="fa fa-tag fa-fw"></i> ' + v.tag_name + '</a></li>');
				
				// Add body
				$('.tabs-versions .tab-content', '#tabVersions').append('<div class="tab-pane" id="' + v.id + '" role="tabpanel"><pre>' + v.body + '</pre></div>');
			});
			
			// If no releases found
			if(data.releases.length == 0) {
				// Add tab
				$('.tabs-versions .nav-tabs', '#tabVersions').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#00000000" role="tab" draggable="false"><i class="fa fa-tag fa-fw"></i> ' + data.versions.installer + '</a></li>');
				
				// Add body
				$('.tabs-versions .tab-content', '#tabVersions').append('<div class="tab-pane" id="00000000" role="tabpanel"><pre>There is no information for this version on GitHub.</pre></div>');
			}
			
			// Activate first release tab
			$('.tabs-versions .nav-tabs a:first', '#tabVersions').tab('show');
			break;
			
		case 'error':
		case 'failure':
			// Add tab
			$('.tabs-versions .nav-tabs', '#tabVersions').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#000000" role="tab" draggable="false"><i class="fa fa-tag fa-fw"></i> Error</a></li>');
			
			// Add body
			$('.tabs-versions .tab-content', '#tabVersions').append('<div class="tab-pane" id="000000" role="tabpanel"><pre>There was an error grabbing versions!</pre></div>');
			break;
	}
}

// Tab - Create - Get Tables
function getTables(data) {
	switch(data.result) {
		case 'success':
			// Display table
			$('#tabCreate .data').html(data.table);
			
			// Set default connection
			default_connection = data.default_connection;
			
			// Activate iCheck
			$('#tabCreate input').iCheck({
				checkboxClass: 'icheckbox_minimal'
			});
			
			// Check All
			$('#tabCreate .header_checkbox').on('ifToggled', function() {
				if($('#tabCreate .header_checkbox').hasClass('switching')) { return false; }
				if($(this).prop('checked')) {
					// Checked
					$('#tabCreate input').iCheck('check');
				} else {
					// Unchecked
					$('#tabCreate input').iCheck('uncheck');
				}
			});
			
			// Select correct options
			$('#tabCreate table select').each(function(){
				var $t = $(this);
				var $c = $t.children();
				$t.val($t.attr('value'));
				// Disable if no actions
				if($c.length == 0 || ($c.length == 1 && $c[0].value == 0)) {
					$t.prop('disabled', true);
				}
			});
			
			// Check header checkbox if all tables are checked
			var check_checks = function() {
				var _check = true;
				$('#tabCreate table .table-checkbox').each(function(){
					if(!$(this).prop('checked')) { _check = false; }
				});
				$('#tabCreate .header_checkbox').addClass('switching');
				if(_check) {
					$('#tabCreate .header_checkbox').iCheck('check');
				} else {
					$('#tabCreate .header_checkbox').iCheck('uncheck');
				}
				$('#tabCreate .header_checkbox').removeClass('switching');
			};
			
			// Bind table checkboxes
			$('#tabCreate table .table-checkbox').on('ifToggled', check_checks);
			
			// Unlock next tab
			unlockTab('#tabCreate');
			break;
			
		case 'error':
		case 'failure':
			$('#tabCreate .fks-card .body pre').append(data.message);
			$('#tabCreate .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			expandCard('#tabCreate');
			break;
	}
}

// Tab - Create - Create Tables
function createTables() {
	// Update the PRE
	$('#tabCreate .fks-card .badge-placeholder').html('');
	$('#tabCreate .fks-card .body pre').html('Creating Tables...<br/>');
	
	// Get all checked checkboxes
	var tables = {};
	$('#tabCreate .data .table-checkbox').each(function() {
		var $t = $(this);
		var $select = $t.closest('tr').find('.table-action-select');
		var _database = $t.attr('database');
		var _table = $t.attr('table');
		if($t.prop('checked')) {
			if(tables[_database] == undefined) { tables[_database] = {}; }
			tables[_database][_table] = $select.val();
		}
	});
	
	//
	var loop = [];
	
	// Check to see if fks_versions is in the list of table to re-create
	if(tables[default_connection] !== undefined && tables[default_connection]['fks_versions'] !== undefined) {
		loop.push({
			db: default_connection,
			table: 'fks_versions',
			value: tables[default_connection]['fks_versions']
		});
		delete(tables[default_connection]['fks_versions']);
	}
	
	// Add remaining tables to the list
	$.each(tables, function(db, table){
		$.each(table, function(name, value){
			loop.push({
				db: db,
				table: name,
				value: value
			});
		});
	});
	
	// Loop through them all to set the table to pending
	$.each(loop, function(db, table){
		$('table .' + table.db + '.' + table.table + ' .version', '#tabCreate').html('<span class="fks-text-info"><i class="fa fa-spin fa-spinner fa-fw"></i> Pending...</span>');
	});
	
	// Start the loop
	fks.block($('#site_settings'));
	tableLoop(loop, {result: 'success', first: true});
}

// Loop through selected tables
function tableLoop(loop, data) {
	switch(data.result) {
		case 'success':
		case 'failure':
			// Skip if first run
			if(data.first === undefined) {
				if(data.result == 'success') {
					// Update table version
					$('table .' + data.data.db + '.' + data.data.table + ' .version', '#tabCreate').html('<span class="fks-text-success">' + data.data.version + '</span>');
					
					if($('#tabCreate .fks-card .badge-placeholder .badge').length == 0) {
						$('#tabCreate .fks-card .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
					}
				}
				if(data.result == 'failure') {
					// Update table version
					$('table .' + data.data.db + '.' + data.data.table + ' .version', '#tabCreate').html('<span class="fks-text-danger">' + data.data.version + '</span>');
					
					$('#tabCreate .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failure</span>');
				}
				
				// Message
				$('#tabCreate .fks-card .body pre').append(data.message);
			}
			
			// Remove table from loop
			table = loop.shift();
			
			// If there are no tables left to modify
			if(table == undefined){
				// Update site version
				ajax({action: 'updateFKSVersion'});
				
				//
				$('#tabCreate .fks-card .badge-placeholder').show();
				
				fks.unblock($('#site_settings'));
				
				unlockTab('#tabAdmin');
				$('.next-btn', '#tabCreate').removeClass('disabled');
				unlockTab('#tabComplete');
				$('.next-btn', '#tabAdmin').removeClass('disabled');
				
				
				return;
			}
			
			// Set to loading text
			$('table .' + table.db + '.' + table.table + ' .version', '#tabCreate').html('<span class="fks-text-info"><i class="fa fa-spin fa-spinner fa-fw"></i> Updating...</span>');
			
			// Start next table
			ajax({
				action: 'createTable',
				data: table,
				callback: function(response){tableLoop(loop, response);}
			});
			break;
			
		case 'error':
			// Add tab
			$('.tabs-versions .nav-tabs', '#tabVersions').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#000000" role="tab" draggable="false"><i class="fa fa-tag fa-fw"></i> Error</a></li>');
			
			// Add body
			$('.tabs-versions .tab-content', '#tabVersions').append('<div class="tab-pane" id="000000" role="tabpanel"><pre>There was an error grabbing versions!</pre></div>');
			break;
	}
}

// Tab - Admin - Create Admin Account
function createAccount() {
	$('#tabAdmin .body-container pre').html('Creating Account...<br/><br/>');
	var form = fks.superSerialize($('#formAccount'));
	fks.formValidate($('#formAccount'));
	ajax({
		action: 'createAccount',
		data: form,
		block: '#site_settings',
		callback: createAccountCallback
	});
}

// Tab - Admin - Create Admin Account
function createAccountCallback(data) {
	switch(data.result) {
		case 'success':
			// Admin
			$('#tabAdmin .body-container pre').append(data.message);
			$('#tabAdmin .fks-card .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
			break;
			
		case 'validate':
			fks.formValidate($('#formAccount'), data.validation);
			$('#tabAdmin .body-container pre').append(data.message);
			$('#tabAdmin .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			break;	
			
		case 'failure':
		case 'error':
			$('#tabAdmin .body-container pre').append(data.message);
			$('#tabAdmin .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			break;
	}
	
	// Expand Card
	expandCard('#tabAdmin');
}

// Tab - Complete - Rename Install
function deleteInstall(data) {
	$('#tabComplete .pre pre').html('Renaming Folder...<br/><br/>');
	
	switch(data.result) {
		case 'success':
			$('#tabComplete .fks-card .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
			window.location = ('/logout.php');
			break;
			
		case 'failure':
		case 'error':
			$('#tabComplete .body-container pre').append(data.message);
			$('#tabComplete .fks-card .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			break;
	}
	
	// Expand Card
	expandCard('#tabComplete');
}