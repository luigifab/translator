# translator

My custom tool to extract translations and update translations files.

The `translate.conf.php` file is yours. Never use it "as is", because it contains my configuration (to translate my modules, my plugins, my programs, and my website). At the beginning of the file, there is an example with all possible options.

If you like this tool, take some of your time to improve the translations of my modules/plugins/programs, go to https://bit.ly/2HyCCEc.

## Translate a module for OpenMage/Magento

 * Configuration in `$updateTranslationOpenMageModule` in `translate.conf.php`
 * Run `php translate.php openmage-module`

Read the examples in _translate.conf.php_ to write your own configuration. Be carefull, the _search_ option is not yet implemented.

## Translate a plugin for Redmine

 * Configuration in `$updateTranslationRedminePlugin` in `translate.conf.php`
 * Run `php translate.php redmine-plugin`

Read the examples in _translate.conf.php_ to write your own configuration. Be carefull, the _search_ option is not yet implemented.

## Translate po

 * Configuration in `$updateTranslationPo` in `translate.conf.php`
 * Run `php translate.php po`

Read the examples in _translate.conf.php_ to write your own configuration. Be carefull, the _search_ option is not yet implemented.

## Translate apijs

 * Configuration in `$updateTranslationApijs` in `translate.conf.php`
 * Run `php translate.php apijs`

_Internal usage._

## Translate website

 * Configuration in `$updateTranslationWebsite` in `translate.conf.php`
 * Run `php translate.php custom`

Read the examples in _translate.conf.php_ to write your own configuration.

## Regenerate translations files of OpenMage/Magento

 * Configuration in `$updateTranslationOpenMageFull` in `translate.conf.php`
 * Run `php translate.php openmage-full`

Read the example in _translate.conf.php_ to write your own configuration.

The _dir_ option must be a directory that contains a default OpenMage/Magento installation. All CSV files present in _app/locale/*/_ will be updated (expect _en_US_).

The _packs_ option must contains a directory that contains translations, for example the _app/locale/_ directory of https://github.com/versedi/Magento-Locales repository.
