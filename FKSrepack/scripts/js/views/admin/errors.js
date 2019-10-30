define([
	'datatables.net',
	'iCheck'
], function() {
	var page = {
		src: './views/admin/errors.php',
		hash: '#admin/errors',
		title: fks.siteTitle + ' : Admin : Site Errors',
		access: fks.checkAccess('errors'),
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
		
		// Bind "View History" button
		$('#crumbs .actions .history').click(function() {
			fks.loadHistory(0, 'loadSiteErrorsHistory', page.src)
		});
		
		// Run functions when page loads
		loadErrorsTable();
	}
	
	function loadErrorsTable() {
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#panel_id_2'),								// Element that you want blocked
				table: $('#panel_id_2 table:first'),					// The table
				reload: $('#panel_id_2 .actions .reload-table'),		// The reload button (Optional)
				columns: $('#panel_id_2 .actions .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable (do not touch)
			empty: 'No errors found',			// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Errors...',		// fks.block loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Errors...'	// fks.block reloading message
			},
			count: 0,							// Column counter (do not touch)
			action: 'loadErrorsTable',			// PHP function for loading data
			functions: {
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
			'order': [[6, 'desc']],
			'columnDefs': [
				{'targets': [tables[0].count++], 'title': 'Code'},
				{'targets': [tables[0].count++], 'title': 'File'},
				{'targets': [tables[0].count++], 'title': 'Function'},
				{'targets': [tables[0].count++], 'title': 'Line'},
				{'targets': [tables[0].count++], 'title': 'Class'},
				{'targets': [tables[0].count++], 'title': 'Member'},
				{'targets': [tables[0].count++], 'title': 'Date Created'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'width': '55px', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'error_code'},
				{'data': 'file_name'},
				{'data': 'error_function'},
				{'data': 'error_line'},
				{'data': 'error_class'},
				{'data': 'member_name'},
				{'data': 'error_created'},
				{'data': 'tools'}
			],
			'createdRow': function (row, data, index) {
				// Set row data
				$(row).data(data);
				
				// Bind tool buttons
				$(row).find('.view').on('click', function() { tables[0].functions.view(data.error_code); });
			},
			'drawCallback': function() {
				// Set fks tooltips inside the table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	function viewFunction(error_code) {
		fks.editModal({
			src: page.src,
			action: 'loadErrorModal',
			action_data: error_code,
			callbacks: {
				onOpen: function() {
					$('#modalForm').submit(function(){
						deleteError(this);
					});
				}
			}
		});
	}
	
	function deleteError(form) {
		fks.ajax({
			src: page.src,
			action: 'deleteError',
			action_data: fks.superSerialize(form),
			form: form,
			block: '#fks_modal .modal-dialog:first',
			callbacks: {
				success: function() {
					fks.loadTable(tables[0], page.src);
					$('#fks_modal').modal('hide');
				}
			}
		});
	}
	
	return {
		view: View
	};
});