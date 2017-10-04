<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

$modelid = 1;
$fortraining = true;


$now = time();
$future = time() + (WEEKSECS * 4);

\core\session\manager::set_user(get_admin());
$model = new \core_analytics\model($modelid);

$analysables = $model->get_analyser()->check_analysables($fortraining);
foreach ($analysables as list($analysable, $result)) {
    if ($result === true) {
        $result = get_string('validformodel');
    }
    var_dump($analysable->get_name() . ' result: ' . $result);
}
