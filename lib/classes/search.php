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
 * Global Search library code
 *
 * @package   Global Search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/accesslib.php');

define('SEARCH_TYPE_HTML', 1);
define('SEARCH_TYPE_TEXT', 2);
define('SEARCH_TYPE_FILE', 3);

define('SEARCH_ACCESS_DENIED', 0);
define('SEARCH_ACCESS_GRANTED', 1);
define('SEARCH_ACCESS_DELETED', 2);

define('SEARCH_MAX_RESULTS', 100);
define('SEARCH_DISPLAY_RESULTS_PER_PAGE', 10);
define('SEARCH_SET_START', 0);
define('SEARCH_SET_ROWS', 1000);
define('SEARCH_SET_FRAG_SIZE', 500);
define('SEARCH_CACHE_TIME', 300);

class core_search {

    public function __construct() {
        global $CFG;

        if (!$CFG->enableglobalsearch) {
            return null;
        }

        $classname = 'search_'.$CFG->search_engine.'\\engine';
        if (!class_exists($classname)) {
            throw new Exception('Engine class notfound:'.$classname);
        }

        $this->engine = new $classname();

        if (!$this->engine->is_installed() || !$this->engine->check_server() ) {
            return null;
        }
    }

    public function search($data) {
        return $this->engine->execute_query($data);
    }

    /**
     * Modules activated for Global Search.
     * @param boolean $requireconfig to check if the admin has de/activated a particular module.
     * @return array $mods
     */
    public function get_modules($requireconfig = true) {
        global $CFG, $DB;
        $mods = $DB->get_records('modules', null, 'name', 'id,name');
        foreach ($mods as $key => $mod) {
            $modname = 'gs_support_' . $mod->name;
            if ($requireconfig) {
                if (empty($CFG->$modname) or !plugin_supports('mod', $mod->name, FEATURE_GLOBAL_SEARCH)) {
                    unset($mods[$key]);
                }
            } else {
                if (!plugin_supports('mod', $mod->name, FEATURE_GLOBAL_SEARCH)) {
                    unset($mods[$key]);
                }
            }
        }
        return $mods;
    }

    /**
     * Search API functions for modules.
     * @param boolean $requireconfig to check if the admin has de/activated a particular module.
     * @return stdClass object $functions
     */
    public function get_iterators($requireconfig = true) {
        global $CFG;

        $functions = array();

        // Course
        $functions['course'] = new stdClass();
        $functions['course']->iterator = 'course_search_iterator';
        $functions['course']->documents = 'course_search_get_documents';
        $functions['course']->access = 'course_search_access';
        $functions['course']->module = 'course';

        // Modules
        $mods = $this->get_modules($requireconfig);
        foreach ($mods as $mod) {
            if (file_exists("$CFG->dirroot/mod/{$mod->name}/db/search.php")) {
                include_once("$CFG->dirroot/mod/{$mod->name}/db/search.php");
                if (!function_exists($mod->name . '_search_iterator')) {
                    throw new coding_exception('Module supports GLOBAL_SEARCH but function \'' .
                                                $mod->name . '_search_iterator' . '\' is missing.');
                }
                if (!function_exists($mod->name . '_search_get_documents')) {
                    throw new coding_exception('Module supports GLOBAL_SEARCH but function \'' .
                                                $mod->name . '_search_get_documents' . '\' is missing.');
                }
                if (!function_exists($mod->name . '_search_access')) {
                    throw new coding_exception('Module supports GLOBAL_SEARCH but function \'' .
                                                $mod->name . '_search_access' . '\' is missing.');
                }
                $functions[$mod->name] = new stdClass();
                $functions[$mod->name]->iterator = $mod->name . '_search_iterator';
                $functions[$mod->name]->documents = $mod->name . '_search_get_documents';
                $functions[$mod->name]->access = $mod->name . '_search_access';
                $functions[$mod->name]->module = $mod->name;
            } else {
                throw new coding_exception('Library file for module \'' . $mod->name . '\' is missing.');
            }
        }

        return $functions;
    }

    /**
     * Merge separate index segments into one.
     */
    public function optimize_index() {
        $this->engine->optimize();
    }

    /**
     * Index all documents.
     */
    public function index() {
        global $CFG;

        set_time_limit(576000);
        $iterators = $this->get_iterators();
        foreach ($iterators as $name => $iterator) {

            mtrace('Processing module ' . $iterator->module);
            $indexingstart = time();

            $iterfunction = $iterator->iterator;
            $getdocsfunction = $iterator->documents;

            $lastindexrun = get_config('search', $name . '_lastindexrun');

            $recordset = $iterfunction($lastindexrun);

            $numrecords = 0;
            $numdocs = 0;
            $numdocsignored = 0;

            foreach ($recordset as $record) {
                ++$numrecords;
                $timestart = microtime(true);
                $documents = $getdocsfunction($record->id);

                foreach ($documents as $document) {
                    switch ($document['type']) {
                        case SEARCH_TYPE_HTML:
                            $this->engine->add_document($document);
                            ++$numdocs;
                            break;
                        default:
                            ++$numdocsignored;
                            throw new Exception('Incorrect document format encountered');
                    }
                }
                $timetaken = microtime(true) - $timestart;
            }
            $recordset->close();
            if ($numrecords > 0) {
                $this->engine->commit();
                $indexingend = time();
                set_config($name . '_indexingstart', $indexingstart, 'search');
                set_config($name . '_indexingend', $indexingend, 'search');
                set_config($name . '_lastindexrun', $record->modified, 'search');
                set_config($name . '_docsignored', $numdocsignored, 'search');
                set_config($name . '_docsprocessed', $numdocs, 'search');
                set_config($name . '_recordsprocessed', $numrecords, 'search');
                mtrace("Processed $numrecords records containing $numdocs documents for " . $iterator->module . '. Commits completed.');
            }
        }
    }

    /**
     * Index all Rich Document files.
     */
    public function index_files() {
        global $CFG;

        set_time_limit(576000);
        $mod_file = array(
                    'lesson' => 'lesson',
                    'wiki' => 'wiki'
                    );

        foreach ($mod_file as $mod => $name) {
            $modname = 'gs_support_' . $name;
            if (empty($CFG->$modname)) {
                unset($mod_file[$mod]);
            }
        }

        mtrace("Memory usage:" . display_size(memory_get_usage()));
        $timestart = microtime(true);

        foreach ($mod_file as $mod => $name) {
            mtrace('Indexing files for module ' . $name);
            $lastindexrun = $this->get_config_file($name);
            require_once($CFG->dirroot.'/mod/'.$name.'/db/search.php');
            $indexfunction = $name . '_search_files';
            // This the the indexing function for indexing rich documents. config settings will be updated inside this function only.
            $indexfunction($lastindexrun);
        }
        $timetaken = microtime(true) - $timestart;
        mtrace("Time : $timetaken");
        $this->engine->commit();
    }

    /**
     * Resets config_plugin table after index deletion as re-indexing will be done from start.
     * optional @param string $s containing modules whose index was chosen to be deleted.
     */
    public function reset_config($s = null) {
        if (!empty($s)) {
            $mods = explode(',', $s);
        } else {
            $get_mods = $this->get_modules(false);
            $mods = array();
            $mods[] = 'course';  // add course
            foreach ($get_mods as $mod) {
                $mods[] = $mod->name;
            }
        }
        foreach ($mods as $key => $name) {
            set_config($name . '_indexingstart', 0, 'search');
            set_config($name . '_indexingend', 0, 'search');
            set_config($name . '_lastindexrun', 0, 'search');
            set_config($name . '_docsignored', 0, 'search');
            set_config($name . '_docsprocessed', 0, 'search');
            set_config($name . '_recordsprocessed', 0, 'search');
            if ($name == 'wiki') { // Extra config setting reset for wiki rich documents.
                set_config($name . '_lastindexedfilerun', 0, 'search');
            }
        }
    }

    /**
     * Deletes index.
     * @param stdClass object $data
     */
    public function delete_index($data) {
        if (!empty($data->module)) {
            $this->engine->delete($data->module);
            $this->reset_config($data->module);
        } else {
            $this->engine->delete();
            $this->reset_config();
        }
        $this->engine->commit();
    }

    /**
     * Deletes index by id.
     * @param Solr Document string $id
     */
    public function delete_index_by_id($id) {
        $this->engine->delete_by_id($id);
        $this->engine->commit();
    }

    /**
     * Returns Global Search configuration settings from config_plugin table.
     * @param array $mods
     * @return array $configsettings
     */
    public function get_config($mods) {
        $allconfigs = get_config('search');
        $vars = array('indexingstart', 'indexingend', 'lastindexrun', 'docsignored', 'docsprocessed', 'recordsprocessed');

        $configsettings =  array();
        foreach ($mods as $mod) {
            $configsettings[$mod] = new stdClass();
            foreach ($vars as $var) {
                $name = "{$mod}_$var";
                if (!empty($allconfigs->$name)) {
                    $configsettings[$mod]->$var = $allconfigs->$name;
                } else {
                    $configsettings[$mod]->$var = 0;
                }
            }
            if (!empty($configsettings[$mod]->lastindexrun)) {
                $configsettings[$mod]->lastindexrun = userdate($configsettings[$mod]->lastindexrun);
            } else {
                $configsettings[$mod]->lastindexrun = "Never";
            }
        }
        return $configsettings;
    }

    /**
     * Returns Global Search iterator setting for indexing files.
     * @param string $mod
     * @return string setting value
     */
    public function get_config_file($mod) {
        switch ($mod) {
            case 'lesson':
                return get_config('search', $mod . '_lastindexrun');

            case 'wiki':
                return get_config('search', $mod . '_lastindexedfilerun');

            default:
                return 0;
        }
    }


    /**
     * Searches the user table for userid
     * @param string name of user
     * @return string $url of the user's profile
     */
    public function get_user_url($fullname) {
        global $DB;
        $url = '';
        try {
            $username = explode(' ', $fullname);
            if (count($username) == 2) {
                $userdata = $DB->get_records('user',
                                             array('firstname' => $username[0],
                                                   'lastname' => $username[1]),
                                             'id', 'username,id');
                $userdata = array_pop($userdata);
                $url = new moodle_url('/user/profile.php?id=' . $userdata->id);
            }
        } catch (dml_missing_record_exception $ex) {
            return $url;
        }
        return $url;
    }

    public function get_more_like_this_text($text) {
        return $this->engine->get_more_like_this_text($text);
    }

    public function post_file($file, $url) {
        return $this->engine->post_file($file, $url);
    }
}
