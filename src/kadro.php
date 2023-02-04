<?php

namespace HexMakina\kadro;

use HexMakina\Debugger\Debugger;
use HexMakina\LeMarchand\LeMarchand;
use HexMakina\Lezer\Lezer;

class kadro
{
    /**
     * @var string
     */
    private const ENV_PRODUCTION = 'production';

    /**
     * @var string
     */
    private const ENV_STAGING = 'staging';

    /**
     * @var string
     */
    private const ENV_DEVELOPPEMENT = 'dev';

    private \Psr\Container\ContainerInterface $box; // PSR-11 Service Locator, ugly until DI is ready

    public function __construct(array $settings)
    {
        //-- loading the Debugger class and therefor shorthands
        new Debugger();

        $this->box = LeMarchand::box($settings);

        $this->setErrorReporting();

        $log_laddy = $this->box->get('Psr\Log\LoggerInterface');

        //-- router
        $router = $this->box->get('HexMakina\BlackBox\RouterInterface');
        $router->addRoutes(require(__DIR__ . '/routes.php'));

        //-- session
        $StateAgent = $this->box->get('HexMakina\BlackBox\StateAgentInterface');
        $StateAgent->addRuntimeFilters((array)$this->box->get('settings.filter'));
        $StateAgent->addRuntimeFilters((array)($_SESSION['filter'] ?? []));
        $StateAgent->addRuntimeFilters((array)($_REQUEST['filter'] ?? []));


        $this->internationalisation();

        // ----     ŝablonoj
        $this->templating();

        // ----     lingva
        $this->locale();

    }

    public function container(): \Psr\Container\ContainerInterface
    {
        return $this->box;
    }

    public function isProduction(): bool
    {
        return $this->matchesHost(self::ENV_PRODUCTION);
    }

    public function isStaging(): bool
    {
        return $this->matchesHost(self::ENV_STAGING);
    }

    public function isDevelopment(): bool
    {
        return $this->matchesHost(self::ENV_DEVELOPPEMENT);
    }

    private function matchesHost(string $env) : bool
    {
      return $this->box->get(sprintf('settings.env.%s.host', $env)) === $_SERVER['HTTP_HOST'];

    }


    private function locale() : void
    {
        $json_path = $this->box->get('settings.locale.json_path');
        $cache_path = $this->box->get('settings.locale.cache_path');
        $fallback_lang = $this->box->get('settings.locale.fallback_lang');
        $lezer = new Lezer($json_path, $cache_path, $fallback_lang);
        $language = $lezer->availableLanguage();
        $lezer->init();

        $this->box->get('\Smarty')->assign('lezer', $lezer);
        $this->box->get('\Smarty')->assign('language', $language);

        setcookie('lang', $language, time() + (365 * 24 * 60 * 60), "/", "");
    }

    private function internationalisation(): void
    {
        // ----     parametroj:signo
        $setting = 'settings.default.charset';
        if (is_string($this->box->get($setting))) {
            ini_set('default_charset', $this->box->get($setting));
            header('Content-type: text/html; charset=' . strtolower($this->box->get($setting)));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        // ----     parametroj:linguo
        $setting = 'settings.default.language';
        if (is_string($this->box->get($setting))) {
            putenv('LANG=' . $this->box->get($setting));
            setlocale(LC_ALL, $this->box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        // ----     parametroj:datoj
        $setting = 'settings.default.timezone';
        if (is_string($this->box->get($setting))) {
            date_default_timezone_set($this->box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }
    }

    private function templating()
    {
        $engine = $this->box->get('HexMakina\BlackBox\TemplateInterface');
        foreach ($this->box->get('settings.template.extraDirectories') as $name => $template_dir) {
            $engine->addFolder($name, $template_dir, true);
        }

        // dd($engine);
        // $smarty = $this->box->get('\Smarty');
        // Load smarty template parser

        // $smarty->setTemplateDir($this->box->get('settings.smarty.template_app_directory'));
        //
        // foreach ($this->box->get('settings.smarty.template_extra_directories') as $template_dir) {
        //     $smarty->addTemplateDir($template_dir);
        // }
        //
        // $smarty->addTemplateDir(__DIR__ . '/Views/'); //kadro templates
        //
        // $setting = 'settings.smarty.compiled_path';
        // if (is_string($this->box->get($setting))) {
        //     $smarty->setCompileDir($this->box->get($setting));
        // } else {
        //     throw new \UnexpectedValueException($setting);
        // }
        //
        // $setting = 'settings.smarty.debug';
        // if (is_bool($this->box->get($setting))) {
        //     $smarty->setDebugging($this->box->get($setting));
        // } else {
        //     throw new \UnexpectedValueException($setting);
        // }
        //
        // $smarty->registerClass('Lezer', '\HexMakina\Lezer\Lezer');
        // $smarty->registerClass('Marker', '\HexMakina\Marker\Marker');
        // $smarty->registerClass('Form', '\HexMakina\Marker\Form');
        // $smarty->registerClass('TableToForm', '\HexMakina\kadro\TableToForm');
        // $smarty->registerClass('Dato', '\HexMakina\Tempus\Dato');
        //
        // $smarty->assign('APP_NAME', $this->box->get('settings.app.name'));
        //
        // return $smarty;
    }

    private function setErrorReporting(): void
    {
        //-- error & logs & messages
        if($this->isDevelopment())
        {
          error_reporting(E_ALL);
          ini_set('display_errors', 1);
        }
        elseif($this->isStaging())
        {
          error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
          ini_set('display_errors', 1);
        }
        elseif($this->isProduction())
        {
          error_reporting(0);
          ini_set('display_errors', 0);
        }
    }

}
