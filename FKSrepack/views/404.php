<?PHP require_once(__DIR__ . '/../scripts/php/views/manager.php'); ?>
<div class="row justify-content-center error-page">
	<div class="col-5 align-self-center">
		<div class="row">
			<div class="col-lg-6 error-number">
				404
			</div>
			<div class="col-lg-6 error-details">
				<div class="title">
					Oops, you're lost
				</div>
				<div class="body">
					The page you have requested cannot be found.
				</div>
				<div class="footer">
					<button class="btn btn-sm fks-btn-secondary" onClick="history.go(-1);">Go Back</button>
				</div>
			</div>
		</div>
	</div>
</div>