<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

if ( ! class_exists( 'GP_Field_Nested_Form' ) ) {
	class GP_Field_Nested_Form extends \GF_Field {
		public $type = 'form';
	}
}
