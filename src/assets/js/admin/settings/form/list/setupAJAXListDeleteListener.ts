const $ = jQuery;
import { ajaxCall } from '../../../helper/ajaxCall';
import { spinner } from '../../../helper/spinner';
import { showMessage } from '../../../helper/showMessage';

interface DeleteResponse {
	msg?: string;
	[key: string]: unknown;
}

/**
 * Handles the deletion of a PDF list item via AJAX
 *
 * @since 4.0
 */
export function setupAJAXListDeleteListener(): void {
	/**
	 * Check if the last item was just deleted
	 */
	function maybeShowEmptyRow(): void {
		const $container = $('#gfpdf_list_form tbody');

		if ($container.find('tr').length === 0) {
			const $row = $('<tr>').addClass('no-items');
			const $cell = $('<td>')
				.attr('colspan', '5')
				.addClass('colspanchange');
			const $addNew = $('<a>')
				.attr(
					'href',
					$('#gfpdf_list_form a.button:first').attr('href') ?? ''
				)
				.append(GFPDF.letsGoCreateOne + '.');
			$cell.append(GFPDF.thisFormHasNoPdfs).append(' ').append($addNew);
			$row.append($cell);
			$container.append($row);
		}
	}

	function deletePdf($elm: JQuery): void {
		$elm.append(spinner('gfpdf-spinner gfpdf-spinner-small'))
			.closest('.row-actions')
			.attr('style', 'position:static; visibility: visible;');

		const data = {
			action: 'gfpdf_list_delete',
			nonce: $elm.data('nonce') as unknown,
			fid: $elm.data('fid') as unknown,
			pid: $elm.data('id') as unknown,
		};

		ajaxCall(data, function (response) {
			const res = response as DeleteResponse;
			if (res.msg) {
				/* Remove spinner */
				$elm.closest('.row-actions')
					.attr('style', '')
					.find('.gfpdf-spinner')
					.remove();

				showMessage(res.msg);

				$elm.parents('tr')
					.css('background', '#ffb8b8')
					.fadeOut(400, function () {
						(this as HTMLElement).remove();
						maybeShowEmptyRow();
					});
			}
		});
	}

	/* Add live delete listener */
	$('#gfpdf_list_form').on('click', 'a.submitdelete', function () {
		const id = String($(this).data('id'));
		if (id.length > 0 && window.confirm(GFPDF.pdfDeleteWarning)) {
			const $elm = $(this);
			deletePdf($elm);
		}

		return false;
	});
}
