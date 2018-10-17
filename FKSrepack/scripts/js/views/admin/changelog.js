define([
	'iCheck',
	'select2',
	'multiselect',
	'datatables.net'
], function() {
	var page = {
		src: './views/admin/changelog.php',
		hash: '#admin/changelog',
		title: fks.siteTitle + ' : Admin : Changelog',
		access: fks.checkAccess('changelog'),
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
	var tabcontrol = null;
	
	// -------------------- Initialize Page -------------------- //
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
		
		// Set fks.tabcontrol
		tabcontrol = fks.tabcontrol({
			container: '#changelog_panel > .header > .title > .nav-tabs',
			select: 'first',
			conmen: {
				menu: [
					{
						type: 'item',
						text: 'Reload Tab',
						onClick: function(data) {
							// Get changelog id
							if($(data).is('a')) {
								var changelog_id = $(data).attr('changelog-id');
							} else {
								var changelog_id = $(data).children('a').attr('changelog-id');
							}
							
							// Is this tab active?
							if($('[href="#tab' + changelog_id + '"]', tabcontrol.container).hasClass('active')) {
								editChangelog(changelog_id);
							} else {
								editChangelog({
									id: changelog_id,
									switch_to: false,
									block: $('[href="#tab' + changelog_id + '"]:first', tabcontrol.container).parent(),
									block_options: {
										width: null,
										body: '<i class="fa fa-refresh fa-spin fa-fw"></i>'
									}
								});
							}
						},
						icon: {
							type: 'fontawesome',
							val: 'refresh'
						}
					},
					'%CLOSE_TAB%'
				]
			}
		});
		
		// Tab drop
		fks.tabdrop({
			ele: $('#changelog_panel > .header > .title > .nav-tabs')
		});

		// Run functions when page loads
		loadChangelogTable();
		
		// When reloading switch to changelogs tab
		$(tables[0].ele.reload).on('click', function() {
			fks.block('#changelog_panel', tables[0].message.reload);
			$('[href="#tab0"]:first').tab('show');
		});
		
		// Load changelog if passed
		if(page.params.id) {
			if($.isArray(page.params.id)) {
				$.each(page.params.id, function(k,v) {
					editChangelog({
						id: v,
						switch_to: false,
						block: '_blank'
					});
				});
			} else {
				editChangelog({
					id: page.params.id,
					block: '_blank'
				});
			}
		}
	}
	
	// -------------------- Load Changelog Tables -------------------- //
	function loadChangelogTable() {
		// Setup table general settings
		tables[0] = {
			ele: {
				table: $('#tab0 table:first'),								// The table
				add: $('#changelog_panel .actions .add-table'),				// The add button (Optional)
				reload: $('#changelog_panel .actions .reload-table'),		// The reload button (Optional)
				columns: $('#changelog_panel .actions .column-toggler'),	// The column toggler button (Optional)
				tab: $('[href="#tab0"]').parent()							// The first tab
			},
			dt: null,						// DataTable variable
			empty: 'No changelogs found',	// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Changelogs...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Changelogs...',	// FKSblock reloading message
				tab: '<i class="fa fa-refresh fa-spin fa-fw"></i><span class="d-none d-lg-inline"> Reloading...</span>'					// For reloading the tab
			},
			count: 0,							// Leave me be
			action: 'loadChangelogTable',		// PHP function for loading data
			functions: {
				add: addChangelog,				// Called when ele.add is clicked (Optional)
				edit: editChangelog,			// Edit function (Optional)
				view: viewChangelog				// View function (Optional)
			},
			callbacks: {
				ajax_always: function() {
					// Unblock (re)loading
					fks.unblock('#changelog_panel');
					
					// Unblock when saving whilst on another tab
					fks.unblock($('[href="#tab0"]').parent());
				}
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
			'order': [[1, 'desc']],
			'columnDefs': [
				{'targets': [tables[0].count++], 'title': 'ID'},
				{'targets': [tables[0].count++], 'title': 'Version'},
				{'targets': [tables[0].count++], 'title': 'Title'},
				{'targets': [tables[0].count++], 'title': 'Date Created', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Created By'},
				{'targets': [tables[0].count++], 'title': 'Date Modified', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Modified By'},
				{'targets': [tables[0].count++], 'title': 'Status'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'className': 'icon-3', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'version'},
				{'data': 'title'},
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
				$(row).find('.view').on('click', function() { tables[0].functions.view(data.id); });
				$(row).find('.edit').on('click', function() { tables[0].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadChangelogHistory', page.src); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.block('#changelog_panel', tables[0].message.load);
		fks.loadTable(tables[0], page.src);
	}
	
	// -------------------- Setup Changelog Notes Table -------------------- //
	function setupChangelogNotesTable(changelog_id) {
		tables[changelog_id] = {
			ele: {
				block: $('#tab' + changelog_id + ' table.notes-table'),						// Element that you want blocked
				table: $('#tab' + changelog_id + ' table.notes-table'),						// The table
			},
			dt: null,						// DataTable variable
			empty: 'No notes found',		// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Notes...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Notes...'	// FKSblock reloading message
			},
			count: 0,								// Leave me be
			action: 'loadChangelogNotesTable',		// PHP function for loading data
			functions: {
				edit: editChangelogNote,			// Edit function
				remove: deleteChangelogNote			// Remove function
			}
		};
		
		tables[changelog_id].dt = $(tables[changelog_id].ele.table).DataTable({
			'bAutoWidth': false,
			'language': {
				'emptyTable': tables[changelog_id].empty
			},
			'iDisplayLength': 15,
			'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
			'order': [[1, 'asc'], [0, 'asc']],
			'columnDefs': [
				{'targets': [tables[changelog_id].count++], 'title': 'ID'},
				{'targets': [tables[changelog_id].count++], 'title': 'Type'},
				{'targets': [tables[changelog_id].count++], 'title': 'Note'},
				{'targets': [tables[changelog_id].count++], 'title': 'Tools', 'className': 'icon-2', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'type'},
				{'data': 'data'},
				{'data': 'tools'}
			],
			'createdRow': function (row, data, index) {
				// Set row data
				$(row).data(data);
				
				// Bind tool buttons
				$(row).find('.edit').on('click', function() { tables[changelog_id].functions.edit({'changelog_id': changelog_id, 'note_id': data.id}); });
				$(row).find('.delete').on('click', function() { tables[changelog_id].functions.remove({'changelog_id': changelog_id, 'note_id': data.id}); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[changelog_id].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[changelog_id], page.src, changelog_id);
	}
	
	// -------------------- Add Changelog Modal -------------------- //
	function addChangelog() {
		fks.editModal({
			'src': page.src,
			'wait': true,
			'action': 'addChangelog',
			'callbacks': {
				'onOpen': function() {
					$('#modalForm').submit(function(){
						createChangelog(this);
					});
				}
			}
		});
	}
	
	// -------------------- Create Changelog from Modal -------------------- //
	function createChangelog(form) {
		var form_data = fks.superSerialize(form);
		fks.ajax({
			src: page.src,
			action: 'createChangelog',
			action_data: form_data,
			form: form,
			block: '#fks_modal .modal-dialog:first',
			callbacks: {
				success: function() {
					fks.block('#changelog_panel', tables[0].message.reload);
					fks.loadTable(tables[0], page.src);
					$('#fks_modal').modal('hide');
					if(form_data.settings == 1) {
						fks.restartJob('fks.keepAlive');
					}
				}
			}
		});
	}
	
	// -------------------- View Changelog Modal -------------------- //
	function viewChangelog(changelog_id) {
		fks.editModal({
			'src': page.src,
			'wait': true,
			'data': changelog_id,
			'action': 'viewChangelog'
		});
	}
	
	// -------------------- Edit Changelog Tab -------------------- //
	function editChangelog(data) {
		// Defaults
		var options = {
			id: 0,
			switch_to: true,
			block: '#changelog_panel',
			block_options: null
		}
		
		// If an array is passed then setup new options
		if($.type(data) !== 'string') {
			$.each(data, function(k,v) {
				options[k] = v;
			});
		} else {
			options.id = data;
		}
		
		fks.ajax({
			src: page.src,
			action: 'editChangelog',
			action_data: options.id,
			block: options.block,
			block_options: options.block_options,
			callbacks: {
				success: function(response) {
					// Create the tab if it doesn't already exist
					if( $('[href="#tab' + options.id + '"]').length == 0 ) {
						$('#changelog_panel > .header > .title > .nav-tabs').append(
							'<li class="nav-item">'
							+ '<a class="nav-link" data-toggle="tab" href="#tab' + options.id + '" role="tab" changelog-id="' + options.id + '" draggable="false"><i class="fa fa-edit fa-fw"></i><span class="d-none d-md-inline"> Edit: <span class="changelog-id"></span></span></a>'
							+ '</li>'
						);
					}
					
					// Create the tab body if it doesn't already exist
					if( $('#tab' + options.id).length == 0 ) {
						$('#changelog_panel > .body > .tab-content').append('<div class="tab-pane" id="tab' + options.id + '" role="tabpanel"></div>');
					}
					
					// Set tab title
					$('[href="#tab' + options.id + '"] .changelog-id').html(response.data.version);
					
					// Load tab template and replace values
					$('#tab' + options.id).html(fks.template('changelog', {
						'%CHANGELOG_ID%': response.data.id,
						'%VERSION%': response.data.version,
						'%TITLE%': (response.data.title == null ? '' : response.data.title),
						'%NOTES%': (response.data.notes == null ? '' : response.data.notes)
					}));
					
					// Change status dropdown
					$('#change_active_' + options.id, '#tab' + options.id).val(response.data.active);
					
					// Show the tab contents
					if(options.switch_to) {
						$('[href="#tab' + options.id + '"]:first').tab('show');
					}
					
					// New way of loading notes...
					setupChangelogNotesTable(response.data.id);
					
					// Bind form on submit
					$('#changelogForm_' + options.id).submit(function(){
						updateChangelog(this);
					});
					
					// Bind submit & reset form
					fks.submitForm();
					fks.resetForm();
					
					// Bind close button
					$('#tab' + options.id + ' .close-tab-btn').on('click', function() {
						tabcontrol.closeTab($('[href="#tab' + options.id + '"]:first'));
					});
					
					// Bind add note button
					$('#tab' + options.id + ' .add-note-btn').on('click', function() {
						editChangelogNote({'changelog_id': response.data.id, 'note_id': '+'});
					});
				}
			}
		});
	}
	
	// -------------------- Update Changelog -------------------- //
	function updateChangelog(form) {
		var form_data = fks.superSerialize(form);
		
		fks.ajax({
			src: page.src,
			action: 'updateChangelog',
			action_data: form_data,
			form: form,
			block: '#changelog_panel',
			callbacks: {
				success: function() {
					// Update the tab title on success
					$('[href="#tab' + form_data.id + '"] .changelog-id').html(form_data.version);
					
					// Reload changelog table
					fks.block(tables[0].ele.tab, {width: null, body: tables[0].message.tab});
					fks.loadTable(tables[0], page.src);
				}
			}
		});
	}
	
	// -------------------- Edit Changelog Note -------------------- //
	function editChangelogNote(data) {
		fks.editModal({
			'src': page.src,
			'wait': true,
			'data': data,
			'action': 'addChangelogNote',
			'callbacks': {
				'onOpen': function() {
					// Create the pages multiselect
					fks.multiSelect('#modal_pages', {
						selectableHeader: {text: 'Selectable Pages', style: true},
						selectionHeader: {text: 'Selected Pages', style: true},
						selectableFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-success">Select All</button>',
						selectionFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-danger">Deselect All</button>',
						selectableOptgroup: true,
						optGroups: true,
						height: 312
					});
					
					// Bind submit modal form
					$('#modalForm').submit(function(){
						createChangelogNote(this);
					});
				}
			}
		});
	}
	
	// -------------------- Delete Changelog Note -------------------- //
	function deleteChangelogNote(changelog_data) {
		fks.bootbox.dialog({
			show: false,
			closeButton: false,
			animate: false,
			title: 'Deletion Confirmation',
			message: 'Are you sure you want to delete this note?',
			buttons: {
				ok: {
					label: '<i class="fa fa-check fa-fw"></i> Yes',
					className: 'fks-btn-success btn-sm',
					callback: function() {
						fks.ajax({
							src: page.src,
							action: 'deleteChangelogNote',
							action_data: changelog_data,
							block: tables[changelog_data.changelog_id].ele.block,
							callbacks: {
								success: function() {
									fks.loadTable(tables[changelog_data.changelog_id], page.src, changelog_data.changelog_id);
								}
							}
						});
					}
				},
				cancel: {
					label: '<i class="fa fa-close fa-fw"></i> No',
					className: 'fks-btn-danger btn-sm',
				}
			}
		}).on('shown.bs.modal', function() {
			$('h4.modal-title', this).changeElementType('h5');
			$('.btn', this).css('width', '65px');
		}).modal('show');
	}
	
	// -------------------- Create Changelog Note -------------------- //
	function createChangelogNote(form) {
		var form_data = fks.superSerialize(form);
		
		fks.ajax({
			src: page.src,
			action: 'createChangelogNote',
			action_data: form_data,
			form: form,
			block: '#fks_modal .modal-dialog:first',
			callbacks: {
				success: function() {
					fks.loadTable(tables[form_data.changelog_id], page.src, form_data.changelog_id);
					$('#fks_modal').modal('hide');
				}
			}
		});
	}
	
	return {
		view: View
	};
});