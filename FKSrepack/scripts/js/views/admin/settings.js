define([
	'datatables.net',
	'select2',
	'multiselect',
	'iCheck',
	'summernote',
	'codemirror',
	'codemirror/mode/htmlmixed/htmlmixed'
], function() {
	var page = {
		src: './views/admin/settings.php',
		hash: '#admin/settings',
		title: fks.siteTitle + ' : Admin : Settings',
		access: fks.checkAccess('site_settings'),
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
			fks.loadHistory(0, 'loadSiteSettingsHistory', page.src)
		});
		
		// Run functions when page loads
		loadSiteSettings();
	}
	
	function loadSiteSettings() {
		fks.ajax({
			src: page.src,
			action: 'loadSiteSettings',
			block: '#site_settings',
			block_options: '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading Settings...',
			callbacks: {
				success: function(response) {
					// Unbind FKS Tabdrop (if bound)
					fks.tabdropUnbind($('#site_settings [fks-tabdrop="bound"]'));
					
					$('#site_settings .title').html(response.tabs);
					$('#site_settings .body').html(response.body);
					$('#site_settings .body').show();
					$('#site_settings .footer').show();
					
					// Enable FKS Tabdrop
					$('.nav.nav-tabs').each(function() {
						fks.tabdrop({
							ele: $(this),
							text: 'More'
						});
					});
					
					fks.submitForm();
					fks.resetForm();
					$('#editSiteSettingsForm').submit(function(){
						saveSiteSettings(this);
					});
					
					$('#TIMEZONE').select2({
						containerCssClass: 'fks-sm',
						dropdownCssClass: 'fks-sm',
						width: '100%'
					});
					
					$('.access-lists').each(function(i, ele) {
						fks.multiSelect('#' + $(ele).attr('id'), {
							selectableHeader: {text: 'Selectable Groups', style: true},
							selectionHeader: {text: 'Selected Groups', style: true},
							selectableFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-success">Select All</button>',
							selectionFooter: '<button type="button" class="btn btn-block btn-sm fks-btn-danger">Deselect All</button>',
							selectableOptgroup: true,
							optGroups: false,
							height: 150
						});
					});
					
					fks.summernote('#EMAIL_VERIFICATION_TEMPLATE', {
						height: 250,
						minHeight: 200,
						maxHeight: 450,
						focus: false,
						codemirror: {
							theme: 'dracula',
							mode: 'htmlmixed',
							lineNumbers: true,
							indentWithTabs: true,
							indentUnit: 4
						}
					});
					
					fks.summernote('#FORGOT_PASSWORD_TEMPLATE', {
						height: 250,
						minHeight: 200,
						maxHeight: 450,
						focus: false,
						codemirror: {
							theme: 'dracula',
							mode: 'htmlmixed',
							lineNumbers: true,
							indentWithTabs: true,
							indentUnit: 4
						}
					});
					
					// Bind reset:after events
					$('#editSiteSettingsForm').bind('reset:after', function() {
						$('.access-lists').each(function(i, ele) {
							$('#' + $(ele).attr('id')).multiSelect('refresh');
						});
						$('#EMAIL_VERIFICATION_TEMPLATE').summernote('reset');
						$('#FORGOT_PASSWORD_TEMPLATE').summernote('reset');
						$('#TIMEZONE').trigger('change');
					});

					// Bind Test Email Button
					$('.test-email-btn').on('click', function(){
                        fks.editModal({
                            'src': page.src,
                            'action': 'sendEmailForm',
                            'callbacks': {
                                'onOpen': sendEmailFormCallback
                            }
                        })
					});
				}
			}
		});
	}
	
	function saveSiteSettings(form) {
		var form_data = fks.superSerialize(form);
		form_data.EMAIL_PASSWORD = btoa(form_data.EMAIL_PASSWORD);
		
		fks.ajax({
			src: page.src,
			action: 'saveSiteSettings',
			action_data: form_data,
			form: form,
			block: '#site_settings',
			block_options: '<i class="fa fa-spinner fa-spin fa-fw"></i> Saving Settings...',
			callbacks: {
				success : function(response) {
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

	function sendEmailFormCallback() {
        $('#editModalForm').submit(function() {
			var form = this;
			fks.ajax({
				src: page.src,
				action: 'sendTestMail',
				action_data: fks.superSerialize(form),
				form: form,
				block: '#fks_modal .modal-dialog:first'
			});
        });
	}
	
	return {
		view: View
	};
});