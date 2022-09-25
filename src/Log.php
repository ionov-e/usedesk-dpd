<?php

namespace App;

/**
 * Логгер (синглтон)
 */
class Log
{
    // Категории логирования
    const DPD_CITY_FIND = 'dpd_city_find';
    const DPD_CITY_UPD = 'dpd_city_upd';
    const DPD_FORM = 'dpd_form';
    const DPD_ORDER = 'dpd_order';
    const UD_BLOCK = 'ud_block';
    const UD_ADD_TTN = 'ud_add_ttn';
    const UD_DEL_TTN = 'ud_del_ttn';
    const UNKNOWN = 'unknown';

    protected static $instance = null;

    private function __construct()
    {
    }

    /**
     * Возвращает объект класса Log (синглтон)
     *
     * @return Log
     */
    public static function getLogger(): Log
    {
        if (self::$instance == null) {
            self::$instance = new Log();
        }
        return self::$instance;
    }

    public static function debug(string $category, string $message): void
    {
        if (!LOG_MIN_LEVEL) {
            self::getLogger()->logMsg('debug', $category, $message);
        }
    }

    public static function info(string $category, string $message): void
    {
        if (LOG_MIN_LEVEL <= 1 ) {
            self::getLogger()->logMsg('info', $category, $message);
        }
    }

    public static function warning(string $category, string $message): void
    {
        if (LOG_MIN_LEVEL <= 2) {
            self::getLogger()->logMsg('warning', $category, $message);
        }
    }

    public static function error(string $category, string $message): void
    {
        if (LOG_MIN_LEVEL <= 3) {
            self::getLogger()->logMsg('error', $category, $message);
        }
    }

    public static function critical(string $category, string $message): void
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
        $logFolder = LOG_FOLDER_ROOT . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

        // Проверяет создана ли соответствующая папка. Создает, если не существует
        // Можно было бы вынести проверку коренной папке при инициализации, а другие нет - может пройти месяц, год (в новые папке сохранять)
        if (!is_dir($logFolder)) {
            mkdir($logFolder, 0770, true);
        }

        $logFileAddress = $logFolder . DIRECTORY_SEPARATOR . date('d') . '.log';

        $logString = date('H:i:s') . " [$level][{$_SERVER["REMOTE_ADDR"]}][$category] $message" . PHP_EOL;
        file_put_contents($logFileAddress, $logString, FILE_APPEND);
    }
}