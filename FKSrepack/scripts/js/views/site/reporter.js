define([
	'datatables.net',
	'iCheck'
], function() {
	var page = {
		src: './views/site/reporter.php',
		hash: '#site/reporter',
		title: fks.siteTitle + ' : Site Name : Tracker',
		access: fks.checkAccess('tracker')
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
	
	var tables = {};	// Datatables array
	
	function initializePage(result) {
		if('#' + fks.location() != page.hash) { return false; }
		document.title = page.title;
		self.$el.html(result);
		if(!fks.pageAccess(result, page)) { return false; }
		
		// Bind fks actions
		fks.fksActions([
			'panelToggle',
			'panelClose',
			'panelFullscreen'
		]);
		
		// Load example panels
		loadExampleTable();
		loadExamplePanel();
	}
	
	function loadExampleTable() {
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#panel_id_2'),								// Element that you want blocked
				table: $('#panel_id_2 table:first'),					// The table
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
			action: 'loadExampleTable',			// PHP function for loading data
			functions: {
				callback: callbackFunction,		// Called when data is loaded (Optional)
				add: addFunction,				// Called when ele.add is clicked (Optional)
				edit: editFunction,				// Edit function (Optional)
				view: viewFunction				// View function (Optional)
			}
		};
		
		// Setup table DataTable settings
		tables[0].dt = $(tables[0].ele.table).DataTable({
			'bAutoWidth': false,
			'language': {
				'emptyTable': tables[0].empty
			},
			'dom': fks.data_table_dom,
			'iDisplayLength': 15,
			'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
			'order': [[0, 'asc']],
			'columnDefs': [
				{'targets': [tables[0].count++], 'title': 'ID'},
				{'targets': [tables[0].count++], 'title': 'Title'},
				{'targets': [tables[0].count++], 'title': 'Date Created', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Created By'},
				{'targets': [tables[0].count++], 'title': 'Date Modified', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Modified By'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'className': 'icon-3', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
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
				$(row).find('.view').on('click', function() { tables[0].functions.view(data.id); });
				$(row).find('.edit').on('click', function() { tables[0].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadExampleHistory', page.src); });
			},
			'drawCallback': function() {
				// Set fks tooltips inside the table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	function callbackFunction() {
		console.log('Callback Function Called!');
	}
	
	function addFunction() {
		console.log('Add Button Clicked!');
	}
	
	function editFunction() {
		console.log('Edit Button Clicked!');
	}
	
	function viewFunction() {
		console.log('View Button Clicked!');
	}
	
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
	
	return {
		view: View
	};
});