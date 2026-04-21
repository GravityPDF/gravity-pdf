/* Dependencies */

/**
 * Contains stateless React components for our Single Template
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface CurrentTemplateProps {
	isCurrentTemplate?: boolean;
	label?: string;
}

export const CurrentTemplate = ({
	isCurrentTemplate,
	label,
}: CurrentTemplateProps) => {
	return isCurrentTemplate ? (
		<span data-test="component-currentTemplate" className="current-label">
			{label}
		</span>
	) : (
		<span />
	);
};

interface NameProps {
	name?: string;
	version?: string;
	versionLabel?: string;
}

export const Name = ({ name, version, versionLabel }: NameProps) => (
	<h2 data-test="component-name" className="theme-name">
		{name}

		<Version version={version} label={versionLabel} />
	</h2>
);

interface VersionProps {
	label?: string;
	version?: string;
}

export const Version = ({ label, version }: VersionProps) => {
	return version ? (
		<span data-test="component-version" className="theme-version">
			{label}: {version}
		</span>
	) : (
		<span />
	);
};

interface AuthorProps {
	author?: string;
	uri?: string;
}

export const Author = ({ author, uri }: AuthorProps) => {
	if (uri) {
		return (
			<p data-test="component-author" className="theme-author">
				<a href={uri}>{author}</a>
			</p>
		);
	}
	return (
		<p data-test="component-author" className="theme-author">
			{author}
		</p>
	);
};

interface GroupProps {
	label?: string;
	group?: string;
}

export const Group = ({ label, group }: GroupProps) => (
	<p data-test="component-group" className="theme-author">
		<strong>
			{label}: {group}
		</strong>
	</p>
);

interface DescriptionProps {
	desc?: string;
}

export const Description = ({ desc }: DescriptionProps) => (
	<p data-test="component-description" className="theme-description">
		{desc}
	</p>
);

interface TagsProps {
	label?: string;
	tags?: string;
}

export const Tags = ({ label, tags }: TagsProps) => {
	return tags ? (
		<p data-test="component-tags" className="theme-tags">
			<span>{label}:</span> {tags}
		</p>
	) : (
		<span />
	);
};
