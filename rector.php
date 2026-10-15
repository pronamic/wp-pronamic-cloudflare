<?php
/**
 * Rector
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\WordPressCloudflare
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/php',
		]
	)
	->withPhpSets()
	->withTypeCoverageLevel( 0 )
	->withDeadCodeLevel( 0 )
	->withCodeQualityLevel( 0 );
