# translator

My tool to extract translations and update translations files (CSV, YML, PO, JS) for PHP 7.2 / 7.3 / 7.4 / 8.0 / 8.1.

The `translate.conf.php` file is yours. Never use it "as is" because it contains my configuration. At the beginning of the file, there is an example with all possible options. If you like this tool, take some of your time to improve my translations, go to https://bit.ly/2HyCCEc.

_This program is free software, you can redistribute it or modify it under the terms of the GNU General Public License (GPL) as published by the free software foundation, either version 2 of the license, or (at your option) any later version._

### Translate module for [OpenMage](https://github.com/OpenMage/magento-lts)

 * Configuration with `$updateTranslationOpenMageModule` in `translate.conf.php`
 * Run `php translate.php openmage-module`

Read the examples in _translate.conf.php_ to write your own configuration.

### Translate plugin for [Redmine](https://github.com/redmine/redmine)

 * Configuration with `$updateTranslationRedminePlugin` in `translate.conf.php`
 * Run `php translate.php redmine-plugin`

Read the examples in _translate.conf.php_ to write your own configuration.

### Translate program with gettext

 * Configuration with `$updateTranslationPo` in `translate.conf.php`
 * Run `php translate.php po`

Read the examples in _translate.conf.php_ to write your own configuration.

### Translate website

 * Configuration with `$updateTranslationWebsite` in `translate.conf.php`
 * Run `php translate.php custom`

Read the examples in _translate.conf.php_ to write your own configuration.

### Translate [apijs](https://github.com/luigifab/apijs)

 * Configuration with `$updateTranslationApijs` in `translate.conf.php`
 * Run `php translate.php apijs`

_Internal usage._
