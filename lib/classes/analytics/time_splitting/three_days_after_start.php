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
 * Time splitting method that generates predictions 3 days after the analysable start.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\analytics\time_splitting;

defined('MOODLE_INTERNAL') || die();

/**
 * Time splitting method that generates predictions 3 days after the analysable start.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class three_days_after_start extends \core_analytics\local\time_splitting\base {

    /**
     * The time splitting method name.
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('timesplitting:threedaysafterstart');
    }

    /**
     * Returns whether the course can be processed by this time splitting method or not.
     *
     * @param \core_analytics\analysable $analysable
     * @return bool
     */
    public function is_valid_analysable(\core_analytics\analysable $analysable) {

        $now = time();

        if (!$analysable->get_start()) {
            return false;
        }

        if ($now < $analysable->get_start()) {
            // It does not make sense to analyse something that has not yet begun.
            return false;
        }

        if ($analysable->get_end() && $analysable->get_end() < $now) {
            // Past stuff can be used for training.
            return true;
        }

        if ($now < $this->get_prediction_time($analysable)) {
            // We can not use this to get predictions as we have not reached start + 3 days.
            return false;
        }

        return true;
    }

    /**
     * This time-splitting method returns one single range.
     *
     * @return array The list of ranges, each of them including 'start', 'end' and 'time'.
     */
    protected function define_ranges() {

        $analysablestart = $this->analysable->get_start();
        $predictiontime = $this->get_prediction_time();

        $ranges = array(
            array(
                'start' => $analysablestart,
                'end' => $predictiontime,
                'time' => $predictiontime,
            )
        );

        return $ranges;
    }

    /**
     * Whether to cache or not the indicator calculations.
     * @return bool
     */
    public function cache_indicator_calculations(): bool {
        return false;
    }

    /**
     * Gets the time to generate the prediction.
     *
     * @param  \core_analytics\analysable|null $analysable
     * @return int
     */
    private function get_prediction_time(?\core_analytics\analysable $analysable = null) {

        if (!$analysable) {
            $analysable = $this->analysable;
        }
        $predictiontime = new \DateTime();
        $predictiontime->setTimestamp($analysable->get_start());
        $predictiontime->add(new \DateInterval('P3D'));

        return $predictiontime->getTimestamp();
    }
}
