<?php
/**
 * Created L/10/12/2012
 * Updated L/06/06/2022
 *
 * Copyright 2012-2022 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/ + https://github.com/luigifab/translator
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
ini_set('display_errors', 1);

date_default_timezone_set('UTC');
header('Content-Type: text/plain; charset=utf-8');

if (PHP_SAPI != 'cli')
	exit('Run me only with cli.');

class Translate {

	public const VERSION = '1.4.1';

	public function run(array $argv) {

		global $openMageDefault;
		global $updateTranslationOpenMageModule;
		global $updateTranslationRedminePlugin;
		global $updateTranslationPo;
		global $updateTranslationApijs;
		global $updateTranslationWebsite;

		if (in_array('all', $argv))
			$argv = ['openmage-module', 'redmine-plugin', 'apijs', 'po', 'custom'];

		if (!empty($updateTranslationOpenMageModule) && !empty($openMageDefault) && in_array('openmage-module', $argv)) {

			$files = glob($openMageDefault, SCANDIR_SORT_NONE);
			$ignoreStrings = $this->loadCSV($files);

			foreach ($updateTranslationOpenMageModule as $config) {
				echo 'updateTranslationOpenMageModule: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) exit("\nfatal: ".$config['dir']." does not exist!\n");
				$this->updateTranslationOpenMageModule($ignoreStrings, $config);
			}
		}

		if (!empty($updateTranslationRedminePlugin) && in_array('redmine-plugin', $argv)) {
			foreach ($updateTranslationRedminePlugin as $config) {
				echo 'updateTranslationRedminePlugin: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) exit("\nfatal: ".$config['dir']." does not exist!\n");
				$this->updateTranslationRedminePlugin([], $config);
			}
		}

		if (!empty($updateTranslationPo) && in_array('po', $argv)) {
			foreach ($updateTranslationPo as $config) {
				echo 'updateTranslationPo: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) exit("\nfatal: ".$config['dir']." does not exist!\n");
				$this->updateTranslationPo([], $config);
			}
		}

		if (!empty($updateTranslationApijs) && in_array('apijs', $argv)) {
			foreach ($updateTranslationApijs as $config) {
				echo 'updateTranslationApijs: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) exit("\nfatal: ".$config['dir']." does not exist!\n");
				$this->updateTranslationApijs([], $config);
			}
		}

		if (!empty($updateTranslationWebsite) && in_array('custom', $argv)) {
			foreach ($updateTranslationWebsite as $config) {
				echo 'updateTranslationWebsite: ',$config['vendor'],'/',$config['name'],"\n";
				if (!is_dir($config['dir'])) exit("\nfatal: ".$config['dir']." does not exist!\n");
				$this->updateTranslationWebsite([], $config);
			}
		}
	}

	public function mergeStrings(array $config, array &$sourceStrings, array &$ignoreStrings) {

		// add data from config
		if (!empty($config['sourceStringsAfter']))
			$sourceStrings = array_merge($sourceStrings, $config['sourceStringsAfter']);

		if (!empty($config['ignoreStrings'])) {
			$ignoreStrings = array_merge($ignoreStrings, $config['ignoreStrings']);
			// don't ignore strings in sourceStringsAfter
			if (!empty($config['sourceStringsAfter']))
				$ignoreStrings = array_diff($ignoreStrings, $config['sourceStringsAfter']);
		}

		// remove ignored strings
		foreach ($sourceStrings as $i => $string) {
			if (($string == ' ') || in_array($string, $ignoreStrings))
				unset($sourceStrings[$i]);
		}

		// very important
		$sourceStrings = array_values(array_unique($sourceStrings));
	}

	public function generateApijsEmbed(array $config) {

		$files = [];
		exec('find '.$config['dir'].' -name "*.js"', $files);
		sort($files);

		foreach ($files as $file) {

			// load strings to translate from JS
			$key = mb_substr($file, mb_strrpos($file, '/') + 1);

			$template = file_get_contents($file);
			if (mb_stripos($template, '// auto start') === false)
				continue;

			$sourceStrings = $this->loadService($config, 'en_US', $key, $template, [], false, true);
			if ($sourceStrings === false)
				continue;

			$locales = $config['locales'];
			if (!in_array('en', $locales) && !in_array('en_US', $locales) && !in_array('en-US', $locales))
				$locales[] = 'en_US';

			// load translated strings from TSV service, and from TSV service
			$translatedStrings = [];
			foreach ($locales as $locale) {
				echo ' ',mb_substr($locale, 0, 5);
				$translatedStrings[$locale] = $this->loadService($config, $locale, $key, $sourceStrings);
			}

			// write final JS file
			echo ' ';
			$final = $this->generateJS($sourceStrings, $translatedStrings, $template);
			$this->writeFile($file, $final);
		}
	}

	public function updateTranslationOpenMageModule(array $ignoreStrings, array $config) {

		// search strings to translate
		if (empty($config['search'])) {

			$files1 = [];
			exec('find '.$config['dir'].'app/ -name "*.xml"', $files1);
			sort($files1);

			$files2 = [];
			exec('find '.$config['dir'].'app/ -name "*.phtml"', $files2);
			exec('find '.$config['dir'].'app/ -name "*.php"', $files2);
			sort($files2);

			if (!empty($config['exclude'])) {
				foreach ($files1 as $idx => $file) {
					if (mb_stripos($file, $config['exclude']) !== false)
						unset($files1[$idx]);
				}
				foreach ($files2 as $idx => $file) {
					if (mb_stripos($file, $config['exclude']) !== false)
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
		$this->mergeStrings($config, $sourceStrings, $ignoreStrings);

		// generate CSV
		foreach ($config['locales'] as $locale) {

			echo ' ',str_pad(mb_substr($locale, 0, 5), 6);

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

			// load template and strings to translate from TSV service
			$key = mb_substr($email, mb_strrpos($email, '/') + 1);
			$template = file_get_contents($email);
			$sourceStrings = $this->loadService($config, 'en_US', $key, $template);

			foreach ($config['locales'] as $locale) {

				echo ' ',str_pad(mb_substr($locale, 0, 5), 6);
				if (!is_dir($config['dir'].'app/locale/'.$locale.'/template/email/')) {
					echo  '  (template/email directory does not exist)',"\n";
					continue;
				}

				// load translated strings from TSV service
				$translatedStrings = $this->loadService($config, $locale, $key, $sourceStrings);

				// write final HTML file
				$final = $this->generateHTML($sourceStrings, $translatedStrings, $template);
				$final = str_replace(' lang="en"', ' lang="'.mb_substr($locale, 0, 2).'"', $final);
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
	}

	public function updateTranslationRedminePlugin(array $ignoreStrings, array $config) {

		// search strings to translate

		if (empty($config['search'])) {

			$files = [];
			exec('find '.$config['dir'].' -name "*.rb"', $files);
			exec('find '.$config['dir'].' -name "*.erb"', $files);
			sort($files);

			if (!empty($config['exclude'])) {
				foreach ($files as $idx => $file) {
					if (mb_stripos($file, $config['exclude']) !== false)
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
		$this->searchAndReadRB($sourceStrings, $files, $config['filter']);
		$this->mergeStrings($config, $sourceStrings, $ignoreStrings);

		// generate YML
		foreach ($config['locales'] as $locale) {

			echo ' ',str_pad(mb_substr($locale, 0, 5), 6);

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
	}

	public function updateTranslationPo(array $ignoreStrings, array $config) {

		foreach ($config['gettext'] as $cmd) {
			echo ' run: ',$cmd,"\n";
			exec($cmd);
		}

		// search strings to translate
		if (empty($config['search'])) {

			$files = [];
			exec('find '.$config['dir'].' -name "*.po"', $files);
			sort($files);

			if (!empty($config['exclude'])) {
				foreach ($files as $idx => $file) {
					if (mb_stripos($file, $config['exclude']) !== false)
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
		$this->mergeStrings($config, $sourceStrings, $ignoreStrings);

		// generate PO
		foreach ($config['locales'] as $locale) {

			echo ' ',str_pad(mb_substr($locale, 0, 5), 6);

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
	}

	public function updateTranslationApijs(array $ignoreStrings, array $config) {

		// JS (apijs magic key)
		$file = $config['dir'].$config['search'][0];

		{
			// load strings to translate from JS
			$template      = file_get_contents($file);
			$sourceStrings = $this->loadService($config, 'en_US', 'base', $template, [], false, true);

			if ($sourceStrings === false)
				exit("\nfatal: sourceStrings not found for APIJS!\n");

			// load translated strings from TSV service
			$translatedStrings = [];
			foreach ($config['locales'] as $locale) {
				echo ' ',mb_substr($locale, 0, 5);
				$translatedStrings[$locale] = $this->loadService($config, $locale, 'base', $sourceStrings);
			}

			// write final JS file
			echo ' ';
			$final = $this->generateJS($sourceStrings, $translatedStrings, $template, true);
			$this->writeFile($file, $final);
		}

		echo "\n";
	}

	public function updateTranslationWebsite(array $ignoreStrings, array $config) {

		// search strings to translate
		$files = [];
		foreach ($config['search'] as $file)
			$files[] = $config['dir'].$file;

		$sourceStrings = empty($config['sourceStringsBefore']) ? [] : $config['sourceStringsBefore'];
		$this->searchAndReadPHP($sourceStrings, $files);
		$this->mergeStrings($config, $sourceStrings, $ignoreStrings);

		// generate CSV
		foreach ($config['locales'] as $locale) {

			echo ' ',str_pad(mb_substr($locale, 0, 5), 6);

			// load translated strings from CSV files, and from TSV service
			$file = $config['dir'].$locale.'.csv';
			$translatedStrings = $this->loadCSV([$file]);
			$translatedStrings = $this->loadService($config, $locale, 'base', $sourceStrings, $translatedStrings);

			// write final CSV file
			$final = $this->generateCSV($sourceStrings, $translatedStrings);
			$this->writeFile($file, $final);
		}

		echo "\n";
	}


	// CREATE FINAL FILES
	public function writeFile(string $file, string $final) {

		if (empty($final)) {
			echo "\n";
			if (is_file($file))
				unlink($file);
		}
		else {
			file_put_contents($file, $final);
			echo realpath($file),"\n";
		}
	}

	public function writeOpenMageFile(array $config, string $locale, string $file, string $final) {

		if (empty($final)) {
			echo "\n";
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
				['es_ES', 'es_VE']
			] as [$from, $to]) {
				if (($locale == $from) && !in_array($to, $config['locales']) && is_file($file))
					unlink(str_replace($locale, $to, $file));
			}
		}
		else {
			file_put_contents($file, $final);
			echo realpath($file),"\n";
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
				['es_ES', 'es_VE']
			] as [$from, $to]) {
				if (($locale == $from) && !in_array($to, $config['locales'])) {
					$dir = (mb_strpos($file, 'template/email') === false) ? 'app/locale/'.$to : 'app/locale/'.$to.'/template/email';
					if (!is_dir($config['dir'].$dir))
						mkdir($config['dir'].$dir, 0755, true);
					file_put_contents(str_replace($locale, $to, $file), $final);
				}
			}
		}
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
			$template = (string) str_replace([
				' '.$string.' ',
				'>'.$string.'<',
				'"'.$string.'"',
				'>'.$string,
				' - '.$string
			], [
				' '.$translation.' ',
				'>'.$translation.'<',
				'"'.$translation.'"',
				'>'.$translation,
				' - '.$translation
			], $template); // (yes)
		}

		return $template;
	}

	public function generateYML(array $sourceStrings, array $translatedStrings, string $locale) {

		$data = [$locale.':'];

		foreach ($sourceStrings as $i => $string)
			$data[] = '  '.$string.': "'.$translatedStrings[$i].'"';

		return implode("\n", $data);
	}

	public function generateJS(array $sourceStrings, array $translatedStrings, string $template, bool $forAPIJS = false) {

		$data = [];
		ksort($translatedStrings);

		foreach ($translatedStrings as $locale => $translatedStrs) {

			$current = $locale;
			$locale  = str_replace('-', '_', $locale);
			if (mb_strlen($locale) > 2) {
				$tmp = (array) explode('_', $locale); // (yes)
				if (mb_strtolower($tmp[0]) == mb_strtolower($tmp[1])) {
					$locale = $tmp[0];
					$double = mb_strtolower($locale).'_'.mb_strtoupper($locale);
				}
				else {
					$double = mb_strtolower($tmp[0]).'_'.mb_strtoupper($tmp[0]);
				}
			}
			else {
				$double = mb_strtolower($locale).'_'.mb_strtoupper($locale);
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

			$tmp = mb_substr($locale, 0, 2);

			if ($forAPIJS)
				$data[] = '		'.mb_strtolower(str_replace('_', '', $locale)).': {';

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
						$data[] = '		d.'.mb_strtolower(str_replace('_', '', $locale)).'['.$string.'] = "'.$translatedStrs[$i].'";';
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
	public function loadService(array $config, string $locale, string $src, $sourceStrings, array $translatedStrings = [],
		bool $fill = false, bool $onlyKeys = false) {

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

				$cells = (array) explode("\t", $line); // (yes)

				if (empty($head)) {
					foreach ($cells as $i => $cell) {
						if (mb_stripos($cell, 'config') !== false)
							$head[$keys = $i] = 'config';
						else if (preg_match('#[a-z]{2}-[A-Z]{2} \(#', $cell) === 1)
							$head[$i] = trim(mb_substr($cell, 0, mb_stripos($cell, ' ')));
					}
					$enus = array_search('en-US', $head);
					if ($keys < 0)
						exit("\nfatal: column config not found in TSV!\n");
					if (($enus === false) || ($enus < 0))
						exit("\nfatal: column en-US not found in TSV!\n");
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

		if (empty($cache[$key]))
			exit("\nfatal: data not found in TSV!\n");

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
					exit("\n");
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
					exit("\n");
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
					exit("\n");
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
						// debug
						//echo "\n"; print_r($sourceStrings);
						exit("\n");
					}
				}
			}
		}

		// update locale code
		if (mb_strlen($locale) == 2)
			$locale .= '_'.mb_strtoupper($locale);
		else if (mb_strlen($locale) > 5)
			$locale = mb_substr($locale, 0, 5);

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
		if (!empty($config['allowNotSame'])) {
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
		return $onlyKeys ? $data[$code]['config'][$src] : $data[$code][str_replace('_', '-', $locale)][$src];
	}


	// LOAD TRANSLATED STRINGS FROM FILES
	// return $data[source] = translation
	public function loadCSV(array $files, bool $onlyKeys = false) {

		$data = [];

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			$resource = fopen($file, 'rb');

			while (($line = fgetcsv($resource, 2500)) !== false) {
				$line = (array) $line; // (yes)
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
	//  >__('Created At')  => it/him/he
	//  >_('Created At')   => she/her
	// example
	//  >__('%d days (%d month)')  => 1
	//  >_('%d days (%d months)')  => 2-4
	//  >__('%d days (%d months)') => 5+
	// return $data[] = source
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
	}

	public function searchAndReadPHP(array &$data, array $files) {

		$regex = '((?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*(?![^\\\\]\\\\))")|(?:\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(?![^\\\\]\\\\))\'))';

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			preg_match_all('#(>_|>__|link|h2|h3)\('.$regex.'#', file_get_contents($file), $strings);

			foreach ($strings[4] as $idx => $string) {

				$string  = empty($string) ? mb_substr($strings[2][$idx], 1, -1) : $string;
				$string  = str_replace(['\\\'','\\\"'], ['\'','\"'], $string);
				$special = ($strings[1][$idx] == '>_') ? ' ' : '';

				if (!empty($string) && !in_array($special.$string, $data))
					$data[] = $special.$string;
			}
		}
	}

	public function searchAndReadRB(array &$data, array $files, string $search) {

		$regex = '# l\(:([^),]+)[),]#';

		foreach ($files as $file) {

			if (!is_file($file))
				continue;

			preg_match_all($regex, file_get_contents($file), $strings);

			foreach ($strings[1] as $string) {
				if ((mb_stripos($string, $search) !== false) || (mb_stripos($string, 'permission_') !== false)) {
					if (!empty($string) && !in_array($string, $data))
						$data[] = $string;
				}
			}
		}
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
	}
}

$obj = new Translate();
require_once('translate.conf.php');
$obj->run($argv);
