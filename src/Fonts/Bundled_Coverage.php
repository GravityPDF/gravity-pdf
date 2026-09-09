<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What the bundled faces can draw, as one character class
 *
 * GENERATED — do not edit. Run `php tools/fonts/generate-bundled-coverage.php` after changing `fonts/`.
 *
 * Read from the faces' own cmaps because a hand-written approximation gets it wrong in the direction that
 * hides misses: Arimo has nothing in the Arabic presentation forms (U+FB50–FDFF) and nothing in halfwidth
 * Katakana, so a class written from block names would silently cover both.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Bundled_Coverage {

	/**
	 * Matches any code point the bundled faces cannot draw
	 *
	 * @since 7.0
	 */
	public const UNCOVERED = '/[^\\x{0000}-\\x{0377}\\x{037A}-\\x{037F}\\x{0384}-\\x{038A}\\x{038C}\\x{038E}-\\x{03A1}\\x{03A3}-\\x{052F}\\x{0591}-\\x{05C7}\\x{05D0}-\\x{05EA}\\x{05F0}-\\x{05F4}\\x{1AB0}-\\x{1ABE}\\x{1C80}-\\x{1C88}\\x{1D00}-\\x{1DF5}\\x{1DFB}-\\x{1F15}\\x{1F18}-\\x{1F1D}\\x{1F20}-\\x{1F45}\\x{1F48}-\\x{1F4D}\\x{1F50}-\\x{1F57}\\x{1F59}\\x{1F5B}\\x{1F5D}\\x{1F5F}-\\x{1F7D}\\x{1F80}-\\x{1FB4}\\x{1FB6}-\\x{1FC4}\\x{1FC6}-\\x{1FD3}\\x{1FD6}-\\x{1FDB}\\x{1FDD}-\\x{1FEF}\\x{1FF2}-\\x{1FF4}\\x{1FF6}-\\x{1FFE}\\x{2000}-\\x{2064}\\x{2066}-\\x{2071}\\x{2074}-\\x{208E}\\x{2090}-\\x{209C}\\x{20A0}-\\x{20BE}\\x{20F0}\\x{2100}-\\x{214F}\\x{2153}-\\x{2154}\\x{215B}-\\x{215E}\\x{2184}\\x{2190}-\\x{2311}\\x{2318}-\\x{2319}\\x{231C}-\\x{2321}\\x{2324}-\\x{2328}\\x{232B}-\\x{232C}\\x{2373}-\\x{2375}\\x{237A}\\x{237D}\\x{2387}\\x{2394}\\x{239B}-\\x{23AE}\\x{23CE}-\\x{23CF}\\x{23E3}\\x{23E5}\\x{23E8}\\x{2460}-\\x{2469}\\x{2500}-\\x{269C}\\x{26A0}-\\x{26B8}\\x{26C0}-\\x{26C3}\\x{26E2}\\x{2701}-\\x{2704}\\x{2706}-\\x{2709}\\x{270C}-\\x{2727}\\x{2729}-\\x{274B}\\x{274D}\\x{274F}-\\x{2752}\\x{2756}\\x{2758}-\\x{275E}\\x{2761}-\\x{2794}\\x{2798}-\\x{27AF}\\x{27B1}-\\x{27BE}\\x{2B00}-\\x{2B1A}\\x{2B1F}-\\x{2B24}\\x{2B53}-\\x{2B54}\\x{2C60}-\\x{2C7F}\\x{2DE0}-\\x{2E42}\\x{A640}-\\x{A69F}\\x{A717}-\\x{A7AE}\\x{A7B0}-\\x{A7B7}\\x{A7F7}-\\x{A7FF}\\x{AB30}-\\x{AB65}\\x{F001}-\\x{F002}\\x{F005}\\x{F00A}-\\x{F00E}\\x{FB01}-\\x{FB04}\\x{FB1D}-\\x{FB36}\\x{FB38}-\\x{FB3C}\\x{FB3E}\\x{FB40}-\\x{FB41}\\x{FB43}-\\x{FB44}\\x{FB46}-\\x{FB4F}\\x{FE20}-\\x{FE2F}\\x{FEFF}\\x{FFFC}-\\x{FFFD}]/u';
}
