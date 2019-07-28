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
 *
 * @package   core_course
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\analytics\target;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package   core_course
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report extends \core_analytics\local\target\binary implements \core_analytics\local\target\reporting {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        // TODO change.
        return new \lang_string('target:coursecompetencies', 'course');
    }

    /**
     * Returns descriptions for each of the values the target calculation can return.
     *
     * @return string[]
     */
    protected static function classes_description() {
        // TODO change.
        return array(
            get_string('targetlabelstudentcompetenciesno', 'course'),
            get_string('targetlabelstudentcompetenciesyes', 'course'),
        );
    }

    /**
     * Based on assumptions.
     *
     * @return bool
     */
    public static function based_on_assumptions() {
        return true;
    }

    /**
     * Only past stuff.
     *
     * @param  \core_analytics\local\time_splitting\base $timesplitting
     * @return bool
     */
    public function can_use_timesplitting(\core_analytics\local\time_splitting\base $timesplitting): bool {
        return ($timesplitting instanceof \core\analytics\time_splitting\custom);
    }

    /**
     * Is this target generating insights?
     *
     * Defaults to true.
     *
     * @return bool
     */
    public static function uses_insights() {
        return false;
    }

    /**
     * No need to update the last analysis time.
     *
     * @return bool
     */
    public function always_update_analysis_time(): bool {
        return false;
    }

    /**
     * Returns the analyser class that should be used along with this target.
     *
     * @return string The full class name as a string
     */
    public function get_analyser_class() {
        return '\core\analytics\analyser\student_enrolments';
    }

    /**
     * Discards courses that are not yet ready to be used for training or prediction.
     *
     * @param \core_analytics\analysable $course
     * @param bool $fortraining
     * @return true|string
     */
    public function is_valid_analysable(\core_analytics\analysable $course, $fortraining = true) {

        if (!$this->students = $course->get_students()) {
            return get_string('nocoursestudents', 'course');
        }

        if ($course->get_end() && $course->get_end() < $course->get_start()) {
            return get_string('errorendbeforestart', 'course');
        }

        return true;
    }

    /**
     * is_valid_sample
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param bool $fortraining
     * @return bool
     */
    public function is_valid_sample($sampleid, \core_analytics\analysable $analysable, $fortraining = true) {
        return true;
    }

    /**
     * YOLO
     *
     * @throws \coding_exception
     * @param int $sampleid
     * @param \core_analytics\analysable $course
     * @param int $starttime
     * @param int $endtime
     * @return int
     */
    protected function calculate_sample($sampleid, \core_analytics\analysable $course, $starttime = false, $endtime = false) {
        // Reporting analysis does not include targets so this should be executed.
        throw new \coding_exception('Reports should not include sample calculations.');
    }
}
