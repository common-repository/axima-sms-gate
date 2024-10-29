<?php include __DIR__ . '/includes/menu.php'; ?>

<div class="page-header">
	<h1><?= __('Dashboard', 'axima-sms-gate') ?></h1>
</div>

<div class="col-sm-4">
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<td><b><?= __('Total SMS delivered/sent', 'axima-sms-gate') ?></b></td>
					<td><span class="pull-right"><?= $totalDelivered ?> / <?= $totalSent ?> (<?= $totalSent == 0 ? 100 : number_format(100 * $totalDelivered / $totalSent) ?>%)</span></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><b><?= __('Last week SMS delivered/sent', 'axima-sms-gate') ?></b></td>
					<td><span class="pull-right"><?= $lastWeekDelivered ?> / <?= $lastWeekSent ?> (<?= $lastWeekSent == 0 ? 100 : number_format(100 * $lastWeekDelivered / $lastWeekSent) ?>%)</span></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="col-sm-4">
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<td><b><?= __('Total errors', 'axima-sms-gate') ?></b></td>
					<td><span class="pull-right"><?= $totalErrors ?> (<?= ($totalSent + $totalErrors) == 0 ? 0 : number_format(100 * $totalErrors / ($totalSent + $totalErrors)) ?>%)</span></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><b><?= __('Last week errors', 'axima-sms-gate') ?></b></td>
					<td><span class="pull-right"><?= $lastWeekErrors ?> (<?= ($lastWeekSent + $lastWeekErrors) == 0 ? 0 : number_format(100 * $lastWeekErrors / ($lastWeekSent + $lastWeekErrors)) ?>%)</span></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="col-sm-4">
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<td>
						<b><?= __('Last known credit', 'axima-sms-gate') ?></b>
						<a class="btn btn-primary btn-xs" href="?page=<?= $domain ?>&check=1"><?= __('Refresh', 'axima-sms-gate') ?></a>
					</td>
					<td><span class="pull-right"><?= $lastCredit ?></span></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><b><?= __('Last update', 'axima-sms-gate') ?></b></td>
					<td><span class="pull-right"><?= $lastCreditUpdate ?></span></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<h3><?= __('Send custom SMS message', 'axima-sms-gate') ?></h3>

<div class="col-sm-6">
	<form class="form-horizontal" method="POST">
		<?php if ($error): ?>
			<div class="alert alert-danger"><?= $error ?></div>
		<?php endif; ?>
		<div class="form-group">
			<label for="form-number" class="col-sm-2 control-label"><?= __('Phone number', 'axima-sms-gate') ?></label>
			<div class="col-sm-10">
				<input name="number" id="form-number" class="form-control" value="<?= $number ?>">
				<p class="help-block"></p>
			</div>
		</div>
		<div class="form-group">
			<label for="form-text" class="col-sm-2 control-label"><?= __('Message text', 'axima-sms-gate') ?></label>
			<div class="col-sm-10">
				<textarea name="text" id="form-text" class="form-control" rows="5"><?= $text ?></textarea>
				<p class="help-block" data-parts-counter="#form-text"></p>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-10 col-sm-offset-2">
				<input type="submit" name="_send" value="<?= __('Send', 'axima-sms-gate') ?>" class="btn btn-primary">
			</div>
		</div>
	</form>

</div>
