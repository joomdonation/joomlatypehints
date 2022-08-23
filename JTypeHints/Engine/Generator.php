<?php
/**
 * @package   JTypeHints
 * @copyright Copyright (c) 2017-2022 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Akeeba\JTypeHints\Engine;

class Generator
{
	/**
	 * The PHP parser
	 *
	 * @var   Parser
	 */
	private $parser;

	/**
	 * Joomla! version or installation these files are generated for
	 *
	 * @var   string
	 */
	private $generatedFor = '';

	/**
	 * Should I overwrite existing files?
	 *
	 * @var   bool
	 */
	private $overwrite = true;

	/**
	 * Generator constructor.
	 *
	 * @param   Parser  $parser  The classmap.php parser we're going to be using
	 */
	public function __construct(Parser $parser)
	{
		$this->parser = $parser;
	}

	/**
	 * Set the "generated for" string
	 *
	 * @param   string  $generatedFor
	 *
	 * @return  Generator
	 */
	public function setGeneratedFor(string $generatedFor): Generator
	{
		$this->generatedFor = $generatedFor;

		return $this;
	}

	/**
	 * Set the overwite existing files flag
	 *
	 * @param   bool  $overwrite
	 *
	 * @return  Generator
	 */
	public function setOverwrite(bool $overwrite)
	{
		$this->overwrite = $overwrite;

		return $this;
	}

	/**
	 * Generate typehint class files
	 *
	 * @param   string  $outputFolder  The folder where the typehint classes will be generated in
	 *
	 * @return  void
	 */
	public function generate(string $outputFolder)
	{
		$map      = $this->parser->getMap();
		$versions = $this->parser->getMaxVersionMap();

		foreach ($map as $oldClass => $newClass)
		{
			$maxVersion          = isset($versions[$oldClass]) ? $versions[$oldClass] : '4.0';
			$oldClassForFilename = str_replace('\\', '_', trim($oldClass, '\\'));
			$filePath            = $outputFolder . '/' . $oldClassForFilename . '.php';
			$fileContent         = $this->generateFakeClass($oldClass, $newClass, $maxVersion);

			if (!$this->overwrite && @file_exists($filePath))
			{
				continue;
			}

			@file_put_contents($filePath, $fileContent);
		}
	}

	/**
	 * Generate a fake class file
	 *
	 * @param   string  $oldClass    The old class name (placed on the left hand side of extends)
	 * @param   string  $newClass    The new class name (placed on the right hand side of extends)
	 * @param   string  $maxVersion  Optional. The Joomla! version at which it's deprecated. Default: 4.0
	 *
	 * @return  string  The contents of the PHP class file
	 */
	private function generateFakeClass(string $oldClass, string $newClass, string $maxVersion = '4.0'): string
	{
		$since = '';

		if (!$this->overwrite && substr($this->generatedFor, 0, 8) == 'version ')
		{
			$sinceVersion = substr($this->generatedFor, 8);
			$since        = "@since       {$sinceVersion}\n * ";
		}

		$content = <<< PHP
<?php
/**
 * This file is only required for type-hinting in your IDE when using a modern version of Joomla! which has replaced
 * core API classes with namespaced ones.
 *
 * Please keep in mind that Joomla! $maxVersion and later will no longer include $oldClass.
 *
 * This file was automatically generated by 'TypeHint Helper for Joomla!' (https://github.com/nikosdion/joomlatypehints)
 * for Joomla! {$this->generatedFor}
 *
 * {$since}@deprecated  $maxVersion
 */
class $oldClass extends $newClass {}
PHP;

		return $content;
	}
}