define([
	'datatables.net',
	'iCheck',
	'multiselect',
	'summernote',
	'codemirror',
	'codemirror/mode/htmlmixed/htmlmixed'
], function() {
	var page = {
		src: './views/admin/announcements.php',
		hash: '#admin/announcements',
		title: fks.siteTitle + ' : Admin : Announcements',
		access: fks.checkAccess('admin_announcements'),
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
	
	var tables = {};	// Datatables array
	
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
		
		// Load Announcements Table
		loadAnnouncementsTable();
	}
	
	function loadAnnouncementsTable() {		
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#announcements_panel'),								// Element that you want blocked
				table: $('#announcements_panel table:first'),					// The table
				add: $('#announcements_panel .actions .add-table'),				// The add button (Optional)
				reload: $('#announcements_panel .actions .reload-table'),		// The reload button (Optional)
				columns: $('#announcements_panel .actions .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable
			empty: 'No announcements found',	// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Announcements...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Announcements...'	// FKSblock reloading message
			},
			count: 0,							// Leave me be
			action: 'loadAnnouncementsTable',	// PHP function for loading data
			functions: {
				add: editAnnouncement,			// Called when ele.add is clicked (Optional)
				edit: editAnnouncement			// Edit function (Optional)
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
				{'targets': [tables[0].count++], 'title': 'Title'},
				{'targets': [tables[0].count++], 'title': 'Access Group(s)'},
				{'targets': [tables[0].count++], 'title': 'Sticky'},
				{'targets': [tables[0].count++], 'title': 'Date Created', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Created By'},
				{'targets': [tables[0].count++], 'title': 'Date Modified', 'type': 'date'},
				{'targets': [tables[0].count++], 'title': 'Modified By'},
				{'targets': [tables[0].count++], 'title': 'Status'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'className': 'icon-2', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'title'},
				{'data': 'access_groups'},
				{'data': 'sticky'},
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
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadAnnouncementHistory', page.src); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	function editAnnouncement(id) {
		if(!id){ var id = '+'; }
		fks.editModal({
			src: page.src,
			wait: true,
			action: 'editAnnouncement', 
			action_data: id,
			callbacks: {
				onOpen: function() {
					fks.multiSelect('#pages', {
						selectableHeader: {text: 'Selectable Pages', style: true},
						selectionHeader: {text: 'Selected Pages', style: true},
						selectableFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-success">Select All</button>',
						selectionFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-danger">Deselect All</button>',
						selectableOptgroup: true,
						optGroups: true,
						height: 312
					});
					
					fks.multiSelect('#access_groups', {
						selectableHeader: {text: 'Selectable Groups', style: true},
						selectionHeader: {text: 'Selected Groups', style: true},
						selectableFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-success">Select All</button>',
						selectionFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-danger">Deselect All</button>',
						selectableOptgroup: true,
						optGroups: false,
						height: 312
					});
					
					fks.summernote('#announcement', {
						height: 200,
						minHeight: 100,
						maxHeight: 500,
						focus: false,
						toolbar: [
							['style', ['style']],
							['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
							['fontsize', ['fontsize']],
							['color', ['color']],
							['para', ['ul', 'ol', 'paragraph']],
							['height', ['height']],
							['table', ['table']],
							['insert', ['hr']],
							['view', ['codeview']]
						],
						codemirror: {
							theme: 'dracula',
							mode: 'htmlmixed',
							lineNumbers: true,
							indentWithTabs: true,
							indentUnit: 4
						}
					});
					
					$('#page_selection').on('change', function() {
						if($('#page_selection').val() == 'some') {
							$('#pages_multi_select').show();
						} else {
							$('#pages_multi_select').hide();
						}
					});
					
					$('#editAnnouncementForm').bind('reset:after', function() {
						$('#pages').multiSelect('refresh');
						$('#access_groups').multiSelect('refresh');
						$('#announcement').summernote('reset');
					});
					
					$('#editAnnouncementForm').submit(function() {
						saveAnnouncement(this);
					});
				}
			}
		});
	}
	
	function saveAnnouncement(form) {
		fks.ajax({
			src: page.src,
			action: 'saveAnnouncement',
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