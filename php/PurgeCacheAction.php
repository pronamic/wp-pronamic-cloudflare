<?php
/**
 * Purge cache action
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPress\CloudflarePlugin
 */

namespace Pronamic\WordPressCloudflare;

/**
 * Purge cache action class
 */
final class PurgeCacheAction {
	/**
	 * Type.
	 * 
	 * @var string
	 */
	public $type;

	/**
	 * Files.
	 * 
	 * @var string[]
	 */
	public $files;

	/**
	 * Cosntruct purge cache action.
	 * 
	 * @param string $type      Type.
	 * @param array  $files     Files.
	 */
	public function __construct( $type, $files ) {
		$this->type  = $type;
		$this->files = $files;
	}

	/**
	 * Get timestamp.
	 * 
	 * @return int
	 */
	public function get_timestamp() {
		switch ( $this->type ) {
			case 'home':
			case 'post':
				return time();
			case 'post-archive':
				return time() + ( 5 * MINUTE_IN_SECONDS );
			case 'post-archive-feed':
				return time() + ( 1 * HOUR_IN_SECONDS );
			case 'post-archive-pages':
				return time() + ( 10 * MINUTE_IN_SECONDS );
			case 'post-comments-feed':
				return time() + ( 1 * HOUR_IN_SECONDS );
			case 'term':
				return time() + ( 5 * MINUTE_IN_SECONDS );
			case 'term-feed':
				return time() + ( 1 * HOUR_IN_SECONDS );
			case 'term-pages':
				return time() + ( 10 * MINUTE_IN_SECONDS );
			case 'author':
				return time() + ( 10 * MINUTE_IN_SECONDS );
			case 'feeds':
				return time() + ( 1 * HOUR_IN_SECONDS );
			default:
				return time() + ( 1 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Get priority.
	 * 
	 * @return int
	 */
	public function get_priority() {
		switch ( $this->type ) {
			case 'home':
			case 'post':
				return 10;
			case 'post-archive':
				return 20;
			case 'post-archive-feed':
				return 20;
			case 'post-archive-pages':
				return 20;
			case 'post-comments-feed':
				return 40;
			case 'term':
				return 20;
			case 'term-feed':
				return 40;
			case 'term-pages':
				return 30;
			case 'author':
				return 30;
			case 'feeds':
				return 40;
			default:
				return 50;
		}
	}
}
