<?php
/**
 * Call this script from CLI.
 *
 * Restart the db engine or clean the caches
 * before each call. So something like:
 *
 * $service postgres restart ; php perf-comparison.php
 * $service postgres restart ; php perf-comparison.php
 *
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

// Store results.
$exportdir = '/home/davidm/Desktop/DELETEME';

///////////////////////////////////////////////////


// To compare time and memory usage.
class comparison {

    protected static $starts = array();
    protected static $finishes = array();

    public static function starts($id) {
        self::$starts[$id] = array('time' => microtime(true), 'memory' => memory_get_usage());
    }

    public static function finishes($id) {
        self::$finishes[$id] = array('time' => microtime(true), 'memory' => memory_get_usage());
    }

    public static function get($id) {
        if (empty(self::$starts[$id]) || empty(self::$finishes[$id])) {
            throw Exception($id . ' not completely set');
        }
        $time = self::$finishes[$id]['time'] - self::$starts[$id]['time'];
        $memory = self::$finishes[$id]['memory'] - self::$starts[$id]['memory'];
        return 'Time: ' . $time . PHP_EOL .
            'Memory: ' . $memory . PHP_EOL;
    }
}

// Process the download logs.
function download_logs($classname, $logstandardreader) {

    $filters = new stdClass();
    $filters->logreader = $logstandardreader;
    $filters->orderby = 'timecreated ASC';
    $filters->userid = '2';

    $tablelog = new $classname('report_log' . rand(1000, 9999), $filters);
    $tablelog->define_baseurl('pref-comparison.php');
    $tablelog->is_downloadable(true);
    $tablelog->is_downloading('csv');
    $tablelog->out(123, false);
}

///////////////////////////////////////////////////

global $CFG;

$classname = 'report_log_table_log';

purge_all_caches();
$logmanager = get_log_manager();
$readers = $logmanager->get_readers('\logstore_standard\log\store');
$logstandardreader = $readers['logstore_standard'];

comparison::starts($classname);

$CFG->comparison = $classname;
download_logs($classname, $logstandardreader);

comparison::finishes($classname);

echo comparison::get($classname) . PHP_EOL;
