<?php
/**
 * Created by PhpStorm.
 * User: flyer
 * Date: 16/12/22
 * Time: 上午10:42
 */

namespace Bootstrap;

use Monolog\Logger as Monolog;
use Monolog\Formatter\LineFormatter;
use Illuminate\Log\Writer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\ConfigureLogging as BaseConfigureLogging;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Config;


class ConfigureLogging extends BaseConfigureLogging
{
    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        // Stream handlers
        $config = $app->make('config');
        $logPath = $config->get('app.log_path');
        $logLevel = $config->get('app.log_level','debug');
        $logMaxFiles = $config->get('app.log_max_files',30);
        $bubble = false;

        // Stream Handlers
        $date = date('Y-m-d',time());
        $date = date('Y-m-d-H',time());
        $streamHandler = new StreamHandler($logPath.'-'.$date.'.log', $logLevel, $bubble);
        $logFormat = "[%datetime%][%channel%][%level_name%][%message%][%context%]%extra%\n";
        $formatter = new LineFormatter($logFormat);
        $streamHandler->setFormatter($formatter);
        $monolog = $log->getMonolog();
        $monolog->pushHandler($streamHandler);
//        $log->useDailyFiles($logPath.$date.'.log',$logMaxFiles,$logLevel);
    }
    protected function configureSingleHandler(Application $app, Writer $log)
    {
        // Stream handlers
        $config = $app->make('config');
        $logPath = $config->get('app.log_path');
        $logLevel = $config->get('app.log_level','debug');
        $logMaxFiles = $config->get('app.log_max_files',30);
        $bubble = false;

        // Stream Handlers
        $date = date('Y-m-d',time());
        $streamHandler = new StreamHandler($logPath.'-'.$date.'.log', $logLevel, $bubble);
        $logFormat = "[%datetime%][%channel%][%level_name%][%message%][%context%]%extra%\n";
        $formatter = new LineFormatter($logFormat);
        $streamHandler->setFormatter($formatter);
        $monolog = $log->getMonolog();
        $monolog->pushHandler($streamHandler);
//        $log->useDailyFiles($logPath.$date.'.log',$logMaxFiles,$logLevel);
    }

}


