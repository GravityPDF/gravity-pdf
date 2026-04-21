const $ = jQuery;
import { updateURLParameter } from '../../common/updateURLParameter';
import { ajaxCall } from '../../../helper/ajaxCall';
import { spinner } from '../../../helper/spinner';
import { showMessage } from '../../../helper/showMessage';

interface DuplicateResponse {
	msg?: string;
	pid?: string;
	name?: string;
	dup_nonce?: string;
	del_nonce?: string;
	state_nonce?: string;
	status?: string;
	[key: string]: unknown;
}

/**
 * Handles the duplicate of a PDF list item via AJAX and fixes up all the nonce actions
 *
 * @since 4.0
 */
export function setupAJAXListDuplicateListener(): void {
	/* Add live duplicate listener */
	$('#gfpdf_list_form').on(
		'click',
		'a.submitduplicate',
		function (e: JQuery.ClickEvent) {
			e.preventDefault();

			const id = String($(this).data('id'));
			const that = this;

			/* Add our spinner */
			$(this)
				.after(spinner('gfpdf-spinner gfpdf-spinner-small'))
				.parent()
				.parent()
				.attr('style', 'position:static; visibility: visible;');

			if (id.length > 0) {
				/* Set up ajax data */
				const data = {
					action: 'gfpdf_list_duplicate',
					nonce: $(this).data('nonce') as unknown,
					fid: $(this).data('fid') as unknown,
					pid: $(this).data('id') as unknown,
				};

				/* Do ajax call */
				ajaxCall(data, function (response) {
					const res = response as DuplicateResponse;
					if (res.msg) {
						/* Remove the spinner */
						$(that)
							.parent()
							.parent()
							.attr('style', '')
							.find('.gfpdf-spinner')
							.remove();

						/* Provide feedback to use */
						showMessage(res.msg);

						/* Clone the row to be duplicated */
						const $row = $(that).parents('tr');
						const $newRow: JQuery = $row.clone();

						/* Update the edit links to point to the new location */
						$newRow
							.find('.column-name > a, .edit a')
							.each(function () {
								let href = $(this).attr('href') ?? '';
								href = updateURLParameter(
									href,
									'pid',
									res.pid ?? ''
								);
								$(this).attr('href', href);
							});

						/* Update the name field */
						$newRow.find('.column-name > a').html(res.name ?? '');

						/* Find duplicate and delete elements */
						const $duplicate = $newRow.find('.duplicate a');
						const $delete = $newRow.find('.delete a');
						const $state = $newRow.find('.check-column button');
						const $shortcode = $newRow.find('.column-shortcode');

						/* Update duplicate ID and nonce pointers so the actions are valid */
						$duplicate.data('id', res.pid as string);
						$duplicate.data('nonce', res.dup_nonce as string);

						/* Update delete ID and nonce pointers so the actions are valid */
						$delete.data('id', res.pid as string);
						$delete.data('nonce', res.del_nonce as string);

						/* update state ID and nonce pointers so the actions are valid */
						$state.data('id', res.pid as string);
						$state.data('nonce', res.state_nonce as string);

						/* Set button data-status to inactive by default */
						$state[0].setAttribute('data-status', 'inactive');

						/* Update our shortcode ID */
						let shortcodeValue =
							$shortcode
								.find('button')
								.attr('data-clipboard-text') ?? '';
						shortcodeValue = shortcodeValue.replace(
							id,
							res.pid ?? ''
						);
						$shortcode
							.find('button')
							.attr('data-clipboard-text', shortcodeValue);
						$shortcode
							.find('input')
							.attr('id', res.pid ?? '')
							.attr('value', shortcodeValue);

						$state
							.removeClass('gform-status--active')
							.addClass('gform-status--inactive')
							.find('.gform-status-indicator-status')
							.html(res.status ?? '');

						/* Add row to node and fade in */
						$newRow.hide().insertAfter($row).fadeIn();
					}
				});
			}
		}
	);
}
