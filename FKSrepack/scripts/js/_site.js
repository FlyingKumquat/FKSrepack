(function(site, $, undefined) {
/*----------------------------------------------
	Global Variables
----------------------------------------------*/
	// Private
	var self = $(document.currentScript).data();
	
	// Public
	site.debug = true;
	site.ready = false;
	
/*----------------------------------------------
	Private Functions
----------------------------------------------*/

/*----------------------------------------------
	Public Functions
----------------------------------------------*/
	site.initialize = function() {
		if(!fks.ready) {
			if(site.debug) { console.log('Site -> Waiting for FKS Initialization...'); }
			setTimeout(site.initialize, 1000);
		}
		if(site.debug) { console.log('Site -> Initialization Started'); }
		
		// Add timed job
		/*
		fks.addJob({
			name: 'site.function',		// Name of the function to run						(required)
			when: 60,					// Interval to run the job in seconds				(required)
			last: 0,					// Last time the job was run						(optional, default: 0)
			debug: true,				// Enable debugging									(optional, default: true)
			pages: ['page'],			// Pages required for the job to run				(optional, default: false)
			function: false				// Provided function to run	instead of job name		(optional, default: false)
		});
		*/
		
		// Add Notifier
		/*
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

		site.ready = true;
		if(site.debug) { console.log('Site -> Initialization Completed'); }
	}
	
} (window.site = window.site || {}, $));