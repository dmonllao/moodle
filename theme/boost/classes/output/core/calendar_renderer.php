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
 * Calendar renderer.
 *
 * @package    theme_boost
 * @copyright  2018 Moodle.com
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost\output\core;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/renderer.php');

/**
 * The primary renderer for the calendar.
 *
 * @package    theme_boost
 * @copyright  2018 Moodle.com
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_renderer extends \core_calendar_renderer {
    /**
     * Displays a course filter selector
     *
     * @param moodle_url $returnurl The URL that the user should be taken too upon selecting a course.
     * @param string $label The label to use for the course select.
     * @param int $courseid The id of the course to be selected.
     * @return string
     */
    public function course_filter_selector(moodle_url $returnurl, $label = null, $courseid = null) {
        global $CFG, $DB;

        if (!isloggedin() or isguestuser()) {
            return '';
        }

        $contextrecords = [];
        $courses = calendar_get_default_courses($courseid, 'id, shortname');

        if (!empty($courses) && count($courses) > CONTEXT_CACHE_MAX_SIZE) {
            // We need to pull the context records from the DB to preload them
            // below. The calendar_get_default_courses code will actually preload
            // the contexts itself however the context cache is capped to a certain
            // amount before it starts recycling. Unfortunately that starts to happen
            // quite a bit if a user has access to a large number of courses (e.g. admin).
            // So in order to avoid hitting the DB for each context as we loop below we
            // can load all of the context records and add them to the cache just in time.
            $courseids = array_map(function($c) {
                return $c->id;
            }, $courses);
            list($insql, $params) = $DB->get_in_or_equal($courseids);
            $contextsql = "SELECT ctx.instanceid, " . context_helper::get_preload_record_columns_sql('ctx') .
                          " FROM {context} ctx WHERE ctx.contextlevel = ? AND ctx.instanceid $insql";
            array_unshift($params, CONTEXT_COURSE);
            $contextrecords = $DB->get_records_sql($contextsql, $params);
        }

        unset($courses[SITEID]);

        $courseoptions = array();
        $courseoptions[SITEID] = get_string('fulllistofcourses');
        foreach ($courses as $course) {
            if (isset($contextrecords[$course->id])) {
                context_helper::preload_from_record($contextrecords[$course->id]);
            }
            $coursecontext = context_course::instance($course->id);
            $courseoptions[$course->id] = format_string($course->shortname, true, array('context' => $coursecontext));
        }

        if ($courseid) {
            $selected = $courseid;
        } else if ($this->page->course->id !== SITEID) {
            $selected = $this->page->course->id;
        } else {
            $selected = '';
        }
        $courseurl = new moodle_url($returnurl);
        $courseurl->remove_params('course');

        if ($label === null) {
            $label = get_string('listofcourses');
        }

        $select = html_writer::label($label, 'course', false, ['class' => 'mr-3']);
        $select .= html_writer::select($courseoptions, 'course', $selected, false, ['class' => 'cal_courses_flt w-25']);

        return $select;
    }
}