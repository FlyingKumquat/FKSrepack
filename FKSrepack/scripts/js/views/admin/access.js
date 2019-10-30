define([
	'datatables.net',
	'iCheck',
	'select2',
	'multiselect',
	'jstree'
], function() {
	var page = {
		src: './views/admin/access.php',
		hash: '#admin/access',
		title: fks.siteTitle + ' : Admin : Access',
		access: fks.checkAccess('access_groups'),
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
	var tables = {},
		access_types = [
			{id: 0, title: 'None'},
			{id: 1, title: 'Read'},
			{id: 2, title: 'Write'},
			{id: 3, title: 'Admin'}
		];
	
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
		
		// Load Access Groups Table
		loadAccessGroupsTable();
	}
	
	function loadAccessGroupsTable() {	
		// Setup table general settings
		tables[0] = {
			ele: {
				block: $('#access_groups_panel'),								// Element that you want blocked
				table: $('#access_groups_panel table:first'),					// The table
				add: $('#access_groups_panel .actions .add-table'),				// The add button (Optional)
				reload: $('#access_groups_panel .actions .reload-table'),		// The reload button (Optional)
				columns: $('#access_groups_panel .actions .column-toggler')		// The column toggler button (Optional)
			},
			dt: null,							// DataTable variable
			empty: 'No access groups found',	// Empty message
			message: {
				load: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Access Groups...',		// FKSblock loading message
				reload: '<i class="fa fa-refresh fa-spin fa-fw"></i> Reloading Access Groups...'	// FKSblock reloading message
			},
			count: 0,							// Leave me be
			action: 'loadAccessGroups',			// PHP function for loading data
			functions: {
				add: editGroup,			// Called when ele.add is clicked (Optional)
				edit: editGroup			// Edit function (Optional)
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
				{'targets': [tables[0].count++], 'title': 'Hierarchy'},
				{'targets': [tables[0].count++], 'title': 'None'},
				{'targets': [tables[0].count++], 'title': 'Read'},
				{'targets': [tables[0].count++], 'title': 'Write'},
				{'targets': [tables[0].count++], 'title': 'Admin'},
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
				{'data': 'hierarchy'},
				{'data': 'data_none'},
				{'data': 'data_read'},
				{'data': 'data_write'},
				{'data': 'data_admin'},
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
				$(row).find('.history').on('click', function() { fks.loadHistory(data.id, 'loadGroupHistory', page.src); });
			},
			'drawCallback': function() {
				// Set tooltips of the first table inside table element
				fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', tables[0].ele.table)});
			}
		});
		
		// Load table
		fks.loadTable(tables[0], page.src);
	}
	
	function editGroup(id) {
		if(!id) { var id = '+'; }
		fks.editModal({
			src: page.src,
			action: 'editGroup', 
			action_data: id,
			callbacks: {
				onOpen: editGroupCallback
			}
		});
	}
	
	function editGroupCallback(data) {
		createAccessMenu($.extend(true, {}, data.access));
		
		$('#editGroupForm').submit(function() {
			saveGroup(this);
		});
		
		$('[fks-action="expandAll"]').on('click', function() {
			$('#access_group_tree').jstree('open_all');
		});
		
		$('[fks-action="collapseAll"]').on('click', function() {
			$('#access_group_tree').jstree('close_all');
		});
		
		$('#editGroupForm').bind('reset:after', function() {
			$('#access_group_tree').jstree('destroy');
			createAccessMenu($.extend(true, {}, data.access));
		});
	}
	
	function createAccessMenu(access) {
		var nodes = {};
		
		$('#access_group_tree').jstree({
			core: {
				animation: 0,
				check_callback: true,
				themes:{
					responsive:!1
				}
			},
			types: {
				parent: {
					icon:'fa fa-folder icon-state-warning'
				},
				child: {
					icon:'fa fa-file-o font-dark'
				}
			},
			search:{
				show_only_matches: true,
				show_only_matches_children: true
			},
			plugins:['types','search']
		});
		
		$('#access_group_tree').on('open_node.jstree close_node.jstree', function() {
			$('#access_group_tree select:not(.bound)').each(function() {
				$(this).addClass('bound');
				$(this).on('change', function() {
					updateTreeDropdown(this);
				});
				
				$(this).on('click', function() {
					event.stopImmediatePropagation();
					event.stopPropagation();
					event.preventDefault();
				});
			});
		});
		
		var to = false;
		$('#access_group_tree_q').keyup(function() {
			if(to) { clearTimeout(to); }
			to = setTimeout(function() {
				var v = $('#access_group_tree_q').val();
				searchTree(v, '#access_group_tree');
			}, 100);
		});

		$.each(access, function(k, v) {
			var value = v.access
				select = $('<select/>')
				options = '';
				
			select.attr('name', 'select_access_' + v.id);
			select.addClass('form-control');
			select.addClass('form-control-sm');
			
			$.each(access_types, function() {
				options += '<option value="' + this.id + '"' + (value == this.id ? 'selected' : '') + '>' + this.title + '</option>';
			});
			
			select.html(options);
			
			if(page.access < 2) {
				select.prop('disabled', true);
			}
				
			nodes[v.id] = {
				text: v.title,
				icon: v.icon != null ? 'fa fa-' + v.icon + ' font-dark' : 'fa fa-times error',
				dropdown: ' <span style="display: inline-block;">' + select.clone().wrap('<p>').parent().html() + '</span>',
				rank: '',
				children: false
			};
			
			if(nodes[v.parent] && !nodes[v.parent].children) {
				nodes[v.parent].children = true;
			}
			
			if(value > 0) { nodes[v.id].rank += '<i class="fa fa-book fa-fw"></i>'; }
			if(value > 1) { nodes[v.id].rank += '<i class="fa fa-pencil fa-fw"></i>'; }
			if(value > 2) { nodes[v.id].rank += '<i class="fa fa-cogs fa-fw"></i>'; }
		});
		
		var added = false;
		while(!added) {
			$.each(access, function(k, v) {
				if(!access[k].added && $('#access_group_tree').jstree(
					'create_node',
					v.parent == '0' ? $('#access_group_tree') : $('#access_group_tree').find('[id="access-node-' + v.parent + '"]'),
					{
						'id': 'access-node-' + v.id,
						'text': nodes[v.id].text + '<span class="pull-right" style="height: 27px;">' + nodes[v.id].rank + nodes[v.id].dropdown + '</span>',
						'type': nodes[v.id].children || v.parent == '0' ? 'parent' : 'child',
						'icon': nodes[v.id].icon
					},
					'last'
				)) {
					access[k].added = true;
				}
				$('#access_group_tree').jstree('open_all');
			});
			
			$.each(access, function(k, v){
				return added = v.added;
			});
		}
		$('#access_group_tree').jstree('close_all');
	}
	
	function searchTree(str, ele) {
		var find = new RegExp(str, 'i'),
			matches = [];
			
		$(ele).jstree('open_all');
		$(ele + ' li a').each(function() {
			var e = $(this),
				html = e.html(),
				title = html.replace(/<i(.*?)>(.*?)<\/i>/i, '').replace(/<span(.*?)>(.*?)<\/span>/i, ''),
				option = '';
			
			e.parent().removeClass('access-search');
			
			html.replace(/<option(.*?) selected(.*?)>(.*?)<\/option>/i, function($0, $1, $2, $3) {
				option = $3;
			});
			
			if(str != '' && (title.match(find) || option.match(find))) {
				matches.push(e.parentsUntil(ele).filter('li'));
			}
		});
		
		$(ele).jstree('close_all');
		$.each(matches, function(i, m) {
			$.each(m, function(k, v) {
				$(ele).jstree('open_node', v);
			});
			$('#' + m[0].id).addClass('access-search');
		});
	} 
	
	function updateTreeDropdown(ele) {
		var e = $(ele),
			v = e.val(),
			p = $(e.parents('[role="treeitem"]')[0]),
			j = $(e.parents('.jstree')),
			id = e.attr('name').replace('select_access_', ''),
			html = $('a:first', p).html().replace(/<i(.*?)>(.*?)<\/i>/i, '').replace(/<span(.*?)>(.*?)<\/span>/i, ''),
			options = '',
			dropdown = '',
			rank = '';
			
		if(v > 0) { rank += '<i class="fa fa-book fa-fw"></i>'; }
		if(v > 1) { rank += '<i class="fa fa-pencil fa-fw"></i>'; }
		if(v > 2) { rank += '<i class="fa fa-cogs fa-fw"></i>'; }
		
		$.each(access_types, function() {
			options += '<option value="' + this.id + '"' + (v == this.id ? 'selected' : '') + '>' + this.title + '</option>';
		});
		
		dropdown = ' <span style="display: inline-block;"><select name="select_access_' + id + '" class="form-control form-control-sm">' + options + '</select></span>';
		
		$('select.bound', p).removeClass('bound').off('change click');
		
		j.jstree('rename_node', p, html + '<span class="pull-right" style="height: 27px;">' + rank + dropdown + '</span>');

		$('#access_group_tree select:not(.bound)').each(function() {
			$(this).addClass('bound');
			$(this).on('change', function() {
				updateTreeDropdown(this);
			});
			
			$(this).on('click', function() {
				event.stopImmediatePropagation();
				event.stopPropagation();
				event.preventDefault();
			});
		});
	}
	
	function saveGroup(form) {
		// Manipulate access tree
		var form_data = fks.superSerialize(form);
		form_data.data = {};
		$('#access_group_tree').jstree('open_all').find('select').each(function() {
			var id = parseInt($(this).attr('name').replace('select_access_', '')),
				val = parseInt($(this).val());
			form_data.data[id] = val;
		}).jstree('close_all');
		$.each(form_data, function(k, v) { if(k.indexOf('select_access_') == 0) { delete(form_data[k]); } });
		
		// Send request with form and data
		fks.ajax({
			src: page.src,
			action: 'saveGroup',
			action_data: form_data,
			block: '#fks_modal .modal-dialog',
			form: form,
			callbacks: {
				success: function(response) {
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