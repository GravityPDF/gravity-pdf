import selector, {
	searchTemplates,
	sortTemplates,
} from '../../../../src/assets/js/react/selectors/getTemplates';
import type { TemplateItem } from '../../../../src/assets/js/react/types';

describe('Selectors - getTemplates', () => {
	const templateObject = [
		{
			id: 'zadani',
			group: 'Core',
			template: 'Zadani',
			description: '',
			author: 'Gravity PDF',
		},
		{
			id: 'rubix',
			group: 'Core',
			template: 'Rubix',
			description: '',
			author: '',
		},
		{
			id: 'focus-gravity',
			group: 'Core',
			template: 'Focus Gravity',
			description: '',
			author: 'William',
		},
		{
			id: 'adelade',
			group: 'Core',
			template: 'Adelade',
			description: '',
			author: 'William',
		},
		{
			id: 'default',
			group: 'Legacy',
			template: 'Default',
			description: 'Old Template',
			author: '',
		},
		{
			id: 'default-template',
			group: 'Legacy',
			template: 'Default Template',
			description: '',
			author: '',
		},
		{
			id: 'default-template',
			group: 'Core',
			template: 'Default Template',
			description: '',
			author: '',
		},
		{
			id: 'new-template',
			group: 'Custom',
			template: 'New Template',
			description: '',
			author: '',
			new: true,
		},
	];
	const templates = templateObject as unknown as TemplateItem[];

	describe('sortTemplates()', () => {
		test('check the function sorts the results correctly', () => {
			let list = sortTemplates(templates, '');

			expect(list[0].id).toBe('adelade');

			/* Check our new template is pushed to the end of the queue */
			const checkLast = list.length - 1;

			expect(list[checkLast].id).toBe('new-template');

			/* check the active template is hoisted above the rest */
			list = sortTemplates(templates, 'zadani');

			expect(list[0].id).toBe('zadani');
		});
	});

	describe('searchTemplates()', () => {
		test('check we get the expected results', () => {
			expect(searchTemplates('default', templates).length).toBe(3);
			expect(searchTemplates('Gravity PDF', templates).length).toBe(1);
			expect(searchTemplates('William', templates).length).toBe(2);
			expect(searchTemplates('Core', templates).length).toBe(5);
			expect(searchTemplates('Zadani', templates).length).toBe(1);
			expect(searchTemplates('Old', templates).length).toBe(1);
		});
	});

	describe('selector', () => {
		test('check we get the correct results back from the actual reselect function', () => {
			const selectorFn = selector as unknown as (
				state: unknown
			) => TemplateItem[];

			/* check the sort works */
			let list = selectorFn({ list: templates, search: '', activeTemplate: '' });

			expect(list[0].id).toBe('adelade');

			/* check the search works */
			list = selectorFn({ list: templates, search: 'default', activeTemplate: '' });

			expect(list.length).toBe(3);

			/* check the sort and search works */
			list = selectorFn({ list: templates, search: 'core', activeTemplate: 'zadani' });
			const checkforLast = list.length - 1;

			expect(list.length).toBe(5);
			expect(list[0].id).toBe('zadani');
			expect(list[checkforLast].id).toBe('rubix');
		});
	});
});
