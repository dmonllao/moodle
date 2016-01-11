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
 * Provide interface for code fragments.
 *
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core
 * @since      Moodle 3.1
 */

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

// Get the callback.
$callback = required_param('callback', PARAM_RAW);
// Get the params
$params = optional_param_array('params', null, PARAM_RAW);

$PAGE->set_requirements_for_fragments();
// Call the function.
if (isset($params)) {
    $data = call_user_func_array($callback, $params);
} else {
    $data = call_user_func($callback);
}
$jsfooter = $PAGE->requires->get_end_code();
$output = array($data, $jsfooter);
echo json_encode($output);

die();