define([
	'bootstrap',
	'backbone',
	'slimscroll',
	'conmen',
	'fks'
], function() {
	var Router = Backbone.Router.extend({
		routes: {
			'logout': 'logout',
			'login': 'login',
            '*generic': 'loadPage',
        },
		initialize: function() {
			Backbone.history.start({pushState: false});
			this.on('all', function(e) {
				if(e == 'route') { return; }
			});
		},
		'login': function() {
			window.location.replace('/login.php#' + fks.currentPage);
		},
		'logout': function() {
			fks.logout('LOGOUT_MANUAL', fks.currentPage);
		},
		'loadPage': function() {
			loadView('views/' + fks.location());
			fks.setActive(fks.location());
			fks.currentPage = fks.location();
		}
	});
	
	function loadView(view) {
		var params = Array.prototype.splice.call(arguments, 1);
		try {
			require([view], function(page) {
				try {
					if(fks.debug.page) { console.log('loading page -> ' + view); }
					if(require.undef) {
						require.undef(view);
					} else {
						require('require').undef(view);
					}
					if(params.length > 0 && page.add) { page.add(params); }
					new page.view;
				} catch(ex) {
					if(fks.debug.page) { console.log('1 failed to load page -> ' + view); }
					if(require.undef) {
						require.undef(view);
					} else {
						require('require').undef(view);
					}
					loadErrorPage('500');
					//throw ex;
				}
			}, function(err) {
				if(fks.debug.page) {
					console.log('2 failed to load page -> ' + view);
					console.log(err);
				}
				if(require.undef) {
					require.undef(view);
				}
				loadErrorPage('404');
			});
		} catch(ex) {
			if(fks.debug.page) {
				console.log('3 failed to load page -> ' + view);
				console.log(ex);
			}
			loadErrorPage('500');
		}
	}
	
	function loadErrorPage(p) {
		document.title = fks.siteTitle + ' : ' + p;
		$.ajax({
			url: '/views/' + p + '.php',
			success: function(result) {
				$(fks.container).html(result);
			}
		});
	}

	return Router;
});