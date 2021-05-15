<?php 
namespace HexMakina\kadro\Logger;


/**
 * Extends PSR log levels to include success
 */
class LogLevel extends \Psr\Log\LogLevel
{
    const NICE = 'success';
}
