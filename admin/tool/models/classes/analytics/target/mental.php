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
 * Stupid test prediction.
 *
 * @package   tool_models
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_models\analytics\target;

defined('MOODLE_INTERNAL') || die();

/**
 * Stupid test prediction.
 *
 * @package   tool_models
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mental extends \core_analytics\local\target\binary {

    /**
     * Machine learning backends are not required to predict.
     *
     * @return bool
     */
    public static function based_on_assumptions() {
        return true;
    }

    public static function get_name() {
        return get_string('target:mental', 'tool_models');
    }

    public function prediction_actions(\core_analytics\prediction $prediction, $includedetailsaction = false) {
        global $USER;

        // No need to call the parent as the parent's action is view details and this target only have 1 feature.
        $actions = array();

        $sampledata = $prediction->get_sample_data();
        $course = $sampledata['course'];

        $url = new \moodle_url('/course/view.php', array('id' => $course->id));
        $pix = new \pix_icon('i/course', get_string('course'));
        $actions['viewcourse'] = new \core_analytics\prediction_action('viewcourse', $prediction,
            $url, $pix, get_string('view'));

        return $actions;
    }

    protected static function classes_description() {
        return array(
            get_string('labelmentalyes', 'tool_models'),
            get_string('labelmentalno', 'tool_models'),
        );
    }

    /**
     * Returns the predicted classes that will be ignored.
     *
     * @return array
     */
    protected function ignored_predicted_classes() {
        // No need to list the course if there is teaching activity.
        return array(0);
    }

    public function get_analyser_class() {
        return '\\core_analytics\\local\\analyser\\site_courses';
    }

    public function is_valid_analysable(\core_analytics\analysable $analysable, $fortraining = true) {
        // The analysable is the site, so yes, it is always valid.
        return true;
    }

    /**
     * Only process samples which start date is getting close.
     *
     * @param mixed $sampleid
     * @param \core_analytics\analysable $analysable
     * @param bool $fortraining
     * @return void
     */
    public function is_valid_sample($sampleid, \core_analytics\analysable $analysable, $fortraining = true) {
        return true;
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @return void
     */
    protected function calculate_sample($sampleid, \core_analytics\analysable $analysable, $starttime = false, $endtime = false) {

        $ismentalindicator = $this->retrieve('\core_course\analytics\indicator\mental', $sampleid);
        if ($ismentalindicator) {
            // It is mental.
            return 1;
        }
        return 0;
    }
}
