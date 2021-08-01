<?php
/*
 * LogLaddy
 *
 * I carry a log – yes. Is it funny to you? It is not to me.
 * Behind all things are reasons. Reasons can even explain the absurd.
 *
 * LogLaddy manages error reporting and crud tracking
 * PSR-3 Compliant, where NOTICE is to be read as SUCCESS
 */

namespace HexMakina\kadro\Logger;

use \HexMakina\Crudites\{Crudites,CruditesException,Table};
use \HexMakina\Crudites\TableInterface;


class LogLaddy implements LoggerInterface
{
  use \Psr\Log\LoggerTrait; // PSR implementation
  use \HexMakina\Debugger\Debugger;

  const LOG_TABLE_NAME = 'kadro_action_logger';
  const REPORTING_USER = 'user_messages';

  const INTERNAL_ERROR = 'error';

  // TODO: separate KadroException (parent of all operational exceptions)
  // from \Exception (parent of all exception)
  // CruditesException must Extend KadroException, and all throw new \Exception must be S & D
  // Only then the notion of USER_EXCEPTION will be pertinent
  // create KadroException, ew constant KADRO_EXCEPTION, adapt code, test ..
  const USER_EXCEPTION = 'exception';

  const LOG_LEVEL_SUCCESS = 'ok';

  private $has_halting_messages = false;


  /**
   * Everything went fine, which is always nice.
   * LogLaddy, being born yesterday, is a bit more optimistic than PSRLog
   * @param string $message
   * @param array  $context
   *
   * @return void
   */
  public function nice($message, array $context = array())
  {
      $this->log(LogLevel::NICE, $message, $context);
  }

  // ----------------------------------------------------------- Static handlers for error, use set_error_handler('\HexMakina\kadro\Logger\LogLaddy::error_handler')
  public static function error_handler($level, $message, $file = '', $line = 0)
  {
    $loglevel = self::map_error_level_to_log_level($level);

    (new LogLaddy())->$loglevel($message, ['file' => $file, 'line' => $line, 'trace' => debug_backtrace()]);
  }

  // ----------------------------------------------------------- Static handlers for throwables, use set_exception_handler('\HexMakina\kadro\Logger\LogLaddy::exception_handler');
  public static function exception_handler($throwable)
  {
    $context['text'] = $throwable->getMessage();
    $context['file'] = $throwable->getFile();
    $context['line'] = $throwable->getLine();
    $context['code'] = $throwable->getCode();
    $context['class'] = get_class($throwable);
    $context['trace'] = $throwable->getTrace();

    if(is_subclass_of($throwable, 'Error') || get_class($throwable) === 'Error')
      (new LogLaddy())->alert(self::INTERNAL_ERROR, $context);
    elseif(is_subclass_of($throwable, 'Exception') || get_class($throwable) === 'Exception')
      (new LogLaddy())->notice(self::USER_EXCEPTION, $context);
    else
    {
      die('This Throwable is not an Error or an Exception. This is unfortunate.');
    }

  }

  public function system_halted($level)
  {

    switch($level)
    {
      case LogLevel::ERROR:
      case LogLevel::CRITICAL:
      case LogLevel::ALERT:
      case LogLevel::EMERGENCY:
        return true;
    }
    return false;
  }

  // ----------------------------------------------------------- Implementation of LoggerInterface::log(), all other methods are in LoggerTrait

  public function log($level, $message, array $context = [])
  {

    $display_error = null;

    // --- Handles Throwables (exception_handler())
    if($message==self::INTERNAL_ERROR || $message== self::USER_EXCEPTION)
    {
      $this->has_halting_messages = true;
      $display_error = \HexMakina\Debugger\Debugger::format_throwable_message($context['class'], $context['code'], $context['file'], $context['line'], $context['text']);
      error_log($display_error);
      $display_error.= \HexMakina\Debugger\Debugger::format_trace($context['trace'], false);
      self::HTTP_500($display_error);
    }
    elseif($this->system_halted($level)) // analyses error level
    {
      $display_error = sprintf(PHP_EOL.'%s in file %s:%d'.PHP_EOL.'%s', $level, \HexMakina\Debugger\Debugger::format_file($context['file']), $context['line'], $message);
      error_log($display_error);
      $display_error.= \HexMakina\Debugger\Debugger::format_trace($context['trace'], false);
      self::HTTP_500($display_error);
    }
    else
    {// --- Handles user messages, through SESSION storage
      $this->report_to_user($level, $message, $context);
    }

    // --- may of may not show errors, depends on environment

  }

  public static function HTTP_500($display_error)
  {
    \HexMakina\Debugger\Debugger::display_errors($display_error);
    http_response_code(500);
    die;
  }
  // ----------------------------------------------------------- Allows to know if script must be halted after fatal error
  // TODO NEH.. not good
  public function has_halting_messages()
  {
    return $this->has_halting_messages === true;
  }

  // ----------------------------------------------------------- User messages

  // ----------------------------------------------------------- User messages:add one
  /* Before decoupling with Lezer
  public function report_to_user($level, $message, $context = [])
  {
    {
    if(defined("L::$message")) // message isa translatable code
      foreach($context as $i => $param) // message need translated params
        if(defined("L::$param"))
          $context[$i] = L($param);

      $message = L($message, $context);
    }

    // $_SESSION[self::REPORTING_USER][$level][] = date('Y-m-d H:i:s.u').' '.$message;
    // ddt($message);
    $_SESSION[self::REPORTING_USER] = $_SESSION[self::REPORTING_USER] ?? [];
    $_SESSION[self::REPORTING_USER][$level] = $_SESSION[self::REPORTING_USER][$level] ?? [];

    // $_SESSION[self::REPORTING_USER][$level][] = $message; // this
    $_SESSION[self::REPORTING_USER][$level][] = [$message, $context];
  }
*/
  // ----------------------------------------------------------- User messages:add one
  public function report_to_user($level, $message, $context = [])
  {
    if(!isset($_SESSION[self::REPORTING_USER]))
      $_SESSION[self::REPORTING_USER] = [];

    if(!isset($_SESSION[self::REPORTING_USER][$level]))
      $_SESSION[self::REPORTING_USER][$level] = [];

    $_SESSION[self::REPORTING_USER][$level][] = [$message, $context];
  }

  // ----------------------------------------------------------- User messages:get all
  public function get_user_report()
  {
    return $_SESSION[self::REPORTING_USER] ?? [];
  }

  // ----------------------------------------------------------- User messages:reset all
  public function clean_user_report()
  {
    unset($_SESSION[self::REPORTING_USER]);
  }

  // ----------------------------------------------------------- CRUD Tracking:get for one model
  // public function history($table, $id, $sort='DESC')
  // {
  //   $table_alias = 'logladdy';
  //   $table = Crudites::inspect(self::LOG_TABLE_NAME);
  //   $q = $table->select(["$table_alias.*", 'name'], $table_alias);
  //   $q->join([User::table_name(), 'u'], [[$table_alias,'query_by', 'u','id']], 'INNER');
  //   $q->aw_fields_eq(['query_table' => $table, 'query_id' => $id], $table_alias);
  //
  //   $q->order_by(['query_on', $sort]);
  //   $q->run();
  //   $res = $q->ret_ass();
  //
  //   return $res;
  // }

  // ----------------------------------------------------------- CRUD Tracking:get for many models
  public function changes($options=[])
  {

    if(!isset($options['limit']) || empty($options['limit']))
      $limit = 1000;
    else  $limit = intval($options['limit']);

    // TODO SELECT field order can't change without adapting the result parsing code (foreach $res)
    $table = Crudites::inspect(self::LOG_TABLE_NAME);
    $select_fields = ['SUBSTR(query_on, 1, 10) AS working_day', 'query_table', 'query_id',  'GROUP_CONCAT(DISTINCT query_type, "-", query_by) as action_by'];
    $q = $table->select($select_fields);
    $q->order_by(['', 'working_day', 'DESC']);
    $q->order_by([self::LOG_TABLE_NAME, 'query_table', 'DESC']);
    $q->order_by([self::LOG_TABLE_NAME, 'query_id', 'DESC']);

    $q->group_by('working_day');
    $q->group_by('query_table');
    $q->group_by('query_id');
    $q->having("action_by NOT LIKE '%D%'");
    $q->limit($limit);

    foreach($options as $o => $v)
    {
          if(preg_match('/id/', $o))                    $q->aw_eq('query_id', $v);
      elseif(preg_match('/tables/', $o))                $q->aw_string_in('query_table', is_array($v) ? $v : [$v]);
      elseif(preg_match('/table/', $o))                 $q->aw_eq('query_table', $v);
      elseif(preg_match('/(type|action)/', $o))         $q->aw_string_in('query_type', is_array($v) ? $v : [$v]);
      elseif(preg_match('/(date|query_on)/', $o))       $q->aw_like('query_on', "$v%");
      elseif(preg_match('/(oper|user|query_by)/', $o))  $q->aw_eq('query_by', $v);
    }

    try{$q->run();}
    catch(CruditesException $e){vdt($e);return false;}

    $res = $q->ret_num(); // ret num to list()
    // ddt($res);
    $ret = [];

    foreach($res as $r)
    {
      list($working_day, $class, $instance_id, $logs) = $r;

      if(!isset($ret[$working_day]))
        $ret[$working_day] = [];
      if(!isset($ret[$working_day][$class]))
        $ret[$working_day][$class] = [];

      $ret[$working_day][$class][$instance_id] = $logs;
    }
    return $ret;
  }

  // ----------------------------------------------------------- Error level mapping from \Psr\Log\LogLevel.php & http://php.net/manual/en/errorfunc.constants.php
  /** Error level meaning , from \Psr\Log\LogLevel.php
   * const EMERGENCY = 'emergency'; // System is unusable.
   * const ALERT     = 'alert'; // Action must be taken immediately, Example: Entire website down, database unavailable, etc.
   * const CRITICAL  = 'critical';  // Application component unavailable, unexpected exception.
   * const ERROR     = 'error'; // Run time errors that do not require immediate action
   * const WARNING   = 'warning'; // Exceptional occurrences that are not errors, undesirable things that are not necessarily wrong
   * const NOTICE    = 'notice'; // Normal but significant events.
   * const INFO      = 'info'; // Interesting events. User logs in, SQL logs.
   * const DEBUG     = 'debug'; // Detailed debug information.
  */
  private static function map_error_level_to_log_level($level) : string
  {
    // http://php.net/manual/en/errorfunc.constants.php

    $m[E_ERROR]=$m[E_PARSE]=$m[E_CORE_ERROR]=$m[E_COMPILE_ERROR]=$m[E_USER_ERROR]=$m[E_RECOVERABLE_ERROR]=LogLevel::ALERT;
    $m[1]=$m[4]=$m[16]=$m[64]=$m[256]=$m[4096]=LogLevel::ALERT;

    $m[E_WARNING]=$m[E_CORE_WARNING]=$m[E_COMPILE_WARNING]=$m[E_USER_WARNING]=LogLevel::CRITICAL;
    $m[2]=$m[32]=$m[128]=$m[512]=LogLevel::CRITICAL;

    $m[E_NOTICE]=$m[E_USER_NOTICE]=LogLevel::ERROR;
    $m[8]=$m[1024]=LogLevel::ERROR;

    $m[E_STRICT]=$m[E_DEPRECATED]=$m[E_USER_DEPRECATED]=$m[E_ALL]=LogLevel::DEBUG;
    $m[2048]=$m[8192]=$m[16384]=$m[32767]=LogLevel::DEBUG;

    if(isset($m[$level]))
      return $m[$level];

    throw new \Exception(__FUNCTION__."($level): $level is unknown");
  }
}
