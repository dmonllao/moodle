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
 * Creates the solr schema.
 *
 * @package   search_solr
 * @copyright 2015 David Monllao
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// TODO add a new define page and get rid of all this stuff
//admin_externalpage_setup('search_solr_create_schema');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/search/engine/solr/create_schema.php'));
echo $OUTPUT->header();

if (!\core_search::is_global_search_enabled()) {
    throw new moodle_exception('globalsearchdisabled', 'search');
}

if ($CFG->searchengine !== 'solr') {
    throw new moodle_exception('solrnotselected', 'search_solr');
}

$schema = new \search_solr\schema();
$schema->create();

$renderer = $PAGE->get_renderer('search');
echo $renderer->schema_created();

echo $OUTPUT->footer();
