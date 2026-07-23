<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Monolog\Processor\ProcessorInterface;

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
 * Monolog processor that strips secrets and neutralises log-injection before a record is formatted.
 *
 * Gravity PDF logs licensing keys and signed EDD package/download URLs, so redaction is a real requirement.
 * Two rules a purely keyed model can't satisfy:
 *
 *  - Pattern-based message redaction. The message is a free-form sprintf-baked string with no key to match on, so a
 *    secret interpolated into it ("License $key rejected", a signed URL in an error, a Bearer token echoed from an
 *    HTTP failure) is invisible to key redaction. So the message body is regex-scrubbed: license/hex shapes,
 *    bearer/OAuth/secret-key tokens, and URL query strings (which carry signed-link secrets) are masked.
 *  - Keyed, recursive context redaction. A default deny-key set covers what the plugin actually emits, recursing into
 *    nested arrays and objects so ['response']['headers']['authorization'] is caught. String leaves under non-deny
 *    keys are additionally scrub()'d (URL-query blanking + secret patterns), so a raw non-JSON API body or
 *    string-encoded request body stored under a benign key ('response'/'body') can't leak a signed URL or echoed key.
 *    The gfpdf_logging_redact_keys filter (passed the logger slug) may only add keys (merged over the defaults so a
 *    host can't weaken them).
 *
 * Log-injection and CRLF forging: a newline in user-controlled message data would forge a second well-formed log line,
 * so message() collapses \r\n|\r|\n to a space and strips non-printable control chars before redacting (the GF
 * LineFormatter runs with allowInlineLineBreaks=false). Context is JSON-encoded to one line by the formatter, so it's
 * safe.
 *
 * @since 6.16.0
 */
class Redact_Processor implements ProcessorInterface {

	private const REPLACEMENT = '[redacted]';

	/* Bound recursion so a circular or pathologically-deep context graph can't exhaust the stack (DoS). */
	private const MAX_DEPTH = 10;

	/**
	 * Default deny-keys (lower-case) for keyed context redaction: what the plugin actually emits, not just licensing.
	 */
	private const DEFAULT_KEYS = [
		'license',
		'edd_license_key',
		'package',
		'download_link',
		'access_token',
		'refresh_token',
		'authorization',
		'password',
		'secret',
		'token',
		'cookie',
		'set-cookie',
		'nonce',
	];

	/**
	 * Message-body patterns masked wherever they appear.
	 *
	 * @var list<string>
	 */
	private const PATTERNS = [
		'/\b[0-9a-f]{28,40}\b/i', // 28/32/40-hex license / signature shapes
		'/Bearer\s+\S+/i',        // bearer tokens
		'/ya29\.[\w\-]+/',        // Google OAuth access tokens
		'/sk_[A-Za-z0-9]+/',      // Stripe-style secret keys
	];

	/**
	 * The effective deny-keys as a lower-cased lookup map (defaults plus any added via the filter), for O(1) matching.
	 *
	 * @var array<string, true>
	 */
	private $keys;

	/**
	 * @param string $slug The logger slug, used to scope the redact-keys filter.
	 *
	 * @since 6.16.0
	 */
	public function __construct( string $slug ) {
		$added = array_filter( (array) apply_filters( 'gfpdf_logging_redact_keys', self::DEFAULT_KEYS, $slug ), 'is_string' );

		$keys = array_merge( self::DEFAULT_KEYS, array_map( 'strtolower', $added ) );

		/* Flip to a lookup map so context() matches keys in O(1) on the per-record path; keys dedupe for free */
		$this->keys = array_fill_keys( $keys, true );
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 *
	 * @since 6.16.0
	 */
	public function __invoke( array $record ) {
		$record['message'] = $this->message( $record['message'] );
		$record['context'] = $this->context( $record['context'] );

		return $record;
	}

	/**
	 * Normalise (anti log-forging) then pattern-redact the free-form message body.
	 *
	 * @param string $message
	 *
	 * @return string
	 *
	 * @since 6.16.0
	 */
	public function message( string $message ): string {
		/* Collapse line breaks and strip control chars before redacting (Monolog allowInlineLineBreaks=false). */
		$message = (string) preg_replace( '/\r\n|\r|\n/', ' ', $message );
		$message = (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message );

		return $this->scrub( $message );
	}

	/**
	 * Mask secrets in a string: blank URL query strings (signed update/storage links carry secrets there — keep the
	 * path, drop ?…) then apply the token/hex patterns.
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @since 6.16.0
	 */
	private function scrub( string $value ): string {
		$value = (string) preg_replace( '#(https?://[^\s?]+)\?\S*#i', '$1?', $value );

		return (string) preg_replace( self::PATTERNS, self::REPLACEMENT, $value );
	}

	/**
	 * Keyed-redact a context array, recursing into nested arrays and objects up to a bounded depth.
	 *
	 * @param array<array-key, mixed> $context
	 * @param int                     $depth   Current recursion depth (internal).
	 *
	 * @return array<array-key, mixed>
	 *
	 * @since 6.16.0
	 */
	public function context( array $context, int $depth = 0 ): array {
		$out = [];

		foreach ( $context as $key => $value ) {
			if ( is_string( $key ) && isset( $this->keys[ strtolower( $key ) ] ) ) {
				$out[ $key ] = self::REPLACEMENT;
				continue;
			}

			if ( is_array( $value ) || is_object( $value ) ) {
				$out[ $key ] = $depth < self::MAX_DEPTH
					? $this->context( (array) $value, $depth + 1 )
					: self::REPLACEMENT;
			} elseif ( is_string( $value ) ) {
				/* Scrub string leaves too (a raw API body under a benign key like 'response' can carry a signed URL/key
				   keyed redaction misses); scrub() not message() — the formatter one-lines context, so normalization is moot */
				$out[ $key ] = $this->scrub( $value );
			} else {
				$out[ $key ] = $value;
			}
		}

		return $out;
	}
}
