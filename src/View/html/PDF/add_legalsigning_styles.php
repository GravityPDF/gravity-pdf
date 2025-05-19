<?php

/**
 * The styles needed to display the Legal Signing for Gravity Forms plugin fields
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.10
 */

/* Exit if accessed directly */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<style>
  .legalsigning-field-signature__signed {
	background: #FFF;
	padding: 10px;
	width: 400px;
	border-radius: 3px;
  }

  .legalsigning-field-signature__signed-wrapper {
	border: 1px solid rgba(11, 16, 51, 0.1);
	border-radius: 10px;
	padding: 6px 18px;
  }

  .legalsigning-field-signature__signed-signature {
	font-size: 38px;
	line-height: 55px;
	text-align: center;
	vertical-align: middle;
  }

  .legalsigning-field-signature__signed-by,
  .legalsigning-field-signature__signed-verification {
	text-align: center;
  }

  .legalsigning-field-signature__signed-by {
	font-size: 7pt;
	text-transform: uppercase;
	letter-spacing: 0.92px;
	margin-bottom: -7px;
	z-index: 1;
  }

  .legalsigning-field-signature__signed-verification {
	font-size: 8pt;
	margin-top: -8px;
  }

  .legalsigning-field-signature__signed-by--inner,
  .legalsigning-field-signature__signed-verification--inner {
	border: 5px solid #FFF;
	border-top: 0;
	border-bottom: 0;
	background: #FFF;
  }

  /* Text Signatures */
  .legalsigning-field-signature__signed-signature--caveat {
	font-family: "Caveat", caveat, cursive;
  }

  .legalsigning-field-signature__signed-signature--dancing-script {
	font-family: "Dancing Script", dancing-script, cursive;
  }

  .legalsigning-field-signature__signed-signature--homemade-apple {
	font-family: "Homemade Apple", homemade-apple, cursive;
  }

  .legalsigning-field-signature__signed-signature--permanent-marker {
	font-family: "Permanent Marker", permanent-marker, cursive;
  }

  .legalsigning-field-signature__signed-signature--rock-salt {
	font-family: "Rock Salt", rock-salt, cursive;
  }
</style>
