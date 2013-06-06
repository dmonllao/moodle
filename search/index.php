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
 * Global Search index page for entering queries and display of results
 *
 * @package   Global Search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

$page      = optional_param('page', 0, PARAM_INT);
$search    = trim(optional_param('search', '', PARAM_NOTAGS));
$fq_title  = trim(optional_param('fq_title', '', PARAM_NOTAGS));
$fq_author = trim(optional_param('fq_author', '', PARAM_NOTAGS));
$fq_module = trim(optional_param('fq_module', '0', PARAM_NOTAGS));
$fq_from   = optional_param('fq_from', 0, PARAM_INT);
$fq_till   = optional_param('fq_till', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('globalsearch', 'search'));
$PAGE->set_heading(get_string('globalsearch', 'search'));

require_login();

$urlparams = array('search' => $search, 'fq_title' => $fq_title, 'fq_author' => $fq_author,
                   'fq_module' => $fq_module, 'fq_from' => $fq_from, 'fq_till' => $fq_till,  'page' => $page);

$url = new moodle_url('/search/index.php', $urlparams);

$PAGE->set_url($url);

$searchrenderer = $PAGE->get_renderer('core', 'search');

$content = $searchrenderer->index($url, $page, $search, $fq_title, $fq_author, $fq_module, $fq_from, $fq_till);

echo $OUTPUT->header(), $content, $OUTPUT->footer();
