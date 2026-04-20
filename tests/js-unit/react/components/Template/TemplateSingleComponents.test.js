import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import {
	CurrentTemplate,
	Name,
	Version,
	Author,
	Group,
	Description,
	Tags,
} from '../../../../../src/assets/js/react/components/Template/TemplateSingleComponents';

describe('Template - TemplateSingleComponents.js', () => {
	test('renders <CurrentTemplate /> component', () => {
		const { container } = render(
			<CurrentTemplate isCurrentTemplate={true} label="text" />
		);
		const component = findByTestAttr(
			container,
			'component-currentTemplate'
		);
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('text');
	});

	test('renders <Name /> component', () => {
		const { container } = render(
			<Name name="nameText" version="4" versionLabel="versionLabelText" />
		);
		expect(findByTestAttr(container, 'component-name')).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-version')
		).toBeInTheDocument();
	});

	test('renders <Version /> component', () => {
		const { container } = render(<Version version="4" label="labelText" />);
		const component = findByTestAttr(container, 'component-version');
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('labelText: 4');
	});

	test('renders <Author /> component', () => {
		const { container } = render(<Author author="authorText" />);
		const component = findByTestAttr(container, 'component-author');
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('authorText');
	});

	test('renders <Author /> component with link', () => {
		const { container } = render(
			<Author author="authorText" uri="uriContent" />
		);
		const component = findByTestAttr(container, 'component-author');
		expect(component).toBeInTheDocument();
		expect(container.querySelector('a')).toBeInTheDocument();
		expect(container.querySelector('a').textContent).toBe('authorText');
	});

	test('renders <Group /> component', () => {
		const { container } = render(
			<Group label="labelText" group="groupContent" />
		);
		const component = findByTestAttr(container, 'component-group');
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('labelText: groupContent');
	});

	test('renders <Description /> component', () => {
		const { container } = render(<Description desc="descText" />);
		const component = findByTestAttr(container, 'component-description');
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('descText');
	});

	test('renders <Tags /> component', () => {
		const { container } = render(
			<Tags label="labelText" tags="tagsContent" />
		);
		const component = findByTestAttr(container, 'component-tags');
		expect(component).toBeInTheDocument();
		expect(component.textContent).toBe('labelText: tagsContent');
	});
});
