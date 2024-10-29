<?php include __DIR__ . '/includes/menu.php'; ?>

<div class="page-header">
	<h1><?= __('Settings', 'axima-sms-gate') ?></h1>
</div>

<form class="form-horizontal" method="post">
	<div class="col-sm-12">
		<h3><?= __('User settings', 'axima-sms-gate') ?></h3>

		<?php if (isset($settings['name'])): ?>
			<?= sprintf(__('User logged in as %s', 'axima-sms-gate'), $settings['name']) ?>.<br>
			<a class="btn btn-warning" data-toggle="collapse" data-target="#loginForm"><?= __('Change', 'axima-sms-gate') ?></a>
		<?php endif; ?>

		<div class="collapse<?php if (!isset($settings['name'])): ?> in<?php endif; ?>" id="loginForm">
			<div class="form-group">
				<label for="form-name" class="col-sm-2 control-label"><?= __('Login', 'axima-sms-gate') ?></label>
				<div class="col-sm-4">
					<input type="text" name="name" id="form-name" class="form-control" value="<?= isset($settings['name']) ? $settings['name'] : '' ?>">
					<p class="help-block"><?= __('Your login for pays.cz service', 'axima-sms-gate') ?></p>
				</div>
			</div>
			<div class="form-group">
				<label for="form-password" class="col-sm-2 control-label"><?= __('Password', 'axima-sms-gate') ?></label>
				<div class="col-sm-4">
					<input type="password" name="password" id="form-password" class="form-control">
					<p class="help-block"><?= __('Your password for pays.cz service.', 'axima-sms-gate') ?><?= isset($settings['password']) ? __('Fill only if you want to change saved password.') : '' ?> </p>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-10 col-sm-offset-2">
					<button type="submit" name="_submit" class="btn btn-primary"><?= __('Save', 'axima-sms-gate') ?></button>
					<a href="?page=<?= $domain ?>" class="btn btn-default"><?= __('Cancel', 'axima-sms-gate') ?></a>
				</div>
			</div>
		</div>

		<h3><?= __('Messages settings', 'axima-sms-gate') ?></h3>
		<p>
			<?= __('You can use following placeholders:', 'axima-sms-gate') ?>
		</p>
		<div class="table-responsive col-sm-6">
			<table class="table table-condensed table-hover table-striped">
				<thead>
					<tr>
						<th><?= __('Placeholder', 'axima-sms-gate') ?></th>
						<th><?= __('Example data', 'axima-sms-gate') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($shortCodes as $name => $code): ?>
						<tr>
							<td><code>[<?= $name ?>]</code></td>
							<td><?= $code ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="clearfix"></div>
		<?php foreach ($statuses as $name => $status): ?>
			<div class="form-group">
				<label for="form-enable-<?= $name ?>" class="col-sm-2 control-label"><?= __('Status:', 'axima-sms-gate') ?> <?= $status ?></label>
				<div class="col-sm-10">
					<div class="checkbox">
						<label>
							<input data-toggle-enable="#form-group-<?= $name ?>" type="checkbox" name="enabled[<?= $name ?>]" id="form-enable-<?= $name ?>"<?php if (isset($settings['enabled'][$name]) && $settings['enabled'][$name]): ?> checked<?php endif; ?> <?= __('Enabled', 'axima-sms-gate') ?>
						</label>
					</div>
				</div>
			</div>
			<div class="form-group" id="form-group-<?= $name ?>"<?php if (!(isset($settings['enabled'][$name]) && $settings['enabled'][$name])): ?> style="display: none;"<?php endif; ?>>
				<label for="form-text-<?= $name ?>" class="col-sm-2 control-label"><?= __('Text', 'axima-sms-gate') ?></label>
				<div class="col-sm-5">
					<?php
						$default = '';
						if (isset($settings['texts']) && isset($settings['texts'][$name])) {
							$default = $settings['texts'][$name];
						}
					?>
					<textarea name="texts[<?= $name ?>]" id="form-text-<?= $name ?>" class="form-control" rows="4"><?= $default ?></textarea>
					<p class="help-block" data-parts-counter="#form-text-<?= $name ?>"></p>
				</div>
				<label for="form-preview-<?= $name ?>" class="col-sm-1 control-label"><?= __('Preview', 'axima-sms-gate') ?></label>
				<div class="col-sm-4">
					<textarea name="form-preview-<?= $name ?>" id="form-preview-<?= $name ?>" readonly data-preview="#form-text-<?= $name ?>" class="form-control" rows="4"></textarea>
					<p class="help-block" data-parts-counter="#form-preview-<?= $name ?>"></p>
				</div>
			</div>
		<?php endforeach; ?>
		<div class="form-group">
			<div class="col-sm-10 col-sm-offset-2">
				<button type="submit" name="_submit" class="btn btn-primary"><?= __('Save', 'axima-sms-gate') ?></button>
				<a href="?page=<?= $domain ?>" class="btn btn-default"><?= __('Cancel', 'axima-sms-gate') ?></a>
			</div>
		</div>
	</div>
</form>

<script>
	(function ($) {
		$(function () {
			function apply($element) {
				var data = $element.data('toggle-enable');
				if ($element.is(':checked')) {
					$(data).show();
				} else {
					$(data).hide();
				}
			}
			$('[data-toggle-enable]').each(function () {
				apply($(this));
			}).on('change', function () {
				apply($(this));
			});
		});
	})(jQuery);
</script>
