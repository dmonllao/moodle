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
 * Potential social breadth indicator.
 *
 * @package   core_course
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Potential social breadth indicator.
 *
 * It extends linear instead of discrete as there is a linear relation between
 * the different social levels activities can reach.
 *
 * @package   core_course
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_social_breadth extends \core_analytics\local\indicator\linear {

    /**
     * get_name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('indicator:potentialsocial', 'moodle');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        // We require course because, although it can also work with course_modules.
        return array('course');
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param string $sampleorigin
     * @param int|false $notusedstarttime
     * @param int|false $notusedendtime
     * @return float
     */
    public function calculate_sample($sampleid, $sampleorigin, $notusedstarttime = false, $notusedendtime = false) {

        if ($sampleorigin === 'course_modules') {
            $cm = $this->retrieve('course_modules', $sampleid);
            $cminfo = \cm_info::create($cm);

            $socialbreadthindicator = $this->get_social_indicator($cminfo->modname);
            $value = $socialbreadthindicator->get_social_breadth_level($cminfo);
            if ($value > \core_analytics\local\indicator\community_of_inquiry_activity::MAX_SOCIAL_LEVEL) {
                $this->level_not_accepted($value);
            }

        } else {
            $course = $this->retrieve('course', $sampleid);
            $modinfo = get_fast_modinfo($course);

            $cms = $modinfo->get_cms();
            if (!$cms) {
                return self::get_min_value();
            }

            $ncms = count($cms);
            $total = 0;
            foreach ($cms as $cm) {
                if (!$socialbreadthindicator = $this->get_social_indicator($cm->modname)) {
                    continue;
                }
                $level = $socialbreadthindicator->get_social_breadth_level($cm);
                if ($level > \core_analytics\local\indicator\community_of_inquiry_activity::MAX_SOCIAL_LEVEL) {
                    $this->level_not_accepted($level);
                }
                $total += $level;
            }
            $value = $total / $ncms;
        }

        // Supporting only social breadth level 1 and 2 the possible values are -1 or 1.
        $levelscore = round(self::get_max_value() - self::get_min_value(), 2);

        // We rest $levelscore because we want to start from the lower socre and there is no cognitive depth level 0.
        return self::get_min_value() + ($levelscore * $value) - $levelscore;
    }

    /**
     * Returns the social breadth class of this indicator.
     *
     * @param string $modname
     * @return \core_analytics\local\indicator\base|false
     */
    protected function get_social_indicator($modname) {
        $classname = '\mod_' . $modname . '\analytics\indicator\social_breadth';
        if (!class_exists($classname)) {
            return false;
        }
        return new $classname();
    }

    /**
     * Throw a \coding_exception.
     *
     * @param int $level
     */
    protected function level_not_accepted($level) {
        throw new \coding_exception('Although social breadth levels can go from 1 to 5 at the moment Moodle core can only accept' .
            ' social breadth levels 1 and 2. Sorry for the inconvenience, this will change in future releases.');
    }
}
