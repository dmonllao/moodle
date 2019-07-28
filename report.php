<?php

// define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/report.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title('asd');
$PAGE->set_heading('asd');
echo $OUTPUT->header();

$courseid = optional_param('courseid', 5282, PARAM_INT);
$starttime = optional_param('starttime', null, PARAM_INT);
$endtime = optional_param('endtime', null, PARAM_INT);

// TODO $indicators array and verified is_valid instances.
$indicators = '["mod_forum\\\analytics\\\indicator\\\social_breadth"]';
// $indicators = '["core\\\analytics\\\indicator\\\read_actions", "mod_forum\\\analytics\\\indicator\\\social_breadth"]';
$results = \core_analytics\manager::report($courseid, $indicators, $starttime, $endtime);

$headers = array_shift($results);
// var_dump($results);

// TODO TEST
$results = array_slice($results, 0, 500);

// From 0 to 1.
$values = array_map(function($result) {
    // This is the sample id value.
    $zeroto1 = [array_shift($result)];

    foreach($result as $value) {
        $zeroto1[] = strval(($value + 1) / 2 * 100);
    }
    return $zeroto1;
}, $results);

$index = 1;
$indexmean = 2;

$chart = new \core\chart_pie();
$chart->set_title('pieee');
$grouped = array_count_values(array_column($values, $index));
$series1 = new \core\chart_series('valuessss', array_values($grouped));
$chart->add_series($series1);
$chart->set_labels(array_map(function($el) {
    return $el . '%';
}, array_keys($grouped)));
echo $OUTPUT->render($chart);

$chart = new \core\chart_line();
$chart->set_title('lineee');
$chart->set_smooth(true);
$series1 = new \core\chart_series('valuessss', array_column($values, $index));
$series2 = new \core\chart_series('mean', array_column($values, $indexmean));
$series2->set_type(\core\chart_series::TYPE_LINE);
$chart->add_series($series1);
$chart->add_series($series2);
$chart->set_labels(array_column($values, 0));

echo $OUTPUT->render($chart);
echo $OUTPUT->footer();