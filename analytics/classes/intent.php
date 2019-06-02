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
 * Intents for this component.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Intents for this component.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class intent {

    public function upcoming_activities(array $entities) {
        return (object)['redirect' => new \moodle_url('/my')];
    }

    public function course_grades(array $entities) {
        global $USER;

        if (!empty($entities['course'])) {
            $course = \core_analytics\entity_matcher::course($entities['course']);

            if ($course) {
                $url = new \moodle_url('/course/user.php',
                    ['mode' => 'grade', 'id' => $course->id, 'user' => $USER->id]);
            }
        }

        if (!isset($url)) {
            $url = new \moodle_url('/grade/report/overview/index.php');
        }

        return (object)['redirect' => $url];
    }
}
