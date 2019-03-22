/***********************************************
	Updated: 03/01/2019
***********************************************/
(function(fks, $, undefined) {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	// Private
	var self = $(document.currentScript).data(),
		modalOnClose = {},
		modalOnCloseData = {},
		jobs = {},
		templates = {},
		keepAlivePages = [];
	
	// Public
	fks.handler = '/scripts/php/views/handler.php';
	fks.siteTitle = '';
	fks.homePage = '';
	fks.currentPage = '';
	fks.currentPageLabel = '';
	fks.layout = null;
	fks.container = '#content';
	fks.ready = false;
	fks.readyCallback = null;
	fks.debug = {
		general: false,
		ajax: false,
		jobs: false,
		page: false,
		webSocks: false,
		keepAlive: false
	};
	fks.session = {
		actions: 1,
		last_action: 0,
		timeout: 0,
		guest: true,
		access: {}
	};
	fks.ws = {
		url: null,
		server: null,
		callbacks: {
			open: null,
			close: null,
			message: null,
			error: null,
			logout: null
		}
	};
	fks.toastr_options = {
		tapToDismiss: true,
		preventDuplicates: false,
		newestOnTop: true,
		escapeHtml: false,
		closeButton: false,
		debug: false,
		progressBar: true,
		positionClass: 'toast-top-right',
		toastClass: 'toast',
		onShown: null,
		onclick: null,
		showDuration: 100,
		hideDuration: 100,
		timeOut: 5000,
		extendedTimeOut: 1000,
		showEasing: 'swing',
		hideEasing: 'linear',
		showMethod: 'fadeIn',
		hideMethod: 'fadeOut',
		type: 'success',
		header: '',
		msg: ''
	};
	fks.announcements = {};
	fks.alerts = {};
	fks.notifiers = {};
	fks.sound = {
		cache: {},
		collection: {}
	};
	
/*----------------------------------------------
	Private Functions
----------------------------------------------*/
	function thread() {
		var now = fks.now();
		$.each(jobs, function(k, v) {
			if(v.page != false && $.inArray(fks.currentPage, v.page) == -1 ) { return; }
			if(now - v.last >= v.when) {
				if(k != '') {
					jobs[k].last = now;
					
					if($.type(v.function) === 'function') {
						v.function();
					} else {
						fks.executeFunctionByName(k, window);
					}
					
					jobs[k].count++;
					if(fks.debug.jobs && v.debug) { console.log('Running Job -> ' + k + ' (' + jobs[k].count + ')'); }
				}
			}
		});
		
		setTimeout(function() {
			thread();
		}, 100);
	}
	
	function keepAliveCallback() {
		if(fks.session.guest || fks.session.timeout == 0) { return; }
		
		var inactive = fks.now() - fks.session.last_action;
		if(inactive >= (fks.session.timeout - 120)) {
			if($('.modal.warning-inactive').length < 1) {
				var message = `
					<p>You have been inactive for an extended period of time and will be automatically logged out soon to keep your account secure.</p>
					<div class="progress" style="height: 4px; border-radius: 0px; margin-bottom:0px">
						<div class="progress-bar bg-info" style="width:100%; transition: width 60s cubic-bezier(1, 1, 1, 1)"></div>
					</div>
				`;
				fks.bootbox.dialog({
					show: false,
					closeButton: false,
					animate: false,
					className: 'warning-inactive',
					title: 'Inactivity Detected',
					message: message,
					buttons: {
						ok: {
							label: '<i class="fa fa-sign-out fa-fw"></i> Log me out',
							className: 'fks-btn-danger btn-sm',
							callback: function() {
								fks.logout('LOGOUT_MANUAL', fks.location());
							}
						},
						cancel: {
							label: '<i class="fa fa-check fa-fw"></i> Stay logged in',
							className: 'fks-btn-success btn-sm',
							callback: function() {
								fks.keepAlive();
							}
						}
					}
				}).on('shown.bs.modal', function() {
					$('h4.modal-title', this).changeElementType('h5');
					$('.progress>.progress-bar', this).width(0);
				}).modal('show');
			}
		}
	}
	
	function displayAnnouncements(announcements) {
		// Check to see if announce modal is already open
		if($('.modal.announcements-modal').length > 0) { return false; }
		
		// Loop through new announcements
		$.each(announcements, function(k, v) {
			if(fks.announcements[k] === undefined) {
				fks.announcements[k] = v;
			}
		});
		
		// Trigger announcements
		announce();
	}
	
	function announce(force = false) {
		var message = '',
			showNext = true,
			announcements = {},
			count = 0,
			index = 0;
			
		$.each(fks.announcements, function(k, v) {
			if(v.viewed != true) {
				if(!force && v.pages != null && v.pages.indexOf(fks.location()) < 0) {
				// Not forced and not on correct page, move to next announcement
					return true;
				}
				announcements[k] = v;
			}
		});
		
		updateAnnouncementNotifier();
			
		count = Object.keys(announcements).length;
		if(count == 0) { return false; }
		$.each(announcements, function(k, v) {
			var hidden = ' style="display: none;"';
			
			if(!v.viewed && showNext) {
				hidden = '';
				showNext = false;
				fks.announcements[k].viewed = true;
			}
			
			var a = '<div announcement-id="' + k + '" announcement-number="' + index + '"' + hidden + '>';
				a += '<div style="height: 27px; line-height: 27px;"><div style="float: left; font-weight: bold; font-size: 16px;">' + v.title + '</div><div style="float: right;"><i>' + v.created + '</i></div></div>';
				a += '<div style="border-top: 1px solid #eceeef; padding-top: 10px;">' + v.announcement + '</div>';
				a += '<div class="row" style="margin-top: 10px;">';
					a += '<div class="col-sm-4">';
						if(index != 0) {
						// Previous Button
							a += '<button class="btn fks-btn-primary btn-sm" style="float: left; width: 100px;" previous><i class="fa fa-angle-double-left"></i> Previous</button>';
						}
					a += '</div>';
					a += '<div class="col-sm-4" style="text-align: center; line-height: 27px;">';
						a += (index  + 1) + ' / ' + (count);
					a += '</div>';
					a += '<div class="col-sm-4">';
						if(index != count - 1) {
						// Next Button
							a += '<button class="btn fks-btn-primary btn-sm" style="float: right; width: 100px;" next>Next <i class="fa fa-angle-double-right"></i></button>';
						} else {
						// Done Button
							a += '<button class="btn fks-btn-success btn-sm" style="float: right; width: 100px;" next><i class="fa fa-check"></i> Okay</button>';
						}
					a += '</div>';
				a += '</div>';
			a += '</div>';
			
			message += a;
			index++;
		});
		
		message = '<div class="announcements">' + message + '</div>';
		
		// Seen all announcements already
		if(showNext) { return false; }
		
		fks.bootbox.dialog({
			closeButton: false,
			animate: false,
			show: false,
			size: 'large',
			className: 'announcements-modal',
			title: '<i class="fa fa-bullhorn fa-fw"></i> Announcements',
			message: message,
			buttons: {
				close: {
					label: '<i class="fa fa-times fa-fw"></i> Close',
					className: 'fks-btn-secondary btn-sm done-reading',
					callback: function() {
						saveAnnouncements();
						updateAnnouncementNotifier();
					}
				}
			}
		}).on('shown.bs.modal', function() {
			var _self = this;
			$('h4.modal-title', _self).changeElementType('h5');
			$('[announcement-number] [next]', _self).on('click', function() {
				var a = $(this).parents('[announcement-number]');
				fks.announcements[a.attr('announcement-id')].seen = true;
				if(a.attr('announcement-number') == count - 1) {
					$('.done-reading', _self).click();
				} else {
					a.hide();
					$('[announcement-number="' + (a.attr('announcement-number') * 1 + 1) + '"]').show();
				}
			});
			$('[announcement-number] [previous]', _self).on('click', function() {
				var a = $(this).parents('[announcement-number]');
				a.hide();
				$('[announcement-number="' + (a.attr('announcement-number') * 1 - 1) + '"]').show();
			});
		}).modal('show');
	}
	
	function saveAnnouncements() {
		var seen = [];
		
		$.each(fks.announcements, function(k, v) {
			if(v.seen) { seen.push(k); }
		});
		
		if(seen.length > 0) {
			$.post(fks.handler, {action: 'saveAnnouncements', data: seen})
			.done(function(data) {
				if(fks.debug.ajax) { console.log(data); }
			});
		}
	}
	
	function updateAnnouncementNotifier() {
		if(fks.notifiers.announcement_notifier === undefined) { return false; }
		
		var not_seen = [],
			notifier = fks.notifiers.announcement_notifier,
			badge_class = 'secondary';
			
		$.each(fks.announcements, function(k, v) {
			if(!v.seen) { not_seen.push(k); }
			else {
				var a = notifier.body.find('[a-id="' + k + '"]');
				if(a.length > 0) { a.remove(); }
			}
		});
		
		if(not_seen.length > 0) {
			badge_class = 'info';
		}
		
		notifier.badge.removeClass('fks-badge-secondary fks-badge-info');
		
		notifier.badge.addClass('fks-badge-' + badge_class);
		notifier.badge.html(not_seen.length);
		
		$.each(not_seen, function(k, v) {
			if(notifier.body.find('[a-id="' + v + '"]').length > 0) { return true; }
			var a = fks.announcements[v],
				div = $('<div/>');
				
			div.addClass('notify-announcement').attr('a-id', v).html(
				'<div class="created">' + a.created + '</div><div class="title">' + a.title + '</div>'
			).click(function() {
				// Set all announcements to viewed
				$.each(fks.announcements, function(ak, av) { av.viewed = true; });
				
				a.viewed = false;
				announce(true);
			});
			
			notifier.body.append(div);
		});
	}
	
	// Add Modal Reset
	function addModalReset() {
		$('.modal').each(function() {
			var ele = $(this),
				id = '#' + ele.attr('id');
				
			$(this).on('hidden.bs.modal', function(e) {
				ele.find('.modal-title').html('');
				ele.find('.modal-body').html('');
				ele.find('.modal-footer').html('');
				ele.find('.modal-dialog').removeClass('modal-xs');
				ele.find('.modal-dialog').removeClass('modal-sm');
				ele.find('.modal-dialog').removeClass('modal-md');
				ele.find('.modal-dialog').removeClass('modal-lg');
				ele.find('.modal-dialog').removeClass('modal-xl');
				ele.find('.modal-dialog').removeClass('modal-full');
				ele.find('.modal').removeClass('modal-primary');
				ele.find('.modal').removeClass('modal-info');
				ele.find('.modal').removeClass('modal-success');
				ele.find('.modal').removeClass('modal-warning');
				ele.find('.modal').removeClass('modal-danger');
				ele.find('.modal-body').removeClass('no-padding');
				ele.find('.modal-dialog').hide();
				ele.find('.modal-loader').show();
				
				if($.type(modalOnClose[id]) === 'function') {
					modalOnCloseData[id] ? modalOnClose[id](modalOnCloseData[id]) : modalOnClose[id]();
					modalOnClose[id] = undefined;
					modalOnCloseData[id] = undefined;
				}
			});
		});
	}

/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	fks.initialize = function(callback) {
		if(fks.debug.general) { console.log('FKS -> Initialization Started'); }
		
		fks.readyCallback = callback;
		fks.siteTitle = document.title;
		fks.buildMenus();
		
		addModalReset();
		
		$(document).on('mousedown', function(e) {
			fks.session.actions += 1;
			var open = $('#top_nav .nav-item.open'),
				target = $(e.target);
			
			if(
				target.parents('.conmen-overlay').length > 0
				|| target.hasClass('conmen-overlay')
				|| target.hasClass('modal')
				|| target.hasClass('modal-backdrop')
				|| target.parents('.modal').length > 0
				|| target.attr('data-dismiss') == 'modal'
			) { return; }
			
			open.each(function() {
				var container = target.parents('#top_nav .nav-item.open');
				if(container.length == 0 || container[0] != $(this)[0]) {
					$(this).removeClass('open');
				}
			});
			
			if($('body').hasClass('fullscreen') && $('#top_nav').is(':visible')) {
				var container = target.parents('#top_nav');
				if(target[0] != $('#top_nav')[0] && container[0] != $('#top_nav')[0]) {
					$('#top_nav').hide();
				}
			}
		});
		
		$(document).on('mousemove', function(e) {
			fks.session.actions += 1;
			var target = $(e.target);			
			if(e.pageY <= (10 + $(window).scrollTop()) && $('body').hasClass('fullscreen') && !$('#top_nav').is(':visible')){
				$('#top_nav').show();
			} else if($('body').hasClass('fullscreen') && target.attr('fks-nav') != $('#top_nav').attr('fks-nav') && target.parents('#top_nav').length == 0) {
				$('#top_nav').hide();
			}	
		});
		
		$(document).keypress(function(e) {
			fks.session.actions += 1;
		});
		
		$.fn.putCursorAtEnd = function() {
			return this.each(function() {
				// Cache references
				var $el = $(this),
					el = this;
				// Only focus if input isn't already
				if(!$el.is(":focus")) {
					$el.focus();
				}
				// If this function exists... (IE 9+)
				if(el.setSelectionRange) {
					// Double the length because Opera is inconsistent about whether a carriage return is one character or two.
					var len = $el.val().length * 2;
					// Timeout seems to be required for Blink
					setTimeout(function() {
						el.setSelectionRange(len, len);
					}, 1);

				} else {
					// As a fallback, replace the contents with itself
					// Doesn't work in Chrome, but Chrome supports setSelectionRange
					$el.val($el.val());
				}
				// Scroll to the bottom, in case we're in a tall textarea
				// (Necessary for Firefox and Chrome)
				this.scrollTop = 999999;
			});
		};
		
		$.fn.changeElementType = function(newType) {
			var attrs = {};

			$.each(this[0].attributes, function(idx, attr) {
				attrs[attr.nodeName] = attr.nodeValue;
			});

			this.replaceWith(function() {
				return $("<" + newType + "/>", attrs).append($(this).contents());
			});
		};
		
		$(window).resize(function() {
			var top_nav = $('#top_nav').outerHeight(),
				footer = $('#footer').outerHeight(),
				side_nav = $('#side_nav').outerWidth(),
				winH = $(this).height(),
				winW = $(this).width();
				
			if($('body.fullscreen').length > 0) { top_nav = 0; }
			
			$('#content_container').css('margin-top', top_nav);
			$('#content_container').css('margin-left', side_nav);
			$('#content.slimscroll').slimScroll({
				height: winH - (top_nav + footer),
				size: 7,
				color: '#000000',
				railColor: '#222',
				railOpacity: 0.3,
				railVisible: true,
				touchScrollStep: 50
			});
			
			$('#side_nav').css('height', $('#content_container').height());
			$('body:not(.collapsed) #side_nav .scroll').slimScroll({
				height: $('#content_container').height(),
				size: 3,
				color: '#000000',
				railVisible: false,
				touchScrollStep: 50
			});
			$('body.collapsed #side_nav .scroll').css('overflow', 'visible').slimScroll({destroy: true});
			
			$('.fks-panel.fullscreen').each(function() {
				var $t = $(this),
					headerH = $('.header:first', $t).outerHeight(),
					footerH = $('.footer:first', $t).outerHeight(),
					docH = $(document).outerHeight(),
					offset = 10 + (footerH == undefined ? 0 : 0),
					
				height = docH - (headerH == undefined ? 0 : headerH) - (footerH == undefined ? 0 : footerH) - offset;
				$('.body:first', $t).css('height', height);
				$('.body:first', $t).css('overflow-y', 'auto');
				$('.body:first', $t).css('overflow-x', 'hidden');
			});
		});
		
		$(window).resize();
		
		if($('#top_nav > .notifiers > .nav-list').length > 0) {
			// Add announcement notifier
			fks.addNotifier({
				title: '<i class="fa fa-bell fa-fw"></i> <span class="badge fks-badge-secondary">0</span>',
				id: 'announcement_notifier',
				header: 'Announcements / Alerts',
				actions: '<a href="javascript:void(0);"><i class="fa fa-eye"></i></a>'
			});
			
			// Bind view all button for notifier
			$('a', fks.notifiers.announcement_notifier.header).click(function() {
				// Set all announcements to not viewed
				$.each(fks.announcements, function(k, v) { v.viewed = false; });
				
				// Trigger announcements
				announce(true);
				
				// Set all announcements to viewed
				$.each(fks.announcements, function(k, v) { v.viewed = true; });
			});
		}
		
		// Add timed jobs
		fks.addJob({name: 'fks.keepAlive', when: 60});
		
		// Start job thread
		thread();
	}
	
	fks.addNotifier = function(args) {
	/* EXAMPLE:
		fks.addNotifier({
			title: '<i class="fa fa-cube fa-fw"></i> <span class="badge fks-badge-secondary">99+</span>',
			id: 'cube_id',
			class: 'cube-class',
			width: 400,
			height: 400,
			header: 'Header',
			actions: '<a href="javascript:void(0);" onclick="site.function()" data-toggle="tooltip" title="Function"><i class="fa fa-plus"></i></a>',
			body: 'Body',
			footer: 'Footer'
		});
	*/
		var list = $('#top_nav > .notifiers > .nav-list');
		
		if(args == undefined || list.length == 0) { return false; }
		if(args.id != undefined && $('#' + args.id).length != 0) { return false; }
		
		var li = $('<li/>', {class: 'nav-item'}).html(`
			<a href="javascript:void(0);">
				<span class="notifier-title">
					` + (args.title != undefined ? args.title : '<i class="fa fa-question fa-fw"></i>') + `
				</span>
			</a>
		`);
		
		if(args.id != undefined) { li.attr('id', args.id); }
		if(args.class != undefined) { li.addClass(args.class); }
		
		var ul = $('<ul/>', {class: 'sub-menu notifier-content'}).html(`
			` + (args.header != undefined ? '<div class="notifier-header">' + args.header + (args.actions != undefined ? '<div class="notifier-actions">' + args.actions + '</div>' : '') + '</div>' : '') + `
			<div class="notifier-body">` + (args.body != undefined ? args.body : '') + `</div>
			` + (args.footer != undefined ? '<div class="notifier-footer">' + args.footer + '</div>' : '') + `
		`);
		
		if(args.width != undefined) { ul.width(args.width); }
		if(args.height != undefined) { ul.height(args.height); }
		
		ul.appendTo(li);
		li.appendTo(list);
		
		var parent = ul.parent(),
			link = parent.find('a:first'),
			header = ul.find('.notifier-header'),
			body = ul.find('.notifier-body'),
			footer = ul.find('.notifier-footer');
		
		link.on('click', function() {
			parent.toggleClass('open');
			
			body.slimScroll({
				height: ul.outerHeight() - ((header.length > 0 ? header.outerHeight() + 1 : 1) + (footer.length > 0 ? footer.outerHeight() : 0)),
				size: 5,
				color: '#ffffff',
				railVisible: false
			});
			ul.find('.slimScrollDiv').css('top', (header.length > 0 ? header.outerHeight() : 0));
		});
		
		fks.notifiers[args.id] = {
			ul: ul,
			li: li,
			parent: parent,
			link: link,
			badge: li.find('.badge:first'),
			header: header,
			body: body,
			footer: footer
		};
	}
	
	fks.buildMenus = function() {
		var menus = [];
		$('div[fks-menu]:not([loaded])').each(function() {
			var value = $(this).attr('fks-menu') * 1;
			if($.inArray(menus) == -1) {
				menus.push(value);
			}
		});
		
		if(menus.length == 0) { return false; }
		
		$.post(fks.handler, {action: 'buildMenus', data: menus})
		.done(function(data) {
			if(fks.debug.ajax) { console.log(data); }
			var response = JSON.parse(data);
			switch(response.result) {
				case 'success':
					$('div[fks-menu]').each(function() {
						var menu = $(this),
							left = menu.is('.menu-left'),
							value = menu.attr('fks-menu') * 1,
							autocollapse = menu.is('[autocollapse]');
							
						if(response.menus[value] != undefined) {
							$(this).html(response.menus[value]);
							// Reverse list if left menu
							if(left) {
								var ul = $('ul.nav-list:first', this);
								ul.children().each(function(i,li) { ul.prepend(li); });
							}
							// Bind Submenu
							$('.sub-menu', menu).each(function() {
								var parent = $(this).parent(),
									link = parent.find('a:first');
								
								link.on('click', function() {
									if(autocollapse) {
										var sub = parent.parent().hasClass('sub-menu'),
											open = parent.hasClass('open');
										
										if(sub) {
											$('.open', parent.parent()).removeClass('open');
										} else {
											$('.open', menu).each(function() {
												if(!$(this).parent().hasClass('sub-menu')) {
													$(this).removeClass('open');
												}
											});
										}
										if(!open) { parent.addClass('open'); }
									} else {
										parent.toggleClass('open');
									}
								});
							});
							$(this).attr('loaded', true);
						}
					});
					fks.setActive(fks.location());
					break;
					
				case 'failure':
					fks.toast({type: 'error', msg: response.message});
					break;
					
				default:
					break;
			}
		});
	}
	
	fks.rebuildMenus = function() {
		$('div[fks-menu][loaded]').removeAttr('loaded');
		fks.buildMenus();
	}
	
	fks.setActive = function(loc) {
		var ele = $('[fks-menu] .nav-item [href="#' + loc + '"]'),
			menu = ele.parents('[fks-menu]'),
			parents = ele.parentsUntil('[fks-menu]'),
			nav = ele.parents('[fks-nav]');

		$('[fks-menu] .nav-item.active').removeClass('active');
		$('[fks-menu] .nav-item.selected').removeClass('selected');
		
		$(parents[0]).addClass('selected');

		parents.each(function() {
			var $t = $(this);
			$t.not('.sub-menu').not('.nav-list').addClass('active');
			if(nav.length == 1 && nav.attr('fks-nav') != 'top') {
				$t.has('.sub-menu').not('.nav-list').addClass('open');
			}
		});

		if(menu.is('[autoclose]')) {
			$('.open', menu).removeClass('open');
		}
	}
	
	fks.location = function() {
		var hash = window.location.hash.replace(/#/g, '');
		if(hash.indexOf('?') >= 0) {
			hash = hash.substring(0, hash.indexOf('?'));
		}
		if(hash == '') {
			hash = fks.homePage;
			window.history.replaceState(null , null, '/#' + hash);
		};
		return hash;
	}
	
	fks.sideMenuToggle = function() {
		$('body').toggleClass('collapsed');
		$(window).resize();
	}
	
	fks.fullscreen = function() {
		$('body').toggleClass('fullscreen');
		if($('body').hasClass('fullscreen')) {
		// in fullscreen mode
			$('#top_nav').hide();
		} else {
		// NOT in fullscreen mode
			$('#top_nav').show();
		}
		$(window).resize();
	}
	
	fks.addJob = function(job) {
	/* EXAMPLE:
		fks.addJob({
			name: 'fks.jobName',		// Name of the function to run						(required)
			when: 60,					// Interval to run the job in seconds				(required)
			last: 0,					// Last time the job was run						(optional, default: 0)
			debug: true,				// Enable debugging									(optional, default: true)
			pages: ['home'],			// Pages required for the job to run				(optional, default: false)
			function: false				// Provided function to run	instead of job name		(optional, default: false)
		});
	*/
		jobs[job.name] = {
			when: job.when,
			last: (job.last !== undefined ? job.last : 0),
			debug: (job.debug !== undefined ? job.debug : true),
			page: (job.pages !== undefined ? job.pages : false),
			function: (job.function !== undefined ? job.function : false),
			count: 0
		};
	}
	
	fks.restartJob = function(job) {
		jobs[job].last = 0;
	}
	
	fks.jobExists = function(job) {
		return jobs[job] != undefined;
	}
	
	fks.jobs = function(job = false) {
		if(job) { return jobs[job]; }
		return jobs;
	}
	
	fks.wsServer = function(url) {
		var callbacks = {},
			ws_url = url,
			conn;

		this.bind = function(event_name, callback) {
			callbacks[event_name] = callbacks[event_name] || [];
			callbacks[event_name].push(callback);
			return this;// chainable
		};

		this.send = function(event_name, event_data) {
			this.conn.send(event_data);
			return this;
		};

		this.connect = function() {
			if ( typeof(MozWebSocket) == 'function' )
				this.conn = new MozWebSocket(url);
			else
				this.conn = new WebSocket(url);

			// dispatch to the right handlers
			this.conn.onmessage = function(evt) {
				dispatch('message', evt.data);
			};

			this.conn.onclose = function() { dispatch('close',null); }
			this.conn.onopen = function() { dispatch('open',null); }
			
			this.conn.onerror = function(evt) {
				dispatch('error', evt);
			};
		};

		this.disconnect = function() {
			this.conn.close();
		};

		var dispatch = function(event_name, message) {
			var chain = callbacks[event_name];
			if(typeof chain == 'undefined') return; // no callbacks for this event
			for(var i = 0; i < chain.length; i++) {
				chain[i]( message )
			}
		}
	}
	
	fks.wsServerSetup = function(options) {
		if(fks.debug.webSocks) { console.log('WebSocks -> Connecting...'); }
			
		fks.ws = options;
		fks.ws.server = new fks.wsServer(fks.ws.url);
		
		fks.ws.server.bind('open', function() {
			if(fks.ws.callbacks.open != null) {
				fks.executeFunctionByName(fks.ws.callbacks.open, window);
			}
		});

		fks.ws.server.bind('close', function(data) {
			if(fks.ws.callbacks.open != null) {
				fks.executeFunctionByName(fks.ws.callbacks.close, window, data);
			}
		});

		fks.ws.server.bind('message', function(msg) {
			if(fks.ws.callbacks.message != null) {
				fks.executeFunctionByName(fks.ws.callbacks.message, window, msg);
			}
		});
		
		fks.ws.server.bind('error', function(msg) {
			if(fks.ws.callbacks.message != null) {
				fks.executeFunctionByName(fks.ws.callbacks.error, window, msg);
			}
		});

		fks.ws.server.connect();
	}
	
	fks.send = function(datas) {
		if(fks.ws.server != null && fks.ws.server.conn.readyState == 1) {
			fks.ws.server.send('message', JSON.stringify(datas));
		}
	}
	
	fks.encryptUser = function() {
		var out = false
		$.ajax({
			type: 'POST',
			url: fks.handler,
			data: { action: 'encryptUser' },
			async: false
		}).done(function(data) {
			if(fks.debug.ajax) { console.log(data); }
			var response = JSON.parse(data);
			switch(response.result) {
				case 'success':
					out = response.message;
					break;
			}
		});
		return out;
	}
	
	fks.toast = function(options) {
		if(!options) { fks.toastr.error('Missing options.'); }
		var o = $.extend(true, {}, fks.toastr_options);
		$.each(options, function(k, v) { o[k] = v; });
		
		if(options.remove) {
			$('.toast.' + options.remove).remove();
			o.toastClass = o.toastClass + ' ' + options.remove;
		}
		
		return fks.toastr[o.type](o.msg, o.header, o);
	}
	
	fks.burntToast = function(options) {
		options.type = (options.type != undefined ? options.type : 'error');
		options.timeOut = null;
		options.extendedTimeOut = null;
		options.tapToDismiss = false;
		options.closeButton = (options.closeButton != undefined ? options.closeButton : true);
		fks.toast(options);
	}
	
	fks.superSerialize = function(form, attr = false) {
		var out = {};
		
		$.each($(form).serializeArray(), function() {
			var find = (attr ? $('[name="' + this.name + '"]', form).attr(attr) : this.name);
			if(find === undefined) { return; }
			if(out[find]) {
				out[find] += ',' + this.value;
			} else {
				out[find] = this.value;
			}
		});
		
		// Add summernotes to out
		$(form).find('.fks-summernote').each(function() {
			var $t = $(this);
			if($t.attr('name') != undefined) {
				out[$t.attr('name')] = encodeURI($t.summernote('code').replace(/\+/g, '&plus;'));
			} else if($t.attr('id') != undefined) {
				out[$t.attr('id')] = encodeURI($t.summernote('code').replace(/\+/g, '&plus;'));
			}
		});
		
		return out;
	}
	
	fks.sortObj = function(obj, key){
	/*
		obj = the object array to sort
		key = the key to sort by
	*/
		var arr = [],
			added = false;
			
		$.each(obj, function(ok, ov){
			added = false;
			$.each(arr, function(ak, av){
				if(ov[key] < av[key]){
					arr.splice(ak, 0, ov);
					added = true;
					return false;
				}
			});
			if(!added){
				arr.push(ov);
				added = true;
			}
		});
		
		return arr;
	}
	
	fks.now = function() {
		return Math.floor(((new Date).getTime()) / 1000);
	}
	
	fks.nowDateTime = function() {
		return new Date().toISOString().slice(0, 19).replace('T', ' ');
	}
	
	fks.showModal = function(args) {
		if(!args){ return false; }
		if(!args.modal){ args.modal = '#fks_modal'; }
		$(args.modal).modal('hide');
		if(!args.title){ args.title = 'No Title'; }
		if(!args.body){ args.body = 'No Body'; }
		if(!args.padding && args.padding != false){ args.padding = true; }
		if(!args.footer){ args.footer = ''; }
		if(!args.size){ args.size = 'md'; }
		if(!args.type){ args.type = ''; }
		if(args.type != ''){ $(args.modal).addClass('modal-' + args.type); }
		
		if(args.footer == ''){
			args.footer = ''
				+ '<button class="btn fks-btn-secondary btn-sm" data-dismiss="modal">Close</button>';
		}
		
		if($.type(args.onClose) === 'function'){
			modalOnClose[args.modal] = args.onClose;
		}
		
		$(args.modal + ' .modal-title').html(args.title);
		$(args.modal + ' .modal-body').html(args.body);
		$(args.modal + ' .modal-footer').html(args.footer);
		$(args.modal + ' .modal-dialog').addClass('modal-' + args.size);
		if(!args.padding){ $(args.modal + ' .modal-body').addClass('no-padding'); }
		$(args.modal).modal('show');
		$(args.modal + ' .modal-loader').hide();
		$(args.modal + ' .modal-dialog').show();
		
		if($.type(args.onOpen) === 'function'){
			args.onOpen();
		}
	}
	
	fks.editModal = function(args, auth_code) {
		var send = {};
		// Check for args
		if(!args.handler){
			args.handler = fks.handler;
		}
		if(args.wait){
			send.wait = args.wait;
		}
		if(args.src){
			send.src = args.src;
		}
		if(!args.action){
			fks.toast({type: 'error', msg: 'editModal - missing action!'});
			return false;
		}
		if(!args.modal){ args.modal = '#fks_modal'; }
		
		send.action = args.action;
		
		if(args.data) {
			// deprecated - 10/23/2018
			console.warn('[Deprecation] fks.editModal: args.data deprecated, use args.action_data instead');
			send.data = args.data;
		}
		
		if(args.action_data){ send.data = args.action_data; }
		
		if(auth_code != undefined){
			if(args.data == undefined && args.action_data == undefined){ send.data = {}; }
			send.data.form_auth = auth_code;
		}
		
		// Check for debug and set default if not defined
		if(args.debug == undefined) { args.debug = true; }
		
		// Open modal loading bar
		$(args.modal + ' .modal-loader').css('margin', ($(args.modal).outerHeight() / 2) - 10 + 'px auto');
		$(args.modal).modal('show');
		
		// Load data
		$.post(args.handler, send)
		.done(function(data){
			if(fks.debug.ajax && args.debug) { console.log(data); }
			try { var response = JSON.parse(data); } catch(e) { fks.toast({type: 'error', msg: 'Server Error!'}); $(args.modal).modal('hide'); return; }
			if(fks.debug.ajax && args.debug) { console.log(response); }
			switch(response.result){
				case 'success':
					var parts = response.parts;
						parts.no_padding = parts.no_padding ? true : (args.no_padding ? true : false);
						parts.size = parts.size ? parts.size : (args.size ? args.size : 'md');
						parts.title = parts.title ? parts.title : (args.title ? args.title : 'No Title');
						parts.body_before = parts.body_before ? parts.body_before : (args.body_before ? args.body_before : '');
						parts.body = parts.body ? parts.body : (args.body ? args.body : 'No Body');
						parts.body_after = parts.body_after ? parts.body_after : (args.body_after ? args.body_after : '');
						parts.footer = parts.footer ? parts.footer : (args.footer ? args.footer : '');
						if(parts.footer == ''){
							parts.footer = ''
								+ '<button class="btn fks-btn-danger btn-sm" data-dismiss="modal">Cancel</button>'
								+ '<button class="btn fks-btn-primary btn-sm" onclick="$(\'' + args.modal + ' .modal-body form\').submit();">Save Changes</button>';
						}
						
					// Adjust modal visuals
					if(parts.no_padding){ $(args.modal + ' .modal-body').addClass('no-padding'); }
					$('.modal-dialog', args.modal).addClass('modal-' + parts.size);
					
					// Populate modal parts
					if($.isArray(parts.title)) {
						var _title = '',
							_active = (parts.active_tab ? parts.active_tab : 0);
						$.each(parts.title, function(k, v) {
							_title += '<li class="nav-item"><a class="nav-link' + (k == _active ? ' active' : '') + '" data-toggle="tab" href="#fks_modal_tab_' + k + '" role="tab" draggable="false">' + v + '</a></li>';
						});
						$('.modal-title', args.modal).html('<ul class="nav nav-tabs">' + _title + '</ul>');
					} else {
						$('.modal-title', args.modal).html(parts.title);
					}
					
					if($.isArray(parts.body)) {
						var _body = '',
							_active = (parts.active_tab ? parts.active_tab : 0);
						$.each(parts.body, function(k, v) {
							_body += '<div class="tab-pane' + (k == _active ? ' active' : '') + '" role="tabpanel" id="fks_modal_tab_' + k +'">' + v + '</div>';
						});
						$('.modal-body', args.modal).html(parts.body_before + '<div class="tab-content">' + _body + '</div>' + parts.body_after);
					} else {
						$('.modal-body', args.modal).html(parts.body_before + parts.body + parts.body_after);
					}

					$('.modal-footer', args.modal).html(parts.footer);
					
					$('.modal-loader', args.modal).hide();
					$('.modal-dialog', args.modal).show();
					
					fks.submitForm();
					fks.resetForm();
					
					if(args.callbacks){					
						if($.type(args.callbacks.onOpen) === 'function'){
							if(parts.callbackData && parts.callbackData.onOpen){
								args.callbacks.onOpen(parts.callbackData.onOpen);
							}else{
								args.callbacks.onOpen();
							}
						}
						if($.type(args.callbacks.onClose) === 'function'){
							if(parts.callbackData && parts.callbackData.onClose){
								modalOnCloseData[args.modal] = parts.callbackData.onClose;
							}
							modalOnClose[args.modal] = args.callbacks.onClose;
						}
					}
					
					/*
					args.focus = false;			// Do not focus anything
					args.focus = true;			// Focus first VISIBLE ENABLED EDITABLE input	(default if undefined)
					
					args.focus = {
						container: null,		// The container element inside the modal		(optional, default: args.modal)
						hidden: true,			// Allow hidden									(optional, default: false)
						disabled: true,			// Allow disabled								(optional, default: false)
						readonly: true			// Allow readonly								(optional, default: false)
					};
					*/
					
					// Set focus to first input on modal
					if(args.focus != false) {
						// Default container to modal
						var container = args.modal;
						
						// Set default if undefined
						if(args.focus == undefined) { args.focus = true; }
						
						// Set container if not undefined and has a value
						if(args.focus.container != undefined && args.focus.container) {
							container = $(args.focus.container, args.modal);
						}
						
						// Get all inputs on the modal
						var inputs = $('input, select, textarea', args.modal);
						
						// Filter out hidden inputs
						if(args.focus.hidden == undefined || args.focus.hidden == false) { inputs = inputs.not(':hidden'); }
						
						// Filter out disabled inputs
						if(args.focus.disabled == undefined || args.focus.disabled == false) { inputs = inputs.not('[disabled]'); }
						
						// Filter out readonly inputs
						if(args.focus.readonly == undefined || args.focus.readonly == false) { inputs = inputs.not('[readonly]'); }
						
						// Focus the input
						inputs.first().focus();
					}
					break;
					
				case 'auth':
					$(args.modal).modal('hide');
					fks.requireAuth();
					$('#require_authentication_form').on('submit', function(){
						var auth_form = fks.superSerialize(this);
						fks.editModal(args, auth_form.form_verification_code);
						
						$(this).parents('.modal:first').modal('hide');
					});
					break;
					
				case 'failure':
					$(args.modal).modal('hide');
					// Display failure toast
					fks.toast({
						type: 'error',
						header: (response.title ? response.title : ''),
						msg: response.message,
						timeOut: null,
						extendedTimeOut: null,
						tapToDismiss: false,
						closeButton: true
					});
					break;
			}
		})
		.fail(function(data){
			$(args.modal).modal('hide');
			fks.toast({type: 'error', msg: 'Server error.'});
		})
		.always(function(data){
			
		});
	}
	
	fks.requireAuth = function() {
		var box = fks.bootbox.dialog({
			closeButton: false,
			animate: false,
			show: false,
			size: 'small',
			className: 'require-auth',
			title: '<i class="fa fa-shield fa-fw"></i> Authentication',
			message: `<form class="form" role="form" action="javascript:void(0);" id="require_authentication_form">
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group" style="margin-bottom:0px;">
							<label for="verification_code">Verification Code</label>
							<input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="" maxlength="6">
						</div>
					</div>
				</div>
			</form>`,
			buttons: {
				ok: {
					label: '<i class="fa fa-check fa-fw"></i> Verify',
					className: 'fks-btn-success btn-sm',
					callback: function() {
						$('form', this).submit();
						return false;
					}
				},
				cancel: {
					label: '<i class="fa fa-times fa-fw"></i> Cancel',
					className: 'fks-btn-secondary btn-sm',
					callback: function() {
						
					}
				}
			}
		}).on('shown.bs.modal', function() {
			$('h4.modal-title', this).changeElementType('h5');
			$('form input:first', this).focus();
		}).modal('show');
	}
	
	fks.logout = function(type, ref) {
		Pace.track(function() {
			$.post(fks.handler, {action: 'logout', data: type})
			.done(function(data) {
				if(fks.debug.ajax) { console.log(data); }
				var response = JSON.parse(data);
				switch(response.result) {
					case 'success':
						if(fks.ws.server != null && fks.ws.server.conn.readyState == 1) {
							fks.executeFunctionByName(fks.ws.callbacks.logout, window, type, ref);
						} else {
							window.location.replace('/login.php' + (ref != undefined ? '#' + ref : ''));
						}
						break;
						
					default:
						break;
				}
			})
			.always(function(data) {
			});
		});
	}
	
	fks.keepAlive = function() {
		// Check for keep alive pages
		if(keepAlivePages.indexOf(fks.location()) > -1) {
			// Add 1 action to keep the session alive
			fks.session.actions += 1;
			if(fks.debug.keepAlive) { console.log('Keep Alive (' + fks.location() + ')'); }
		} else {
			if(fks.debug.keepAlive) { console.log('Keep Alive (' + fks.session.actions + ' action' + (fks.session.actions == 1 ? '' : 's') + ')'); }
		}
		Pace.ignore(function() {
			$.ajax({
				type: 'POST',
				url: fks.handler,
				data: { action: 'keepAlive', data: fks.session.actions },
				async: fks.ready
			}).done(function(data) {
				if(fks.debug.keepAlive && fks.debug.ajax) { console.log(data); }
				var response = JSON.parse(data);
				switch(response.result) {
					case 'success':
						// Check for inactivity modal
						if($('.modal.warning-inactive').length > 0 && fks.session.last_action == response.last_action) {
							// Logout if still inactive
							fks.logout('LOGOUT_INACTIVE', fks.location());
							return;
						} else {
							// Hide modal and continue if not inactive
							$('.modal.warning-inactive').modal('hide');
						}
					
						// Set session data
						fks.session.last_action = response.last_action;
						fks.session.timeout = response.timeout;
						fks.session.guest = response.guest;
						fks.session.access = response.access;
						
						// Display announcements
						displayAnnouncements(response.announcements);
						
						// keepAliveCallback
						keepAliveCallback();
						
						// Set fks.ready if not already set
						if(!fks.ready) {
							fks.ready = true;
							if(fks.debug.general) { console.log('FKS -> Initialization Completed'); }
							fks.homePage = (response.site_home_page ? response.site_home_page : 'home');
							fks.readyCallback();
						}
						break;
						
					case 'logout':
						fks.burntToast({header: 'Logging out...', msg: response.message});
						setTimeout(function() {
							window.location.reload();
						}, 500);
						break;
						
					default:
						if(fks.debug.keepAlive) { console.log('Keep Alive Error'); console.log(response); }
						break;
				}
			});
		});
		fks.session.actions = 0;
	}
	
	fks.drawTable = function(table, data) {
		var tablePage = (table.state.save()).page();
		table.clear();
		
		$.each(data, function(k, v) {
			table.row.add(v);
		});

		table.draw();
		table.page(tablePage).draw(false);
	}
	
	fks.columnDropdown = function(table, dropdown, icheck_options){
		dropdown.html('');
		var columns = table.context[0].aoColumns;
		$.each(columns, function(k, v){
			if(v.toggleable != undefined && !v.toggleable){ return true; }
			var li = $('<li>'),
				label = $('<label>')
					.html('<span>' + v.title + '</span>'),
				input = $('<input>')
					.attr('type', 'checkbox')
					.attr('checked', v.bVisible)
					.attr('data-column', k);

			input.prependTo(label);
			label.appendTo(li);
			li.appendTo(dropdown);
		});
		
		// Enabled Check Boxes
		if(!icheck_options){
			icheck_options = {
				checkboxClass: 'icheckbox_minimal'
			}
		}
		$('input', dropdown).iCheck(icheck_options);
		
		// Check Box Column Toggler
		$('input', dropdown).on('ifToggled', function(e){
			table.column(parseInt($(this).attr('data-column'))).visible($(this).is(':checked'));
		});
		
		$('.iCheck-helper:first', dropdown).click();
		$('.iCheck-helper:first', dropdown).click();
	}
	
	fks.updateTable = function(table, data, check = 'id') {
		var tablePage = (table.state.save()).page(),
			changes = {added: 0, removed: 0, updated: 0};
		
		$.each(table.rows().data(), function(k, v) {
			var row = table.row(k),
				id = v[check];
			if(data[id] != undefined) {
				if(JSON.stringify(row.data()) != JSON.stringify(data[id])) {
					row.data(data[id]);
					changes.updated++;
				}
				delete(data[id]);
			} else {
				$(row.node()).attr('flag', 'remove');
				changes.removed++;
			}
		});
		
		table.rows('[flag="remove"]').remove();
		
		$.each(data, function(k, v) {
			table.row.add(v);
			changes.added++;
		});

		table.draw();
		table.page(tablePage).draw(false);
		return changes;
	}
	
	fks.multiSelect = function(ele, args) {
		ele = $(ele);
		
		var options = {
			selectableHeader: false,
			selectionHeader: false,
			selectableFooter: false,
			selectionFooter: false,
			selectableOptgroup: true,
			optGroups: true,
			height: 150,
			afterInit: false,
			afterSelect: false,
			afterDeselect: false
		};
		
		var makeHeader = function(text) {
			return `
				<div class="form-group">
					<label class="form-control-label">` + text + `</label>
					<div>
						<input type="text" class="form-control form-control-sm" placeholder="Search...">
					</div>
				</div>
			`;
		}
		
		if(args.selectableHeader != undefined) {
			options.selectableHeader = (args.selectableHeader.style != undefined && args.selectableHeader.style == false) ? args.selectableHeader.text : makeHeader(args.selectableHeader.text);
		}
		
		if(args.selectionHeader != undefined) {
			options.selectionHeader = (args.selectionHeader.style != undefined && args.selectionHeader.style == false) ? args.selectionHeader.text : makeHeader(args.selectionHeader.text);
		}
		
		if(args.selectableFooter != undefined) { options.selectableFooter = args.selectableFooter; }
		if(args.selectionFooter != undefined) { options.selectionFooter = args.selectionFooter; }
		if(args.selectableOptgroup != undefined) { options.selectableOptgroup = args.selectableOptgroup; }
		if(args.optGroups != undefined) { options.optGroups = args.optGroups; }
		if(args.height != undefined) { options.height = args.height; }
		if(args.afterInit != undefined) { options.afterInit = args.afterInit; }
		if(args.afterSelect != undefined) { options.afterSelect = args.afterSelect; }
		if(args.afterDeselect != undefined) { options.afterDeselect = args.afterDeselect; }

		ele.multiSelect({
			selectableHeader: options.selectableHeader,
			selectionHeader: options.selectionHeader,
			selectableFooter: options.selectableFooter,
			selectionFooter: options.selectionFooter,
			selectableOptgroup: options.selectableOptgroup,
			afterInit: function() {
				var that = this;
				$('.ms-list', that.$container).height(args.height);
				that.sAble = that.$selectableUl.prev().find('input').on('keydown keyup', function(e){
					if(e.which == 40) {
						that.$selectableUl.focus();
						return false;
					}
					fks.searchMultiSelect({
						ms: that,
						find: $(this).val(),
						optgroups: options.optGroups,
						searchSelection: false
					});
				});
				
				that.sTion = that.$selectionUl.prev().find('input').on('keydown keyup', function(e){
					if(e.which == 40) {
						that.$selectionUl.focus();
						return false;
					}
					fks.searchMultiSelect({
						ms: that,
						find: $(this).val(),
						optgroups: options.optGroups,
						searchSelectable: false
					});
				});
				
				that.sAble.keydown();
				that.sTion.keydown();
				
				that.$selectableUl.next().on('click', function(){
					$('.ms-elem-selectable:not(.ms-selected):not(:hidden)', that.$selectableUl[0]).each(function(){
						$(this).click();
					});
				});
				
				that.$selectionUl.next().on('click', function(){
					$('.ms-elem-selection.ms-selected:not(:hidden)', that.$selectionUl[0]).each(function(){
						$(this).click();
					});
				});
				
				if($.type(options.afterInit) === 'function') {
					options.afterInit();
				}
			},
			afterSelect: function() {
				this.sAble.keydown();
				this.sTion.keydown();
				if($.type(options.afterSelect) === 'function') {
					options.afterSelect();
				}
			},
			afterDeselect: function() {
				this.sAble.keydown();
				this.sTion.keydown();
				if($.type(options.afterDeselect) === 'function') {
					options.afterDeselect();
				}
			}
		});
	}
	
	fks.searchMultiSelect = function(args) {
		if(args.ms == undefined) { return false; }
		if(args.optgroups == undefined) { args.optgroups = false; }
		if(args.find == undefined) { args.find = ''; }
		if(args.searchSelectable == undefined) { args.searchSelectable = true; }
		if(args.searchSelection == undefined) { args.searchSelection = true; }
		
		args.find = args.find.toLowerCase();
		
		sAble = args.ms.$selectableUl[0];
		sTion = args.ms.$selectionUl[0];
		
		if(args.optgroups) {
			if(args.searchSelectable) {
				$('.ms-optgroup-container', sAble).each(function(){
					var group = $('.ms-optgroup', this),
						label = group.find('.ms-optgroup-label'),
						children = group.find('.ms-elem-selectable:not(.ms-selected)');
					
					if(children.length > 0) {
						var child_visible = false;
						children.each(function(){
							if($(this).text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
								$(this).show();
								child_visible = true;
							} else {
								$(this).hide();
							}
						});
						if(child_visible || label.text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
							label.show();
							if(!child_visible) {
								children.each(function(){
									$(this).show();
								});
							}
						} else {
							label.hide();
						}
					}
				});
			}
			
			if(args.searchSelection) {
				$('.ms-optgroup-container', sTion).each(function(){
					var group = $('.ms-optgroup', this),
						label = group.find('.ms-optgroup-label'),
						children = group.find('.ms-elem-selection.ms-selected');
					
					if(children.length > 0) {
						var child_visible = false;
						children.each(function(){
							if($(this).text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
								$(this).show();
								child_visible = true;
							} else {
								$(this).hide();
							}
						});
						if(child_visible || label.text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
							label.show();
							if(!child_visible) {
								children.each(function(){
									$(this).show();
								});
							}
						} else {
							label.hide();
						}
					}
				});
			}
		} else {
			if(args.searchSelectable) {
				$('.ms-elem-selectable:not(.ms-selected)', sAble).each(function(){
					if($(this).text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			}
			
			if(args.searchSelection) {
				$('.ms-elem-selection.ms-selected', sTion).each(function(){
					if($(this).text().toLowerCase().indexOf(args.find) > -1 || args.find == '') {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			}
		}
	}
	
	fks.fksActions = function(actions){
		$.each(actions, function(k,v){
			window['fks'][v]();
		});
	}
	
	fks.onClick = function(functionList) {
		$('[fks-action*="onClick:"]').each(function(){
			var t = this,
				ele = $(t),
				func = ele.attr('fks-action').replace('onClick:', ''),
				values = {};
			
			ele.removeAttr('fks-action');
			
			$.each(functionList, function(i, f) {
				if(f.name == func) {
					$.each(t.attributes, function() {
						if(this.specified) {
							if(this.name.indexOf('fks-values') >= 0) {
								values = this.value.toString().split(',');
								return false;
							} else if(this.name.indexOf('fks-value-') >= 0) {
								values[this.name.replace('fks-value-', '')] = this.value;
							} else if(this.name.indexOf('fks-value') >= 0) {
								values = this.value;
								return false;
							}
						}
					});
				
					ele.on('click', function() {
						f(values, this);
					});
					
					return false;
				}
			});		
		});
	}
	
	fks.submitForm = function() {
		$('[fks-action="submitForm"]').each(function() {
			var ele = $(this).removeAttr('fks-action'),
				target = ele.attr('fks-target'),
				form = target != undefined && target != false ? $(target) : ele.parents('form:first');
			
			ele.on('click', function() {
				form.submit();
			});
		});
	}
	
	fks.resetForm = function() {
		$('[fks-action="resetForm"]').each(function() {
			var ele = $(this).removeAttr('fks-action'),
				target = ele.attr('fks-target'),
				form = target != undefined && target != false ? $(target) : ele.parents('form:first');
			
			ele.on('click', function() {
				form.trigger('reset');
				form.trigger('reset:after');
			});
		});
	}
	
	fks.panelToggle = function() {
		$('[fks-action="panelToggle"]').each(function() {
			var ele = $(this).removeAttr('fks-action'),
				target = ele.attr('fks-target'),
				panel = target != undefined && target != false ? $(target) : ele.parents('.fks-panel:first');
			
			ele.on('click', function() {
				panel.toggleClass('collapsed');
				if(panel.hasClass('collapsed')) {
					ele.removeClass('fa-rotate-180');
				} else {
					ele.addClass('fa-rotate-180');
				}
			});
			
			if(panel.hasClass('collapsed')) {
				ele.removeClass('fa-rotate-180');
			} else {
				ele.addClass('fa-rotate-180');
			}
		});
	}
	
	fks.panelClose = function() {
		$('[fks-action="panelClose"]').each(function() {
			var ele = $(this).removeAttr('fks-action'),
				target = ele.attr('fks-target'),
				panel = target != undefined && target != false ? $(target) : ele.parents('.fks-panel:first');
			
			ele.on('click', function() {
				panel.remove();
			});
		});
	}
	
	fks.panelFullscreen = function() {
		$('[fks-action="panelFullscreen"]').each(function() {
			var ele = $(this).removeAttr('fks-action'),
				target = ele.attr('fks-target'),
				panel = target != undefined && target != false ? $(target) : ele.parents('.fks-panel:first');
			
			ele.on('click', function() {
				panel.toggleClass('fullscreen');
				$('body').toggleClass('has-panel-fullscreen');
				if(!panel.hasClass('fullscreen')) {
					$('.body', panel).css('height', 'inherit');
					$('.body', panel).css('overflow', 'initial');
				}
				$(window).resize();
			});
			
			$('.body', panel).on('mousewheel', function(e) {
				if(!panel.hasClass('fullscreen')) { return; }
				var event = e.originalEvent,
					d = event.wheelDelta || -event.detail;

				this.scrollTop += (d < 0 ? 1 : -1) * 30;
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
			});
			
			$(window).resize();
		});
	}
	
	fks.summernote = function(ele, options) {
		$(ele).summernote(options);
		$(ele).addClass('fks-summernote');
		/*
		var editor = $(ele).next('.note-editor');
		
		// fix floating popovers
		$('.note-popover').css({'display': 'none'});
		
		// fix header class
		$('div.note-toolbar.panel-heading', editor)
			.addClass('card-header');
			
		// fix button class in editor
		$('button.btn-default', editor)
			.removeClass('btn-default')
			.addClass('btn-secondary');
		
		// fix button class in popover
		$('.note-popover button.btn-default')
			.removeClass('btn-default')
			.addClass('btn-secondary');
		
		// fix color dropdown
		$('div.note-btn-group.note-color .btn-group', editor)
			.css('display', 'inline-block');
		
		// fix modals
		$('.modal', editor).each(function() {
			var header = $(this).find('.modal-header'),
				body = $(this).find('.modal-body'),
				footer = $(this).find('.modal-footer');
				
			$('button.close', header).appendTo(header);
			$('h4.modal-title', header).changeElementType('h5');
			
			$('input.form-control', body).addClass('form-control-sm');
			$('input[type="checkbox"]', body).iCheck({checkboxClass: 'icheckbox_minimal'});
			if(body.css('overflow') == 'scroll') {
				body.css('overflow-x', 'auto');
			}
			
			$('button.btn', footer).addClass('btn-sm');
		});
		
		// fix/remove dropdown carets
		$('span.note-icon-caret', editor).remove();
		*/
	}
	
	fks.formValidate = function(form, validation) {
		var form = $(form);
		
		if(validation == undefined) {
			$('.fks-text-danger', form).each(function() { $(this).removeClass('fks-text-danger'); });
			$('.fks-border-danger', form).each(function() { $(this).removeClass('fks-border-danger'); });
			$('.form-control-feedback', form).each(function() { $(this).html('').hide(); });
		} else {
			$.each(validation, function(k, v) {
				$('[name="' + k + '"]', form).addClass('fks-border-danger');
				$('[name="' + k + '"]', form).parents('.form-group').addClass('fks-text-danger');
				$('[name="' + k + '"]', form).parents('.form-group').find('.form-control-feedback').html(v).show();
			});
		}
	}
	
	fks.pageAccess = function(result, page) {
		// Check access to page
		if(result.indexOf('<span style="display: none;">Access Denied</span>') == 0) { document.title = fks.siteTitle + ' : 403'; return false; }
		if(result.indexOf('<span style="display: none;">Require Login</span>') == 0) { window.location = '/login.php#' + fks.currentPage; return false; }
		
		// Do announcements
		announce();
		
		// Hide access elements
		fks.checkAccessElements(page.access);
		
		// Load templates
		fks.loadTemplates();
		
		// Bind changelog buttons
		$('[fks-action="loadPageChangelog"]').each(function() {
			var $t = $(this);
			$t.removeAttr('fks-action');
			$t.click(function() { fks.loadPageChangelog(fks.currentPageLabel); });
			
			// Set tooltip
			//fks.tooltip({ele: $t, title: 'View Changelog', pos: 'bottom'});
		});
		
		// Check for keep_alive option
		if(page.keep_alive && keepAlivePages.indexOf(page.hash.substring(1)) < 0) {
			// Add page to keep alive pages array
			keepAlivePages.push(page.hash.substring(1));
			if(fks.debug.keepAlive) { console.log('Keep Alive -> Page Added (' + page.hash.substring(1) + ')'); }
		}

		return true;
	}
	
	fks.checkAccess = function(label, setLabel = true) {
		if(setLabel) { fks.currentPageLabel = label; }
		if(fks.session.access[label] == undefined) { return 0; }
		return parseInt(fks.session.access[label]);
	}
	
	fks.checkAccessElements = function(access) {
		$('[fks-access]').each(function() {
			if(access < parseInt($(this).attr('fks-access'))) {
				$(this).remove();
			}
		});
	}
	
	fks.executeFunctionByName = function(functionName, context) {
		var args = [].slice.call(arguments).splice(2),
			namespaces = functionName.split('.'),
			func = namespaces.pop();
		for(var i = 0; i < namespaces.length; i++) { context = context[namespaces[i]]; }
		return context[func].apply(context, args);
	}
	
	fks.sec2time = function(s) {
	/* EXAMPLE:
		fks.sec2time(90061); // outputs: '1d 01h 01m 01s'
	*/
		if(!$.isNumeric(s)) { return s; }
		
		var out = '',
			day = parseInt(s / 86400),
			hour = parseInt(s / 3600) % 24,
			min = parseInt(s / 60) % 60,
			sec = s % 60;

		if(day > 0) { out += day + 'd ' }
		if(hour > 0 || day > 0) { out += (hour < 10 && (day > 0) ? '0' : '') + hour + 'h ' }
		if(min > 0 || hour > 0 || day > 0) { out += (min < 10 && (hour > 0 || day > 0) ? '0' : '') + min + 'm ' }
		if(sec <= 0) { sec = 0; }
		
		out += (sec < 10 && (min > 0 || hour > 0 || day > 0) ? '0' : '') + sec + 's';
		
		return out;
	}
	
	fks.time2sec = function(t) {
	/* EXAMPLE:
		fks.time2sec('1d 01h 01m 01s'); // outputs: 90061
	*/
		var parts = t.split(' '),
			s = 0;
			
		$.each(parts, function(k, v) {
			if(v.indexOf('d') > -1) { s += parseInt(v) * 60 * 60 * 24; }
			if(v.indexOf('h') > -1) { s += parseInt(v) * 60 * 60; }
			if(v.indexOf('m') > -1) { s += parseInt(v) * 60; }
			if(v.indexOf('s') > -1) { s += parseInt(v); }
		});
		
		return s;
	}
	
	fks.tabdrop = function(options) {
	/* EXAMPLE:
		fks.tabdrop({
			ele: $('#element'),		// The element to check overflow
			pos: 'last',			// What to overflow (first / last)
			text: 'More',			// The text of the overflow dropdown
			rebind: true,			// Automatically rebind on tab create/remove
			callback: function		// The callback function on change (sends container as parameter)
		});
	*/
		var defaults = {
			ele: null,
			pos: 'last',
			text: 'More',
			rebind: true,
			callback: null
		};
		if(options != undefined) { $.each(options, function(k, v) { defaults[k] = v; }); }
		options = defaults;
		
		var tabs = '';
		$('a', options.ele).each(function() {
			$t = $(this).clone();
			$t.removeClass('nav-link');
			$t.addClass('dropdown-item hidden');
			$t.css({display: 'none'});
			tabs += $t[0].outerHTML;
		});
	
		var cont = $('<li/>', {class: 'nav-item dropdown fks-tabdrop', style: 'display: none;' + (options.pos == 'last' ? ' position:absolute; right: 0px;' : '')}).html(`
			<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
				` + options.text + `
			</a>
			<div class="dropdown-menu` + (options.pos == 'last' ? ' dropdown-menu-right' : '') + `">
				` + tabs + `
			</div>
		`);
		
		if(options.pos == 'last') {
			cont.appendTo(options.ele);
		} else {
			cont.prependTo(options.ele);
		}
		
		options.ele
			.data('cont', cont)
			.addClass('no-wrap');
		
		function setActive(ele) {
			var active = (ele != undefined ? ele.attr('href') : $('a.nav-link.active', options.ele).attr('href'));
			$('a.active', options.ele).removeClass('active');
			$('a[href="' + active + '"]', options.ele).addClass('active');
			if($('a.nav-link[href="' + active + '"]:not(:visible)', options.ele).length > 0) { $('a.nav-link', options.ele.data().cont).addClass('active'); }
		}
		
		$('a[role="tab"]', options.ele).unbind('click').click(function(e) {
			var $t = $(this);
			if($t.hasClass('disabled')) { return false; }
			$t.tab('show'); // show tab content before messing with active tabs (required)
			setActive($t);
		});
		
		$('a[role="tab"]', options.ele).on('shown.bs.tab', function(e) {
			setTimeout(function() { $(window).resize(); }, 0);
		});
		
		if(options.ele.attr('fks-tabdrop') == undefined) {
			options.ele.bind('DOMNodeInserted DOMNodeRemoved', function(e) {
				if(options.ele.attr('fks-tabdrop') == 'unbound') { return false; }
				if($(e.target).hasClass('sortable-chosen')) { return false; }
				if(options.rebind && $(e.target).hasClass('nav-item')) {
				// tab added or removed
					options.ele.trigger('tabdrop-rebind');
				}
			});
			
			options.ele.bind('tabdrop-rebind', function(e) {
				fks.tabdropUnbind(options.ele);
				fks.tabdrop(options);
			});
			
			$(window).resize(function() {
				if(options.ele.closest('body').length < 1 || options.ele.attr('fks-tabdrop') == 'unbound') { return false; }
				if(!options.ele.is(':visible')) { return false; }
				
				var resize = false,
					callback = false,
					width = 0,
					max_width = options.ele.width(),
					panel_tabs = options.ele.parents('.fks-panel.tabs:first'),
					header = options.ele.parents('.header:first'),
					title = options.ele.parents('.title:first'),
					offset = 0;
					
				$('li.nav-item:visible', options.ele).each(function() { width += $(this).width(); });
				
				if(
					panel_tabs.length > 0
					&& header.length > 0
					&& title.length > 0
				) {
				// FKS Panel Header Tabs
					max_width = header.width();
					if($('.text', title).length > 0 && $('.text', title).outerWidth() > 0) {
					// Panel has title text
						max_width -= $('.text', title).outerWidth() + 5;
						if(panel_tabs.hasClass('tabs-right')) {
						// Tabs are right-aligned
							options.ele.css('margin-right', 5);
						} else {
						// Tabs are left-aligned
							options.ele.css('margin-left', 5);
						}
						offset += 5;
					}
					if($('.actions', header).length > 0 && $('.actions', header).outerWidth() > 0) {
					// Panel has actions
						max_width -= $('.actions', header).outerWidth() + 5;
						offset += 5;
					}
					if(panel_tabs.hasClass('tabs-right')) {
					// Tabs are right-aligned
						options.ele.addClass('justify-content-end');
						$('.fks-tabdrop', options.ele)
							.css('position', 'absolute')
							.css('left', '0px');
					}
					options.ele.width(max_width);
					title.width(max_width + $('.text', title).outerWidth() + offset);
				}
				
				if(width > max_width) {
				// Element is overflowing
					if(options.pos == 'last') {
						$('li.nav-item:not(.fks-tabdrop):visible:last', options.ele).hide();
						$('a.dropdown-item.hidden:last', options.ele.data().cont).removeClass('hidden').show();
						callback = true;
					} else {
						$('li.nav-item:not(.fks-tabdrop):visible:first', options.ele).hide();
						$('a.dropdown-item.hidden:first', options.ele.data().cont).removeClass('hidden').show();
						callback = true;
					}
					options.ele.data().cont.show();
					if($('li.nav-item:not(.fks-tabdrop):visible', options.ele).length > 0) { resize = true; }
				} else {
				// Element is not overflowing
					if($('a.dropdown-item:not(.hidden)', options.ele.data().cont).length == 1) { width -= $('.fks-tabdrop', options.ele).width(); }
					if(options.pos == 'last') {
						var e = $('li.nav-item:not(.fks-tabdrop):not(:visible):first', options.ele);
						if(e.length > 0 && e.width() + width <= max_width) {
							e.show();
							$('a.dropdown-item:not(.hidden):first', options.ele.data().cont).addClass('hidden').hide();
							resize = true;
							callback = true;
						}
					} else {
						var e = $('li.nav-item:not(.fks-tabdrop):not(:visible):last', options.ele);
						if(e.length > 0 && e.width() + width <= max_width) {
							e.show();
							$('a.dropdown-item:not(.hidden):last', options.ele.data().cont).addClass('hidden').hide();
							resize = true;
							callback = true;
						}
					}
					if($('a.dropdown-item:not(.hidden)', options.ele.data().cont).length == 0) { options.ele.data().cont.hide(); }
				}
				
				setActive();
				
				if(callback && $.type(options.callback) === 'function') { options.callback(options.ele.data().cont); }
				if(resize) { $(window).resize(); }
			});
		}
		
		options.ele.attr('fks-tabdrop', 'bound');
		$(window).resize();
	}
	
	fks.tabdropUnbind = function(ele) {
		ele.each(function() {
			var e = $(this),
				active = $('a.nav-link.active', e).attr('href');
			
			e.attr('fks-tabdrop', 'unbound');
			$('>.nav-item', e).show();
			$('a.active', e).removeClass('active');
			$('a[href="' + active + '"]', e).addClass('active');
			$('.fks-tabdrop', e).remove();
		});
		$(window).resize();
	}
	
	fks.tooltip = function(options) {
	/* EXAMPLES:
		fks.tooltip();	// Enables all $('[data-toggle="fks-tooltip"]') using defaults
		
		// Defaults
		fks.tooltip({
			ele: $('[data-toggle="fks-tooltip"]'),		// The element(s) to give a tooltip to
			title: '',									// The title to give the tooltip, can be a function (sends o as parameter)
			pos: 'top',									// Where to position the tooltip (top|right|bottom|left)
			trigger: 'hover',							// How to trigger the tooltip (hover|toggle|click|focus)
			class: '',									// Class to add to the tooltip
			animate: false,								// How to animate the tooltip (false|fade|slide)
			duration: 150,								// Duration (in ms) of the animation
			delay: 0,									// Delay (in ms) before showing the tooltip
			callback: null								// The callback function to run when shown (sends tooltip as parameter)
		});
	*/
		var defaults = {
			ele: $('[data-toggle="fks-tooltip"]'),
			title: '',
			pos: 'top',
			trigger: 'hover',
			class: '',
			animate: false,
			duration: 150,
			delay: 0,
			callback: null
		};
		if(options != undefined) { $.each(options, function(k, v) { defaults[k] = v; }); }
		options = defaults;
		
		function showTooltip(o) {
			o.dying = false;			
			o.loc = {x: o.ele.position().left, y: o.ele.position().top};
			$('div.fks-tooltip').each(function() {
				if(
					$(this).data().owner.closest('body').length < 1		// owner no longer exists
					|| $(this).data().owner == o.ele					// owner is the same
				) {
					$(this).remove(); // remove old/orphaned tooltip
				}
			});
			
			if(o.duration < 0) { o.duration = 0; }
			if(o.delay < 0) { o.delay = 0; }
			
			var tooltip = $(
					'<div/>', {
						class: 'fks-tooltip '
							+ o.pos + (o.class != '' ? ' ' + o.class : '')
							+ (o.animate != false && o.animate != 'false' ? ' ' + o.animate : ' hide'),
						style: ''
							+ '-webkit-transition: all ' + (o.duration / 1000) + 's ' + (o.delay / 1000) + 's;'
							+ 'transition: all ' + (o.duration / 1000) + 's ' + (o.delay / 1000) + 's;'
							+ 'white-space: nowrap;'
				})
				.html('<div>' + ($.type(o.title) === 'function' ? o.title(o) : o.title) + '<div class="arrow"></div></div>')
				.appendTo(o.ele.parent())
				.data('owner', o.ele),
				css = {};
				
			tooltip
				.css('width', tooltip.outerWidth())
				.css('white-space', 'normal')
				
			if($.type(o.callback) === 'function') { o.callback(tooltip); }

			switch(o.pos) {
				case 'top':
					css = {
						left: o.loc.x + (o.ele.outerWidth() / 2) - (tooltip.outerWidth() / 2),
						top: o.loc.y - tooltip.outerHeight()
					};
					break;
					
				case 'right':
					css = {
						left: o.loc.x + o.ele.outerWidth(),
						top: o.loc.y + (o.ele.outerHeight() / 2) - (tooltip.outerHeight() / 2)
					};
					break;
					
				case 'bottom':
					css = {
						left: o.loc.x + (o.ele.outerWidth() / 2) - (tooltip.outerWidth() / 2),
						top: o.loc.y + o.ele.outerHeight()
					};
					break;
					
				case 'left':
					css = {
						left: o.loc.x - tooltip.outerWidth(),
						top: o.loc.y + (o.ele.outerHeight() / 2) - (tooltip.outerHeight() / 2)
					};
					break;
			}

			if(o.pos == 'top' || o.pos == 'bottom') {
				var _right = (o.ele.width() / 2) + o.ele.offset().left + (tooltip.outerWidth() / 2),
					_left = (o.ele.width() / 2) + o.ele.offset().left - (tooltip.outerWidth() / 2);
					
				if(_right > $(document).outerWidth()) {
					$('div.arrow', tooltip).css('margin-left', parseInt($('div.arrow', tooltip).css('margin-left')) + (_right - $(document).outerWidth()));
					css.left -= (_right - $(document).outerWidth());
				}
				
				if(_left < 0) {
					$('div.arrow', tooltip).css('margin-left', parseInt($('div.arrow', tooltip).css('margin-left')) + css.left);
					css.left = 0;
				}
			}

			tooltip.css(css);
			
			if(o.animate != false && o.animate != 'false') {
				if(o.animate == 'fade' || o.animate == 'slide') {
					tooltip.removeClass(o.animate);
				}
			} else {
				tooltip.css('-webkit-transition', 'all 0s ' + (o.delay / 1000) + 's');
				tooltip.css('transition', 'all 0s ' + (o.delay / 1000) + 's');
				tooltip.removeClass('hide');
			}
			
			return tooltip;
		}
		
		function hideTooltip(o) {
			if(o.tooltip != null && o.animate != false && o.animate != 'false') {
				if(o.animate == 'fade' || o.animate == 'slide') {
					o.life = Math.ceil(o.duration / 100);
					o.dying = true;
					o.tooltip.addClass(o.animate);
					o.tooltip.css('-webkit-transition-delay', '0s');
					o.tooltip.css('transition-delay', '0s');
					function die() {
						if(o.dying && o.life > 0) { o.life -= 1; setTimeout(die, 100); return; }
						if(o.tooltip != null && o.dying && o.life <= 0) {
							o.tooltip.remove();
							o.tooltip = null;
						}
					}
					die();
				}
			} else if(o.tooltip != null) {
				o.tooltip.remove();
				o.tooltip = null;
			}
		}
		
		options.ele.each(function() {
			var o = $.extend(true, {}, options),
				e = $(this);
				
			o.ele = e;
			o.tooltip = null;
			o.dying = false;
			o.title = (e.attr('title') != undefined ? e.attr('title') : o.title);
			o.pos = (e.attr('data-placement') != undefined ? e.attr('data-placement') : o.pos);
			o.trigger = (e.attr('data-trigger') != undefined ? e.attr('data-trigger') : o.trigger);
			o.animate = (e.attr('data-animation') != undefined ? e.attr('data-animation') : o.animate);
			o.duration = (e.attr('data-duration') != undefined ? e.attr('data-duration') : o.duration);
			o.class = (e.attr('data-class') != undefined ? e.attr('data-class') : o.class);
			
			e.removeAttr('title');
			e.removeAttr('data-toggle');
			e.removeAttr('data-placement');
			e.removeAttr('data-trigger');
			e.removeAttr('data-animation');
			e.removeAttr('data-duration');
			e.removeAttr('data-class');
			
			switch(o.trigger) {
				case 'hover':
					e.mouseenter(function(){ o.tooltip = showTooltip(o); });
					e.mouseleave(function(){ hideTooltip(o); });
					break;
					
				case 'toggle':
					e.click(function() {
						if(o.tooltip == null) {
							o.tooltip = showTooltip(o);
						} else {
							hideTooltip(o);
						}
					});
					break;
					
				case 'click':
					e.click(function(){ o.tooltip = showTooltip(o); });
					e.blur(function(){ hideTooltip(o); });
					break;				
					
				case 'focus':
					e.focus(function(){ o.tooltip = showTooltip(o); });
					e.blur(function(){ hideTooltip(o); });
					break;
			}
		});
	}
	
	fks.block = function(target, args = {}) {		
		// Make target a jQuery element if a string is passed
		if($.type(target) === 'string') { target = $(target); }
		
		// Return false if target is not found
		if(target.length == 0) { return false; }
		
		// Set target to first element if multiple found
		if(target.length > 1) { target = $(target[0]); }
		
		// Set array of allowed posisions
		var positions = ['absolute', 'fixed', 'relative', 'sticky'];
		
		// Set default options
		var options = {
			header: null,
			body: '<i class="fa fa-spinner fa-spin fa-fw"></i> Please wait...',
			width: 250,
			height: null,
			fade: null,
			classes: {
				test: null,
				container: null,
				background: null,
				message: {
					container: null,
					header: null,
					body: null
				}
			}
		};
		
		// Create elements
		var block = {
			test: $('<div/>').addClass('fks-block-test'),
			container: $('<div/>').addClass('fks-block-container'),
			background: $('<div/>').addClass('fks-block-background'),
			message: {
				container: $('<div/>').addClass('fks-block-message-container'),
				header: $('<div/>').addClass('fks-block-message-header'),
				body: $('<div/>').addClass('fks-block-message-body')
			}
		};
		
		// Check message type
		if($.type(args) === 'string') {
			// Set options body value
			options.body = args;
		} else {
			// Set options
			$.each(args, function(k, v) {
				options[k] = v;
			});
		}
		
		// Add classes
		$.each(options.classes, function(k, v) {
			if(!v || !block[k]) { return; }
			if(k == 'message') {
				$.each(v, function(ck, cv) {
					if(!cv || !block.message[ck]) { return; }
					block.message[ck].addClass(cv);
				});
			} else {
				block[k].addClass(v);
			}
		});
		
		// Build full block element
		block.background.appendTo(block.container);
		if(options.header != null) {
			// Set header if not null
			block.message.header.html(options.header);
			block.message.header.appendTo(block.message.container);
		}
		block.message.body.html(options.body);
		block.message.body.appendTo(block.message.container);
		block.message.container.appendTo(block.container);
		
		// Add position to target element
		if(positions.indexOf(target.css('position')) < 0) { target.css('position', 'relative'); }
		
		// Create test block element to get width and height
		block.message.container.clone().appendTo(block.test);
		block.test.appendTo(target);
		
		// Get width if set to null
		if(options.width == null) { options.width = block.test.outerWidth(); }
		
		// Get height if set to null
		if(options.height == null) { options.height = block.test.outerHeight(); }
		
		// Remove test block element
		block.test.remove();
		
		// Set block element width and height
		block.message.container.width(options.width);
		block.message.container.height(options.height);
		
		// Add block element to container
		block.container.appendTo(target);
		
		// Set fade to 0 if not numeric
		if(isNaN(parseFloat(options.fade)) || !isFinite(options.fade)) {
			options.fade = 0;
		}
		
		// Fade-in block element
		block.container.fadeIn(options.fade);
		
		// Return block
		return block;
	}
	
	fks.unblock = function(target, args = {}) {
		// Make target a jQuery element if a string is passed
		if($.type(target) === 'string') { target = $(target); }
		
		// Return false if target is not found
		if(target.length == 0) { return false; }
		
		// Set target to first element if multiple found
		if(target.length > 1) { target = $(target[0]); }
		
		// Get block element
		var block_container = $('>.fks-block-container:first', target);
		
		// Return false if block element is not found
		if(block_container.length == 0) { return false; }
		
		// Set default options
		var options = {
			header: null,
			body: null,
			width: 'inherit',
			height: null,
			delay: null,
			fade: 500
		};
		
		// Create elements
		var block = {
			test: $('<div/>').addClass('fks-block-test'),
			container: block_container,
			message: {
				container: $('<div/>').addClass('fks-block-message-container'),
				header: $('<div/>').addClass('fks-block-message-header'),
				body: $('<div/>').addClass('fks-block-message-body')
			}
		};
		
		// Check message type
		if($.type(args) === 'string') {
			// Set options body value
			options.body = args;
		} else {
			// Set options
			$.each(args, function(k, v) {
				options[k] = v;
			});
		}
		
		// Set header if not null
		if(options.header != null) {
			block.message.header.html(options.header);
			block.message.header.appendTo(block.message.container);
		}
		
		// Set body if not null
		if(options.body != null) {
			block.message.body.html(options.body);
			block.message.body.appendTo(block.message.container);
		}
		
		// Header or body is not null
		if(options.header != null || options.body != null) {
			// Create test block element to get width and height
			block.message.container.clone().appendTo(block.test);
			block.test.appendTo(target);
			
			// Inherit old message width
			if(options.width == 'inherit') { options.width = $('.fks-block-message-container', block.container).outerWidth(); }
			
			// Get width if set to null
			if(options.width == null) { options.width = block.test.outerWidth(); }
			
			// Get height if set to null
			if(options.height == null) { options.height = block.test.outerHeight(); }
			
			// Remove test block element
			block.test.remove();
			
			// Set block element width and height
			block.message.container.width(options.width);
			block.message.container.height(options.height);
			
			// Remove old message container from block element
			$('.fks-block-message-container', block.container).remove();
			
			// Add new message container to block element
			block.message.container.appendTo(block.container);
		}
		
		// Set delay to 0 if not numeric
		if(isNaN(parseFloat(options.delay)) || !isFinite(options.delay)) {
			options.delay = 0;
		}
		
		// Set fade to 0 if not numeric
		if(isNaN(parseFloat(options.fade)) || !isFinite(options.fade)) {
			options.fade = 0;
		}
		
		// Set delay timeout
		setTimeout(function() {
			// Fade out and remove block element
			block.container.fadeOut(options.fade, function() { $(this).remove(); });
		}, options.delay);
		
	}
	
	fks.loadTemplates = function() {
		// Loop through and set all templates
		$('template').each(function() {
			var ele = $(this);
			templates[ele.attr('name')] = ele.html().toString();
			ele.remove();
		});
	}
	
	fks.template = function(id, replace = {}) {
		var t = templates[id];
		if(t === undefined) { return false; }
		
		$.each(replace, function(k, v) {
			t = t.replace(new RegExp(k, 'g'), v);
		});
		
		return t;
	}
	
	fks.bindTable = function(table, page_src) {
		// Bind add if set
		if(!table.loaded && table.ele.add && table.functions && table.functions.add) {
			table.ele.add.click(function() {
				table.functions.add();
			});
		}
		
		// Bind reload if set
		if(!table.loaded && table.ele.reload && table.functions && table.functions.reload) {
			table.ele.reload.click(function() {
				table.functions.reload();
			});
		} else if(!table.loaded && table.ele.reload) {
			table.ele.reload.click(function() {
				fks.loadTable(table, page_src);
			});
		}
		
		// Bind column dropdown if set
		if(!table.loaded && table.ele.columns) {
			fks.columnDropdown(table.dt, table.ele.columns);
		}
	}
	
	fks.loadTable = function(table, page_src, action_data) {
		// Create callbacks variable to extend table callbacks
		var callbacks = (table.callbacks ? $.extend(true, {}, table.callbacks) : {});
		
		// Check for debug and set default if not defined
		if(table.debug == undefined) { table.debug = true; }
		
		// Check for wait and set default if not defined
		if(table.wait == undefined) { table.wait = true; }
		
		// Bind the table
		fks.bindTable(table, page_src);
		
		// Modify/set success callback
		if($.type(callbacks.success) === 'function') {
			var temp_callback = callbacks.success;
			delete(callbacks.success);
			callbacks.success = function(response, options) {
				fks.drawTable(table.dt, response.data);
				temp_callback(response, options);
			};
		} else {
			callbacks.success = function(response, options) {
				fks.drawTable(table.dt, response.data);
			};
		}
		
		// Modify/set ajax_always callback
		if($.type(callbacks.ajax_always) === 'function') {
			var temp_callback = callbacks.ajax_always;
			delete(callbacks.ajax_always);
			callbacks.ajax_always = function(data, options) {
				table.loaded = true;
				temp_callback(data, options);
			};
		} else {
			callbacks.ajax_always = function(data, options) {
				table.loaded = true;
			};
		}
		
		// fks.ajax request
		fks.ajax({
			debug: table.debug,
			src: page_src,
			wait: table.wait,
			action: table.action,
			action_data: (action_data ? action_data : ''),
			block: (table.ele.block ? table.ele.block : undefined),
			block_options: (table.ele.block ? (!table.loaded ? table.message.load : table.message.reload) : undefined),
			callbacks: callbacks
		});
	}
	
	fks.loadHistory = function(id, action, page_src) {
		fks.editModal({
			src: page_src,
			wait: true,
			action: action,
			data: id,
			callbacks: {
				onOpen: function(data) {
					$('.modal-body', '#fks_modal').html('<table class="table table-striped table-hover table-border table-sm dataTable no-footer history"><thead class="thead-dark"></thead></table>');
					
					var history = $('table', '#fks_modal').DataTable({
						'bAutoWidth': false,
						'language': {
							'emptyTable': 'No history found'
						},
						'iDisplayLength': 15,
						'lengthMenu': [[15, 25, 50, 100, -1], [15, 25, 50, 100, 'All']],
						'order': [[0, 'desc']],
						'columnDefs': [
							{'targets': [0], 'title': 'Date'},
							{'targets': [1], 'title': 'Action'},
							{'targets': [2], 'title': 'Member'},
							{'targets': [3], 'title': 'Description'},
							{'targets': [4], 'title': 'Tools', 'width': '55px', 'sortable': false, 'toggleable': false, 'responsivePriority': 1}
						],
						'columns': [
							{'data': 'date_created'},
							{'data': 'action_title'},
							{'data': 'username'},
							{'data': 'misc_formatted'},
							{'data': 'tools'}
						],
						'createdRow': function (row, row_data, index) {
							// Set row data
							$(row).data(row_data);
							
							$(row).find('.view').on('click', function() { fks.viewDetailedHistory(row_data, true); });
						},
						'drawCallback': function() {
							fks.tooltip({ele: $('[data-toggle="fks-tooltip"]', '#fks_modal table')});
						}
					});
					
					fks.drawTable(history, data.history);
				}
			}
		});
	}
	
	fks.viewDetailedHistory = function(data, modal = false) {
		if(modal) {
			fks.block('#fks_modal .modal-dialog:first', 'Viewing Details');
		}
		var box = fks.bootbox.dialog({
			closeButton: false,
			animate: false,
			show: false,
			onEscape: function() {
				if(modal) {
					fks.unblock('#fks_modal .modal-dialog:first', {fade: null});
				}
				return true;
			},
			backdrop: !modal,
			size: 'large',
			className: 'history',
			title: data.action_title,
			message: `<div class="history-details fks-alert-signature">
				<div><b>Date:</b> ` + data.date_created + `</div>
				<div><b>Action:</b> ` + data.action_title + `</div>
				<div><b>Member:</b> ` + data.username + `</div>
				<div><b>Target ID:</b> ` + data.target_id + `</div>
			</div>` + data.misc_formatted_detailed,
			buttons: {
				close: {
					label: '<i class="fa fa-times fa-fw"></i> Close',
					className: 'fks-btn-danger btn-sm',
					callback: function() {
						if(modal) {
							fks.unblock('#fks_modal .modal-dialog:first', {fade: null});
						}
					}
				}
			}
		}).on('shown.bs.modal', function() {
			$('h4.modal-title', this).changeElementType('h5');
		}).modal('show');
	}
	
	fks.sound.create = function(options) {
	/*
		fks.sound.create({
			app: 'some_app',	// the app the sound belongs to
			name: 'some_name',	// name of the sound
			path: '',			// path to the sound file				(technically optional)
			title: '',			// title of the sound					(optional)
			description: '',	// description of the sound				(optional)
			allow_loop: false,	// allow sound loop - true/false		(optional)
			loop: false,		// loop the sound - true/false			(optional)
			enabled: true,		// sound is enabled - true/false		(optional)
			volume: 1			// sound volume - 0 (0%) to 1 (100%)	(optional)
		});
	*/
		// No app or name, return false
		if(!options.app || !options.name) { return false; }
		
		// Set default options
		var default_options = {
			allow_loop: false,
			description: '',
			enabled: true,
			loop: false,
			path: '',
			title: '',
			volume: 1
		};
		
		// Set app if it doesn't exist
		if(!fks.sound.collection[options.app]) { fks.sound.collection[options.app] = {}; }
		
		// Loop through default options
		$.each(default_options, function(k, v) {
			// Set missing option to default
			if(options[k] === undefined) { options[k] = v; }
		});
		
		// Save sound to collection
		fks.sound.collection[options.app][options.name] = options;
	}
	
	fks.sound.play = function(options) {
	/*
		fks.sound.play({
			app: 'some_app',	// the app the sound belongs to
			name: 'some_name'	// name of the sound
		});
	*/
		// No app or name, return false
		if(!options.app || !options.name) { return false; }
		
		// Shorten sound variable
		var sound = fks.sound.collection[options.app][options.name];
		
		// Do not play if sound it not enabled or no path
		if(!sound.enabled || !sound.path) { return false; }
		
		if(fks.sound.cache[sound.path] == undefined){
		// Sound has not been played yet, create cache
			fks.sound.cache[sound.path] = new Audio(sound.path);
			fks.sound.cache[sound.path].volume = sound.volume;
			if(sound.allow_loop == 1 && sound.loop == 1) {
				fks.sound.cache[sound.path].loop = true;
			} else {
				fks.sound.cache[sound.path].loop = false;
			}
		}
		
		// Play the sound from cache
		fks.sound.cache[sound.path].play();
	}
	
	fks.sound.stop = function(options) {
	/*
		fks.sound.stop({
			app: 'some_app',	// the app the sound belongs to
			name: 'some_name'	// name of the sound
		});
	*/
		// No app or name, return false
		if(!options.app || !options.name) { return false; }
		
		// Shorten sound variable
		var sound = fks.sound.collection[options.app][options.name];
			
		if(fks.sound.cache[sound.path] != undefined){
		// Stop the sound
			fks.sound.cache[sound.path].pause();
			fks.sound.cache[sound.path].currentTime = 0;
		}
	}
	
	fks.loadPageChangelog = function(page) {
		fks.editModal({
			wait: true,
			action: 'loadPageChangelog',
			data: page,
			callbacks: {
				onOpen: function(data) {
					
				}
			}
		});
	}
	
	fks.findInObject = function(find, obj) {
	/* EXAMPLE:
		var found = fks.findInObject('item.settings.title', obj);
	*/
		var _obj = $.extend(true, {}, obj),
			find = find.split('.'),
			result = null;
			
		$.each(find, function(k, v) {
			if(_obj[v] != undefined) {
				_obj = _obj[v];
				result = _obj;
			} else {
				result = null;
			}
		});
		
		return result;
	}
	
	fks.ajax = function(options) {
	/* EXAMPLE:
		fks.ajax({
			debug: true,					// console.log ajax return data		(optional, default: true)
			handler: fks.handler,			// the URL to request				(optional, default: fks.handler)
			type: 'POST',					// the type of request to make		(optional, default: 'POST')
			async: true,					// make request asynchronous		(optional, default: true)
			src: page.src,					// the source of the request		(optional, default: undefined)
			wait: true,						// add a timeout to the request		(optional, default: true)
			action: 'functionName',			// the PHP function to call			(optional, default: 'functionName')
			action_data: undefined,			// data to pass to PHP function		(optional, default: undefined)
			form: undefined,				// the form to interact with		(optional, default: undefined)
			form_validate: true,			// validate the form				(optional, default: true)
			block: undefined,				// the element to fks.block			(optional, default: undefined)
			block_options: undefined,		// options to give fks.block		(optional, default: undefined)
			unblock: true,					// fks.unblock after ajax.always	(optional, default: true)
			unblock_options: undefined,		// options to give fks.unblock		(optional, default: undefined)
			toasts: {						// toast configurations				(optional, default: undefined)
				success: {					// 'success' result toast			(optional)
					sticky: false,			// burntToast						(optional, default: false)
					suppress: false			// don't display toast				(optional, default: false)
				},
				info: {						// 'info' result toast				(optional)
					sticky: false,			// burntToast						(optional, default: false)
					suppress: false			// don't display toast				(optional, default: false)
				},
				validate: {					// 'validate' result toast			(optional)
					sticky: false,			// burntToast						(optional, default: false)
					suppress: false			// don't display toast				(optional, default: false)
				},
				failure: {					// 'failure' result toast			(optional)
					sticky: true,			// burntToast						(optional, default: true)
					suppress: false			// don't display toast				(optional, default: false)
				},
				ajax_error: {				// ajax.done error toast			(optional)
					sticky: false,			// burntToast						(optional, default: false)
					suppress: false			// don't display toast				(optional, default: false)
				},
				ajax_fail: {				// ajax.fail toast					(optional)
					sticky: false,			// burntToast						(optional, default: false)
					suppress: false			// don't display toast				(optional, default: false)
				}
			},
			callbacks: {					// response.result callbacks		(optional)
				success: function,			// 'success' result callback		(optional, default: undefined)
				info: function,				// 'info' result callback			(optional, default: undefined)
				validate: function,			// 'validate' result callback		(optional, default: undefined)
				failure: function,			// 'failure' result callback		(optional, default: undefined)
				default: function,			// unmatched result callback		(optional, default: undefined)
				response: function,			// any result callback				(optional, default: undefined)
				ajax_error: function,		// ajax.done error callback			(optional, default: undefined)
				ajax_fail: function,		// ajax.fail callback				(optional, default: undefined)
				ajax_always: function		// ajax.always callback				(optional, default: undefined)
			}
		});
	*/

		// Set default send_data
		var send_data = {
			wait: true,
			action: 'functionName'
		};
		
		// doToast function
		var doToast = function(category, msg = undefined, header = undefined) {
			if((msg == undefined || msg == '') && (header == undefined || header == '')) { return; }
			
			var type = 'success';
			
			switch(category) {
				case 'info':
					type = 'info';
					break;
					
				case 'validate':
					type = 'warning';
					break;
				
				case 'failure':
				case 'ajax_error':
				case 'ajax_fail':
					type = 'error';
					break;
			}
			
			// Check for toast settings
			if(toast = fks.findInObject('toasts.' + category, options)) {
				// Stop if suppress
				if(toast.suppress) {
					return;
				}
				
				// Do burntToast if sticky and stop
				if(toast.sticky) {
					fks.burntToast({type: type, header: header, msg: msg});
					return;
				}
			} else {
				// Do burntToast if failure
				if(category == 'failure') {
					fks.burntToast({type: type, header: header, msg: msg});
					return;
				}
			}
				
			// Default toast
			fks.toast({type: type, header: header, msg: msg});
		};
		
		// Check for debug and set default if not defined
		if(options.debug == undefined) { options.debug = true; }

		// Check for handler and set default if not defined
		if(options.handler == undefined) { options.handler = fks.handler; }
		
		// Check for type and set default if not defined
		if(options.type == undefined) { options.type = 'POST'; }
		
		// Check for async and set default if not defined
		if(options.async == undefined) { options.async = true; }
		
		// Set send_data src if defined
		if(options.src != undefined) { send_data.src = options.src; }
		
		// Set send_data wait if defined
		if(options.wait != undefined) { send_data.wait = options.wait; }
		
		// Set send_data action if defined
		if(options.action != undefined) { send_data.action = options.action; }
		
		// Set send_data action_data if defined
		if(options.action_data != undefined) { send_data.data = options.action_data; }
		
		// Block element
		if(options.block != undefined) {
			fks.block(options.block, (options.block_options != undefined ? options.block_options : {}));
		}
		
		// Reset form validation
		if(options.form != undefined && (options.form_validate == undefined || options.form_validate)) {
			fks.formValidate(options.form);
		}
		
		$.ajax({
			type: options.type,
			url: options.handler,
			data: send_data,
			async: options.async
		}).done(function(data) {
			if(fks.debug.ajax && options.debug) { console.log(data); }
			// Catch server errors
			try {
				var response = JSON.parse(data);
			} catch(e) {
				// Do toast
				doToast('ajax_error', 'Server Error');
				
				// ajax_error callback function
				if(callback = fks.findInObject('callbacks.ajax_error', options)) {
					if($.type(callback) === 'function') {
						callback(data, options);
					}
				}
				return;
			}
			switch(response.result) {
				case 'success':
					// Do toast
					doToast('success', response.message, response.title);
					
					// success callback function
					if(callback = fks.findInObject('callbacks.success', options)) {
						if($.type(callback) === 'function') {
							callback(response, options);
						}
					}
					break;
					
				case 'info':
					// Do toast
					doToast('info', response.message, response.title);
					
					// info callback function
					if(callback = fks.findInObject('callbacks.info', options)) {
						if($.type(callback) === 'function') {
							callback(response, options);
						}
					}
					break;
					
				case 'validate':
					// Do toast
					doToast('validate', response.message, response.title);
					
					// form validation
					if(options.form != undefined && (options.form_validate == undefined || options.form_validate)) {
						fks.formValidate(options.form, response.validation);
					}
					
					// validate callback function
					if(callback = fks.findInObject('callbacks.validate', options)) {
						if($.type(callback) === 'function') {
							callback(response, options);
						}
					}
					break;
					
				case 'failure':
					// Do toast
					doToast('failure', response.message, response.title);
					
					// failure callback function
					if(callback = fks.findInObject('callbacks.failure', options)) {
						if($.type(callback) === 'function') {
							callback(response, options);
						}
					}
					break;
					
				default:
					// default callback function
					if(callback = fks.findInObject('callbacks.default', options)) {
						if($.type(callback) === 'function') {
							callback(response, options);
						}
					}
					break;
			}
			
			// response callback function
			if(callback = fks.findInObject('callbacks.response', options)) {
				if($.type(callback) === 'function') {
					callback(response, options);
				}
			}
		}).fail(function(data) {
			// Do toast
			doToast('ajax_fail', data.statusText, data.status);
			
			// ajax_fail callback function
			if(callback = fks.findInObject('callbacks.ajax_fail', options)) {
				if($.type(callback) === 'function') {
					callback(data, options);
				}
			}
		}).always(function(data) {
			// Unblock element
			if(options.block != undefined && (options.unblock == undefined || options.unblock)) {
				fks.unblock(options.block, (options.unblock_options != undefined ? options.unblock_options : {}));
			}
			
			// ajax_always callback function
			if(callback = fks.findInObject('callbacks.ajax_always', options)) {
				if($.type(callback) === 'function') {
					callback(data, options);
				}
			}
		});
	}
	
	fks.tabcontrol = function(options) {
	/* EXAMPLE:
		fks.tabcontrol('element');				// sets element as container and uses all default options
		
		fks.tabcontrol({
			container: 'element',				// element to bind the control to
			add_class: 'fks-tabcontrol',		// class to add to the container		(optional, default: 'fks-tabcontrol')
			close_function: undefined,			// function to 'close' the tab			(optional)
			middle_click: true,					// close tab on middle click			(optional, default: true)
			select: 'first',					// tab to select when on closed tab		(optional, default: 'first', options: false, 'first', 'previous', 'next', 'last')
			conmen: {							// conmen configuration					(optional)
				enabled: true,					// enable conmen						(optional, default: true)
				options: undefined,				// options to use						(optional)
				menu: undefined,				// custom menu to use					(optional)
			},
			callbacks: {						// event callbacks						(optional)
				click_left: function,			// left mouse click callback			(optional)
				click_middle: function,			// middle mouse click callback			(optional)
				click_right: function,			// right mouse click callback			(optional)
				click_default: function,		// unmatched mouse click callback		(optional)
				tab_close: function,			// tab close callback					(optional)
				tab_shown: function				// tab shown callback					(optional)
			}
		});
		
	---------------------------------------------------------------------------------------------------------
	
		Conmen menu Keywords:
			%CLOSE_TAB%			// Gets replaced by the default 'close tab' menu item
			
			EXAMPLE:
				menu: [
					{ ... },
					'%CLOSE_TAB%',		// The 'close tab' menu item will be between the 2 menu items
					{ ... }
				]
		
	---------------------------------------------------------------------------------------------------------
	
		Tab Flags (comma separated):
			ignore_all			// Completly ignores the tab
			ignore_left			// Ignore left click events on the tab
			ignore_middle		// Ignore middle click events on the tab
			ignore_right		// Ignore right click events on the tab
			no_conmen			// Do not trigger conmen
			no_shown			// Do not trigger shown.bs.tab
			
			EXAMPLE:
				<li class="nav-item" fks-tabcontrol-flags="ignore_middle,no_conmen">
	*/
		// Make options an object if a string is passed
		if($.type(options) === 'string') { options = {container: options}; }
	
		// Make container a jQuery element if a string is passed
		if($.type(options.container) === 'string') { options.container = $(options.container); }
		
		// Return if container does not match a single element
		if(options.container.length != 1) { return false; }
		
		// Add class to container
		if(options.add_class) {
			options.container.addClass(options.add_class);
		} else {
			options.container.addClass('fks-tabcontrol');
		}
		
		// Check to see if already bound
		if(options.container.attr('fks-tabcontrol') == 'bound') {
			// Unbind on_shown from tabs
			$('.nav-item:not(.fks-tabdrop)', options.container).off('shown.bs.tab');
			
			// Unbind DOMNodeInserted
			options.container.off('DOMNodeInserted');
			
			// Unbind mouseup event
			options.container.off('mouseup');
			
			// Remove fks-tabcontrol attribute
			options.container.removeAttr('fks-tabcontrol');
		}
		
		// Function used when a tab is shown
		var on_shown = function(e) {
			// Set current_tab values
			var current_tab = {
				a: $(e.target),
				li: $(e.target).parent()
			};
			
			// Set previous_tab values
			var previous_tab = {
				a: $(e.relatedTarget),
				li: $(e.relatedTarget).parent()
			};
			
			// Get flags on current_tab
			flags = (current_tab.li.attr('fks-tabcontrol-flags') ? current_tab.li.attr('fks-tabcontrol-flags').replace(/ /g, '').split(',') : []);
			
			// Do nothing if no_shown flag is set
			if($.inArray('no_shown', flags) !== -1) { return true; }
			
			// tab_shown callback function
			if(callback = fks.findInObject('callbacks.tab_shown', options)) {
				if($.type(callback) === 'function') {
					callback(current_tab, previous_tab);
				}
			}
		};
		
		// Function used to 'close' the tab
		var close_tab = function($a, $li) {
			// Set $li to $a.parent() if not defined
			if($li == undefined) { $li = $a.parent(); }
			
			var closed = false,
				active = $a.hasClass('active'),
				index = $li.index(),
				href = $a.attr('href');
			
			// use options.close_function function if defined
			if(close_function = fks.findInObject('close_function', options)) {
				if($.type(close_function) === 'function') {
					close_function($a, $li);
					closed = true;
				}
			}
			
			// Use default close method
			if(!closed) {				
				// Remove tab
				$li.remove();
				
				// Remove tabdrop tab
				$('.fks-tabdrop > .dropdown-menu [href="' + href + '"]', options.container).remove();
				
				// Remove tab content
				$(href).remove();
			}
			
			// Select new tab if active one was closed
			if(active) {
				var select = (options.select == undefined ? 'first' : options.select),
					count = $('>.nav-item:not(.dropdown)', options.container).length,
					$tab = null;
				
				if(select && count > 0) {
					switch(select) {
						case 'first':
							$tab = $('> .nav-item:not(.dropdown):first > a', options.container);
							break;
							
						case 'previous':
							if(index == 0 || (index == 1 && $('> .nav-item:first', options.container).hasClass('dropdown'))) {
								$tab = $('> .nav-item:not(.dropdown):first > a', options.container);
							} else {
								$tab = $($('> .nav-item > a', options.container).get(index - 1));
							}
							break;
							
						case 'next':
							if(index < count) {
								$tab = $($('> .nav-item > a', options.container).get(index));
							} else {
								$tab = $('> .nav-item:not(.dropdown):last > a', options.container);
							}
							break;
							
						case 'last':
							$tab = $('> .nav-item:not(.dropdown):last > a', options.container);
							break;
					}
					
					// Set tab to null if disabled
					if($tab && $tab.hasClass('.disabled')) {
						$tab = null;
					}

					// Show the new tab if one was found
					if($tab) { $tab.tab('show'); }
				}
			}
			
			// tab_close callback function
			if(callback = fks.findInObject('callbacks.tab_close', options)) {
				if($.type(callback) === 'function') {
					callback(active, index, href);
				}
			}
			
			// Trigger resize for tabdrop fix
			$(window).resize();
		};
		
		// Function used to get the active tab
		var active_tab = function() {
			var $a = $('a.nav-link.active:first', options.container),
				$li = $('a.nav-link.active:first', options.container).parent(),
				$div = $($a.attr('href'));
				
			return {
				a: $a,
				li: $li,
				div: $div
			};
		};
		
		// Bind on_shown to existing tabs
		$('.nav-item:not(.fks-tabdrop)', options.container).on('shown.bs.tab', function(e) {
			on_shown(e);
		});
		
		// Bind DOMNodeInserted
		options.container.bind('DOMNodeInserted', function(e) {
			var $target = $(e.target);
			// Target is nav-item but is not fks-tabdrop
			if($target.is('.nav-item') && !$target.is('.fks-tabdrop')) {
				// Bind on_shown
				$target.on('shown.bs.tab', function(e) {
					on_shown(e);
				});
			}
		});
		
		// Bind mouseup event
		options.container.on('mouseup', function(e) {
			var $t = $(e.target),
				$li = $t.parents('li.nav-item'),
				$a = $('>a.nav-link', $li),
				flags = ($li.attr('fks-tabcontrol-flags') ? $li.attr('fks-tabcontrol-flags').replace(/ /g, '').split(',') : []),
				type = 'default',
				tabdrop = false;

			// Check for fks-tabdrop and adjust variables
			if($li.hasClass('fks-tabdrop')) {
				if($t.hasClass('dropdown-item')) {
					tabdrop = true;
					$t = $('a.nav-link[href="' + $t.attr('href') + '"]:first', options.container);
					$li = $t.parents('li.nav-item');
					$a = $('>a.nav-link', $li);
					flags = ($li.attr('fks-tabcontrol-flags') ? $li.attr('fks-tabcontrol-flags').replace(/ /g, '').split(',') : []);
				} else {
					return true;
				}
			}
			
			// Do nothing if ignore_all flag is set
			if($.inArray('ignore_all', flags) !== -1) { return true; }
			
			// Do nothing if tab is disabled
			if($a.hasClass('.disabled')) { return true; }
				
			switch(e.which) {
				case 1:	// Left Click
					type = 'left';
					
					// Do nothing if ignore_left flag is set
					if($.inArray('ignore_left', flags) !== -1) { return true; }
					break;
					
				case 2:	// Middle Click
					type = 'middle';
					
					// Do nothing if ignore_middle flag is set
					if($.inArray('ignore_middle', flags) !== -1) { return true; }
					
					// Check to see if middle_click option is enabled
					if(options.middle_click == undefined || options.middle_click) {
						// Call close_tab
						close_tab($a, $li);
					}
					
					// Change href to prevent new tabs/windows
					$a.attr('href-old', $a.attr('href')).attr('href', 'javascript:void(0);');
					setTimeout(function() {
						$a.attr('href', $a.attr('href-old')).removeAttr('href-old');
					}, 0);
					break;
					
				case 3:	// Right Click
					type = 'right';
					
					// Do nothing if ignore_right flag is set
					if($.inArray('ignore_right', flags) !== -1) { return true; }
					
					// Skip conmen if no_conmen flag is set
					if($.inArray('no_conmen', flags) !== -1) { break; }
					
					// Set conment variables
					var conmen = {
						enabled: fks.findInObject('conmen.enabled', options),
						options: fks.findInObject('conmen.options', options),
						menu: fks.findInObject('conmen.menu', options),
					};
					
					// Check to see if conmen is enabled
					if(conmen.enabled == null || conmen.enabled) {
						var _ele = (tabdrop ? $(e.target) : $li)
							_options = {
								ignoreMod: 'ctrlKey',
								setActive: false
							},
							_replace = {
								'%CLOSE_TAB%': {
									type: 'item',
									text: 'Close Tab',
									helper: ($.inArray('ignore_middle', flags) === -1 ? '(middle-click)' : null),
									onClick: function() {
										// Call close_tab
										close_tab($a, $li);
									},
									icon: {
										type: 'fontawesome',
										val: 'times'
									}
								}
							}
								
						// Set passed conmen options
						if(conmen.options) {
							$.each(conmen.options, function(k, v) { _options[k] = v; });
						}
						
						// Set conmen menu if undefined
						if(_options.menu == undefined) {
							_options.menu = ['%CLOSE_TAB%'];
						}
						
						// Add conmen menu items
						if(conmen.menu) {
							_options.menu = conmen.menu;
						}
						
						// replace function
						var replace = function(obj) {
							$.each(obj, function(k, v) {
								if($.type(v) !== 'string') {
									if(v.menu != undefined) {
										replace(v.menu);
									}
									return;
								}
								if(_replace[v] != undefined) {
									obj[k] = _replace[v];
								}
							});
						};
						
						// Do replacements
						replace(_options.menu);
						
						// Bind conmen
						_ele.conmen(_options);
					}
					break;
			}
			
			// Callback function
			if(callback = fks.findInObject('callbacks.click_' + type, options)) {
				if($.type(callback) === 'function') {
					callback($a, $li);
				}
			}
		});
		
		// Set fks-tabcontrol attribute to bound
		options.container.attr('fks-tabcontrol', 'bound');
		
		// Return object
		return {
			container: options.container,
			closeTab: close_tab,
			activeTab: active_tab
		};
	}
	
	fks.getParams = function() {
		var params = {};
		
		const regex = /[\?&](.*?)=([^&#]*)/g;
		const str = window.location.href;
		let m;

		while((m = regex.exec(str)) !== null) {
			if(m.index === regex.lastIndex) { regex.lastIndex++; }

			var id = null;
			m.forEach((match, groupIndex) => {
				if(match == '') { return false; }
				if(groupIndex == 1) {
					id = match;
				} else if(id && groupIndex == 2) {
					if(params[id]) {
						if(!$.isArray(params[id])) {
							params[id] = [params[id]];
						}
						if($.inArray(match, params[id]) === -1) {
							params[id].push(match);
						}
					} else {
						params[id] = match;
					}
				} else {
					id = null;
				}
			});
		}
		
		return params;
	}
	
	fks.sortable = function(options) {
	/* EXAMPLE:
		fks.sortable('element');				// sets element as container and uses all default options
	*/
		// Make options an object if a string is passed
		if($.type(options) === 'string') { options = {container: options}; }
	
		// Make container a jQuery element if a string is passed
		if($.type(options.container) === 'string') { options.container = $(options.container); }
		
		// Return if container does not match a single element
		if(options.container.length != 1) { return false; }
		
		// Set default options
		var default_options = {
			container: null,
			
			animation: 100,
			ghostClass: 'order-ghost',
			filter: '.fks-tabdrop',
			
			scroll: true,
			scrollSensitivity: 50,
			scrollSpeed: 10,
			
			forceFallback: true,
			
			onSort: function(evt) {
				if(options.container.attr('fks-tabdrop') == 'bound') {
					options.container.trigger('tabdrop-rebind');
				}
			}
		};
		
		// Set merge options
		var merge_options = $.extend(true, {}, default_options);
		
		// Merge the options
		$.each(options, function(k, v) { merge_options[k] = v; });
		
		// Create sortable
		return fks.Sortable.create(options.container.get(0), merge_options);
	}
	
	// Add DataTables Sorting
	fks.addDataTablesSorting = function() {
		// FKS Time Sorting
		$.fn.dataTable.ext.order['fks-time'] = function(settings, col) {
			return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
				return fks.time2sec($(td).text());
			});
		}
		
		// FKS Progress Bar Sorting
		$.fn.dataTable.ext.order['fks-progress'] = function(settings, col) {
			return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
				return parseInt($('.progress-bar', td).attr('aria-valuenow'));
			});
		}
	}

}(window.fks = window.fks || {}, $));