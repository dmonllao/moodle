<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

$coursesfilepath = __DIR__ . '/courses.txt';
$activitiesfilepath = __DIR__ . '/activities.txt';
$usersfilepath = __DIR__ . '/users.txt';

// Course and activity names.
$coursesfh = fopen($coursesfilepath, 'w');
$activitiesfh = fopen($activitiesfilepath, 'w');

$courses = $DB->get_recordset('course');
foreach ($courses as $course) {
    fwrite($coursesfh, $course->fullname . PHP_EOL);
    fwrite($coursesfh, $course->shortname . PHP_EOL);

    $modinfo = get_fast_modinfo($course, -1);
    foreach ($modinfo->get_instances() as $modinstances) {
        foreach ($modinstances as $cm) {
            fwrite($activitiesfh, $cm->name . PHP_EOL);
        }
    }
}
fclose($coursesfh);
fclose($activitiesfh);

// Users.
$usersfh = fopen($usersfilepath, 'w');
$users = $DB->get_recordset('user');
foreach ($users as $user) {
    fwrite($usersfh, fullname($user) . PHP_EOL);
    fwrite($usersfh, $user->firstname . ' ' . $user->lastname . PHP_EOL);
}
fclose($usersfh);
