require.config({
	baseUrl: 'scripts/js/',
	paths: {
		'jquery': 'plugins/jquery/jquery-3.2.1.min',
		'popper': 'plugins/popper/popper.min',
		'bootstrap': 'plugins/bootstrap/bootstrap.min',
		'underscore': 'plugins/underscore/underscore-min',
		'backbone': 'plugins/backbone/backbone.min',
		'iCheck': 'plugins/iCheck/icheck.min',
		'bootbox': 'plugins/bootbox/bootbox.min',
		'pace': 'plugins/pace/pace.min',
		'toastr': 'plugins/toastr/toastr.min',
		'select2': 'plugins/select2/select2.full.min',
		'moment': 'plugins/bootstrap-daterangepicker/moment.min',
		'bootstrap-daterangepicker': 'plugins/bootstrap-daterangepicker/daterangepicker',
		'slimscroll': 'plugins/slimscroll/jquery.slimscroll.min',
		'summernote': 'plugins/summernote/summernote-bs4.min',
		'multiselect': 'plugins/multiselect/js/jquery.multi-select',
		'jstree': 'plugins/jstree/jstree.min',
		'Sortable': 'plugins/Sortable/Sortable.min',
		'nestable': 'plugins/nestable/jquery.nestable',
		'conmen': 'plugins/conmen/conmen',
		
		'datatables.net': 'plugins/datatables/DataTables-1.10.13/js/jquery.dataTables.min',
		'datatables.net-fixed-header': 'plugins/datatables/FixedHeader-3.1.2/js/dataTables.fixedHeader.min',
		
		'fks': 'plugins/fks/fks',
		
		'amcharts': '//www.amcharts.com/lib/3/amcharts',
		'amcharts.funnel': '//www.amcharts.com/lib/3/funnel',
		'amcharts.gauge': '//www.amcharts.com/lib/3/gauge',
		'amcharts.pie': '//www.amcharts.com/lib/3/pie',
		'amcharts.radar': '//www.amcharts.com/lib/3/radar',
		'amcharts.serial': '//www.amcharts.com/lib/3/serial',
		'amcharts.xy': '//www.amcharts.com/lib/3/xy',
		
		'amcharts.export': '//www.amcharts.com/lib/3/plugins/export/export.min',
		'amcharts.plugins.export.libs.blob': '//www.amcharts.com/lib/3/plugins/export/libs/blob.js/blob',
		'amcharts.plugins.export.libs.fabric': '//www.amcharts.com/lib/3/plugins/export/libs/fabric.js/fabric',
		'amcharts.plugins.export.libs.fileSaver': '//www.amcharts.com/lib/3/plugins/export/libs/FileSaver.js/FileSaver',
		'amcharts.plugins.export.libs.xlsx': '//www.amcharts.com/lib/3/plugins/export/libs/xlsx/xlsx',
		'amcharts.plugins.export.libs.jszip': '//www.amcharts.com/lib/3/plugins/export/libs/jszip/jszip',
		'amcharts.plugins.export.libs.pdfmake': '//www.amcharts.com/lib/3/plugins/export/libs/pdfmake/pdfmake',
		'amcharts.plugins.export.libs.pdfFonts': '//www.amcharts.com/lib/3/plugins/export/libs/pdfmake/vfs_fonts'
	},
	packages: [{
		name: 'codemirror',
		location: 'plugins/codemirror',
		main: 'lib/codemirror'
	}],
	urlArgs: 'bust=' + (new Date()).getTime(),
	shim: {
		'popper': {
			deps: ['jquery'],
			exports: 'Popper'
		},
		'bootstrap': {
			deps: ['jquery', 'popper'],
			exports: 'Bootstrap'
		},
		'underscore': {
			exports: '_'
		},
		'backbone': {
			deps: ['underscore', 'jquery'],
			exports: 'Backbone'
		},
		'iCheck': {
			deps: ['jquery'],
			exports: 'iCheck'
		},
		'bootbox': {
			deps: ['bootstrap'],
			exports: 'bootbox'
		},
		'pace': {
			exports: 'pace'
		},
		'toastr': {
			deps: ['jquery'],
			exports: 'toastr'
		},
		'select2': {
			deps: ['jquery'],
			exports: 'select2'
		},
		'bootstrap-daterangepicker': {
			deps: ['bootstrap'],
			exports: 'daterangepicker'
		},
		'slimscroll': {
			deps: ['jquery'],
			exports: 'slimscroll'
		},
		'summernote': {
			deps: ['jquery'],
			exports: 'summernote'
		},
		'multiselect': {
			deps: ['jquery'],
			exports: 'multiselect'
		},
		'jstree': {
			deps: ['jquery'],
			exports: 'jstree'
		},
		'conmen': {
			deps: ['jquery'],
			exports: 'conmen'
		},
		'fks': {
			deps: ['jquery'],
			exports: 'fks'
		},
		'amcharts.funnel': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.gauge': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.pie': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.radar': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.serial': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.xy': {
			'deps': [ 'amcharts' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;
			}
		},
		'amcharts.plugins.export.libs.pdfFonts': {
			"deps": [ 'amcharts.plugins.export.libs.pdfmake' ]
		},
		'amcharts.export': {
			'deps': [ 'amcharts', 'amcharts.plugins.export.libs.blob', 'amcharts.plugins.export.libs.fabric', 'amcharts.plugins.export.libs.fileSaver', 'amcharts.plugins.export.libs.jszip', 'amcharts.plugins.export.libs.pdfmake', 'amcharts.plugins.export.libs.pdfFonts', 'amcharts.plugins.export.libs.xlsx' ],
			'exports': 'AmCharts',
			'init': function() {
				AmCharts.isReady = true;

				// CSS exception; load once it's ready
				var link = document.createElement( 'link' );
				link.type = 'text/css';
				link.rel = 'stylesheet';
				link.href = 'https://www.amcharts.com/lib/3/plugins/export/export.css';
				document.getElementsByTagName( 'head' )[ 0 ].appendChild( link );
			}
		}
	}
});

require(['popper'], function(Popper) {
	window.Popper = Popper;
	require([
		'pace',
		'main',
		'toastr',
		'bootbox',
		'Sortable'
	], function(pace, MainRouter, toastr, bootbox, Sortable) {
		pace.start({
			document: false,
			restartOnPushState: true,
			restartOnRequestAfter: true,
			ajax: {
				ignoreURLs: []
			},
			catchupTime: 50,
			maxProgressPerFrame: 20,
			minTime: 0,
			ghostTime: 100
		});
		
		$(document).ready(function() {
			fks.bootbox = bootbox;
			fks.toastr = toastr;
			fks.Sortable = Sortable;
			fks.initialize();
			require(['autoloader'], function(content) {
				// Loaded
			}, function(err) {
				// Error
			});
			new MainRouter;
		});
	});
});