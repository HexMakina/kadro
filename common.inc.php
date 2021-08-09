<?php

namespace HexMakina\kadro
{
  use \HexMakina\LocalFS\FileSystem;
  use \HexMakina\Lezer\Lezer;

  define('KADRO_BASE', APP_BASE.'/lib/kadro/'); // this is project dependant, should be in settings
  // define('QIVIVE_BASE', APP_BASE.'/lib/qivive/'); // this is project dependant, should be in settings

  set_include_path(implode(PATH_SEPARATOR, [get_include_path(), APP_BASE, APP_BASE.'/lib/', APP_BASE.'/vendor/', KADRO_BASE]));

  //---------------------------------------------------------------     autoloader
  require APP_BASE.'vendor/autoload.php';

  require 'PSR4Autoloader.class.php';
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

  set_error_handler('\HexMakina\LogLaddy\LogLaddy::error_handler');
  set_exception_handler('\HexMakina\LogLaddy\LogLaddy::exception_handler');

  \HexMakina\Debugger\Debugger::init();

  //---------------------------------------------------------------     parametroj
  require_once APP_BASE.'configs/settings.php';
  $box=new Container\LeMarchand($settings);

  foreach($box->get('settings.app.namespaces') as $namespace => $path)
  {
    $loader->addNamespace($namespace, $path);
  }

  define('PRODUCTION', $_SERVER['HTTP_HOST'] === $box->get('settings.app.production_host'));
  ini_set('display_errors', PRODUCTION ? 0 : 1);

  //---------------------------------------------------------------       logger
  $box->register('LoggerInterface', new \HexMakina\LogLaddy\LogLaddy());

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
  $StateAgent=new \HexMakina\StateAgent\Smith($box->get('settings.app.session_start_options') ?? []);
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

  $smarty->registerClass('Lezer',       '\HexMakina\Lezer\Lezer');
  $smarty->registerClass('Marker',       '\HexMakina\Marker\Marker');
  $smarty->registerClass('Form',         '\HexMakina\Marker\Form');
  $smarty->registerClass('TableToForm',  '\HexMakina\kadro\TableToForm');
  $smarty->registerClass('Dato',         '\HexMakina\Format\Tempo\Dato');

  $smarty->assign('APP_NAME', $box->get('settings.app.name'));

  //---------------------------------------------------------------     lingva
  $locale_path = $box->get('settings.locale.directory_path');
  $file_name = $box->get('settings.locale.file_name');
  $fallback_lang = $box->get('settings.locale.fallback_lang');

  $lezer = new Lezer($locale_path.'/'.$file_name, $locale_path.'/cache', $fallback_lang);
  $language = $lezer->one_language();

  $lezer->init();

  $smarty->assign('language', $language);
  if($cookies_enabled === true)
    setcookie('lang', $language, time()+(365 * 24 * 60 * 60), "/", "");

}
