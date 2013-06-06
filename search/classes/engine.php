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
 * Base class for search engines.
 * 
 * All search engines must extend this class;
 *
 * @package   core_search
 * @copyright 2015 Daniel Neis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for search engines.
 * 
 * All search engines must extend this class;
 *
 * @package   core_search
 * @copyright 2015 Daniel Neis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class engine {

    abstract function is_installed();

    abstract function check_server();

    abstract function add_document($doc);

    abstract function commit();

    abstract function optimize();

    abstract function post_file();

    abstract function execute_query($data);

    abstract function get_more_like_this_text($text);

    abstract function delete($module = null);
}
