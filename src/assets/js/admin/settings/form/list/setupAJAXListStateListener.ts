const $ = jQuery;
import { ajaxCall } from '../../../helper/ajaxCall';

interface StateResponse {
	state?: string;
	[key: string]: unknown;
}

/**
 * Handles the state change of a PDF list item via AJAX
 *
 * @since 4.0
 */
export function setupAJAXListStateListener(): void {
	/* Add live state listener to change active / inactive value */
	$('#gfpdf_list_form').on('click', '.check-column button', function () {
		const id = String($(this).data('id'));
		const button = $(this);
		const label = button.find('span.gform-status-indicator-status');

		if (id.length > 0) {
			button
				.addClass('gform_status--pending')
				.removeClass('gform-status--active gform-status--inactive');

			/* Set up ajax data */
			const data = {
				action: 'gfpdf_change_state',
				nonce: $(this).data('nonce') as unknown,
				fid: $(this).data('fid') as unknown,
				pid: $(this).data('id') as unknown,
			};

			/* Do ajax call */
			ajaxCall(data, function (response) {
				const res = response as StateResponse;
				label.html(res.state ?? '');

				if (button.data('status') === 'active') {
					/* Set button data-status to inactive */
					button[0].setAttribute('data-status', 'inactive');

					button
						.data('status', 'inactive')
						.removeClass('gform_status--pending')
						.addClass('gform-status--inactive');
				} else {
					/* Set button data-status to active */
					button[0].setAttribute('data-status', 'active');

					button
						.data('status', 'active')
						.removeClass('gform_status--pending')
						.addClass('gform-status--active');
				}
			});
		}
	});
}
