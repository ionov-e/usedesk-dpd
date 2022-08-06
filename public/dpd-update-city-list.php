<?php
/**
 * Обновляет базу городов для доставки курьером из DPD
 */

use App\Service\DpdCityList;

require_once "../vendor/autoload.php";
require_once "../config/global.php";

DpdCityList::updateDpdCityList();

exit();