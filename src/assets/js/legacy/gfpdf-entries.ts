/**
 * Gravity PDF Entries
 *
 * @param { jQuery } $
 *
 * @since 4.0
 */

(function ($: JQueryStatic) {
	/**
	 * Fires on the Document Ready Event
	 */
	$(function () {
		let timer: ReturnType<typeof setTimeout> | null = null;
		$('.gfpdf_form_action_has_submenu > a')
			/* Handle keyboard navigation */
			.on('click', function (this: HTMLElement) {
				if ($(this).attr('aria-expanded') === 'false') {
					$(this).parent().addClass('open');
					$(this).attr('aria-expanded', 'true');
				} else {
					$(this).parent().removeClass('open');
					$(this).attr('aria-expanded', 'false');
				}

				return false;
			})
			.parent()
			/* Hide submenu after a delay */
			.on('mouseover', function (this: HTMLElement) {
				clearTimeout(timer ?? undefined);

				$(this)
					.addClass('open')
					.find('> a')
					.attr('aria-expanded', 'true');
			})
			.on('mouseout', function (this: HTMLElement) {
				const $submenu = $(this);
				timer = setTimeout(function () {
					$submenu
						.removeClass('open')
						.find('> a')
						.attr('aria-expanded', 'false');
				}, 1000);
			});
	});
})(jQuery);
