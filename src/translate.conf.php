<?php

// read https://github.com/luigifab/translator
// $dir     is the base directory
// $search  is optionnal and it is relative to $dir
// $service please read the 'EXAMPLE' tab of this google document:
//          https://docs.google.com/spreadsheets/d/1UUpKZ-YAAlcfvGHYwt6aUM9io390j0-fIL0vMRh1pW0/edit?usp=sharing
$example = [
	[
		'dir'     => './example/',
		'search'  => ['app/code/local/Example/Example/','app/design/adm/def/def/layout/example/example.xml'], // optionnal
		'vendor'  => 'Example|Custom',
		'name'    => 'Example|Apijs',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/...&output=tsv', // optionnal
		'filter'  => 'example_',              // optionnal
		'nocheckStrings'      => ['example'], // optionnal
		'sourceStringsBefore' => ['example'], // optionnal
		'sourceStringsAfter'  => ['example'], // optionnal
		'ignoreStrings'       => ['example']  // optionnal
	]
];

////////////////////////////////////////////////////////////////////////////////
// MODULES FOR OPENMAGE
$openMageDefault = './locales/Mage_*.csv';
$updateTranslationOpenMageModule = [
	[
		'dir'     => './mage-maillog/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Maillog',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv',
		'nocheckStrings' => ['<p>Synchronization allow to synchronize'],
		'ignoreStrings'  => ['<b>']
	], [
		'dir'     => './mage-cronlog/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Cronlog',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	], [
		'dir'     => './mage-modules/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Modules',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	], [
		'dir'     => './mage-versioning/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Versioning',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv',
		'sourceStringsAfter' => array_merge($obj->loadCSV(['./mage-versioning/src/app/locale/en_US/Luigifab_Versioning.csv'], true), ['Error number: §']),
		'ignoreStrings'      => ['%s (%s)']
	], [
		'dir'     => './mage-urlnosql/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Urlnosql',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	], [
		'dir'     => './mage-paypalrefund/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Paypalrefund',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	], [
		'dir'     => './mage-minifier/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Minifier',
		'locales' => ['fr_FR'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	], [
		'dir'     => './mage-apijs/src/',
		'vendor'  => 'Luigifab',
		'name'    => 'Apijs',
		'locales' => ['fr_FR','fr_CA','pt_PT','pt_BR','it_IT','es_ES','de_DE','pl_PL','nl_NL','cs_CZ','tr_TR','ru_RU','ja_JP','zh_CN'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	]
];

////////////////////////////////////////////////////////////////////////////////
// PLUGINS FOR REDMINE
$updateTranslationRedminePlugin = [
	[
		'dir'     => './redmine-apijs/src/',
		'vendor'  => 'Rluigifab',
		'name'    => 'Apijs',
		'locales' => ['en','fr','pt','pt-BR','it','es','de','pl','nl','cs','tr','ru','ja','zh'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv',
		'filter'  => 'apijs_'
	]
];

////////////////////////////////////////////////////////////////////////////////
// PO FILES
$updateTranslationPo = [
	[
		'dir'     => './gtk-awf/src/po/',
		'gettext' => [
			'xgettext -d awf -o ./gtk-awf/src/awf.pot -k_ -s ./gtk-awf/src/awf.c',
			'msgmerge ./gtk-awf/src/po/fr.po ./gtk-awf/src/awf.pot -o ./gtk-awf/src/po/fr.po',
		],
		'vendor'  => 'Luigifab',
		'name'    => 'Awf',
		'locales' => ['fr'], // en
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv',
		'allowNotSame' => true
	]
];

////////////////////////////////////////////////////////////////////////////////
// APIJS (luigifab.fr/apijs)
$updateTranslationApijs = [
	[
		'dir'     => './apijs/src/',
		'search'  => ['javascripts/i18n.js'],
		'vendor'  => 'Custom',
		'name'    => 'Apijs',
		'locales' => ['en','fr','pt','pt-BR','it','es','de','pl','nl','cs','tr','ru','ja','zh'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=1790927668&single=true&output=tsv'
	]
];

////////////////////////////////////////////////////////////////////////////////
// CUSTOM (luigifab.fr)
$updateTranslationWebsite = [
	[
		'dir'     => './locales/',
		'search'  => [
			'../../footer.php',
			'../../index.php',
			'../../apijs-dev/index.php',     '../../apijs/index.php',
			'../../apijs-dev/dialog.php',    '../../apijs/dialog.php',
			'../../apijs-dev/upload.php',    '../../apijs/upload.php',
			'../../apijs-dev/slideshow.php', '../../apijs/slideshow.php',
			'../../apijs-dev/install.php',   '../../apijs/install.php',
			'../../apijs-dev/config.php',    '../../apijs/config.php',
			'../../openmage/cronlog.php',      '../../magento/cronlog.php',
			'../../openmage/modules.php',      '../../magento/modules.php',
			'../../openmage/versioning.php',   '../../magento/versioning.php',
			'../../openmage/urlnosql.php',     '../../magento/urlnosql.php',
			'../../openmage/paypalrefund.php', '../../magento/paypalrefund.php',
			'../../openmage/maillog.php',      '../../magento/maillog.php',
			'../../openmage/apijs.php',        '../../magento/apijs.php',
			'../../redmine/apijs.php',
			'../../adminer/shortcuts.php',
			'../../gtk/old-ubuntu-human-theme.php'
		],
		'vendor'  => 'Custom',
		'name'    => 'Doc',
		'locales' => ['fr_FR'],
		'service' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vTqS3j4Wd-Bt7Zb52eJiQed_'.
				'NilvKo0wGdw8noL4vhFOPsUeV9O6EN8odni6YepDGicYApcJ4Zy5opv/pub?gid=281396442&single=true&output=tsv',
		'sourceStringsBefore' => [
			'General Public License',
			'eXtensible Markup Language',
			'eXtensible HyperText Markup Language',
			'HyperText Markup Language',
			'Cascading Style Sheets',
			'Scalable Vector Graphics',
			'Really Simple Syndication',
			'Asynchronous JavaScript and XML',
			'Web Content Accessibility Guidelines',
			'<strong>Warning</strong>: your browser <strong>§ §</strong> is outdated, please <a §>upgrade your browser</a>.',
			'Menu',
			'Print preview',
			'Print',
			'Our modules for OpenMage',
			'Our modules for Magento'
		],
		'nocheckStrings' => ['<p>Synchronization allow to synchronize'],
		'ignoreStrings'  => ['<abbr>CSS</abbr>', '<abbr>SVG</abbr>', 'luigifab.fr']
	]
];

////////////////////////////////////////////////////////////////////////////////
// FULL OPENMAGE TRANSLATE
$updateTranslationOpenMageFull = [
	'dir'   => '../../openmage/', // a default installation
	'packs' => ['../zzx/src/app/locale/'],
	'ignoreStrings' => ['...', 'px.', 'XML', 'CSV', 'PDF', 'DHL', 'UPS', 'USPS',
		'United Parcel Service', 'Universal Analytics', 'ASCII', 'Android', 'LinkedIn', 'Library Mail', 'Library Mail Parcel',
		'Twitter', 'RSS', 'PayPal', 'HTML', 'HTTPS (SSL)', 'Google', 'FedEx', '80px x 80px', '80x80 px',
		'Google Analytics', 'Google Base', 'Google Checkout', 'Google Sitemaps', 'Google Website Optimizer',
		'Google base', '1. set up', '2. customize', '3 Day Select', '%s', 'md5sum', 'iPad', 'iPhone', 'images/logo.gif']
];
