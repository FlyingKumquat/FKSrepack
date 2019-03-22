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
	var r_admins = {};	// Remote admin array
	
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
	
	// -------------------- Load Site Settings Function -------------------- //
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
					
					$('#SITE_HOME_PAGE').select2({
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
						$('#SITE_HOME_PAGE').trigger('change');
					});

					// Bind Test Email Button
					$('.test-email-btn').on('click', function(){
						fks.editModal({
							src: page.src,
							action: 'sendEmailForm',
							callbacks: {
								onOpen: sendEmailFormCallback
							}
						})
					});
					
					// Bind remote database on change
					$('#REMOTE_SITE').on('change', function(){
						if( $('#REMOTE_SITE').val() == 'Secondary' ) {
							fks.bootbox.dialog({
								show: false,
								closeButton: false,
								animate: false,
								className: 'warning-inactive',
								title: 'Warning!',
								message: 'If you continue to setup a Secondary site ALL members will be ereased!',
								buttons: {
									ok: {
										label: '<i class="fa fa-check fa-fw"></i> Continue',
										className: 'fks-btn-warning btn-sm'
									}
								}
							}).on('shown.bs.modal', function() {
								$('h4.modal-title', this).changeElementType('h5');
							}).modal('show');
						}
					});
					
					// Bind remote database on change
					$('#REMOTE_DATABASE').on('change', function(){
						$('#editSiteSettingsForm .remote-user-search').hide();
						$('#editSiteSettingsForm .remote-validate').show();
					});
					
					// Bind Remote Validate Button
					$('#tab1-7 .remote-test-btn').on('click', function(){
						// Grab REMOTE_SITE and REMOTE_DATABASE
						var form_data = fks.superSerialize($('#editSiteSettingsForm'));
						if(form_data['REMOTE_SITE'] != 'Secondary') { fks.burntToast({msg:'Remote Site must be on Secondary!'}); return false; }
						if(form_data['REMOTE_DATABASE'] == '') { fks.burntToast({msg:'Remote Database must not be blank!'}); return false; }
						
						// Send to PHP to validate connection
						fks.ajax({
							src: page.src,
							action: 'validateRemoteDatabase',
							action_data: form_data['REMOTE_DATABASE'],
							block: '#site_settings',
							block_options: '<i class="fa fa-spinner fa-spin fa-fw"></i> Validating Connection...',
							callbacks: {
								success: function(response) {
									//
									r_admins = {};
									
									// This runs to update the table after the page loads
									updateRemoteAdminsTable();
									
									// Hide Validate button and show User Search
									// TODO - DO NOT CHANGE THIS IF VALIDATING WHEN SECONDARY AND THE SELECTED DB IS ALREADY SET
									$('#editSiteSettingsForm .remote-validate').hide();
									$('#editSiteSettingsForm .remote-user-search').show();
								}
							}
						});
					});
					
					// Bind Remote Search Button
					$('#tab1-7 .remote-search-btn').on('click', function(){
						// Grab REMOTE_SITE and REMOTE_DATABASE
						var form_data = fks.superSerialize($('#editSiteSettingsForm'));
						if(form_data['REMOTE_SITE'] != 'Secondary') { fks.burntToast({msg:'Remote Site must be on Secondary!'}); return false; }
						if(form_data['REMOTE_DATABASE'] == '') { fks.burntToast({msg:'Remote Database must not be blank!'}); return false; }
						if(form_data['REMOTE_SEARCH'] == '') { fks.burntToast({msg:'Need to search for something!'}); return false; }
						
						// Send to PHP to validate connection
						fks.editModal({
							debug: page.debug,
							src: page.src,
							wait: true,
							action: 'searchRemoteDatabase',
							action_data: {database: form_data['REMOTE_DATABASE'], search: form_data['REMOTE_SEARCH']},
							callbacks: {
								onOpen: function(data) {
									// Bind the add buttons
									$('#fks_modal .member-add-btn').on('click', function(){
										//
										var _tr = $(this).closest('tr');
										var _sel = $('.member-access', _tr);
										
										// Add to table
										r_admins[$(_sel).attr('member-id')] = {
											id: $(_sel).attr('member-id'),
											username: $('.username', _tr).text(),
											access_id: $(_sel).val(),
											access: $('option:selected', _sel).text()
										}
										
										// Update the table with the newly added/updated member
										updateRemoteAdminsTable();
									});
								}
							}
						});
					});
				}
			}
		});
	}
	
	// -------------------- Save Site Settings Function -------------------- //
	function saveSiteSettings(form) {
		var form_data = fks.superSerialize(form);
		form_data.EMAIL_PASSWORD = btoa(form_data.EMAIL_PASSWORD);
		form_data.REMOTE_ADMINS = r_admins;
		
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
					
					// Reload the page if the member got logged out
					if(!response.do_log) {
						window.location.reload();
					}
				}
			}
		});
	}
	
	// -------------------- Load Site Settings Function -------------------- //
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
	
	// -------------------- Update Remote Admins Table -------------------- //
	function updateRemoteAdminsTable() {
		// Clear out table
		$('#tab1-7 table.remote-admins tbody').html('');
		
		// Check to see if there are any to add
		if( Object.keys(r_admins).length > 0 ) {
			$.each(r_admins, function(k, v){
				$('#tab1-7 table.remote-admins tbody').append('<tr><td>' + v.username + '</td><td>-</td><td>-</td><td>' + v.access + '</td><td><button type="button" class="btn fks-btn-danger btn-sm member-remove-btn"><i class="fa fa-trash fa-fw"></i></button></td></tr>');
				
				$('#tab1-7 table.remote-admins tbody tr:last-child .member-remove-btn').on('click', function(){
					delete(r_admins[k]);
					updateRemoteAdminsTable();
				});
			});
		} else {
			$('#tab1-7 table.remote-admins tbody').append('<tr><td colspan="5">-</td></tr>');
		}
		
	}
	
	return {
		view: View
	};
});