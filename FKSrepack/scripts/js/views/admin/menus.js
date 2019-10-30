define([
	'datatables.net',
	'iCheck',
	'select2',
	'multiselect',
	'nestable'
], function() {
	var page = {
		src: './views/admin/menus.php',
		hash: '#admin/menus',
		title: fks.siteTitle + ' : Admin : Menus',
		access: fks.checkAccess('menus'),
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
		loadMenusTable();
		loadMenuItemsTable();
		
		// On tab switch
		$('#main_panel .header .title .nav a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			// Get tab id
			var tab_id = $(e.target).attr('href').replace('#', '');
			
			// Hide all actions
			$('#main_panel .header .actions').hide();
			
			// Show tab actions
			$('#main_panel .header .actions[tab-actions="' + tab_id + '"]').show();
			
			// Resize window to fix tabdrop
			$(window).resize();
		});
	}
	
	function loadMenusTable() {
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#menus_panel'),								// Element that you want blocked
				table: $('#menus_panel table:first'),					// The table
				add: $('#main_panel .actions[tab-actions="menus_panel"] .add-table'),				// The add button (Optional)
				reload: $('#main_panel .actions[tab-actions="menus_panel"] .reload-table'),		// The reload button (Optional)
				columns: $('#main_panel .actions[tab-actions="menus_panel"] .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,						// DataTable variable
			empty: 'No menus found',		// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Menus...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Menus...'	// FKSblock reloading message
			},
			count: 0,							// Leave me be
			action: 'loadMenusTable',			// PHP function for loading data
			functions: {
				add: editMenu,					// Called when ele.add is clicked (Optional)
				edit: editMenu					// Edit function (Optional)
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
				{'targets': [tables[0].count++], 'title': 'Status'},
				{'targets': [tables[0].count++], 'title': 'Tools', 'className': 'icon-2', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
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
				$(row).find('.edit').on('click', function() { tables[0].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadMenuHistory', page.src); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	function editMenu(id) {
		if(!id) { var id = '+'; }
		fks.editModal({
			src: page.src,
			action: 'editMenu', 
			action_data: id,
			callbacks: {
				onOpen: function() {
					$('#editMenuForm').submit(function() {
						saveMenu(this);
					});
				}
			}
		});
	}
	
	function saveMenu(form) {
		fks.ajax({
			src: page.src,
			action: 'saveMenu',
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
	
	function loadMenuItemsTable() {		
		// Setup table general settings
		tables[1] = {
			ele: {
				block: $('#menu_items_panel'),									// Element that you want blocked
				table: $('#menu_items_panel table:first'),						// The table
				add: $('#main_panel .actions[tab-actions="menu_items_panel"] .add-table'),				// The add button (Optional)
				reload: $('#main_panel .actions[tab-actions="menu_items_panel"] .reload-table'),			// The reload button (Optional)
				columns: $('#main_panel .actions[tab-actions="menu_items_panel"] .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable
			empty: 'No menu items found',		// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Menu Items...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Menu Items...'	// FKSblock reloading message
			},
			count: 0,								// Leave me be
			action: 'loadMenuItemsTable',			// PHP function for loading data
			functions: {
				add: editMenuItem,					// Called when ele.add is clicked (Optional)
				edit: editMenuItem					// Edit function (Optional)
			}
		};
		
		// Setup table DataTable settings
		tables[1].dt = $(tables[1].ele.table).DataTable({
			'bAutoWidth': false,
			'language': {
				'emptyTable': tables[1].empty
			},
			'dom': fks.data_table_dom,
			'iDisplayLength': 15,
			'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
			'order': [[1, 'asc']],
			'columnDefs': [
				{'targets': [tables[1].count++], 'title': 'ID'},
				{'targets': [tables[1].count++], 'title': 'Menu'},
				{'targets': [tables[1].count++], 'title': 'Parent'},
				{'targets': [tables[1].count++], 'title': 'Position'},
				{'targets': [tables[1].count++], 'title': 'Title'},
				{'targets': [tables[1].count++], 'title': 'URL'},
				{'targets': [tables[1].count++], 'title': 'Icon'},
				{'targets': [tables[1].count++], 'title': 'Label'},
				{'targets': [tables[1].count++], 'title': 'Date Created', 'type': 'date', 'visible': false},
				{'targets': [tables[1].count++], 'title': 'Created By', 'visible': false},
				{'targets': [tables[1].count++], 'title': 'Date Modified', 'type': 'date', 'visible': false},
				{'targets': [tables[1].count++], 'title': 'Modified By', 'visible': false},
				{'targets': [tables[1].count++], 'title': 'Status'},
				{'targets': [tables[1].count++], 'title': 'Tools', 'className': 'icon-3', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
			],
			'columns': [
				{'data': 'id'},
				{'data': 'menu_title'},
				{'data': 'parent_title'},
				{'data': 'pos'},
				{'data': 'title'},
				{'data': 'url'},
				{'data': 'icon'},
				{'data': 'label'},
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
				$(row).find('.edit').on('click', function() { tables[1].functions.edit(data.id); });
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadMenuItemHistory', page.src); });
				$(row).find('.create').on('click', function() { createMenuItemPages(data.id); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[1].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[1], page.src);
	}
	
	function editMenuItem(id) {
		if(!id) { var id = '+'; }
		fks.editModal({
			src: page.src,
			action: 'editMenuItem', 
			action_data: id,
			callbacks: {
				onOpen: function(data) {
					$('#editMenuItemForm').submit(function() {
						saveMenuItem(this);
					});
					
					$('[name="icon"].select2').select2({
						containerCssClass: 'fks-sm',
						dropdownCssClass: 'fks-sm',
						width: '100%',
						data: data.icons,
						templateResult: function(icon) {
							if(!icon.id) { return icon.text; }
							var $icon = $(
								'<span><i class="fa fa-' + icon.text + ' fa-fw"></i> ' + icon.text + '</span>'
							);
							return $icon;
						}
					}).val(data.current_icon).trigger('change');
					
					$('[name="icon"].select2').on('change', function() {
						$('#icon_preview').html('<i class="fa fa-' + $(this).val() + ' fa-fw"></i>');
					});
					
					data.parents[0] = {id: 0, parent_title: 'None'};
					
					$('[name="menu_id"].select2').select2({
						containerCssClass: 'fks-sm',
						dropdownCssClass: 'fks-sm',
						width: '100%'
					}).on('change', function(e) {
						$('[name="parent_id"].select2').empty().select2({
							containerCssClass: 'fks-sm',
							dropdownCssClass: 'fks-sm',
							width: '100%',
							data: $.map(data.parents, function(parent) {
								if(parent.id != 0 && parent.menu_id != $('[name="menu_id"].select2').val()) { return; }
								return {
									id: parent.id,
									text: parent.parent_title
								};
							})
						});
					}).trigger('change');
					
					$('[name="parent_id"].select2').val(data.current_parent).trigger('change');
					
					$('.gen-url', '#fks_modal').click(function() {
						var title = $('[name="title"]', '#fks_modal').val().trim().toLowerCase().replace(/ /g, '_').replace(/[^a-zA-Z0-9-_]/g, '');
						$('[name="url"]', '#fks_modal').val(title);
					});
					
					$('.gen-label', '#fks_modal').click(function() {
						var title = $('[name="title"]', '#fks_modal').val().trim().toLowerCase().replace(/ /g, '_').replace(/[^a-zA-Z0-9-_]/g, '');
						var parent = $('[name="parent_id"]', '#fks_modal').val();
						if(parent != 0) {
							parent = data.parents[parent].parent_title.trim().toLowerCase().replace(/[ \/]/g, '_').replace(/[^a-zA-Z0-9-_]/g, '');
							title = parent + '_' + title;
						}
						$('[name="label"]', '#fks_modal').val(title);
					});
					
					$('#editMenuItemForm').bind('reset:after', function() {
						$('[name="icon"].select2').val(data.current_icon).trigger('change');
						$('[name="menu_id"].select2').trigger('change');
						$('[name="parent_id"].select2').val(data.current_parent).trigger('change');
					});
				}
			}
		});
	}
	
	function saveMenuItem(form) {
		fks.ajax({
			src: page.src,
			action: 'saveMenuItem',
			action_data: fks.superSerialize(form),
			form: form,
			block: '#fks_modal .modal-dialog:first',
			callbacks: {
				success: function() {
					fks.loadTable(tables[1], page.src);
					$('#fks_modal').modal('hide');
				}
			}
		});
	}
	
	function createMenuItemPages(menu_id) {
		fks.ajax({
			src: page.src,
			action: 'createMenuItemPages',
			action_data: menu_id,
			block: tables[1].ele.block,
			block_options: '<i class="fa fa-spinner fa-spin fa-fw"></i> Creating Pages...',
			callbacks: {
				success: function() {
					fks.loadTable(tables[1], page.src);
				}
			}
		});
	}
	
	return {
		view: View
	};
});