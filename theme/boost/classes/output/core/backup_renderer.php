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
 * Course renderer.
 *
 * @package    theme_boost
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost\output\core;
defined('MOODLE_INTERNAL') || die();

use moodle_url;
use html_writer;
use html_table;
use html_table_row;

require_once($CFG->dirroot . '/backup/util/ui/renderer.php');

/**
 * The primary renderer for the backup.
 *
 * Can be retrieved with the following code:
 * <?php
 * $renderer = $PAGE->get_renderer('core', 'backup');
 * ?>
 *
 * @package    theme_boost
 * @copyright  2018 Moodle.com
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_renderer extends \core_backup_renderer {

    /**
     * Renders a restore course search object
     *
     * @param restore_course_search $component
     * @return string
     */
    public function render_restore_course_search(\restore_course_search $component) {
        $url = $component->get_url();

        $output = html_writer::start_tag('div', array('class' => 'restore-course-search form-inline mb-1'));
        $output .= html_writer::start_tag('div', array('class' => 'rcs-results w-100'));

        $table = new html_table();
        $table->head = array('', get_string('shortnamecourse'), get_string('fullnamecourse'));
        $table->data = array();
        if ($component->get_count() !== 0) {
            foreach ($component->get_results() as $course) {
                $row = new html_table_row();
                $row->attributes['class'] = 'rcs-course';
                if (!$course->visible) {
                    $row->attributes['class'] .= ' dimmed';
                }
                $row->cells = array(
                    html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'targetid', 'value' => $course->id)),
                    format_string($course->shortname, true, array('context' => \context_course::instance($course->id))),
                    format_string($course->fullname, true, array('context' => \context_course::instance($course->id)))
                );
                $table->data[] = $row;
            }
            if ($component->has_more_results()) {
                $cell = new html_table_cell(get_string('moreresults', 'backup'));
                $cell->colspan = 3;
                $cell->attributes['class'] = 'notifyproblem';
                $row = new html_table_row(array($cell));
                $row->attributes['class'] = 'rcs-course';
                $table->data[] = $row;
            }
        } else {
            $cell = new html_table_cell(get_string('nomatchingcourses', 'backup'));
            $cell->colspan = 3;
            $cell->attributes['class'] = 'notifyproblem';
            $row = new html_table_row(array($cell));
            $row->attributes['class'] = 'rcs-course';
            $table->data[] = $row;
        }
        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'rcs-search'));
        $attrs = array(
            'type' => 'text',
            'name' => \restore_course_search::$VAR_SEARCH,
            'value' => $component->get_search(),
            'class' => 'form-control'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $attrs = array(
            'type' => 'submit',
            'name' => 'searchcourses',
            'value' => get_string('search'),
            'class' => 'btn btn-secondary'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Creates a detailed pairing (key + value)
     *
     * @staticvar int $count
     * @param string $label
     * @param string $value
     * @return string
     */
    protected function backup_detail_pair($label, $value) {
        static $count = 0;
        $count ++;
        $html  = html_writer::start_tag('div', array('class' => 'detail-pair'));
        $html .= html_writer::tag('label', $label, array('class' => 'detail-pair-label', 'for' => 'detail-pair-value-'.$count));
        $html .= html_writer::tag('div', $value, array('class' => 'detail-pair-value pl-2', 'name' => 'detail-pair-value-'.$count));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::tag('div', '', array('class' => 'clearfix'));
        return $html;
    }
}