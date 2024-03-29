<?php
/**
 * @package   JTypeHints
 * @copyright Copyright (c) 2017-2023 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Akeeba\JTypeHints\Command;

use Symfony\Component\Console\Output\OutputInterface;

class Rector extends Collect
{
	public function __invoke($folder, OutputInterface $output)
	{
		$filePath = __DIR__ . '/../../classmapstats.json';
		$stats    = $this->loadClassmapStats($filePath);

		if (empty($stats))
		{
			$output->writeln("Please remember to run the collect command first");

			return;
		}

		$lastResult = [];

		for ($major = 3; $major <= 4; $major++)
		{
			for ($minor = 0; $minor <= 99; $minor++)
			{
				$version = "$major.$minor.0";
				$map     = $this->filterForVersion($stats, $version);

				if ($map == $lastResult)
				{
					continue;
				}

				$lastResult = $map;
				$fileName   = "joomla_{$major}_{$minor}.php";

				$lines = "";
				foreach ($map as $old => $new)
				{
					$lines .= sprintf("\t\t\t'%s' => '%s',\n", ltrim($old, '\\'), ltrim($new, '\\'));
				}
				$lines = trim($lines);

				$php = <<< PHP
<?php
/**
 * @package   JTypeHints
 * @copyright Copyright (c) 2017-2023 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

/**
 * Rector 0.14 configuration for converting legacy Joomla! classes to namespaced ones, compatible with Joomla! {$major}.{$minor}
 */
return static function (RectorConfig \$rectorConfig): void {
	\$rectorConfig->ruleWithConfiguration(
		RenameClassRector::class,
		[

{$lines}

		]
	);
};

PHP;

				file_put_contents($folder . '/' . $fileName, $php);

				$output->writeln(sprintf("Joomla! %s.%s rector written to %s", $major, $minor, $fileName));
			}
		}
	}

	private function filterForVersion(array $stats, $version)
	{
		$ret = [];

		foreach ($stats as $oldClass => $record)
		{
			if (version_compare($record['min'], $version, 'gt'))
			{
				continue;
			}

			$ret[$oldClass] = $record['new'];
		}

		return $ret;
	}
}