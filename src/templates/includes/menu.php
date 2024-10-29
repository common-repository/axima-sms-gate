<script>
	var data = <?= json_encode($shortCodes) ?>;
	(function ($) {
		$(function () {
			function escapeRegExp(str) {
				return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
			}

			function parseText(text) {
				for (var key in data) {
					text = text.replace(new RegExp(escapeRegExp('[' + key + ']'), 'g'), data[key]);
				}
				return text;
			}

			function getPartsText(text) {
				var length = text.length;
				var parts = length <= 160 ? 1 : Math.ceil(length / 153);
				return '<?= __('segment count:', 'axima-sms-gate') ?> ' + parts + ', <?= __('characters count:', 'axima-sms-gate') ?> ' + length;
			}

			$('[data-preview]').each(function () {
				var $this = $(this);
				var selector = $this.data('preview');
				$(selector).on('input', function () {
					$this.val(parseText($(this).val()));
					$this.trigger('input');
				});
				$this.val(parseText($(selector).val()));
			});

			$('[data-parts-counter]').each(function () {
				var $this = $(this);
				var selector = $this.data('partsCounter');
				$(selector).on('input', function () {
					$this.text(getPartsText($(this).val()));
				});
				$this.text(getPartsText($(selector).val()));
			})
		});
	})(jQuery);

</script>

<ul class="nav nav-tabs" style="margin-top: 25px;">
	<li<?php if ($page === 'default'): ?> class="active"<?php endif; ?>>
		<a href="?page=<?= $domain ?>"><?= __('Dashboard', 'axima-sms-gate') ?></a>
	</li>
	<li<?php if ($page === 'logs'): ?> class="active"<?php endif; ?>>
		<a href="?page=<?= $domain ?>&payspage=logs"><?= __('Logs', 'axima-sms-gate') ?></a>
	</li>
	<li<?php if ($page === 'settings'): ?> class="active"<?php endif; ?>>
		<a href="?page=<?= $domain ?>&payspage=settings"><?= __('Settings', 'axima-sms-gate') ?></a>
	</li>
</ul>
