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
 * No accesses since the start of the course.
 *
 * @package   core_course
 * @copyright 2019 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\analytics\target;

defined('MOODLE_INTERNAL') || die();

/**
 * No accesses since the start of the course.
 *
 * @package   core_course
 * @copyright 2019 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class no_access_since_course_start extends course_enrolments {

    /**
     * Machine learning backends are not required to predict.
     *
     * @return bool
     */
    public static function based_on_assumptions() {
        return true;
    }

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('target:noaccesssincecoursestart', 'course');
    }

    /**
     * Discards courses that are not yet ready to be used for prediction.
     *
     * @param \core_analytics\analysable $course
     * @param bool $fortraining
     * @return true|string
     */
    public function is_valid_analysable(\core_analytics\analysable $course, $fortraining = true) {

        if (!$course->was_started()) {
            return get_string('coursenotyetstarted', 'course');
        }

        if (!$this->students = $course->get_students()) {
            return get_string('nocoursestudents', 'course');
        }

        if ($course->get_end() && $course->get_end() < $course->get_start()) {
            return get_string('errorendbeforestart', 'course');
        }

        // Finished courses can not be used to get predictions.
        if ($course->is_finished()) {
            return get_string('coursealreadyfinished', 'course');
        }

        return true;
    }

    /**
     * Discard student enrolments that are invalid.
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $course
     * @param bool $fortraining
     * @return bool
     */
    public function is_valid_sample($sampleid, \core_analytics\analysable $course, $fortraining = true) {
        $parentresult = parent::is_valid_sample($sampleid, $course, $fortraining);
        if (!$parentresult) {
            return false;
        }

        // TODO Discard enrolments whose timecreated or timestart are not close to the course start date.
    }

    /**
     * Do the user has any read action in the course?
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param int $starttime
     * @param int $endtime
     * @return float 0 -> accesses, 1 -> no accesses.
     */
    protected function calculate_sample($sampleid, \core_analytics\analysable $analysable, $starttime = false, $endtime = false) {

        $readactions = $this->retrieve('\core\analytics\indicator\read_actions', $sampleid);
        if ($readactions == \core\analytics\indicator\read_actions::get_min_value()) {
            return 1;
        }
        return 0;
    }
}
