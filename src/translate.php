<?php declare(strict_types=1);
/**
 * Created L/10/12/2012
 * Updated D/24/12/2023
 *
 * Copyright 2012-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://github.com/luigifab/translator
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

chdir(__DIR__);
error_reporting(E_ALL);
ini_set('display_errors', (PHP_VERSION_ID < 80100) ? '1' : 1);

date_default_timezone_set('UTC');
header('Content-Type: text/plain; charset=utf-8');

if (PHP_SAPI != 'cli')
	exit(-1);

class Translate {

	public const VERSION = '1.5.0';

	public function run(array $argv) {

		global $openMageDefault;
		global $updateTranslationOpenMageModule;
		global $updateTranslationRedminePlugin;
		global $updateTranslationDolibarrModule;
		global $updateTranslationPo;
		global $updateTranslationApijs;
		global $updateTranslationWebsite;

		$all = in_array('all', $argv);

		if (!empty($updateTranslationOpenMageModule) && ($all || in_array('openmage-module', $argv) || in_array('openmage-modules', $argv))) {

			$files = glob($openMageDefault, SCANDIR_SORT_NONE);
			$ignoreStrings = $this->loadCSV($files);

			foreach ($updateTranslationOpenMageModule as $config) {
				echo 'updateTranslationOpenMageModule: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationOpenMageModule($ignoreStrings, $config);
			}
		}

		if (!empty($updateTranslationRedminePlugin) && ($all || in_array('redmine-plugin', $argv) || in_array('redmine-plugins', $argv))) {
			foreach ($updateTranslationRedminePlugin as $config) {
				echo 'updateTranslationRedminePlugin: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationRedminePlugin([], $config);
			}
		}

		if (!empty($updateTranslationDolibarrModule) && ($all || in_array('dolibarr-module', $argv) || in_array('dolibarr-modules', $argv))) {
			foreach ($updateTranslationDolibarrModule as $config) {
				echo 'updateTranslationDolibarrModule: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationDolibarrModule([], $config);
			}
		}

		if (!empty($updateTranslationPo) && ($all || in_array('po', $argv))) {
			foreach ($updateTranslationPo as $config) {
				echo 'updateTranslationPo: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationPo([], $config);
			}
		}

		if (!empty($updateTranslationApijs) && ($all || in_array('apijs', $argv))) {
			foreach ($updateTranslationApijs as $config) {
				echo 'updateTranslationApijs: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationApijs([], $config);
			}
		}

		if (!empty($updateTranslationWebsite) && ($all || in_array('custom', $argv))) {
			foreach ($updateTranslationWebsite as $config) {
				echo 'updateTranslationWebsite: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) {
					echo "fatal: ",$config['dir']," does not exist!\n";
					exit(-1);
				}
				$this->updateTranslationWebsite([], $config);
			}
		}

		return $this;
	}

	public function filterStrings(array $config, array &$sourceStrings, array &$ignoreStrings) {

		// before: sourceStringsBefore, searchAndRead...
		// now: sourceStringsAfter, ignoreStrings, filterStrings

		// add data from config
		if (!empty($config['sourceStringsAfter'])) {
			$sourceStrings = array_merge($sourceStrings, $config['sourceStringsAfter']);
		}

		if (!empty($config['ignoreStrings'])) {
			$ignoreStrings = array_merge($ignoreStrings, $config['ignoreStrings']);
			// don't ignore strings in sourceStringsBefore/sourceStringsAfter
			if (!empty($config['sourceStringsBefore']))
				$ignoreStrings = array_diff($ignoreStrings, $config['sourceStringsBefore']);
			if (!empty($config['sourceStringsAfter']))
				$ignoreStrings = array_diff($ignoreStrings, $config['sourceStringsAfter']);
		}

		// remove ignored strings
		foreach ($sourceStrings as $i => $sourceString) {
			if (($sourceString == ' ') || in_array($sourceString, $ignoreStrings))
				unset($sourceStrings[$i]);
		}

		// keep only strings in filterStrings
		if (!empty($config['filterStrings'])) {
			foreach ($sourceStrings as $i => $sourceString) {
				$remove = true;
				foreach ($config['filterStrings'] as $check) {
					if (mb_stripos($sourceString, $check) === 0) {
						$remove = false;
						break;
					}
				}
				if ($remove)
					unset($sourceStrings[$i]);
			}
		}


		// very important
		$sourceStrings = array_values(array_unique($sourceStrings));

		return $this;
	}

	public function generateApijsEmbed(array $config) {

		$files = [];
		if (is_dir($config['dir'])) {
			exec('find '.$config['dir'].' -name "*.js"', $files);
			sort($files);
		}
		else {
			echo "\033[35m"; // terminal color
			echo ' warn: "',$config['dir'],'" is not a dir';
			echo "\033[0m\n";
		}

		foreach ($files as $file) {

			// load strings to translate from JS
			$key = mb_substr($file, mb_strrpos($file, '/') + 1);

			$template = file_get_contents($file);
			if (mb_stripos($template, '// auto start') === false)
				continue;

			$sourceStrings = $this->loadService($config, 'en_US', $key, $template, [], false, true);
			if ($sourceStrings === false)
				continue;

			echo ' special: ',basename($file),"\n";

			$locales = $config['locales'];
			if (!in_array('en', $locales) && !in_array('en_US', $locales) && !in_array('en-US', $locales))
				$locales[] = 'en_US';

			// load translated strings from TSV service, and from TSV service
			$translatedStrings = [];
			foreach ($locales as $locale) {
				$translatedStrings[$locale] = $this->loadService($config, $locale, $key, $sourceStrings);
			}

			// write final JS file
			$final = $this->generateJS($sourceStrings, $translatedStrings, $template);
			$this->writeFile($file, $final);
		}

		return $this;
	}

	public function updateTranslationOpenMageModule(array $ignoreStrings, array $config) {

		// search strings to translate
		if (empty($config['search'])) {

			$files1 = [];
			$files2 = [];

			if (is_dir($config['dir'])) {

				exec('find '.$config['dir'].'app/ -name "*.xml"', $files1);
				sort($files1);

				exec('find '.$config['dir'].'app/ -name "*.phtml"', $files2);
				exec('find '.$config['dir'].'app/ -name "*.php"', $files2);
				sort($files2);
			}
			else {
				echo "\033[35m"; // terminal color
				echo ' warn: "',$config['dir'],'" is not a dir';
				echo "\033[0m\n";
			}

			if (!empty($config['excludeFiles'])) {
				foreach ($files1 as $idx => $file) {
					if (mb_stripos($file, $config['excludeFiles']) !== false)
						unset($files1[$idx]);
				}
				foreach ($files2 as $idx => $file) {
					if (mb_stripos($file, $config['excludeFiles']) !== false)
						unset($files2[$idx]);
				}
			}
		}
		else {
			$files1 = [];
			$files2 = [];

			foreach ($config['search'] as $file) {
				if (mb_substr($file, -4) == '.xml')
					$files1[] = $config['dir'].$file;
				else
					$files2[] = $config['dir'].$file;
			}
		}

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->searchAndReadXML($sourceStrings, $files1);
		$this->searchAndReadPHP($sourceStrings, $files2);
		$this->filterStrings($config, $sourceStrings, $ignoreStrings);

		// generate CSV
		foreach ($config['locales'] as $locale) {

			// load translated strings from CSV files, and from TSV service
			$file = $config['dir'].'app/locale/'.$locale.'/'.$config['vendor'].'_'.$config['name'].'.csv';
			$translatedStrings = $this->loadCSV([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings);

			// write final CSV file
			$final = $this->generateCSV($sourceStrings, $translatedStrings, empty($config['service']));
			if (!is_dir($config['dir'].'app/locale/'.$locale))
				mkdir($config['dir'].'app/locale/'.$locale, 0755);

			$this->writeOpenMageFile($config, $locale, $file, $final);
		}

		// generate HTML
		$emails = glob($config['dir'].'app/locale/en_US/template/email/*.html');
		foreach ($emails as $email) {

			echo ' email: ',basename($email),"\n";

			// load template and strings to translate from TSV service
			$key = mb_substr($email, mb_strrpos($email, '/') + 1);
			$template = file_get_contents($email);
			$sourceStrings = $this->loadService($config, 'en_US', $key, $template);

			foreach ($config['locales'] as $locale) {

				if (!is_dir($config['dir'].'app/locale/'.$locale.'/template/email/')) {
					echo "\033[35m"; // terminal color
					echo ' warn: app/locale/',$locale,'/template/email/ is not a dir';
					echo "\033[0m\n";
					continue;
				}

				// load translated strings from TSV service
				$translatedStrings = $this->loadService($config, $locale, $key, $sourceStrings);

				// write final HTML file
				$final = $this->generateHTML($sourceStrings, $translatedStrings, $template);
				$lang  = substr($locale, 0, 2); // not mb_substr
				$final = str_replace([' lang="en"', '{{lang}}'], [' lang="'.$lang.'"', $lang], $final);
				$this->writeOpenMageFile($config, $locale, str_replace('en_US', $locale, $email), $final);
			}

			// copy to en_XX HTML files
			foreach (['en_AU', 'en_CA', 'en_GB', 'en_IE', 'en_NZ'] as $to) {
				if (!in_array($to, $config['locales'])) {
					if (!is_dir($config['dir'].'app/locale/'.$to.'/template/email'))
						mkdir($config['dir'].'app/locale/'.$to.'/template/email', 0755, true);
					copy($email, str_replace('en_US', $to, $email));
				}
			}
		}

		// generate JS (apijs magic key)
		$this->generateApijsEmbed($config);

		echo "\n";
		return $this;
	}

	public function updateTranslationRedminePlugin(array $ignoreStrings, array $config) {

		// search strings to translate
		if (empty($config['search'])) {

			$files = [];
			if (is_dir($config['dir'])) {
				exec('find '.$config['dir'].' -name "*.rb"', $files);
				exec('find '.$config['dir'].' -name "*.erb"', $files);
				sort($files);
			}
			else {
				echo "\033[35m"; // terminal color
				echo ' warn: "',$config['dir'],'" is not a dir';
				echo "\033[0m\n";
			}

			if (!empty($config['excludeFiles'])) {
				foreach ($files as $idx => $file) {
					if (mb_stripos($file, $config['excludeFiles']) !== false)
						unset($files[$idx]);
				}
			}
		}
		else {
			$files = [];
			foreach ($config['search'] as $file)
				$files[] = $config['dir'].$file;
		}

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->searchAndReadRB($sourceStrings, $files);
		$this->filterStrings($config, $sourceStrings, $ignoreStrings);

		// generate YML
		foreach ($config['locales'] as $locale) {

			// load translated strings from YML files, and from TSV service
			$file = $config['dir'].'config/locales/'.$locale.'.yml';
			$translatedStrings = $this->loadYML([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings, true);

			// write final CSV file
			$final = $this->generateYML($sourceStrings, $translatedStrings, $locale);
			$this->writeFile($file, $final);
		}

		// generate JS (apijs magic key)
		$this->generateApijsEmbed($config);

		echo "\n";
		return $this;
	}

	public function updateTranslationDolibarrModule(array $ignoreStrings, array $config) {

		// search strings to translate
		if (empty($config['search'])) {

			$files = [];
			if (is_dir($config['dir'])) {
				exec('find '.$config['dir'].' -name "*.phtml"', $files);
				exec('find '.$config['dir'].' -name "*.php"', $files);
				sort($files);
			}
			else {
				echo "\033[35m"; // terminal color
				echo ' warn: "',$config['dir'],'" is not a dir';
				echo "\033[0m\n";
			}

			if (!empty($config['excludeFiles'])) {
				foreach ($files as $idx => $file) {
					if (mb_stripos($file, $config['excludeFiles']) !== false)
						unset($files[$idx]);
				}
			}
		}
		else {
			$files = [];
			foreach ($config['search'] as $file)
				$files[] = $config['dir'].$file;
		}

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->searchAndReadPHP($sourceStrings, $files);
		$this->filterStrings($config, $sourceStrings, $ignoreStrings);

		// generate LANG
		foreach ($config['locales'] as $locale) {

			// load translated strings from YML files, and from TSV service
			$file = $config['dir'].'langs/'.$locale.'/'.strtolower($config['name']).'.lang'; // not mb_strtolower
			if (!is_dir($config['dir'].'langs/'.$locale))
				mkdir($config['dir'].'langs/'.$locale, 0755);

			$translatedStrings = $this->loadLANG([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings, true);

			// write final CSV file
			$final = $this->generateLANG($sourceStrings, $translatedStrings, $locale);
			$this->writeFile($file, $final);
		}

		// generate JS (apijs magic key)
		$config['locales'] = $config['locales2'];
		$this->generateApijsEmbed($config);

		echo "\n";
		return $this;
	}

	public function updateTranslationPo(array $ignoreStrings, array $config) {

		foreach ($config['gettext'] as $cmd) {
			echo ' run: ',$cmd,"\n";
			exec($cmd);
		}

		// search strings to translate
		if (empty($config['search'])) {

			$files = [];
			if (is_dir($config['dir'])) {
				exec('find '.$config['dir'].' -name "*.po"', $files);
				sort($files);
			}
			else {
				echo "\033[35m"; // terminal color
				echo ' warn: "',$config['dir'],'" is not a dir';
				echo "\033[0m\n";
			}

			if (!empty($config['excludeFiles'])) {
				foreach ($files as $idx => $file) {
					if (mb_stripos($file, $config['excludeFiles']) !== false)
						unset($files[$idx]);
				}
			}
		}
		else {
			$files = [];
			foreach ($config['search'] as $file)
				$files[] = $config['dir'].$file;
		}

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->readPo($sourceStrings, $files);
		$this->filterStrings($config, $sourceStrings, $ignoreStrings);

		// generate PO
		foreach ($config['locales'] as $locale) {

			// load translated strings from PO files, and from TSV service
			$file = $config['dir'].$locale.'.po';
			$translatedStrings = $this->loadPO([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings, true);

			// write final PO file
			$final = $this->generatePO($sourceStrings, $translatedStrings, file_get_contents($file));
			$this->writeFile($file, $final);

			// regenerate PO
			foreach ($config['gettext'] as $cmd) {
				if (mb_stripos($cmd, 'msgmerge') !== false) {
					echo ' run: ',$cmd,"\n";
					exec($cmd);
				}
			}
		}

		echo "\n";
		return $this;
	}

	public function updateTranslationApijs(array $ignoreStrings, array $config) {

		// JS (apijs magic key)
		$file = $config['dir'].$config['search'][0];

		{
			// load strings to translate from JS
			$template      = file_get_contents($file);
			$sourceStrings = $this->loadService($config, 'en_US', 'base', $template, [], false, true);

			if ($sourceStrings === false) {
				echo "fatal: sourceStrings not found for APIJS!\n";
				exit(-1);
			}

			// load translated strings from TSV service
			$translatedStrings = [];
			foreach ($config['locales'] as $locale) {
				$translatedStrings[$locale] = $this->loadService($config, $locale, 'base', $sourceStrings);
			}

			// write final JS file
			$final = $this->generateJS($sourceStrings, $translatedStrings, $template, true);
			$this->writeFile($file, $final);
		}

		echo "\n";
		return $this;
	}

	public function updateTranslationWebsite(array $ignoreStrings, array $config) {

		// search strings to translate
		if (empty($config['search'])) {
			echo "fatal: 'search' is required!\n";
			exit(-1);
		}
		else {
			$files = [];
			foreach ($config['search'] as $file)
				$files[] = $config['dir'].$file;
		}

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->searchAndReadPHP($sourceStrings, $files);
		$this->filterStrings($config, $sourceStrings, $ignoreStrings);

		// generate CSV
		foreach ($config['locales'] as $locale) {

			// load translated strings from CSV files, and from TSV service
			$file = $config['dir'].$locale.'.csv';
			$translatedStrings = $this->loadCSV([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings);

			// write final CSV file
			$final = $this->generateCSV($sourceStrings, $translatedStrings);
			$this->writeFile($file, $final);
		}

		echo "\n";
		return $this;
	}


	// CREATE FINAL FILES
	public function writeFile(string $file, string $final) {

		if (empty($final)) {
			if (is_file($file))
				unlink($file);
		}
		else {
			$orig = is_file($file) ? md5_file($file) : 'abc';
			file_put_contents($file, $final);
			echo (($orig != md5_file($file)) ? "\033[36m".realpath($file)."\033[0m\n" : ''); // terminal color
		}

		return $this;
	}

	public function writeOpenMageFile(array $config, string $locale, string $file, string $final) {

		if (empty($final)) {
			if (is_file($file))
				unlink($file);
			foreach ([
				['fr_FR', 'fr_CA'],
				['fr_FR', 'fr_CH'],
				['it_IT', 'it_CH'],
				['de_DE', 'de_CH'],
				['de_DE', 'de_AT'],
				['es_ES', 'es_AR'],
				['es_ES', 'es_CL'],
				['es_ES', 'es_CO'],
				['es_ES', 'es_CR'],
				['es_ES', 'es_MX'],
				['es_ES', 'es_PA'],
				['es_ES', 'es_PE'],
				['es_ES', 'es_VE'],
			] as [$from, $to]) {
				if (($locale == $from) && !in_array($to, $config['locales']) && is_file($file))
					unlink(str_replace($locale, $to, $file));
			}
		}
		else {
			$orig = is_file($file) ? md5_file($file) : 'abc';
			file_put_contents($file, $final);
			echo (($orig != md5_file($file)) ? "\033[36m".realpath($file)."\033[0m\n" : ''); // terminal color
			foreach ([
				['fr_FR', 'fr_CA'],
				['fr_FR', 'fr_CH'],
				['it_IT', 'it_CH'],
				['de_DE', 'de_CH'],
				['de_DE', 'de_AT'],
				['es_ES', 'es_AR'],
				['es_ES', 'es_CL'],
				['es_ES', 'es_CO'],
				['es_ES', 'es_CR'],
				['es_ES', 'es_MX'],
				['es_ES', 'es_PA'],
				['es_ES', 'es_PE'],
				['es_ES', 'es_VE'],
			] as [$from, $to]) {
				if (($locale == $from) && !in_array($to, $config['locales'])) {
					$dir = (stripos($file, 'template/email') === false) ? 'app/locale/'.$to : 'app/locale/'.$to.'/template/email';
					if (!is_dir($config['dir'].$dir))
						mkdir($config['dir'].$dir, 0755, true);
					file_put_contents(str_replace($locale, $to, $file), $final);
				}
			}
		}

		return $this;
	}

	public function generateCSV(array $sourceStrings, array $translatedStrings, bool $noService = false, bool $onlyMissing = false) {

		$data = [];

		foreach ($sourceStrings as $i => $string) {
			if ($noService) {
				if (!empty($translatedStrings[$i]))
					$data[] = '"'.str_replace('"', '""', $string).'","'.str_replace('"', '""', $translatedStrings[$i]).'"';
				else
					$data[] = '"'.str_replace('"', '""', $string).'",""';
			}
			else if ($onlyMissing) {
				if (empty($translatedStrings[$i]))
					$data[] = '"'.str_replace('"', '""', $string).'",""';
			}
			else if (!empty($translatedStrings[$i]) && ($translatedStrings[$i] != $string)) {
				$data[] = '"'.str_replace('"', '""', $string).'","'.str_replace('"', '""', $translatedStrings[$i]).'"';
			}
		}

		return implode("\n", $data);
	}

	public function generateHTML(array $sourceStrings, array $translatedStrings, string $template) {

		foreach ($sourceStrings as $i => $string) {
			$translation = empty($translatedStrings[$i]) ? $string : $translatedStrings[$i];
			$template = str_replace([
				' '.$string.' ',
				'>'.$string.'<',
				'"'.$string.'"',
				'>'.$string,
				' - '.$string,
			], [
				' '.$translation.' ',
				'>'.$translation.'<',
				'"'.$translation.'"',
				'>'.$translation,
				' - '.$translation,
			], $template);
		}

		return $template;
	}

	public function generateYML(array $sourceStrings, array $translatedStrings, string $locale) {

		$data = [$locale.':'];

		foreach ($sourceStrings as $i => $string)
			$data[] = '  '.$string.': "'.$translatedStrings[$i].'"';

		return implode("\n", $data);
	}

	public function generateLANG(array $sourceStrings, array $translatedStrings, string $locale) {

		$data = [];

		foreach ($sourceStrings as $i => $string)
			$data[] = $string.' = '.$translatedStrings[$i];

		return implode("\n", $data);
	}

	public function generateJS(array $sourceStrings, array $translatedStrings, string $template, bool $forAPIJS = false) {

		$data = [];
		ksort($translatedStrings);

		foreach ($translatedStrings as $locale => $translatedStrs) {

			$current = $locale;
			$locale  = str_replace('-', '_', $locale);
			if (strlen($locale) > 2) { // not mb_strlen
				$tmp = explode('_', $locale);
				if (strtolower($tmp[0]) == strtolower($tmp[1])) { // not mb_strtolower
					$locale = strtolower($tmp[0]);               // not mb_strtolower
					$double = $locale.'_'.strtoupper($locale);   // not mb_strtoupper
				}
				else {
					$double = strtolower($tmp[0]).'_'.strtoupper($tmp[0]); // not mb_strtolower mb_strtoupper
				}
			}
			else {
				$double = strtolower($locale).'_'.strtoupper($locale); // not mb_strtolower mb_strtoupper
			}

			if ($locale == 'en_US')
				$locale = 'en';
			else if ($locale == 'zh_CN')
				$locale = 'zh';
			else if ($locale == 'ja_JP')
				$locale = 'ja';
			else if ($locale == 'cs_CZ')
				$locale = 'cs';
			else if ($locale == 'uk_UA')
				$locale = 'uk';
			else if ($locale == 'el_GR')
				$locale = 'el';

			$tmp = substr($locale, 0, 2); // not mb_substr

			if ($forAPIJS)
				$data[] = '		'.strtolower(str_replace('_', '', $locale)).': {'; // not mb_strtolower

			foreach ($sourceStrings as $i => $string) {
				if (!empty($translatedStrs[$i])) {
					// ignore
					if ($locale != 'en') {
						if (($double != $current) && array_key_exists($double, $translatedStrings) && ($translatedStrs[$i] == $translatedStrings[$double][$i]))
							continue;
						if (array_key_exists('en', $translatedStrings) && ($translatedStrs[$i] == $translatedStrings['en'][$i]))
							continue;
						if (array_key_exists('en_US', $translatedStrings) && ($translatedStrs[$i] == $translatedStrings['en_US'][$i]))
							continue;
						if (($tmp != $locale) && array_key_exists($tmp, $translatedStrings) &&
						    ($translatedStrs[$i] == $translatedStrings[$tmp][$i])) // ignore pt_BR if pt_BR == pt(_PT)
							continue;
					}
					// keep
					if ($forAPIJS)
						$data[] = '			'.$string.': "'.$translatedStrs[$i].'",';
					else
						$data[] = '		d.'.strtolower(str_replace('_', '', $locale)).'['.$string.'] = "'.$translatedStrs[$i].'";'; // not
				}
			}

			if ($forAPIJS) {
				$data[count($data) - 1] = mb_substr($data[count($data) - 1], 0, -1);
				$data[] = '		},';
			}
		}

		return mb_substr($template, 0, mb_stripos($template, '// auto start') + 14).
			($forAPIJS ? mb_substr(implode("\n", $data), 0, -1)."\n\t\t" : implode("\n", $data)."\n\t\t").
			mb_substr($template, mb_stripos($template, '// auto end'));
	}

	public function generatePO(array $sourceStrings, array $translatedStrings, string $originalContent) {

		$data = ['msgid ""', 'msgstr ""', '"Content-Type: text/plain; charset=utf-8\n"', '"Content-Transfer-Encoding: 8bit\n"'];
		$cnt  = 0;

		foreach ($sourceStrings as $i => $string) {

			if (!empty($translatedStrings[$i])) {

				$string = preg_replace('#^§|§$#u', ' ', $string);
				$translatedString = preg_replace('#^§|§$#u', ' ', $translatedStrings[$i]);

				if (mb_stripos($originalContent, 'msgid "'.$string.'"') !== false) {
					$data[] = 'msgid "'.$string.'"';
					$data[] = 'msgstr "'.$translatedString.'"';
					$data[] = '';
				}
				else if (mb_stripos($originalContent, "msgid \"\"\n\"".$string) !== false) {
					$cnt = -2;
					$data[] = 'msgid ""';
					$data[] = '"'.$string.'"';
					$data[] = 'msgstr ""';
					$data[] = '"'.$translatedString.'"';
				}
				else if (mb_stripos($originalContent, "\"\n\"".$string."\"\nmsgstr") !== false) {
					array_splice($data, $cnt--, 0, ['"'.$string.'"']);
					$data[] = '"'.$translatedString.'"';
					$data[] = '';
				}
				else if (mb_stripos($originalContent, "\"\n\"".$string.'"') !== false) {
					array_splice($data, $cnt--, 0, ['"'.$string.'"']);
					$data[] = '"'.$translatedString.'"';
				}
			}
		}

		return implode("\n", $data);
	}


	// LOAD TRANSLATED STRINGS FROM SERVICE
	// TSV file (for example a google sheet export)
	// return $data[] = translations
	public function loadService(
		array $config,
		string $locale,
		string $src,
		$sourceStrings, // string or array
		array $translatedStrings = [],
		bool $fill = false,
		bool $onlyKeys = false
	) {
		if (empty($config['service'])) {
			// return same format when service is available
			$data = [];
			foreach ($translatedStrings as $key => $value) {
				$key = array_search($key, $sourceStrings);
				if ($key !== false)
					$data[$key] = $value;
			}
			ksort($data);
			return $data;
		}

		$code = $config['vendor'].'/'.$config['name'];
		$strs = empty($config['nocheckStrings']) ? [] : $config['nocheckStrings'];

		global $cache;
		if (empty($cache))
			$cache = [];

		// load TSV
		// from cache or from the world wide web
		if (array_key_exists($key = md5($config['service']), $cache)) {
			$lines = $cache[$key];
		}
		else {
			$lines = explode("\n", file_get_contents($config['service']));
			$cache[$key] = $lines;
		}

		// search data
		// from cache of from TSV
		if (array_key_exists($key = md5($code), $cache)) {
			$data = $cache[$key];
		}
		else {
			$data  = [];
			$head  = [];
			$keys  = -1;
			$enus  = -1;
			$found = false;
			$group = 'base';

			foreach ($lines as $line) {

				$cells = explode("\t", $line);

				if (empty($head)) {
					foreach ($cells as $i => $cell) {
						if (mb_stripos($cell, 'config') !== false)
							$head[$keys = $i] = 'config';
						else if (preg_match('#[a-z]{2}-[A-Z]{2} \(#', $cell) === 1)
							$head[$i] = trim(mb_substr($cell, 0, mb_stripos($cell, ' '))); // en-US ...
					}
					$enus = array_search('en-US', $head);
					if ($keys < 0) {
						echo "\nfatal: column config not found in TSV!\n";
						exit(-1);
					}
					if (($enus === false) || ($enus < 0)) {
						echo "\nfatal: column en-US not found in TSV!\n";
						exit(-1);
					}
					$enus = (int) $enus; // (yes)
				}
				else if (!$found) {
					if (mb_strtolower($cells[$keys]) == mb_strtolower($code))
						$found = true;
				}
				else if (empty($cells[$keys]) && empty($cells[$enus])) {
					if ($code != 'Custom/Doc')
						break;
				}
				else if ((mb_stripos($cells[$keys], '.html') !== false) || ((mb_stripos($cells[$keys], '.js') !== false))) {
					$group = $cells[$keys];
				}
				else {
					foreach ($head as $i => $value) { // rtrim for the last column
						$cell = rtrim($cells[$i]);
						if ($fill && empty($cell))
							$data[$code][$value][$group][] = rtrim($cells[$enus]);
						else
							$data[$code][$value][$group][] = $cell;
					}
				}
			}

			$cache[$key] = $data;
		}

		if (empty($cache[$key])) {
			echo "\nfatal: data not found in TSV!\n";
			exit(-1);
		}

		// check data from HTML
		if (is_string($sourceStrings) && (mb_substr($src, -5) == '.html')) {

			foreach ($data[$code]['en-US'][$src] as $i => $string) {

				if ((mb_stripos($sourceStrings, '>'.$string.'<') === false) &&
				    (mb_stripos($sourceStrings, '"'.$string.'"') === false) &&
				    (mb_stripos($sourceStrings, '>'.$string) === false) &&
				    (mb_stripos($sourceStrings, ' '.$string.' ') === false)
				) {
					echo "\n\n",'STOP! string was not found in HTML';
					if (isset($data[$code]['en-US'][$src][$i - 1]))
					echo "\n",' previous string found: ',$data[$code]['en-US'][$src][$i - 1];
					echo "\n",'      string not found: ',$data[$code]['en-US'][$src][$i];
					if (isset($data[$code]['en-US'][$src][$i + 1]))
					echo "\n",'           next string: ',$data[$code]['en-US'][$src][$i + 1];
					echo "\n";
					exit(-1);
				}
			}
		}
		// check data from JS
		else if (is_string($sourceStrings) && ((mb_substr($src, -3) == '.js') || (mb_substr($config['name'], -3) == '.js'))) {

			if (empty($data[$code]['en-US'][$src]))
				return false;

			foreach ($data[$code]['en-US'][$src] as $i => $string) {

				if ((mb_stripos($sourceStrings, '] = "'.$string.'"') === false) && (mb_stripos($sourceStrings, ': "'.$string.'"') === false)) {
					echo "\n\n",'STOP! string was not found in JS';
					if (isset($data[$code]['en-US'][$src][$i - 1]))
					echo "\n",' previous string found: ',$data[$code]['en-US'][$src][$i - 1];
					echo "\n",'      string not found: ',$data[$code]['en-US'][$src][$i];
					if (isset($data[$code]['en-US'][$src][$i + 1]))
					echo "\n",'           next string: ',$data[$code]['en-US'][$src][$i + 1];
					echo "\n";
					exit(-1);
				}
			}
		}
		// check data from CSV
		else if (is_array($sourceStrings) && empty($config['allowNotSame'])) {

			foreach ($sourceStrings as $i => $string) {

				if (!isset($data[$code]['en-US'][$src][$i])) {
					echo "\n\n",'STOP! string was not found in TSV';
					if (isset($data[$code]['en-US'][$src][$i - 1]))
					echo "\n",' previous string found: ',$data[$code]['en-US'][$src][$i - 1];
					echo "\n",'          string found: (nothing)';
					echo "\n",'       string expected: ',$string;
					echo "\n";
					exit(-1);
				}

				if (($string != $data[$code]['en-US'][$src][$i]) && // 'Created At'
					($string != ' '.$data[$code]['en-US'][$src][$i]) && // ' Created At'
					($string != $data[$code]['config'][$src][$i])
				) {
					$allow = false;
					foreach ($strs as $str) {
						if (mb_stripos($string, $str) !== false) {
							// special in the TSV for new lines
							foreach (array_keys($data[$code]) as $value) {
								$data[$code][$value][$src][$i] = str_replace(' #<', "\n<", $data[$code][$value][$src][$i]);
							}
							$allow = true;
							break;
						}
					}
					if (!$allow) {
						echo "\n\n",'STOP! string was not found in TSV';
						if (isset($data[$code]['en-US'][$src][$i - 1]))
						echo "\n",' previous string found: ',$data[$code]['en-US'][$src][$i - 1];
						echo "\n",'          string found: ',$data[$code]['en-US'][$src][$i];
						echo "\n",'       string expected: ',$string;
						if (isset($data[$code]['en-US'][$src][$i + 1]))
						echo "\n",'           next string: ',$data[$code]['en-US'][$src][$i + 1];
						echo "\n";
						//print_r($sourceStrings);
						exit(-1);
					}
				}
			}
		}

		// update locale code
		if (strlen($locale) == 2)                // not mb_strlen
			$locale .= '_'.strtoupper($locale); // not mb_strtoupper
		else if (strlen($locale) > 5)            // not mb_strlen
			$locale = substr($locale, 0, 5);    // not mb_substr

		if ($locale == 'en_EN')
			$locale = 'en_US';
		else if ($locale == 'zh_ZH')
			$locale = 'zh_CN';
		else if ($locale == 'ja_JA')
			$locale = 'ja_JP';
		else if ($locale == 'cs_CS')
			$locale = 'cs_CZ';
		else if ($locale == 'uk_UK')
			$locale = 'uk_UA';
		else if ($locale == 'el_EL')
			$locale = 'el_GR';

		// reorder data
		if (!empty($config['keepOrder'])) {
			$newdata = [];
			foreach ($sourceStrings as $string) {
				$key = array_search($string, $data[$code]['en-US'][$src]);
				if ($key !== false)
					$newdata[] = $data[$code][str_replace('_', '-', $locale)][$src][$key];
				else
					$newdata[] = $string;
			}
			$data[$code][str_replace('_', '-', $locale)][$src] = $newdata;
		}

		// return translated data
		return $onlyKeys ? $data[$code]['config'][$src] ?? [] : $data[$code][str_replace('_', '-', $locale)][$src] ?? [];
	}


	// LOAD TRANSLATED STRINGS FROM FILES
	// return $data[source] = translation
	public function loadCSV(array $files, bool $onlyKeys = false) {

		$data = [];

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			$resource = fopen($file, 'rb');

			while (!empty($line = fgetcsv($resource, 2500))) {
				if (!empty($line[0]) && !empty($line[1]) && empty($data[$line[0]]))
					$data[$line[0]] = trim(stripslashes($line[1]));
			}

			fclose($resource);
		}

		return $onlyKeys ? array_keys($data) : $data;
	}

	public function loadYML(array $files) {

		$data = [];

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			$resource = fopen($file, 'rb');

			while (($line = fgets($resource, 2500)) !== false) {
				$pos = mb_stripos($line, ': ');
				if ($pos !== false) {
					$line = [mb_substr($line, 0, $pos), mb_substr($line, $pos + 2)];
					$data[trim($line[0])] = trim(stripslashes($line[1]), " \t\n\r\0\x0B\"");
				}
			}

			fclose($resource);
		}

		return $data;
	}

	public function loadLANG(array $files) {

		$data = [];

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			$resource = fopen($file, 'rb');

			while (($line = fgets($resource, 2500)) !== false) {
				$pos = mb_stripos($line, '= ');
				if ($pos !== false) {
					$line = [mb_substr($line, 0, $pos), mb_substr($line, $pos + 2)];
					$data[trim($line[0])] = trim(stripslashes($line[1]), " \t\n\r\0\x0B\"");
				}
			}

			fclose($resource);
		}

		return $data;
	}

	public function loadPO(array $files) {

		$data = [];

		$this->readPo($data, $files, false);

		return $data;
	}


	// SEARCH STRINGS TO TRANSLATE
	//   XML <parent translate="child"><child>...</child></parent>
	//   PHP >_(...) >__(...) link(...) h2(...) h3(...)
	//  RUBY  l(:...)
	// example
	//  >__('Created At') => it/him/he
	//  >_('Created At')  => she/her
	// example
	//  >__('%d days (%d month)')  => 1
	//  >_('%d days (%d months)')  => 2-4
	//  >__('%d days (%d months)') => 5+
	// &$data[] = source
	public function searchAndReadXML(array &$data, array $files) {

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			if (mb_stripos($file, 'ws') === false) {

				$xslDoc = new DOMDocument();
				$xslDoc->load('./translate.xsl');
				$xmlDoc = new DOMDocument();
				$xmlDoc->load($file);

				$processor = new XSLTProcessor();
				$processor->importStylesheet($xslDoc);
				$strings = $processor->transformToXml($xmlDoc);

				if (!empty($strings) && (mb_strlen($strings) > 5)) {

					$strings = explode('§', $strings);
					foreach ($strings as $string) {
						if (!empty($string) && !in_array($string, $data))
							$data[] = str_replace('`', '"', $string);
					}
				}
			}
		}

		return $this;
	}

	public function searchAndReadPHP(array &$data, array $files) {

		$regex = '((?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*(?![^\\\\]\\\\))")|(?:\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(?![^\\\\]\\\\))\'))';

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			preg_match_all('#(>_|>__|link|h2|h3|>trans)\('.$regex.'#', file_get_contents($file), $strings);

			foreach ($strings[4] as $idx => $string) {

				$string  = empty($string) ? mb_substr($strings[2][$idx], 1, -1) : $string;
				$string  = str_replace(['\\\'','\\\"'], ['\'','\"'], $string);
				$special = ($strings[1][$idx] == '>_') ? ' ' : '';

				if (!empty($string) && !in_array($special.$string, $data))
					$data[] = $special.$string;
			}
		}

		return $this;
	}

	public function searchAndReadRB(array &$data, array $files) {

		$regex = '# l\(:([^),]+)[),]#';

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			preg_match_all($regex, file_get_contents($file), $strings);

			foreach ($strings[1] as $string) {
				if (!empty($string) && !in_array($string, $data))
					$data[] = $string;
			}
		}

		return $this;
	}

	public function readPo(array &$data, array $files, bool $key = true) {

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			$lines = explode("\n", file_get_contents($file));
			$ready = false;
			$multi = false;

			foreach ($lines as $line) {
				if (!$ready) {
					if (!empty($line) && (strncmp($line, '#', 1) === 0))
						$ready = true;
				}
				else if (!empty($line)) {
					if ($key) {
						if ($line == 'msgid ""')
							$multi = true;
						else if ($multi && (strncmp($line, '"', 1) === 0))
							$data[] = preg_replace('#^ | $#', '§', trim($line, '"'));
						else if ($multi && ($line[0] != '"'))
							$multi = false;
						else if (mb_stripos($line, 'msgid "') !== false)
							$data[] = preg_replace('#^ | $#', '§', trim(str_replace('msgid ', '', $line), '"'));
					}
					else if ($line == 'msgstr ""')
						$multi = true;
					else if ($multi && (strncmp($line, '"', 1) === 0))
						$data[] = preg_replace('#^ | $#', '§', trim($line, '"'));
					else if ($multi && ($line[0] != '"'))
						$multi = false;
					else if (mb_stripos($line, 'msgstr "') !== false)
						$data[] = preg_replace('#^ | $#', '§', trim(str_replace('msgstr ', '', $line), '"'));
				}
			}
		}

		return $this;
	}
}

$obj = new Translate();
require_once('translate.conf.php');
$obj->run($argv);