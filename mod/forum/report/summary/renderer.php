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
 * Provides rendering functionality for the forum summary report subplugin.
 *
 * @package   forumreport_summary
 * @copyright 2019 Michael Hawkins <michaelh@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the forum summary report.
 *
 * @copyright  2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumreport_summary_renderer extends plugin_renderer_base {

    /**
     * Render the filters available for the forum summary report.
     *
     * @param \stdClass $course The course object.
     * @param \context $context The context object.
     * @param \moodle_url $actionurl The form action URL.
     * @param array $filters Optional array of currently applied filter values.
     * @return string The filter form HTML.
     */
    public function render_filters_form(\stdClass $course, \context $context, \moodle_url $actionurl, array $filters = []): string {
        $renderable = new \forumreport_summary\output\filters($course, $context, $actionurl, $filters);
        $templatecontext = $renderable->export_for_template($this);

        return self::render_from_template('forumreport_summary/filters', $templatecontext);
    }

    /**
     * Render the summary report table.
     *
     * @param int $courseid ID of the course where the forum is located.
     * @param int $forumid Forum ID report is being generated for.
     * @param string $url Base URL for the report page.
     * @param array $filters Values of filters to be applied.
     * @param int $perpage Number of results to render per page.
     * @return string The report table HTML.
     */
    public function render_report($courseid, $forumid, $url, $filters, $perpage) {
        // Initialise table.
        $table = new \forumreport_summary\summary_table($courseid, $forumid);
        $table->baseurl = $url;

        // Apply filters.
        $table->add_filter($table::FILTER_GROUPS, $filters['groups']);

        // Buffer so calling script can output the report as required.
        ob_start();

        // Render table.
        $table->out($perpage, false);

        $tablehtml = ob_get_contents();

        ob_end_clean();

        return self::render_from_template('forumreport_summary/report', ['tablehtml' => $tablehtml, 'placeholdertext' => false]);
    }

    /**
     * Render the placeholder content for use when the report table is not visible.
     *
     * @return string The placeholder HTML.
     */
    public function render_report_placeholder() {
        $generatebuttontext = get_string('generatereport', 'forumreport_summary');
        $context = [
            'placeholdertext' => get_string('reportplaceholder', 'forumreport_summary', $generatebuttontext),
            'tablehtml' => false
        ];

        return self::render_from_template('forumreport_summary/report', $context);
    }
}
