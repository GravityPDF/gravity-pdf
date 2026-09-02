<?php
/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace GravityPDF\Sniffs\EarlyLoad;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Forbid unguarded use of GPDFAPI in the files pdf.php loads before it checks the plugin's minimum requirements.
 *
 * Those files (the plugin updater, the activation hooks) run so update and license checks keep working on a site that
 * cannot run the plugin itself. GPDFAPI is only declared by src/bootstrap.php, which is reached only when every
 * requirement passes, so calling it from there fatals for the users who need the updater most.
 *
 * @see https://github.com/GravityPDF/gravity-pdf/issues/1703
 *
 * @since 6.17.0
 */
class GPDFAPIUsageSniff implements Sniff {

	/**
	 * The class that isn't loaded yet
	 */
	private const API_CLASS = 'GPDFAPI';

	/**
	 * The tokens that open a new variable scope, and so a new place a guard has to be repeated
	 */
	private const FUNCTION_TOKENS = [ T_FUNCTION, T_CLOSURE, T_FN ];

	/**
	 * @return array
	 */
	public function register() {
		return [ T_STRING ];
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( $tokens[ $stackPtr ]['content'] !== self::API_CLASS ) {
			return;
		}

		if ( $this->has_class_exists_guard( $phpcsFile, $stackPtr ) ) {
			return;
		}

		$phpcsFile->addError(
			'%1$s is not declared until Gravity PDF has fully loaded, which never happens when the plugin\'s minimum requirements are not met. Guard the call with class_exists( \'%1$s\' ), or do without it so updates keep working.',
			$stackPtr,
			'Unguarded',
			[ self::API_CLASS ]
		);
	}

	/**
	 * Whether a class_exists( 'GPDFAPI' ) check precedes $stackPtr in the same function
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	private function has_class_exists_guard( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		/* A guard in another function says nothing about this one, so only search the enclosing scope */
		$start = 0;
		foreach ( array_reverse( $tokens[ $stackPtr ]['conditions'], true ) as $scopePtr => $code ) {
			if ( in_array( $code, self::FUNCTION_TOKENS, true ) && isset( $tokens[ $scopePtr ]['scope_opener'] ) ) {
				$start = $tokens[ $scopePtr ]['scope_opener'];
				break;
			}
		}

		for ( $i = $start; $i < $stackPtr; $i++ ) {
			if ( $tokens[ $i ]['code'] !== T_STRING || $tokens[ $i ]['content'] !== 'class_exists' ) {
				continue;
			}

			$opener = $phpcsFile->findNext( T_WHITESPACE, $i + 1, null, true );
			if ( $opener === false || $tokens[ $opener ]['code'] !== T_OPEN_PARENTHESIS ) {
				continue;
			}

			$argument = $phpcsFile->findNext( T_WHITESPACE, $opener + 1, $tokens[ $opener ]['parenthesis_closer'], true );
			if ( $argument !== false && trim( $tokens[ $argument ]['content'], '\'"\\' ) === self::API_CLASS ) {
				return true;
			}
		}

		return false;
	}
}
