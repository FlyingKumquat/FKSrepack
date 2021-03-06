define([
	'datatables.net',
	'iCheck'
], function() {
	var page = {
		src: './views/%FULL_URL%.php',
		hash: '#%FULL_URL%',
		title: fks.siteTitle + '%WINDOW_TITLE%',
		access: fks.checkAccess('%LABEL%'),
		params: fks.getParams(),
		keep_alive: false,
		debug: false
	},
	self,
	View = Backbone.View.extend({
		el: fks.container,
		initialize: function() {
			self = this;
			self.render();
		},
		render: function() {
			$.ajax({
				url: page.src,
				data: {
					depth: page.hash.split('/').length,
					//die: 'Custom die message.'
				},
				success: function(result) {
					initializePage(result);
				}
			});
			return this;
		}
	});
	
	// Set page variables
	var tables = {};	// Datatables array
	
	// -------------------- Initialize Page Function -------------------- //
	function initializePage(result) {
		if('#' + fks.location() != page.hash) { return false; }
		document.title = page.title;
		self.$el.html(result);
		if(!fks.pageAccess(result, page)) { return false; }
		
		// Bind fks element actions
		fks.fksActions([
			'panelToggle',
			'panelClose',
			'panelFullscreen',
			'cardToggle'
		]);
		
		// Bind fks panel actions
		$('#panel_id_3 .actions .tab-modal').on('click', function(){ editModalTabs(); });
		
		// Run functions when page loads
		loadExampleTable();
		loadExamplePanel();
	}
	
	// -------------------- Example Load Table Function -------------------- //
	function loadExampleTable() {
		// Create an id for the table
		var table_id = 0;
		
		// Setup table general settings
		tables[table_id] = {
			id: table_id,
			ele: {
				block: $('#panel_id_2'),								// Element that you want blocked
				table: $('#panel_id_2 table:first'),					// The table
				//form: $('#example_form'),								// Form the table validates (Optional)
				add: $('#panel_id_2 .actions .add-table'),				// The add button (Optional)
				reload: $('#panel_id_2 .actions .reload-table'),		// The reload button (Optional)
				columns: $('#panel_id_2 .actions .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable (do not touch)
			empty: 'No entries found',			// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Entries...',		// fks.block loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Entries...'	// fks.block reloading message
			},
			count: 0,							// Column counter (do not touch)
			debug: page.debug,					// Add a wait time before loading table (optional, default: true)
			wait: true,							// Add a wait time before loading table (optional, default: true)
			action: 'loadExampleTable',			// PHP function name (string) or javascript function for loading data
			functions: {
				add: addFunction,				// Called when ele.add is clicked (Optional)
				//reload: reloadFunction,		// Called when ele.reload is clicked (Optional)
				edit: editFunction,				// Edit function (Optional)
				view: viewFunction				// View function (Optional)
			},
			callbacks: {
				//success: callbackFunction,			// 'success' result callback		(optional, default: undefined, returns data)
				//info: functionName,				// 'info' result callback			(optional, default: undefined, returns data)
				//validate: functionName,			// 'validate' result callback		(optional, default: undefined, returns data)
				//failure: functionName,			// 'failure' result callback		(optional, default: undefined, returns data)
				//default: functionName,			// unmatched result callback		(optional, default: undefined, returns data)
				//response: functionName,			// returns result data, always		(optional, default: undefined, returns data)
				//ajax_error: functionName,		// ajax.done error callback			(optional, default: undefined)
				//ajax_fail: functionName,		// ajax.fail callback				(optional, default: undefined)
				//ajax_always: functionName		// ajax.always callback				(optional, default: undefined)
			}
		};
		
		// Setup table DataTable settings
		tables[table_id].dt = $(tables[table_id].ele.table).DataTable({
			'bAutoWidth': false,
			'language': {
				'emptyTable': tables[table_id].empty
			},
			'dom': fks.data_table_dom,
			'iDisplayLength': 15,
			'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
			'order': [[0, 'asc']],
			'columnDefs': [
				{'targets': [tables[table_id].count++], 'title': 'ID'},
				{'targets': [tables[table_id].count++], 'title': 'Title'},
				{'targets': [tables[table_id].count++], 'title': 'Date Created', 'type': 'date'},
				{'targets': [tables[table_id].count++], 'title': 'Created By'},
				{'targets': [tables[table_id].count++], 'title': 'Date Modified', 'type': 'date'},
				{'targets': [tables[table_id].count++], 'title': 'Modified By', 'fksDisplay': 'md'},
				{'targets': [tables[table_id].count++], 'title': 'Tools', 'className': 'icon-3', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'title'},
				{'data': 'date_created'},
				{'data': 'created_name'},
				{'data': 'date_modified'},
				{'data': 'modified_name'},
				{'data': 'tools'}
			],
			'createdRow': function (row, data, index) {
				// Set row data
				$(row).data(data);
				
				// Bind tool buttons
				$(row).find('.view').on('click', function() { tables[table_id].functions.view(data.id); });
				$(row).find('.edit').on('click', function() { tables[table_id].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadExampleHistory', page.src); });
			},
			'drawCallback': function() {
				// Set fks tooltips inside the table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[table_id].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[table_id], page.src);
	}
	
	// -------------------- Example Callback Function -------------------- //
	function callbackFunction(response, options) {
		//response - Response back from PHP
		//options - Options passed to Ajax
		console.log('Callback Function Ran!');
	}
	
	// -------------------- Example Add Function -------------------- //
	function addFunction() {
		fks.editModal({
			debug: page.debug,				// Optional - Whether to spit out returned text in the console. Default: true
			src: page.src,					// Required - Where to look for the functions.php page.
			focus: false,					// Optional - Auto focuses the first VISIBLE ENABLED EDITABLE input. Default: true
			action: 'addFunction',			// Required - What function to call in PHP.
			action_data: 'passed_data',		// Optional - What data to pass to the PHP function
			callbacks: {
				onOpen: function(data) {
					// Optional - Runs when the modal opens
					
					// Example binding the form for saving
					$('#modalForm').submit(function() {
						saveFunction(this);
					});
				},
				onClose: function(data) {
					// Optional - Runs when the modal closes
				}
			}
		});
	}
	
	// -------------------- Example Edit Function -------------------- //
	function editFunction() {
		console.log('Edit Button Clicked!');
	}
	
	// -------------------- Example View Function -------------------- //
	function viewFunction() {
		console.log('View Button Clicked!');
	}
	
	// -------------------- Example Load Panel Function -------------------- //
	function loadExamplePanel() {
		fks.ajax({
			src: page.src,
			action: 'loadExamplePanel',
			block: '#panel_id_1',
			block_options: {width: null},
			callbacks: {
				success: function(response) {
					$('#panel_id_1 .body').html(response.data);
				}
			}
		});
	}
	
	// -------------------- Tabbed Modal Example (Only Required Options) -------------------- //
	function editModalTabs() {
		fks.editModal({
			src: page.src,					// Required - Where to look for the functions.php page.
			action: 'editModalTabs'			// Required - What function to call in PHP.
		});
	}
	
	return {
		view: View
	};
});