$( document ).ready(function() {
    fks.fksActions([
		'panelToggle',
		'cardToggle'
	]);
	// Activate iCheck
	$('input[type="checkbox"]').iCheck({
		checkboxClass: 'icheckbox_minimal'
	});
	fks.debug.ajax = true;
});

// Expand the card
function expandCard(cardID) {
	console.log(cardID);
	if( $('.fks-card .body-container:hidden', cardID).length ) {
		$('.fks-card .header', cardID).click();
	}
}

// Tab1 - Test Database - Connect
function testDatabase(){
	$('#tab1 .data pre').html('Testing Connection...<br/>');
	fks.block($('#site_settings'));
	
	$.post('handler.php', {action: 'testDatabase'})
	.done(function(data){
		// Catch server errors
		try {
			var response = JSON.parse(data);
			if(fks.debug.ajax) { console.log(response); }
		} catch(e) {
			if(fks.debug.ajax) { console.log(data); }
			$('#tab1 .pre pre').append(data);
			$('#tab1_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			//setTimeout(function(){ $('#tab1_results_body').collapse('show'); }, 250);
			expandCard('#tab1');
			return;
		}
		switch(response.result){
			case 'success':
				// Connect
				$('#tab1 .data pre').append(response.message);
				$('#tab1_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Create
				$('#tab2>.data').html(response.table);
				$('a[href="#tab2"]').removeClass('disabled');
				expandCard('#tab1');
				setTimeout(function() { $('a[href="#tab2"]').tab('show'); }, 500);
				
				// Activate iCheck
				$('#tab2 input').iCheck({
					checkboxClass: 'icheckbox_minimal'
				});
				
				// Check All
				$('#tab2 .header_checkbox').on('ifToggled', function() {
					if($('#tab2 .header_checkbox').hasClass('switching')) { return false; }
					if($(this).prop('checked')) {
						// Checked
						$('#tab2 input').iCheck('check');
					} else {
						// Unchecked
						$('#tab2 input').iCheck('uncheck');
					}
				});
				
				// Select correct options
				$('#tab2 table select').each(function(){
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
					$('#tab2 table .table-checkbox').each(function(){
						if(!$(this).prop('checked')) { _check = false; }
					});
					$('#tab2 .header_checkbox').addClass('switching');
					if(_check) {
						$('#tab2 .header_checkbox').iCheck('check');
					} else {
						$('#tab2 .header_checkbox').iCheck('uncheck');
					}
					$('#tab2 .header_checkbox').removeClass('switching');
				};
				
				// Bind table checkboxes
				$('#tab2 table .table-checkbox').on('ifToggled', check_checks);
				
				check_checks();
				break;
				
			case 'failure':
				$('#tab1 .data pre').append(response.message);
				$('#tab1_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				expandCard('#tab1');
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data) {
		fks.unblock($('#site_settings'));
	});
}

// Tab2 - Create Tables - Create
function createTables() {
	// Update the PRE
	$('#tab2 .body-container pre').html('Creating Tables...<br/>');
	
	// Get all checked checkboxes
	var tables = {};
	$('#tab2 .data .table-checkbox').each(function() {
		var $t = $(this);
		var $select = $t.closest('tr').find('.table-action-select');
		var _database = $t.attr('database');
		var _table = $t.attr('table');
		if($t.prop('checked')) {
			if(tables[_database] == undefined) { tables[_database] = {}; }
			tables[_database][_table] = $select.val();
		}
	});
	
	//console.log(tables);
	fks.block($('#site_settings'));
	
	// Send
	$.post('handler.php', {action: 'createTables', data: tables})
	.done(function(data) {
		// Catch server errors
		try {
			var response = JSON.parse(data);
			if(fks.debug.ajax) { console.log(response); }
		} catch(e) {
			if(fks.debug.ajax) { console.log(data); }
			$('#tab2 .body-container pre').append(data);
			$('#tab2_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			expandCard('#tab2');
			return;
		}
		switch(response.result) {
			case 'success':
				// Create
				$('#tab2 .body-container pre').append(response.message);
				$('#tab2_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Fill
				$('a[href="#tab3"]').removeClass('disabled');
				setTimeout(function() { $('a[href="#tab3"]').tab('show'); }, 500);
				break;
				
			case 'failure':
				$('#tab2 .body-container pre').append(response.message);
				$('#tab2_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				expandCard('#tab2');
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data) {
		fks.unblock($('#site_settings'));
	});
}

// Tab3 - Create Admin Account - Admin
function createAccount() {
	$('#tab3 .body-container pre').html('Creating Account...<br/><br/>');
	var form = fks.superSerialize($('#formAccount'));
	fks.block($('#site_settings'));
	fks.formValidate($('#formAccount'));
	
	$.post('handler.php', {action: 'createAccount', data: form})
	.done(function(data) {
		// Catch server errors
		try {
			var response = JSON.parse(data);
			if(fks.debug.ajax) { console.log(response); }
		} catch(e) {
			if(fks.debug.ajax) { console.log(data); }
			$('#tab3 .body-container pre').append(data);
			$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			expandCard('#tab3');
			return;
		}
		switch(response.result) {
			case 'success':
				// Admin
				$('#tab3 .body-container pre').append(response.message);
				$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Complete
				$('a[href="#tab4"]').removeClass('disabled');
				setTimeout(function() { $('a[href="#tab4"]').tab('show'); }, 500);
				break;
				
			case 'validate':
				fks.formValidate($('#formAccount'), response.validation);
				$('#tab3 .body-container pre').append(response.message);
				$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				expandCard('#tab3');
				break;	
				
			case 'failure':
				$('#tab3 .body-container pre').append(response.message);
				$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				expandCard('#tab3');
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data) {
		fks.unblock($('#site_settings'));
	});
}

// Tab4 - Rename Install - Complete
function deleteInstall() {
	$('#tab4 .pre pre').html('Renaming Folder...<br/><br/>');
	
	fks.block($('#site_settings'));
	
	$.post('handler.php', {action: 'deleteInstall'})
	.done(function(data) {
		// Catch server errors
		try {
			var response = JSON.parse(data);
			if(fks.debug.ajax) { console.log(response); }
		} catch(e) {
			if(fks.debug.ajax) { console.log(data); }
			$('#tab4 .pre pre').append(data);
			$('#tab4_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
			setTimeout(function() { $('#tab4_results_body').collapse('show'); }, 250);
			expandCard('#tab4');
			return;
		}
		switch(response.result) {
			case 'success':
				$('#tab4_results .badge-placeholder').html('<span class="badge badge-success">Success</span>');
				window.location = ('/logout.php');
				break;
				
			case 'failure':
				$('#tab4 .pre pre').append(response.message);
				$('#tab4_results .badge-placeholder').html('<span class="badge badge-danger">Failed</span>');
				setTimeout(function() { $('#tab4_results_body').collapse('show'); }, 250);
				expandCard('#tab4');
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data) {
		fks.unblock($('#site_settings'));
	});
}