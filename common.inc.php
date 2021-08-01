<?php

namespace HexMakina\kadro
{
	use \HexMakina\LocalFS\FileSystem;

	define('KADRO_BASE', APP_BASE.'/lib/kadro/'); // this is project dependant, should be in settings
	// define('QIVIVE_BASE', APP_BASE.'/lib/qivive/'); // this is project dependant, should be in settings

	set_include_path(implode(PATH_SEPARATOR, [get_include_path(), APP_BASE.'/lib/', APP_BASE.'/vendor/', KADRO_BASE]));

	//---------------------------------------------------------------     autoloader
  require 'vendor/autoload.php';

	require 'lib/kadro/PSR4Autoloader.class.php';
	$loader=new PSR4Autoloader;
	$loader->register(); //Register loader with SPL autoloader stack.

	$loader->addNamespace('HexMakina', APP_BASE.'/lib/');

	// $loader->addNamespace('HexMakina\Crudites', APP_BASE.'/lib/Crudites/');
	// $loader->addNamespace('HexMakina\ORM', APP_BASE.'/lib/ORM/');
	// $loader->addNamespace('HexMakina\Lezer', APP_BASE.'/lib/Lezer/');
	// $loader->addNamespace('HexMakina\Format', APP_BASE.'/lib/Format');

	// $loader->addNamespace('HexMakina\kadro', KADRO_BASE);
	// $loader->addNamespace('HexMakina\qivive', QIVIVE_BASE);
	// $loader->addNamespace('HexMakina\LocalFS', __DIR__.'/Format/File');
	$loader->addNamespaceTree(KADRO_BASE);

	//---------------------------------------------------------------     erara raportado
	error_reporting(E_ALL);

	set_error_handler('\HexMakina\kadro\Logger\LogLaddy::error_handler');
	set_exception_handler('\HexMakina\kadro\Logger\LogLaddy::exception_handler');

	\HexMakina\Debugger\Debugger::init();

	//---------------------------------------------------------------     parametroj
	require_once 'configs/settings.php';
	$box=new Container\LeMarchand($settings);

  foreach($box->get('settings.app.namespaces') as $namespace => $path)
  {
  	$loader->addNamespace($namespace, $path);
  }

	define('PRODUCTION', $_SERVER['HTTP_HOST'] === $box->get('settings.app.production_host'));
	ini_set('display_errors', PRODUCTION ? 0 : 1);

	//---------------------------------------------------------------       logger
	$box->register('LoggerInterface', new Logger\LogLaddy());

	//---------------------------------------------------------------       router
	$box->register('RouterInterface', new Router\hopper($box->get('settings.RouterInterface')));

	//---------------------------------------------------------------        kuketoj
	setcookie('cookie_test', 'test_value', time()+(365 * 24 * 60 * 60), "/", "");
	$cookies_enabled=isset($_COOKIE['cookie_test']); // houston, do we have cookies ?

	if($cookies_enabled === false)
	{
		ini_set('session.use_cookies', 0);
		ini_set('session.use_only_cookies', 0);
		ini_set('session.use_trans_sid', 1);
		ini_set('session.cache_limiter', 'nocache');
	}

//---------------------------------------------------------------        Session Management
	$StateAgent=new StateAgent($box->get('settings.app.session_start_options') ?? []);
	$StateAgent->add_runtime_filters((array)$box->get('settings.filter'));
	$StateAgent->add_runtime_filters((array)($_SESSION['filter'] ?? []));
	$StateAgent->add_runtime_filters((array)($_REQUEST['filter'] ?? []));

	$box->register('StateAgent', $StateAgent);


	//---------------------------------------------------------------     parametroj:signo

	ini_set('default_charset', $box->get('settings.default.charset'));
	header('Content-type: text/html; charset='.strtolower($box->get('settings.default.charset')));

	//---------------------------------------------------------------     parametroj:linguo
	putenv('LANG='.$box->get('settings.default.language'));
	setlocale(LC_ALL, $box->get('settings.default.language'));

	//---------------------------------------------------------------     parametroj:datoj
	date_default_timezone_set($box->get('settings.default.timezone'));


	//---------------------------------------------------------------     ŝablonoj
	require_once 'smarty/smarty/libs/Smarty.class.php';
	// Load smarty template parser
	if(is_null($box->get('settings.smarty.template_path')) || is_null($box->get('settings.smarty.compiled_path')))
			throw new \Exception("SMARTY CONFIG ERROR: missing parameters");

	$smarty=new \Smarty();
	$box->register('template_engine', $smarty);

	$smarty->setTemplateDir($box->get('RouterInterface')->file_root() . $box->get('settings.smarty.template_path').'app');
	$smarty->addTemplateDir($box->get('RouterInterface')->file_root() . $box->get('settings.smarty.template_path'));
	$smarty->addTemplateDir(KADRO_BASE . 'Views/');

	$smarty->setCompileDir(APP_BASE . $box->get('settings.smarty.compiled_path'));
	$smarty->setDebugging($box->get('settings.smarty.debug'));

	$smarty->registerClass('Lezer', 			'\HexMakina\Lezer\Lezer');
	$smarty->registerClass('Marker', 			'\HexMakina\Format\HTML\Marker');
	$smarty->registerClass('Form', 				'\HexMakina\Format\HTML\Form');
	$smarty->registerClass('TableToForm',	'\HexMakina\Crudites\TableToForm');
	$smarty->registerClass('Dato', 				'\HexMakina\Format\Tempo\Dato');

	$smarty->assign('APP_NAME', $box->get('settings.app.name'));

	//---------------------------------------------------------------     lingva

	$languages=\HexMakina\Lezer\Lezer::languages_by_file(APP_BASE.'locale/');

  $smarty->assign('languages', $languages);

	$language=null;

	// changing / setting the language
	if(isset($_GET['lang']))
	{
		if(isset($languages[$_GET['lang']]))
		{
			$language=$_GET['lang'];
			if($cookies_enabled === true)
				setcookie('lang', $language, time()+(365 * 24 * 60 * 60), "/", "");
			else
				trigger_error('KADRO_SYSTEM_ERR_COOKIES_ARE_DISABLED_LANGUAGE_CANNOT_BE_STORED', E_USER_WARNING);
		}
		else
			throw new \Exception('KADRO_SYSTEM_ERR_INVALID_PARAMETER');
	}
	else if($cookies_enabled === true && array_key_exists('lang', $_COOKIE) && array_key_exists($_COOKIE['lang'], $languages))
		$language=$_COOKIE['lang'];

	if(is_null($language) && !empty($languages))
	{
		if(count($languages) === 1)
			$language=key($languages);
		elseif(array_key_exists($box->get('settings.default.language'), $languages))
			$language=$box->get('settings.default.language');
		else
			throw new \Exception('FALLBACK_TO_DEFAULT_LANGUAGE_FAILED');
		$i18n = new \HexMakina\Lezer\Lezer(APP_BASE.'locale/'.$language.'/user_interface.json', APP_BASE.'locale/cache/', $language);
		$i18n->init();
		$smarty->assign('language', $language);
	}
}

?>
