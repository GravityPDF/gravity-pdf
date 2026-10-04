<?php

// `composer rector` is a dry run. Phase 1 applies one rule per PR: `bash tools/rector/rector.sh --only=<RuleClass>`.

use Rector\Config\RectorConfig;

$root = dirname( __DIR__, 2 );

return RectorConfig::configure()
	->withPaths(
		[
			$root . '/pdf.php',
			$root . '/api.php',
			$root . '/gravity-pdf-updater.php',
			$root . '/src',
		]
	)
	->withCache( $root . '/tmp/rector' )
	->withPhpSets( php74: true );
