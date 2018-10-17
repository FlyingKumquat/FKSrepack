<?PHP
	if(is_dir('install')) {
		header('Location:splash.html');
	}
	require_once(__DIR__ . '/scripts/php/views/manager.php');
	
	if($_SESSION['site_layout'] == 'Admin') {
		include_once(__DIR__ . '/layouts/admin.php');
	} else {
		include_once(__DIR__ . '/layouts/default.php');
	}
?>