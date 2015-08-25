<?php

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

require_once(__DIR__ . '/config.php');

$PAGE->set_url(new \moodle_url('/render.php'));
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();

$i = 0;
while($i < 2000) {
    //$widget = new three_columns_manual(array('asd' . $i, 'dtdtrert' . $i, '23452345345' . $i));
    //echo $OUTPUT->render($widget);
    echo $OUTPUT->three_columns_manual('asd' . $i, 'dtdtrert' . $i, '23452345345' . $i);
    $i++;
}

echo $OUTPUT->footer();
