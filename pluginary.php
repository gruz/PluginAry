<?php
/**
 * @package    PluginAry
 *
 * @author     Gruz <arygroup@gmail.com>
 * @copyright  Copyleft (є) 2016 - All rights reversed
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('gjfields.gjfields');
jimport('gjfields.helper.plugin');

$latest_gjfields_needed_version = '1.1.4';
$error_msg = 'Install the latest GJFields plugin version <span style="color:black;">' . __FILE__ . '</span>:
<a href="http://www.gruz.org.ua/en/extensions/gjfields-sefl-reproducing-joomla-jform-fields.html">GJFields</a>';


$isOk = true;

while (true)
{
	$isOk = false;

	if (!class_exists('JPluginGJFields'))
	{
		$error_msg = 'Strange, but missing GJFields library for <span style="color:black;">'
			. __FILE__ . '</span><br> The library should be installed together with the extension... Anyway, reinstall it:
			<a href="http://www.gruz.org.ua/en/extensions/gjfields-sefl-reproducing-joomla-jform-fields.html">GJFields</a>';
		break;
	}

	$gjfields_version = file_get_contents(JPATH_ROOT . '/libraries/gjfields/gjfields.xml');
	preg_match('~<version>(.*)</version>~Ui', $gjfields_version, $gjfields_version);
	$gjfields_version = $gjfields_version[1];

	if (version_compare($gjfields_version, $latest_gjfields_needed_version, '<'))
	{
		break;
	}

	$isOk = true;
	break;
}

if (!$isOk)
{
	JFactory::getApplication()->enqueueMessage($error_msg, 'error');

	if (JFactory::getApplication()->isAdmin())
	{
	}
}
else
{
	/**
	 * Empty class to be extended
	 *
	 * @author  Gruz <arygroup@gmail.com>
	 * @since   0.0.1
	 */
	class PlgSystemPluginaryCore extends JPluginGJFields
				{
	}

	if (!function_exists('php_syntax_error'))
	{
		include dirname(__FILE__) . '/helpers/php_syntax_error.php';
	}

	$app = JFactory::getApplication();
	$plgName = 'Pluginary';
	$firstRun = $app->get('FirstRun', true, $plgName);

	if ($firstRun)
	{
		$app->set('FirstRun', true, $plgName);
	}
	else
	{
		return;
	}

	JLoader::register('plgSystemPluginary', __FILE__);

	// Generate and empty object
	$plgParams = new JRegistry;

	// Get plugin details
	$plugin = JPluginHelper::getPlugin('system', 'pluginary');

	// Load params into our params object
	$plgParams->loadString($plugin->params);

	$runinbackend = $plgParams->get('runinbackend')[0];
	$debug = $plgParams->get('debug')[0];

	if (!$runinbackend && JFactory::getApplication()->isAdmin())
	{
		return;
	}

	$enabled = $plgParams->get('{rulesgroup')->isenabled;

	$code = $plgParams->get('{rulesgroup')->code;
	$methodtorun = $plgParams->get('{rulesgroup')->methodtorun;

	$custom_templates = array();
	$functionsToBeAdded = array();

	$c = count($enabled);

	for ($k = 0; $k < $c; $k += 2)
	{
		$v = $enabled[$k];

		if ($v !== '1')
		{
			continue;
		}

		$method = $plgParams->get('{rulesgroup')->{'methodtorun_' . $methodtorun[$k]}[$k];

		$hasErrors = \PluginAryHelper\php_syntax_error($code[$k]);

		if ($hasErrors === false)
		{
			$functionsToBeAdded[$method][] = $code[$k];
		}
		else
		{
			$error_msg = ''
				. '<pre>' . $method . ' () {
					' . htmlentities($code[$k]) . '
				}	</pre>';
			JFactory::getApplication()->enqueueMessage('PluginAry: ' . $error_msg, 'error');
		}
	}

	$app = JFactory::getApplication();
	$path = __DIR__ . '/methods/';

	if (is_dir($path))
	{
		$pluginMethods = JFolder::files($path, '\.php');

		foreach ($pluginMethods as $k => $fileName)
		{
			$method = JFile::stripExt($fileName);

			$code = file_get_contents($path . '/' . $fileName);
			$functionsToBeAdded[$method][] = '?>' . $code;
		}
	}


	$codeToBeInserted = '';

	foreach ($functionsToBeAdded as $method => $codes)
	{
		$codeToBeInserted .= '
			public function ' . $method . ' () {
				$arg_list = func_get_args();
		';

		foreach ($codes as $k => $code)
		{
			$codeToBeInserted .= '
				' . $code . '
			';
		}

		$codeToBeInserted .= '
			}
		';
	}

	if ($debug)
	{
		echo '<pre> Line: ' . __LINE__ . ' ' . PHP_EOL;
		print_r(htmlentities($codeToBeInserted));
		echo PHP_EOL . '</pre>' . PHP_EOL;
	}

	$class_dynamic = '
	class plgSystemPluginary extends plgSystemPluginaryCore {
		public function __construct(& $subject, $config) {
			parent::__construct($subject, $config);
		}
		' . $codeToBeInserted . '
	}';

	eval($class_dynamic);
}
