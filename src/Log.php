<?php

namespace App;

/**
 * Логгер (синглтон)
 */
class Log
{
    // Категории логирования
    const FTP = 'ftp';
    const SOAP = 'soap';
    const FORM = 'form';
    const INPUT = 'input';
    const START = 'start';
    const STATE = 'state';


    #TODO Минимальный уровень имплементировать
    #TODO IP возможно добавить откуда запрос

    protected static $instance = null;

    private function __construct()
    {
    }

    /**
     * Возвращает объект класса Log (синглтон)
     *
     * @return Log
     */
    static public function getLogger(): Log
    {
        if (self::$instance == null) {
            self::$instance = new Log();
        }
        return self::$instance;
    }

    static function debug(string $category, string $message): void
    {
        self::getLogger()->logMsg('debug', $category, $message);
    }

    static function info(string $category, string $message): void
    {
        self::getLogger()->logMsg('info', $category, $message);
    }

    static function warning(string $category, string $message): void
    {
        self::getLogger()->logMsg('warning', $category, $message);
    }

    static function error(string $category, string $message): void
    {
        self::getLogger()->logMsg('error', $category, $message);
    }

    static function critical(string $category, string $message): void
    {
        self::getLogger()->logMsg('critical', $category, $message);
    }

    /**
     * Добавление строки в лог
     *
     * @param string $level Уровень (Например: info)
     * @param string $category Категория (Например: ftp)
     * @param string $message Строка для логирования
     *
     * @return void
     */
    private function logMsg(string $level, string $category, string $message): void
    {
        $logFolder = ".." . DIRECTORY_SEPARATOR . LOG_FOLDER_ROOT . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

        // Проверяет создана ли соответствующая папка. Создает, если не существует
        // Можно было бы вынести проверку коренной папке при инициализации, а другие нет - может пройти месяц, год (в новые папке сохранять)
        if (!is_dir($logFolder)) {
            mkdir($logFolder, 0770, true);
        }

        $logFileAddress = $logFolder . DIRECTORY_SEPARATOR . date('d') . '.log';

        $logString = date('H:i:s') . " [$level][$category] $message" . PHP_EOL;
        file_put_contents($logFileAddress, $logString, FILE_APPEND);
    }
}