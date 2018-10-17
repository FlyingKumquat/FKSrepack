<?PHP
require_once(__DIR__ . '/../scripts/php/views/manager.php');
$Manager = new \FKS\Views\Manager();
?>
<span style="display: none;">Access Denied</span>
<div class="row justify-content-center error-page">
	<div class="col-5 align-self-center">
		<div class="row">
			<div class="col-lg-6 error-number">
				403
			</div>
			<div class="col-lg-6 error-details">
				<div class="title">
					Access Denied
				</div>
				<div class="body">
					You do not have the required permission(s) to view this page.
				</div>
				<div class="footer">
					<button class="btn btn-sm fks-btn-secondary" onClick="history.go(-1);">Go Back</button>
					<?PHP
						if($Manager->Session->guest() && $Manager->checkAccess('log_in', 1)) {
							echo '<button class="btn btn-sm fks-btn-secondary" onClick="window.location = \'/login.php\' + (fks.currentPage != \'\' ? \'#\' + fks.currentPage : \'\');">Log In</button>';
						}
					?>
				</div>
			</div>
		</div>
	</div>
</div>