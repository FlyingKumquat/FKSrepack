define([
	'datatables.net',
	'iCheck',
	'select2',
	'multiselect'
], function() {
	var page = {
		src: './views/admin/members.php',
		hash: '#admin/members',
		title: fks.siteTitle + ' : Admin : Members',
		access: fks.checkAccess('members'),
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
		loadMembersTable();
	}
	
	// -------------------- Load Members Table -------------------- //
	function loadMembersTable() {		
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#members_panel'),								// Element that you want blocked
				table: $('#members_panel table:first'),					// The table
				add: $('#members_panel .actions .add-table'),			// The add button (Optional)
				reload: $('#members_panel .actions .reload-table'),		// The reload button (Optional)
				columns: $('#members_panel .actions .column-toggler')	// The column toggler button (Optional)
			},
			dt: null,						// DataTable variable
			empty: 'No members found',		// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Members...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Members...'	// FKSblock reloading message
			},
			count: 0,							// Leave me be
			action: 'loadMembersTable',			// PHP function for loading data
			functions: {
				add: editMember,				// Called when ele.add is clicked (Optional)
				edit: editMember				// Edit function (Optional)
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
			'order': [[0, 'asc']],
			'columnDefs': [
				{'targets': [tables[0].count++], 'title': 'ID'},
				{'targets': [tables[0].count++], 'title': 'Username'},
				{'targets': [tables[0].count++], 'title': 'First Name'},
				{'targets': [tables[0].count++], 'title': 'Last Name'},
				{'targets': [tables[0].count++], 'title': 'Email Address'},
				{'targets': [tables[0].count++], 'title': 'Access Group(s)'},
				{'targets': [tables[0].count++], 'title': 'Date Created', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Created By'},
				{'targets': [tables[0].count++], 'title': 'Date Modified', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Modified By'},
				{'targets': [tables[0].count++], 'title': 'Status'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'className': 'icon-2', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'username'},
				{'data': 'first_name'},
				{'data': 'last_name'},
				{'data': 'email_address'},
				{'data': 'access_groups'},
				{'data': 'date_created'},
				{'data': 'created_name'},
				{'data': 'date_modified'},
				{'data': 'modified_name'},
				{'data': 'status'},
				{'data': 'tools'}
			],
			'createdRow': function (row, data, index) {
				// Set row data
				$(row).data(data);
				
				// Bind tool buttons
				$(row).find('.edit').on('click', function() { tables[0].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadMemberHistory', page.src); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	// -------------------- Edit Member Modal -------------------- //
	function editMember( id ) {
		if(!id){ var id = '+'; }
		fks.editModal({
			'src': page.src,
			'wait': true,
			'action': 'editMember', 
			'data': id,
			'callbacks': {
				'onOpen': editMemberCallback
			}
		})
	}
	
	// -------------------- Edit Member Modal Callback -------------------- //
	function editMemberCallback(){
		$('#TIMEZONE').select2({
			containerCssClass: 'fks-sm',
			dropdownCssClass: 'fks-sm',
			width: '100%'
		});
		
		fks.multiSelect('#ACCESS_GROUPS', {
			selectableHeader: {text: 'Selectable Groups', style: true},
			selectionHeader: {text: 'Selected Groups', style: true},
			selectableFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-success">Select All</button>',
			selectionFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-danger">Deselect All</button>',
			selectableOptgroup: true,
			optGroups: false,
			height: 340
		});
		
		$('#modalForm').bind('reset:after', function() {
			$('#TIMEZONE').trigger('change');
			$('#ACCESS_GROUPS').multiSelect('refresh');
		});
		
		$('#modalForm').submit(function(){
			saveMember(this);
		});
	}
	
	// -------------------- Save Member from Modal -------------------- //
	function saveMember(form) {
		// Serialize the form
		var form_data = fks.superSerialize(form);
		
		// Encrypt the password fields
		form_data.PASSWORD = btoa(form_data.PASSWORD);
		form_data.PASSWORD2 = btoa(form_data.PASSWORD2);
		
		// Send request with form and data
		fks.ajax({
			src: page.src,
			action: 'saveMember',
			action_data: form_data,
			block: '#fks_modal .modal-dialog',
			form: form,
			callbacks: {
				success: function(response) {
					// Reload table and close modal
					fks.loadTable(tables[0], page.src);
					$('#fks_modal').modal('hide');
					
					// Warning toast for reloading the page
					if(response.reload == 'true') {
						fks.burntToast({
							type: 'warning',
							header: 'Reload Required',
							remove: 'reload',
							closeButton: false,
							msg: 'You need to reload to see some of the new changes.'
						});
					}
				}
			}
		});
	}
	
	return {
		view: View
	};
});