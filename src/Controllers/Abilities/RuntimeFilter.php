<?php

namespace HexMakina\kadro\Controllers\Abilities;

class RuntimeFilter
{
    private static RuntimeFilter $instance;
    private array $filters;

    public static function getInstance(): StateAgentInterface
    {
        if (is_null(self::$instance)) {
            self::$instance = new RuntimeFilter();  // $options : https://www.php.net/manual/fr/session.configuration.php
        }

        return self::$instance;
    }


    private function __construct()
    {
        $this->filters = []
        // $smith->addRuntimeFilters((array)($settings['box']->get('settings.filter')));

        foreach($default_filters as $filters){
            $this->add($filters);
        }

        $this->add((array)($_REQUEST[$filter_key] ?? []));
    }

    public function add(array $filters): void
    {
        $this->filters = array_merge($this->filters, $filters);
    }

    public function set(string $filter_name, string $value): void
    {
        $this->filters[$filter_name] = $value;
    }

    public function has(string $filter_name): bool
    {
        return isset($this->filters[$filter_name]) && strlen('' . $this->filters[$filter_name]) > 0;
    }

    public function get(string $filter_name)
    {
        return $this->filters[$filter_name] ?? null;
    }

    public function reset(string $filter_name = null): void
    {
        if (is_null($filter_name)) {
            $this->filters = [];
        } else {
            unset($this->filters[$filter_name]);
        }
    }

    public function addFilter(string $filter_name, string $value): void
    {
        $_SESSION[self::INDEX_FILTER][$filter_name] = $value;
    }

    public function filters(string $filter_name = null, string $value = null): ?string
    {
        if (is_null($filter_name)) {
            return $_SESSION[self::INDEX_FILTER];
        }

        if (!is_null($value)) {
            $this->addFilter($filter_name, $value);
        }

        return $_SESSION[self::INDEX_FILTER][$filter_name] ?? null;
    }


}