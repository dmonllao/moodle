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
 * The mod_assign helper class.
 *
 * @package    mod_assign
 * @copyright  2016 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

namespace mod_assign\other;

defined('MOODLE_INTERNAL') || die();

class assign_helper {

    public static function get_grade_form($id, $rownum = 0, $studentid = null) {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        list ($course, $cm) = \get_course_and_cm_from_cmid($id, 'assign');
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);
        $PAGE->set_context($context);
        $PAGE->set_url('/mod/assign/view.php');
        return $assign->do_that_stuff('', $rownum, $studentid);
    }
}