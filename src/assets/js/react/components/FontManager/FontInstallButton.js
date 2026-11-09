/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { installFont as installFontAction } from '../../actions/fontManager';
/* Components */
import Spinner from '../Spinner';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Downloads a core font. One at a time, as a font can take a while.
 *
 * @param { Object }      props
 * @param { string }      props.id
 * @param { null|string } props.installingFont
 * @param { Function }    props.installFont
 *
 * @return { JSX.Element } FontInstallButton component
 *
 * @since 6.19.0
 */
export const FontInstallButton = ({ id, installingFont, installFont }) => {
	if (installingFont === id) {
		return <Spinner style="delete-font" />;
	}

	return (
		<button
			type="button"
			data-test="font-install"
			className="button button-primary"
			disabled={installingFont !== null}
			onClick={() => installFont(id)}
		>
			{GFPDF.fontManagerInstall}
		</button>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontInstallButton.propTypes = {
	id: PropTypes.string.isRequired,
	installingFont: PropTypes.string,
	installFont: PropTypes.func.isRequired,
};

const mapStateToProps = (state) => ({
	installingFont: state.fontManager.installingFont,
});

export default connect(mapStateToProps, { installFont: installFontAction })(
	FontInstallButton
);
