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
 * @package   core
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\analytics\time_splitting;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package   core
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom extends \core_analytics\local\time_splitting\base {

    /**
     * Returns a lang_string object representing the name for the time splitting method.
     *
     * Used as column identificator.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('timesplitting:custom');
    }

    /**
     * Custom range using the provided start and end.
     *
     * @return array
     */
    protected function define_ranges() {
        // Key 'time' == 0 because we want it to start predicting from the beginning.
        // TODO read start and end from the options provided.
        return [
            [
                'start' => 1,
                'end' => 9999999999,
                'time' => 0
            ]
        ];
    }
}
