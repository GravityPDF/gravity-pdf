import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import TemplateUsage from '../../../../../src/assets/js/react/components/FontManager/TemplateUsage';

describe('FontManager - TemplateUsage', () => {
	test('disclosure starts collapsed', () => {
		const { getByRole, queryByRole } = renderWithStore(
			<TemplateUsage id="roboto" font_name="Roboto" />
		);
		expect(
			getByRole('button', { name: /show template usage/i })
		).toHaveAttribute('aria-expanded', 'false');
		expect(queryByRole('region')).not.toBeInTheDocument();
	});

	test('toggling the disclosure exposes the snippet', () => {
		const { getByRole, getByText } = renderWithStore(
			<TemplateUsage id="roboto" font_name="Roboto" />
		);
		fireEvent.click(getByRole('button', { name: /show template usage/i }));
		expect(getByRole('region')).toBeInTheDocument();
		expect(getByText(/<style>/i)).toBeInTheDocument();
		expect(getByText(/font-roboto/)).toBeInTheDocument();
	});
});
