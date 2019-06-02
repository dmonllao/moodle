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
 * Matches the entities identified by the NLU backend to moodle entities in DB.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

require_once($CFG->libdir . '/datalib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Matches the entities identified by the NLU backend to moodle entities in DB.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_matcher {

    public static function course(array $identifiedentities): \stdClass {
        foreach ($identifiedentities as $identifiedentity) {

            // TODO Pretty sure that there are better search options than this.
            $courses = get_courses_search($identifiedentity, 'c.sortorder ASC', 0, 1, $totalcount);
            if ($courses) {
                return reset($courses);
            }
        }

        return false;
    }
}
