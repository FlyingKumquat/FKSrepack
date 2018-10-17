<?PHP require_once(__DIR__ . '/../scripts/php/views/manager.php'); ?>
<div class="row justify-content-center error-page">
	<div class="col-5 align-self-center">
		<div class="row">
			<div class="col-lg-6 error-number">
				500
			</div>
			<div class="col-lg-6 error-details">
				<div class="title">
					Oh jeez...
				</div>
				<div class="body">
					It seems we've encountered an issue on the server.
				</div>
				<div class="footer">
					<button class="btn btn-sm fks-btn-secondary" onClick="history.go(-1);">Go Back</button>
					<button class="btn btn-sm fks-btn-secondary" onClick="location.reload();">Refresh</button>
				</div>
			</div>
		</div>
	</div>
</div>