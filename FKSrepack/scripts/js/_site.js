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
		
		// Add timed jobs
		//fks.addJob({name: 'site.SOMETHING', when: 60, last: 0, pages: false});

		site.ready = true;
		if(site.debug) { console.log('Site -> Initialization Completed'); }
	}
	
} (window.site = window.site || {}, $));