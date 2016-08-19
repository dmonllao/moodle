<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task to evaluate models.
 *
 * @package    tool_ie
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ie\task;

require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');
require_once($CFG->dirroot . '/lib/grade/constants.php');

/**
 * Simple task to evaluate models.
 *
 * @package    core
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluate_model_task extends \core\task\scheduled_task {

    const TEACHER_CAPABILITY = 'moodle/grade:viewall';

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return 'Train stuff';
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        $courseid = 5282;

        $path = '/home/davidm/Desktop/ml/';
        if (!file_exists($path)) {
            throw new \Exception('Hey, you need to manually create ' . $path . ' this is still a WIP');
        }
        $logfilepath = $path . '/log.examples.latest.csv';
        $logfh = fopen($logfilepath, 'wa+');

        //$classes = $this->enrolments_set_profile($logfh, array('courseid' => $courseid));
        //$classes = $this->enrolments_write_grades($logfh, array('courseid' => $courseid));
        //$classes = $this->enrolments_activity_grades($logfh, array('courseid' => $courseid));
        $classes = $this->all_indicators($logfh, array('courseid' => $courseid));

        fclose($logfh);

        // Process it as a classification problem.
        mtrace('Running classifier');

        if (count($classes) > 2) {
            $cmd = 'python check-classification-multiclass.py ' . escapeshellarg($logfilepath) . ' 0.7';
        } else {
            $result = $this->model_singleclass_classification($logfilepath);
        }

        if ($result->exitcode !== 0) {
            mtrace('Error training the model: ' . implode(', ', $result->errors));
        } else {
            mtrace('Model successfully trained with a ' . $result->phi . ' confidence');
        }
    }

    protected function add_row($fh, $data) {
        fputcsv($fh, $data);
    }

    protected function model_singleclass_classification($logfilepath, $validation = 0.7, $deviation = 0.02, $nruns = 10) {
        global $CFG;

        $cmd = 'python ' .
            escapeshellarg($CFG->dirroot . DIRECTORY_SEPARATOR . $CFG->admin . DIRECTORY_SEPARATOR . 'tool' . DIRECTORY_SEPARATOR .
                'ie' . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'check-classification-singleclass.py') . ' ' .
            escapeshellarg($logfilepath) . ' ' .
            escapeshellarg($validation) . ' ' .
            escapeshellarg($deviation) . ' ' .
            escapeshellarg($nruns);

        $output = null;
        $exitcode = null;
        $cwd= getcwd();
        chdir($CFG->dirroot);
        $result = exec($cmd, $output, $exitcode);
        chdir($cwd);

        if (!$result) {
            // TODO lang
            throw new \moodle_exception('noclassifierresults', 'insights');
        }


        if (!$resultobj = json_decode($result)) {
            // TODO lang
            throw new \moodle_exception('classifiererror', 'insights', json_last_error_msg());
        }

        return $resultobj;
    }

    protected function enrolments_set_profile(&$logfh, $params) {
        global $DB;

        $classes = array(0.5, 1);

        $courseid = $params['courseid'];

        $coursecontext = \context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext);

        $userclasses = $this->get_user_grade_classes($courseid, $users, $classes);

        foreach ($users as $user) {

            $exampledata = array();

            if (!isset($userclasses[$user->id])) {
                continue;
            }

            // X - picture set.
            if ($user->picture) {
                $exampledata[] = 1;
            } else {
                $exampledata[] = 0;
            }

            if ($user->description) {
                $exampledata[] = 1;
            } else {
                $exampledata[] = 0;
            }

            $exampledata[] = $DB->count_records('message_read', array('useridto' => $user->id));
            $exampledata[] = $DB->count_records('message_read', array('useridfrom' => $user->id));
            $exampledata[] = $DB->count_records('message', array('useridto' => $user->id));
            $exampledata[] = $DB->count_records('message', array('useridfrom' => $user->id));

            // y - Course grade.
            $class = $userclasses[$user->id];

            $this->add_row($logfh, array_merge($exampledata, array($class)));
        }

        return $classes;
    }

    protected function enrolments_write_grades(&$logfh, $params) {
        global $DB;

        $classes = array(0.5, 1);

        $courseid = $params['courseid'];

        $coursecontext = \context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext);

        $userclasses = $this->get_user_grade_classes($courseid, $users, $classes);

        $writes = $DB->get_records_sql("SELECT userid, count(*) AS numlogs FROM {logstore_standard_log} WHERE courseid = :courseid AND crud IN ('c', 'u', 'd') GROUP BY userid",
            array('courseid' => $courseid));

        $reads = $DB->get_records_sql("SELECT userid, count(*) AS numlogs FROM {logstore_standard_log} WHERE courseid = :courseid AND crud = 'r' GROUP BY userid",
            array('courseid' => $courseid));

        foreach ($users as $user) {

            $exampledata = array();

            if (!isset($userclasses[$user->id])) {
                continue;
            }

            if (!empty($writes[$user->id])) {
                $exampledata[] = $writes[$user->id]->numlogs;
            } else {
                $exampledata[] = 0;
            }

            if (!empty($reads[$user->id])) {
                $exampledata[] = $reads[$user->id]->numlogs;
            } else {
                $exampledata[] = 0;
            }

            // y - Course grade.
            $class = $userclasses[$user->id];

            $this->add_row($logfh, array_merge($exampledata, array($class)));
        }

        return $classes;
    }


    protected function all_indicators(&$logfh, $params) {
        global $DB;

        $classes = array(0.5, 1);

        $courseid = $params['courseid'];

        $modinfo = get_fast_modinfo($courseid, -1);

        $coursecontext = \context_course::instance($courseid);
        list($students, $teachers) = $this->get_users($coursecontext);

        $studentclasses = $this->get_user_grade_classes($courseid, $students, $classes);

        $courseindicators = \tool_ie\indicator\course::get_all($modinfo);

        // TODO $teachersindicators =


        // Iterate through course users getting their stats
        foreach ($students as $student) {

            if (!isset($studentclasses[$student->id])) {
                continue;
            }

            $exampledata = array();

            // Add course indicators.
            foreach ($courseindicators as $courseindicator) {
                $exampledata[] = $courseindicator;
            }

            // TODO Add time indicators.

            // TODO Add teachers indicators.

            $studentindicators[$student->id]['description'] = intval($student->description);
            $studentindicators[$student->id]['picture'] = intval(!empty($student->picture));
            $studentindicators[$student->id]['differentlanguagethancourse'] = intval(!empty($modinfo->get_course()->lang) &&
                !empty($student->lang) && $student->lang != $modinfo->get_course()->lang);

            foreach ($studentindicators[$student->id] as $indicator) {
                $exampledata[] = $indicator;
            }

            $class = $studentclasses[$student->id];
            var_dump($exampledata);

            $this->add_row($logfh, array_merge($exampledata, array($class)));
        }

        return $classes;
    }

    protected function enrolments_activity_grades(&$logfh, $params) {
        global $DB;

        $classes = array(0.5, 1);

        $courseid = $params['courseid'];

        $modinfo = get_fast_modinfo($courseid, -1);

        $coursecontext = \context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext);

        $userclasses = $this->get_user_grade_classes($courseid, $users, $classes);

        foreach ($users as $user) {

            $exampledata = array();

            if (!isset($userclasses[$user->id])) {
                continue;
            }

            // Get all contexts we want logs from.
            $contextids = array($coursecontext->id);

            $mods = $modinfo->get_instances();
            foreach ($mods as $modname => $instances) {
                foreach ($instances as $instance) {
                    $contextids[] = $instance->context->id;
                }
            }

            // X - picture set.
            if ($user->picture) {
                $exampledata[] = 1;
            } else {
                $exampledata[] = 0;
            }

            // X - profile description
            if ($user->description) {
                $exampledata[] = 1;
            } else {
                $exampledata[] = 0;
            }

            // X - Number of logs.
            list($ctxsql, $ctxparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
            $sql = "SELECT contextid, count(*) AS nlogs FROM {logstore_standard_log} l
                     WHERE userid = :userid AND contextid " . $ctxsql . "
                    GROUP BY contextid";
            $params = array('userid' => $user->id) + $ctxparams;
            $logs = $DB->get_records_sql($sql, $params);

            // We iterate through contexts as we need all examples to have the same list of contexts.
            $count = 0;
            foreach ($contextids as $contextid) {
                if (!empty($logs[$contextid])) {
                    //$count += $logs[$contextid]->nlogs;
                    $exampledata[] = $logs[$contextid]->nlogs;
                } else {
                    $exampledata[] = 0;
                }
            }
            //$exampledata[] = $count;

            // X - quiz attempts.
            $sql = "SELECT count(*) FROM {logstore_standard_log} l
                      JOIN {context} ctx ON ctx.id = l.contextid
                     WHERE l.userid = :userid AND " . $DB->sql_like('ctx.path', ':path') . "
                     AND l.eventname = :eventname";
            $params = array('userid' => $user->id, 'path' => $coursecontext->path . '%',
                'eventname' => '\mod_quiz\event\attempt_started');
            $attempts = $DB->count_records_sql($sql, $params);
            if (!empty($attempts)) {
                $exampledata[] = $attempts;
            } else {
                $exampledata[] = 0;
            }

            // X - num forum posts.
            $sql = "SELECT count(fp.*) FROM {forum_discussions} fd
                      JOIN {forum_posts} fp ON fd.id = fp.discussion
                      WHERE fp.userid = :userid AND fd.course = :courseid";
            $params = array('userid' => $user->id, 'courseid' => $courseid);
            $posts = $DB->count_records_sql($sql, $params);
            if (!empty($posts)) {
                $exampledata[] = $posts;
            } else {
                $exampledata[] = 0;
            }

            // y - Course grade.
            $class = $userclasses[$user->id];

            $this->add_row($logfh, array_merge($exampledata, array($class)));
        }

        return $classes;
    }

    protected function get_user_grade_classes($courseid, $users, $classes) {
        global $DB;

        list($userssql, $usersparams) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED);
        $params = $usersparams + array('courseid' => $courseid);
        $grades = $DB->get_records_sql("SELECT gg.* FROM {grade_grades} gg
                                       JOIN {grade_items} gi ON gg.itemid = gi.id
                                      WHERE gi.courseid = :courseid AND gi.itemtype = 'course' AND gg.userid $userssql ", $params);

        $userclasses = array();
        foreach ($grades as $grade) {

            // TODO This should look at the course minmaxgrade setting, using the grade_grades one here.
            $weightedgrade = ($grade->finalgrade - $grade->rawgrademin) / ($grade->rawgrademax - $grade->rawgrademin);

            // Interpret this as a classification problem. The last column will always be a grade from 0 to 1.
            foreach ($classes as $key => $classboundary) {
                if ($weightedgrade <= $classboundary) {
                    $class = $key;
                    break;
                }
            }

            if (!isset($class)) {
                throw new \Exception('Something wrong with ' . $weightedgrade . ' grade, should be between 0 and 1');
            }

            $userclasses[$grade->userid] = $class;
        }

        return $userclasses;
    }

    protected function get_users($coursecontext) {

        $users = get_enrolled_users($coursecontext);
        $teachers = get_enrolled_users($coursecontext, self::TEACHER_CAPABILITY);
        $students = array_diff_key($users, $teachers);

        return array($students, $teachers);
    }
}
