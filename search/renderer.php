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
 * Renderer for use with the global search.
 *
 * @package   globalsearch
 * @copyright 2015 Daniel Neis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The global search renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','search');
 */
class core_search_renderer extends plugin_renderer_base {

    public function index($url, $page = 0, $search = '',
                          $fq_title = '', $fq_author = '', $fq_module = '',
                          $fq_from = '', $fq_till = '') {

        $mform = new core_search_search_form();
        $data = new stdClass();

        $content = $mform->render();

        if (!$globalsearch = new core_search()) {
            $content .= 'Global Search is disabled.';
        } else {

            if (!empty($search)) { // search executed from URL params
                $data->queryfield = $search;
                $data->titlefilterqueryfield = $fq_title;
                $data->authorfilterqueryfield = $fq_author;
                $data->modulefilterqueryfield = $fq_module;
                $data->searchfromtime = $fq_from;
                $data->searchtilltime = $fq_till;
                $mform->set_data($data);
                $results = $globalsearch->search($data);
            }

            if ($data = $mform->get_data()) { // search executed from submitting form
                $search = $data->queryfield;
                $fq_title = $data->titlefilterqueryfield;
                $fq_author = $data->authorfilterqueryfield;
                $fq_module = $data->modulefilterqueryfield;
                $fq_from = $data->searchfromtime;
                $fq_till = $data->searchtilltime;
                unset($data->submitbutton);
                $results = $globalsearch->search($data);
            }

            if (!empty($results)) {
                if (is_array($results)) {
                    $perpage = SEARCH_DISPLAY_RESULTS_PER_PAGE;
                    $content .= 'Total accessible records: ' . count($results);
                    $content .= $this->output->paging_bar(count($results), $page, $perpage, $url);
                    $hits = array_slice($results, $page*$perpage, $perpage, true);
                    foreach ($hits as $hit) {
                        $content .= $this->render_results($hit);
                    }
                    $content .= $this->output->paging_bar(count($results), $page, $perpage, $url);
                } else {
                    $content .= $results;
                }
            }
        }
        return $content;
    }

    /**
     * Displaying search results
     * @param stdClass object $result containing a single search response to be displayed
     */
    function render_results($result) {
        global $DB;

        $doc_id = explode('_', $result->id);
        $course = $DB->get_record('course', array('id' => $result->courseid), 'fullname', MUST_EXIST);
        $globalsearch = new core_search();

        $coursefullname = $course->fullname;
        $attributes = array('target' => '_new');
        $s = '';
        $s .= html_writer::start_tag('div', array('class'=>'globalsearchpost clearfix side'));
        $s .= html_writer::start_tag('div', array('class'=>'row header clearfix'));
        $s .= html_writer::start_tag('div', array('class'=>'course'));
        $s .= html_writer::link(new moodle_url('/course/view.php?id=' . $result->courseid), $coursefullname, $attributes);
        $s .= ' > ' . ucfirst($result->module);
        $s .= html_writer::end_tag('div');
        $s .= html_writer::start_tag('div', array('class'=>'name'));
        if (!empty($result->name)) {
            $s .= html_writer::link(new moodle_url($result->modulelink), $result->name, $attributes);
        } else {
            $module_parameters = explode('/', $result->modulelink);
            $module_id = explode('=', $module_parameters[3]);
            $module_record = get_coursemodule_from_id($module_parameters[2], $module_id[1], $result->courseid, false, MUST_EXIST);
            $s .= html_writer::link(new moodle_url($result->modulelink), $module_record->name, $attributes);
        }
        if (!empty($result->intro)) {
            $s .= html_writer::span(' Description: ' . $result->intro, 'description');
        }
        $s .= html_writer::end_tag('div');
        $s .= html_writer::start_tag('div', array('class'=>'author'));
        if (!empty($result->user)) {
            $s .='<b><i>By: </i></b>';
            $s .= html_writer::link($globalsearch->get_user_url($result->user), $result->user , $attributes);
        }
        if (!empty($result->author)) {
            $s .='<b>Document Author(s): </b>';
            foreach ($result->author as $key => $value) {
                $author_url = $globalsearch->get_user_url($value);
                if (!empty($author_url)) {
                    $s .= html_writer::link($author_url, $value, $attributes) . ', ';
                } else {
                    $s .= $value . ', ';
                }
            }
            $s =rtrim($s, ', ');
            $s .='<br>';
        }
        $s .= html_writer::end_tag('div');
        $s .= html_writer::start_tag('div', array('class'=>'timeinfo'));
        if (!empty($result->modified)) {
            $s .='<b><i>Last Modified on: </i></b>' . userdate(strtotime($result->modified)) . ' ';
        }
        if (!empty($result->created)) {
            $s .='<b><i>Created on: </i></b>' . userdate(strtotime($result->created)) . '<br/>';
        }
        $s .= html_writer::end_tag('div');
        $s .= html_writer::end_tag('div');
        $s .= html_writer::start_tag('div', array('class'=>'row maincontent clearfix'));
        $s .= html_writer::start_tag('div', array('class'=>'content'));
        if (!empty($result->title)) {
            $s .=$result->title . '<br/>';
        }
        if (!empty($result->content)) {
            $s .=$result->content . '<br/>';
        }
        $s .= html_writer::end_tag('div');
        $s .= html_writer::end_tag('div');
        $s .= html_writer::start_tag('div', array('class'=>'row footer clearfix side'));

        if (!empty($result->directlink)) {
            if (!empty($result->contextlink)) {
                $s .= html_writer::span(
                                    html_writer::link(new moodle_url($result->directlink), 'Direct link to file', $attributes) . ' | '.
                                    html_writer::link(new moodle_url($result->contextlink), 'View this result in context', $attributes),
                                    'urllink');
            } else {
                $s .= html_writer::span(
                                    html_writer::link(new moodle_url($result->directlink), 'Direct link to file', $attributes),
                                    'urllink');
            }
        } else if (!empty($result->contextlink)) {
            $s .= html_writer::span(
                                    html_writer::link(new moodle_url($result->contextlink), 'View this result in context', $attributes),
                                    'urllink');
        }

        $s .= html_writer::end_tag('div');
        $s .= html_writer::end_tag('div'); // End.
        $s .= '<hr />';

        return $s;
    }

    function admin() {
        global $DB, $CFG;

        $content = '';
        $content .= $this->output->heading(get_string('statistics_desc', 'admin'));

        if (!$search = new core_search()) {
            $content .= $this->output->box_start();
            $content .= 'Global Search is disabled';
            $content .= $this->output->box_end();
            return $content;
        }

        $mform = new core_search_admin_form();

        if ($data = $mform->get_data()) {

            if (!empty($data->delete)) {
                if (!empty($data->all)) {
                    $data->module = null;
                    $search->delete_index($data);
                } else {
                    $a = '';
                    foreach ($data as $key => $value) {
                        if ($value && $key!='delete' && $key!='submitbutton') {
                            $a .= $key . ',';
                        }
                    }
                    $data->module = substr($a, 0, -1);
                    $search->delete_index($data);
                }
            }

            if (!empty($data->reindex)) {
                // Indexing database records for modules + rich documents of forum.
                $search->index();
                // Indexing rich documents for lesson, wiki.
                $search->index_files();
                // Optimize index at last.
                $search->optimize_index();
            }
        }

        $gstable = new html_table();
        $gstable->id = 'gs-control-panel';
        $gstable->head = array( 'Name', 'Newest document indexed', 'Last run <br /> (time, # docs, # records, # ignores)');
        $gstable->colclasses = array('displayname', 'lastrun', 'timetaken');

        $mods = $search->get_iterators(false);
        $config = $search->get_config(array_keys($mods));

        foreach ($mods as $name => $mod) {
            $cname = new html_table_cell(ucfirst($name));
            $clastrun = new html_table_cell($config[$name]->lastindexrun);
            $ctimetaken = new html_table_cell($config[$name]->indexingend - $config[$name]->indexingstart . ' , ' .
                                              $config[$name]->docsprocessed . ' , ' .
                                              $config[$name]->recordsprocessed . ' , ' .
                                              $config[$name]->docsignored);
            $modname = 'gs_support_' . $name;
            //$cactive = new html_table_cell(($CFG->$modname) ? 'Yes' : 'No');
            $row = new html_table_row(array($cname, $clastrun, $ctimetaken));
            $gstable->data[] = $row;
        }

        $content .= html_writer::table($gstable);
        $content .= $this->output->container_start();
        $content .= $this->output->box_start();

        $content .= $mform->render();

        $content .= $this->output->box_end();
        $content .= $this->output->container_end();

        return $content;
    }
}
