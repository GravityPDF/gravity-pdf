<?php

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Monolog\Processor\ProcessorInterface;

/**
 * Redact sensitive information from MonoLog context
 *
 * @package   RedactSensitive
 * @license   MIT License
 * @copyright Copyright (c) 2021 Leo Cavalcante
 * @link      https://github.com/leocavalcante/redact-sensitive/
 */
class Redact_Processor implements ProcessorInterface {

	/**
	 * @var string The default replacement character.
	 */
	public const DEFAULT_REPLACEMENT = '*';

	/**
	 * @var array
	 */
	protected $sensitiveKeys;

	/**
	 * @var string
	 */
	protected $replacement;

	/**
	 * @var string
	 */
	protected $template;

	/**
	 * @var int|null
	 */
	protected $lengthLimit;

	/**
	 * Creates a new RedactSensitiveProcessor instance.
	 *
	 * @param array    $sensitiveKeys Keys that should trigger the redaction.
	 * @param string   $replacement   The replacement character.
	 * @param string   $template      Template for replacement characters.
	 * @param int|null $lengthLimit   Max length after redaction.
	 */
	public function __construct( $sensitiveKeys, $replacement = self::DEFAULT_REPLACEMENT, $template = '%s', $lengthLimit = null ) {
		$this->sensitiveKeys = $sensitiveKeys;
		$this->replacement   = $replacement;
		$this->template      = $template;
		$this->lengthLimit   = $lengthLimit;
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 */
	public function __invoke( array $record ) {
		$record['context'] = $this->traverseArr( $record['context'], $this->sensitiveKeys );

		return $record;
	}

	/**
	 * @param string $value
	 * @param int|string $length
	 *
	 * @return string
	 */
	protected function redact( $value, $length ) {
		$valueLength = strlen( $value );

		if ( $valueLength === 0 ) {
			return $value;
		}

		/* If $length not an integer, treat it as a pattern that needs to match to redact */
		if ( is_string( $length ) ) {
			/* ignore if regex not found */
			$has_match = preg_match( $length, $value, $match );
			if ( ! $has_match ) {
				return $value;
			}

			$hiddenLength = strlen( $match[0] );
			$hidden       = str_repeat( $this->replacement, $hiddenLength );
			$placeholder  = sprintf( $this->template, $hidden );
			$result       = str_replace( $match[0], $placeholder, $value );
		} else {
			$hiddenLength = $valueLength - abs( $length );
			$hidden       = str_repeat( $this->replacement, $hiddenLength );
			$placeholder  = sprintf( $this->template, $hidden );

			$result = substr_replace( $value, $placeholder, max( 0, $length ), $hiddenLength );
		}

		/* If no length limit return the string as-is */
		if ( ! is_integer( $this->lengthLimit ) ) {
			return $result;
		}

		return $length > 0
			? substr( $result, 0, $this->lengthLimit )
			: substr( $result, -$this->lengthLimit );
	}

	/**
	 * @param int|string   $key
	 * @param array|object $value
	 * @param array|int    $keys
	 *
	 * @return array|object
	 */
	protected function traverse( $key, $value, $keys ) {
		if ( is_array( $value ) ) {
			return $this->traverseArr( $value, $keys );
		}

		if ( is_object( $value ) ) {
			return $this->traverseObj( $value, $keys );
		}

		/* unknown type, skip */
		return $value;
	}

	/**
	 * @param array $arr
	 * @param array $keys
	 *
	 * @return array
	 */
	protected function traverseArr( $arr, $keys ) {
		foreach ( $arr as $key => $value ) {
			if ( is_scalar( $value ) ) {
				if ( array_key_exists( $key, $keys ) ) {
					$arr[ $key ] = $this->redact( (string) $value, $keys[ $key ] );
				}
				continue;
			}

			if ( array_key_exists( $key, $keys ) && is_array( $keys[ $key ] ) ) {
				$arr[ $key ] = $this->traverse( $key, $value, $keys[ $key ] );
			} elseif ( null !== $value ) {
				$arr[ $key ] = $this->traverse( $key, $value, $keys );
			}
		}

		return $arr;
	}

	/**
	 * @param object $obj
	 * @param array  $keys
	 *
	 * @return object
	 */
	protected function traverseObj( $obj, $keys ) {
		foreach ( get_object_vars( $obj ) as $key => $value ) {
			if ( is_scalar( $value ) ) {
				if ( array_key_exists( $key, $keys ) ) {
					$obj->{$key} = $this->redact( (string) $value, $keys[ $key ] );
				}

				continue;
			}

			if ( array_key_exists( $key, $keys ) && is_array( $keys[ $key ] ) ) {
				$obj->{$key} = $this->traverse( $key, $value, $keys[ $key ] );
			} elseif ( null !== $value ) {
				$obj->{$key} = $this->traverse( $key, $value, $keys );
			}
		}

		return $obj;
	}
}
