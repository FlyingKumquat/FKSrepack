define([
	'datatables.net',
	'iCheck'
], function() {
	var page = {
		src: './views/member/settings.php',
		hash: '#member/settings',
		title: fks.siteTitle + ' : Member Menu : Account Settings',
		access: fks.checkAccess('account_settings'),
		params: fks.getParams()
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
		
		// Bind fks panel actions
		fks.fksActions([
			'panelToggle',
			'panelClose',
			'panelFullscreen'
		]);
		
		// Run functions when page loads
		loadAccountActivity();
		loadAccountData();
	}
	
	// -------------------- Load Account Activity -------------------- //
	function loadAccountActivity() {
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#panel_id'),									// Element that you want blocked
				table: $('#panel_id table:first'),						// The table
				add: $('#panel_id .actions .add-table'),				// The add button (Optional)
				reload: $('#panel_id .actions .reload-table'),			// The reload button (Optional)
				columns: $('#panel_id .actions .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable (do not touch)
			empty: 'No entries found',			// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Entries...',		// fks.block loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Entries...'	// fks.block reloading message
			},
			count: 0,							// Column counter (do not touch)
			action: 'loadAccountActivity',		// PHP function for loading data
			functions: {
				view: fks.viewDetailedHistory	// View function (Optional)
			}
		};
		
		// Setup table DataTable settings
		tables[0].dt = $(tables[0].ele.table).DataTable({
			'bAutoWidth': false,
			'language': {
				'emptyTable': tables[0].empty
			},
			'iDisplayLength': 15,
			'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
			'order': [[0, 'desc']],
			'columnDefs': [
				{'targets': [tables[0].count++], 'title': 'Date Created'},
				{'targets': [tables[0].count++], 'title': 'Action'},
				{'targets': [tables[0].count++], 'title': 'Description'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'width': '55px', 'sortable': false, 'toggleable': false}
			],
			'columns': [
				{'data': 'date_created'},
				{'data': 'action_title'},
				{'data': 'misc_formatted'},
				{'data': 'tools'}
			],
			'createdRow': function (row, data, index) {
				// Set row data
				$(row).data(data);
				
				// Bind tool buttons
				$(row).find('.view').on('click', function() { tables[0].functions.view(data); });
			},
			'drawCallback': function() {
				// Set fks tooltips inside the table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	// -------------------- Load Account Data -------------------- //
	function loadAccountData() {
		fks.ajax({
			src: page.src,
			action: 'loadAccountData',
			block: '#member_data',
			callbacks: {
				success: function(response) {
					$('#member_data .body table tbody').html(response.data);$('#member_data .body table tbody').html(response.data);
				}
			}
		});
	}
	
	return {
		view: View
	};
});