<?php include __DIR__ . '/includes/menu.php'; ?>

<div class="col-sm-10">
	<div class="page-header">
		<h1><?= __('Logs', 'axima-sms-gate') ?></h1>
	</div>
</div>
<div class="col-sm-2">
	<div>
		<p><?= __('Last check:', 'axima-sms-gate') ?> <?= $lastCheck ?></p>
		<a href="?page=<?= $domain ?>&payspage=logs&list=<?= $list ?>&check=true" class="btn btn-primary"><?= __('Check for delivery status', 'axima-sms-gate') ?></a>
		<p><?= __('This action may take a while to process.', 'axima-sms-gate') ?></p>
	</div>
</div>

<div class="col-sm-12">
	<div class="table-responsive">
		<table class="table table-condensed table-hover table-striped">
			<thead>
				<tr>
					<th><?= __('Date', 'axima-sms-gate') ?></th>
					<th><?= __('Text', 'axima-sms-gate') ?></th>
					<th><?= __('Phone number', 'axima-sms-gate') ?></th>
					<th><?= __('State', 'axima-sms-gate') ?></th>
					<th><?= __('Action', 'axima-sms-gate') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$map = array(
						0 => 'warning',
						1 => '',
						2 => 'danger',
					);
					$textMap = array(
						0 => __('Sent'),
						1 => __('Received'),
						2 => __('Error'),
					);
				?>
				<?php foreach ($messages as $message): ?>
					<tr class="<?= $map[$message->status] ?>">
						<td><?= $message->date_sent ?></td>
						<td><?= $message->text ?></td>
						<td><?= $message->number ?></td>
						<td>
							<?= $textMap[$message->status] ?>
							<?php if ($message->status == 1): ?>
								(<?= $message->date_delivered ?>)
							<?php else: ?>
								<?= $note ?>
							<?php endif; ?>
						</td>
						<td>
							<a class="btn btn-xs btn-default" href="?page=<?= $domain ?>&number=<?= urlencode($message->number) ?>&text=<?= urlencode($message->text) ?>">
								<?= __('Resend', 'axima-sms-gate') ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<ul class="pagination">
		<li<?php if ($list === 1): ?> class="disabled"<?php endif; ?>>
			<a href="?page=<?= $domain ?>&payspage=logs&list=<?= $list + 1 ?>">&laquo;</a>
		</li>
		<?php $last = 0 ?>
		<?php foreach ($pages as $p): ?>
			<?php if ($last + 1 !== $p): ?>
				<li class="disabled">
					<a href="javascript: void(0);">...</a>
				</li>
			<?php endif; ?>
			<?php $last = $p ?>
			<li<?php if ($list === $p): ?> class="active"<?php endif; ?>>
				<a href="?page=<?= $domain ?>&payspage=logs&list=<?= $p ?>"><?= $p ?></a>
			</li>
		<?php endforeach; ?>
		<li<?php if ($list === $maxPage): ?> class="disabled"<?php endif; ?>>
			<a href="?page=<?= $domain ?>&payspage=logs&list=<?= $list - 1 ?>">&raquo;</a>
		</li>
	</ul>
</div>
