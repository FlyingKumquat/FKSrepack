/*
	Version: 1.2
	Updated: 08/07/2017
*/
(function($){
	
	$(window).on('resize', function(){
		$('[conmen-part="overlay"]').trigger('remove');
	});
	
	$(document).on('scroll', function(){
		$('[conmen-part="overlay"]').trigger('remove');
	});
	
    $.fn.conmen = function(options){
		
		if(this.length > 1){
			$.each(this, function(){
				$(this).conmen(options);
			});
			return;
		}
		
		var	f = {},
			counts = {},
			type = $.type(options),
			ele = $(this),
			overlay = null,
			menu = null,
			pos = {x:0, y:0},
			active_theme = null,
			active_position = null,
			active_offset = null,
			settings = $.extend({
				events: 'contextmenu',
				position: null,
				theme: 'conmen-default',
				container: 'body',
				menu: null,
				setActive: false,
				
				triggerMod: null, //'ctrlKey' | 'shiftKey'
				ignoreMod: null, //'ctrlKey' | 'shiftKey'
				
				callback: null,
				beforeOpen: null,
				onOpen: null,
				onClose: null,
				
				offset: {
					top: 0,
					left: 0
				},
				
				restrict:{
					x: true,
					y: true,
				},
				
				classes:{
					element: 'conmen-element',
					active: 'conmen-active',
					disabled: 'conmen-disabled',
					overlay: 'conmen-overlay',
					helper: 'conmen-helper',
					clickable: 'conmen-clickable',
					menu: 'conmen-menu',
					menuList: 'conmen-menu-list',
					menuListTitle: 'conmen-menu-list-title',
					menuListSpacer: 'conmen-menu-list-spacer',
					menuListHeader: 'conmen-menu-list-header',
					menuListItem: 'conmen-menu-list-item',
					menuListItemIcon: 'conmen-menu-list-item-icon',
					menuListItemText: 'conmen-menu-list-item-text',
					menuListItemSub: 'conmen-menu-list-item-sub',
					subMenu: 'conmen-sub-menu'
				}
			}, options);
			
		f.init = function(){
			ele.unbind(settings.events);
		
			if(type === 'undefined'){ return false; }
			if(type === 'string'){
				switch(options){
					case 'destroy':
						ele.removeAttr('conmen-part');
						ele.removeClass(settings.classes.element);
						return false;
						break;
				}
			}
			
			ele.attr('conmen-part', 'conmen-element');
			ele.addClass(settings.classes.element);
			
			return true;
		}
		
		f.buildMenu = function(sMenu){
			if($.type(sMenu) === 'function'){ sMenu = sMenu(ele); }
			
			counts.menus++;
			
			var list = $('<div>')
				.attr('conmen-part', 'menu-list')
				.addClass(settings.classes.menuList),
				this_menu = counts.menus,
				this_item = 0;
				
			$.each(sMenu, function(k, v){
				var item = $('<div>'),
					icon = $('<div>').addClass(settings.classes.menuListItemIcon),
					text = $('<div>').addClass(settings.classes.menuListItemText),
					sub = $('<div>').addClass(settings.classes.menuListItemSub),
					add_style = '',
					add_class = '';
					
				if(v.icon){
					if(v.icon.add_style){ add_style = ' style="' + v.icon.add_style + '"'; }
					if(v.icon.add_class){ add_class = ' ' + v.icon.add_class; }
					switch(v.icon.type){
						case 'fontawesome':
							icon.html('<i class="fa fa-' + v.icon.val + add_class + '"' + add_style + '></i>');
							break;
						
						case 'simple-line-icons':
							icon.html('<i class="icon-' + v.icon.val + ' icons '  + add_class + '"' + add_style + '></i>');
							break;
							
						case 'glyphicons':
							icon.html('<span class="glyphicons glyphicon-' + v.icon.val + add_class + '"' + add_style + '></span>');
							break;
							
						case 'image':
							icon.html('<img src="' + v.icon.val + add_class + '"' + add_style + '>');
							break;
					}
				}
				
				if(v.type == 'item'){
					this_item++;
					text.text(v.text);
					
					item.data('item', {menu: this_menu, id: this_item});
					
					if(v.helper){
						var helper = $('<div>')
							.addClass(settings.classes.helper)
							.text(v.helper);
							
						text.append(helper);
					}
					
					item.attr('conmen-part', 'menu-list-item');
					item.addClass(settings.classes.menuListItem);
					item.append(icon);
					item.append(text);
					
					if($.type(v.disabled) === 'function'){ v.disabled = v.disabled(ele); }
					if(v.disabled){
						item.addClass(active_theme);
						item.addClass(settings.classes.disabled);
						list.append(item);
						return true;
					}
					
					if(v.menu){
						item.append(sub);
						var subMenu = $('<div>')
							.attr('conmen-part', 'sub-menu')
							.addClass(settings.classes.menu)
							.addClass(settings.classes.subMenu)
							.append(f.buildMenu(v.menu));
							
						item.append(subMenu);
						item.on('mouseover', function(e){
							f.adjustSubMenuPositions(e);
						});
					}else{
						item.addClass(settings.classes.clickable);
						item.on('click', function(e){
							if($.type(settings.callback) === 'function'){ settings.callback(ele, $(e.currentTarget)); }
							if($.type(v.onClick) === 'function'){ v.onClick(ele, $(e.currentTarget)); }
							if(!v.stayOpen){ f.closeOverlay(); }
						});
					}
				}
				
				if(v.type == 'title'){
					item.attr('conmen-part', 'menu-list-title');
					item.text(v.text);
					item.addClass(settings.classes.menuListTitle);
				}
				
				if(v.type == 'header'){
					item.attr('conmen-part', 'menu-list-header')
					item.text(v.text);
					item.addClass(settings.classes.menuListHeader);
				}
				
				if(v.type == 'spacer'){
					item.attr('conmen-part', 'menu-list-spacer')
					item.addClass(settings.classes.menuListSpacer);
				}
					
				list.append(item);
			});
			return list;
		}
		
		f.closeOverlay = function(e){
			overlay.remove();
			// On Close Function
			if($.type(settings.onClose) === 'function'){ settings.onClose(e); }
			// Remove Active Class
			ele.removeClass(active_theme);
			ele.removeClass(settings.classes.active);
		}
		
		f.adjustSubMenuPositions = function(e){			
			if(!settings.restrict.x && !settings.restrict.y){ return; }
			
			var offset = {
				x: menu.position().left + menu.outerWidth(),
				y: menu.position().top
			};

			$('.' + settings.classes.subMenu).not(':hidden').each(function(){
				var t = $(this),
					p = $(t.parents('.' + settings.classes.subMenu)),
					w = t.position().left,
					h = t.outerHeight() + t.parent().position().top;
				
				$.each(p, function(k,v){					
					if(Math.abs(parseInt($(v).css('margin-left'))) > 0){
						w -= t.position().left;
					}else{
						w += t.position().left;
					}

					h += $(v).parent().position().top;
					if(Math.abs(parseInt($(v).css('margin-top'))) > 0){
						h += parseInt($(v).css('margin-top'));
					}
				});
				
				if(settings.restrict.x){
					if(offset.x + w > overlay.width()){
						var pad =
							parseInt(t.css('padding-right')) +
							parseInt(t.css('padding-left')) +
							parseInt(t.css('border-right-width')) +
							parseInt(t.css('border-left-width')) +
							parseInt(t.parent().css('border-right-width')) +
							parseInt(t.parent().css('border-left-width')) +
							parseInt(t.parent().css('margin-right')) +
							parseInt(t.parent().css('margin-left')),
							diff = t.outerWidth() - t.position().left - pad;

						t.css({'margin-left':-(t.outerWidth() + t.position().left - diff)});
					}
				}
				
				if(settings.restrict.y){
					if(offset.y + h > overlay.height()){
						var pad =
							parseInt(t.css('border-top-width')) +
							parseInt(t.css('border-bottom-width')),
							diff = pad;
						
						t.css({'margin-top':(overlay.height() - (offset.y + h) - diff)});
					}
				}
			});			
		}

		if(!f.init()){ return false; }
		
        return this.on(settings.events, function(e){
			if(settings.triggerMod && !e[settings.triggerMod]){ return true; }
			if(settings.ignoreMod && e[settings.ignoreMod]){ return true; }
			
			// Before Open Function
			if($.type(settings.beforeOpen) === 'function'){
				if(!settings.beforeOpen(e)){ return; }
			}
			
			active_theme = $.type(settings.theme) === 'function' ? settings.theme(e) : settings.theme;
			active_position = $.type(settings.position) === 'function' ? settings.position(e) : settings.position;
			active_offset = $.type(settings.offset) === 'function' ? settings.offset(e) : settings.offset;
			
			e.stopImmediatePropagation();
			e.stopPropagation();
			e.preventDefault();
			
			// Set Counts
			counts.menus = 0;
				
			// Set Active Class
			if(($.type(settings.setActive) === 'function' && settings.setActive(e)) || ($.type(settings.setActive) !== 'function' && settings.setActive)){
				ele.addClass(active_theme);
				ele.addClass(settings.classes.active);
			}
			
			// Build Menu
			menu = $('<div>')
				.attr('conmen-part', 'menu')
				.addClass(settings.classes.menu)
				.append(f.buildMenu(settings.menu));
			
			// Create Overlay
			overlay = $('<div>')
				.attr('conmen-part', 'overlay')
				.addClass(active_theme)
				.addClass(settings.classes.overlay)
				.append(menu)
				.on('click mousedown remove', function(e){
					if(e.target.className != active_theme + ' ' + settings.classes.overlay){ return true; }
					f.closeOverlay(e);
				});
			
			// Show On Container
			$(settings.container).append(overlay);
			
			// Grab / Set Position
			pos = {x: ele.position().left, y: ele.position().top};
			switch(active_position){
				case 'top-left-corner':
					pos.y -= menu.outerHeight();
					pos.x -= menu.outerWidth();
					break;
					
				case 'top-left':
					pos.y -= menu.outerHeight();
					break;
					
				case 'top':
					pos.y -= menu.outerHeight();
					pos.x += ele.outerWidth() / 2;
					pos.x -= menu.outerWidth() / 2;
					break;
					
				case 'top-right':
					pos.y -= menu.outerHeight();
					pos.x += ele.outerWidth();
					pos.x -= menu.outerWidth();
					break;
					
				case 'top-right-corner':
					pos.y -= menu.outerHeight();
					pos.x += ele.outerWidth();
					break;
					
				case 'right-top':
					pos.x += ele.outerWidth();
					break;
					
				case 'right':
					pos.x += ele.outerWidth();
					pos.y += ele.outerHeight() / 2;
					pos.y -= menu.outerHeight() / 2;
					break;
					
				case 'right-bottom':
					pos.x += ele.outerWidth();
					pos.y += ele.outerHeight();
					pos.y -= menu.outerHeight();
					break;
					
				case 'bottom-right-corner':
					pos.y += ele.outerHeight();
					pos.x += ele.outerWidth();
					break;
					
				case 'bottom-right':
					pos.y += ele.outerHeight();
					pos.x += ele.outerWidth();
					pos.x -= menu.outerWidth();
					break;
					
				case 'bottom':
					pos.y += ele.outerHeight();
					pos.x += ele.outerWidth() / 2;
					pos.x -= menu.outerWidth() / 2;
					break;
					
				case 'bottom-left':
					pos.y += ele.outerHeight();
					break;
					
				case 'bottom-left-corner':
					pos.y += ele.outerHeight();
					pos.x -= menu.outerWidth();
					break;
					
				case 'left-bottom':
					pos.x -= menu.outerWidth();
					pos.y += ele.outerHeight();
					pos.y -= menu.outerHeight();
					break;
					
				case 'left':
					pos.x -= menu.outerWidth();
					pos.y += ele.outerHeight() / 2;
					pos.y -= menu.outerHeight() / 2;
					break;
				
				case 'left-top':
					pos.x -= menu.outerWidth();
					break;
					
				default:
					pos = {x: e.pageX, y: e.pageY};
					break;
			}
			
			pos.x += active_offset.left;
			pos.y += active_offset.top;
			pos.y -= $(document).scrollTop();
			
			menu.css({left: pos.x, top: pos.y});
			
			// Adjust Main Menu Position
			if(settings.restrict.y){
				if(menu.position().top + menu.outerHeight() > overlay.height()){
					menu.css({top: overlay.height() - menu.outerHeight()});
				}
				if(menu.position().top < 0){
					menu.css({top: 0});
				}
			}
			
			if(settings.restrict.x){
				if(menu.position().left + menu.outerWidth() > overlay.width()){
					menu.css({left: overlay.width() - menu.outerWidth()});
				}
				if(menu.position().left < 0){
					menu.css({left: 0});
				}
			}
			
			// On Open Function
			if($.type(settings.onOpen) === 'function'){ settings.onOpen(e); }
		});
		
    };
	
}(jQuery));