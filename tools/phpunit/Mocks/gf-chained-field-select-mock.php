<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

if ( ! class_exists( 'GF_Chained_Field_Select' ) ) {
	class GF_Chained_Field_Select extends \GF_Field {
		public $type = 'chainedselect';
	}
}
