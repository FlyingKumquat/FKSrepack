$( document ).ready(function() {
    fks.panelToggle();
	// Activate iCheck
	$('input[type="checkbox"]').iCheck({
		checkboxClass: 'icheckbox_minimal'
	});
});

// Test Database (Tab1)
function testDatabase(){
	$('#tab1 .data pre').append('Testing Connection...<br/><br/>');
	fks.block($('#site_settings'));
	
	$.post('handler.php', {action: 'testDatabase'})
	.done(function(data){
		if(fks.debug.ajax){ console.log(data) };
		var response = JSON.parse(data);
		switch(response.result){
			case 'success':
				// Connect
				$('#tab1 .data pre').html(response.message);
				$('#tab1_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Create
				$('#tab2 .data').html(response.table);
				$('a[href="#tab2"]').removeClass('disabled');
				setTimeout(function(){ $('a[href="#tab2"]').tab('show'); }, 500);
				
				// Activate iCheck
				$('#tab2 input').iCheck({
					checkboxClass: 'icheckbox_minimal'
				});
				
				// Check All
				$('#tab2 .header_checkbox').on('ifToggled', function(){
					if( $(this).prop('checked') ) {
						// Checked
						$('#tab2 input').iCheck('check');
					} else {
						// Unchecked
						$('#tab2 input').iCheck('uncheck');
					}
				});
				break;
				
			case 'failure':
				$('#tab1 .data pre').append(response.message);
				$('#tab1_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				setTimeout(function(){ $('#tab1_results_body').collapse('show'); }, 250);
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data){
		fks.unblock($('#site_settings'));
	});
}

// Create Tables (Tab2)
function createTables(){
	// Update the PRE
	$('#tab2 .pre pre').html('Creating Tables...<br/>');
	
	// Get all checked checkboxes
	var tables = [];
	$('#tab2 .data .table-checkbox').each(function(){
		if($(this).prop('checked')){
			tables.push($(this).attr('name'));
		}
	});
	
	fks.block($('#site_settings'));
	
	// Send
	$.post('handler.php', {action: 'createTables', data: tables})
	.done(function(data){
		if(fks.debug.ajax){ console.log(data) };
		var response = JSON.parse(data);
		switch(response.result){
			case 'success':
				// Create
				$('#tab2 .pre pre').append(response.message);
				$('#tab2_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Fill
				$('a[href="#tab3"]').removeClass('disabled');
				setTimeout(function(){ $('a[href="#tab3"]').tab('show'); }, 500);
				break;
				
			case 'failure':
				$('#tab2 .pre pre').append(response.message);
				$('#tab2_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				setTimeout(function(){ $('#tab2_results_body').collapse('show'); }, 250);
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data){
		fks.unblock($('#site_settings'));
	});
}

// Create Admin Account (Tab3)
function createAccount(){
	$('#tab3 .pre pre').html('Creating Account...<br/><br/>');
	var form = fks.superSerialize( $('#formAccount') );
	fks.block($('#site_settings'));
	
	$.post('handler.php', {action: 'createAccount', data: form})
	.done(function(data){
		if(fks.debug.ajax){ console.log(data) };
		var response = JSON.parse(data);
		switch(response.result){
			case 'success':
				// Admin
				$('#tab3 .pre pre').append(response.message);
				$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-success">Success</span>');
				
				// Complete
				$('a[href="#tab4"]').removeClass('disabled');
				setTimeout(function(){ $('a[href="#tab4"]').tab('show'); }, 500);
				break;
				
			case 'failure':
				$('#tab3 .pre pre').append(response.message);
				$('#tab3_results .badge-placeholder').html('<span class="badge fks-badge-danger">Failed</span>');
				setTimeout(function(){ $('#tab3_results_body').collapse('show'); }, 250);
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data){
		fks.unblock($('#site_settings'));
	});
}

// Complete (Tab4)
function deleteInstall(){
	$('#tab4 .pre pre').html('Renaming Folder...<br/><br/>');
	
	fks.block($('#site_settings'));
	
	$.post('handler.php', {action: 'deleteInstall'})
	.done(function(data){
		if(fks.debug.ajax){ console.log(data) };
		var response = JSON.parse(data);
		switch(response.result){
			case 'success':
				$('#tab4_results .badge-placeholder').html('<span class="badge badge-success">Success</span>');
				window.location = ('/');
				break;
				
			case 'failure':
				$('#tab4 .pre pre').append(response.message);
				$('#tab4_results .badge-placeholder').html('<span class="badge badge-danger">Failed</span>');
				setTimeout(function(){ $('#tab4_results_body').collapse('show'); }, 250);
				break;
				
			case 'error':
				console.log('Error');
				break;
				
			default:
				break;
		}
	})
	.always(function(data){
		fks.unblock($('#site_settings'));
	});
}