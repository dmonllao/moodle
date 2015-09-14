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

    const SEARCH_SET_FRAG_SIZE = 500;

    protected static $enabledsearchcomponents = array();
    protected static $allsearchcomponents = array();

    protected static $searchsubsystems = null;

    /**
     * @var \core_search
     */
    protected static $instance = null;

    /**
     * Constructor, use \core_search::instance instead to get a class instance.
     *
     * @param \core\search\base The search engine to use
     */
    public function __construct($engine) {
        $this->engine = $engine;
    }

    /**
     * Returns an initialised \core_search instance.
     *
     * It requires global search to be enabled. Use \core_search::is_global_search_enabled
     * to verify it is enabled.
     *
     * @throws moodle_exception
     * @param \core\search\base|bool $engine The engine, ready to be used.
     * @return \core_search
     */
    public static function instance($engine = false) {
        global $CFG;

        // One per request, this should be purged during testing.
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (!self::is_global_search_enabled()) {
            throw new moodle_exception('globalsearchdisabled', 'search');
        }

        if ($engine === false) {

            $classname = '\\search_' . $CFG->searchengine . '\\engine';
            if (!class_exists($classname)) {
                throw new moodle_exception('enginenotfound', 'search', $CFG->searchengine);
            }

            $engine = new $classname();

            if (!$engine->is_installed()) {
                throw new moodle_exception('enginenotinstalled', 'search', $CFG->searchengine);
            }
            if (!$engine->is_server_ready()) {
                throw new moodle_exception('engineserverstatus', 'search', $CFG->searchengine);
            }
        }

        self::$instance = new \core_search($engine);
        return self::$instance;
    }

    public static function is_global_search_enabled() {
        global $CFG;
        return !empty($CFG->enableglobalsearch);
    }

    /**
     * Returns whether the component supports search.
     *
     * @param string $component Frankenstyle component name
     * @return bool
     */
    public static function is_component_supported($component) {
        $classname = '\\' . $component . '\\search';
        if (class_exists($classname) && method_exists($classname, 'is_supported') && $classname::is_supported()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the an instance of the component search.
     *
     * @param string $componentname Frankenstyle component name
     * @return \core\search\base|bool False if the component does not implement search
     */
    public static function get_search_component($componentname) {

        if (!empty(self::$enabledsearchcomponents[$componentname])) {
            return self::$enabledsearchcomponents[$componentname];
        }

        $classname = '\\' . $componentname . '\\search';
        if (class_exists($classname) && $classname::is_supported()) {
            return new $classname();
        }

        return false;
    }

    /**
     * Return the list of components featuring global search.
     *
     * @param bool $enabled Return only the enabled ones.
     * @return \core\search\base[]
     */
    public static function get_search_components_list($enabled = false) {

        // Two different arrays, we don't expect these arrays to be big.
        if (!$enabled && !empty(self::$allsearchcomponents)) {
            return self::$allsearchcomponents;
        } else if ($enabled && !empty(self::$enabledsearchcomponents)) {
            return self::$enabledsearchcomponents;
        }

        $searchcomponents = array();

        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $unused) {
            $plugins = \core_component::get_plugin_list($plugintype);
            foreach ($plugins as $pluginname => $unused) {

                $plugin = $plugintype . '_' . $pluginname;
                if (self::is_component_supported($plugin)) {
                    $classname = '\\' . $plugin . '\\search';
                    $searchclass = new $classname();
                    if (!$enabled || ($enabled && $searchclass->is_enabled())) {
                        $searchcomponents[$plugin] = $searchclass;
                    }
                }
            }
        }

        $subsystems = \core_component::get_core_subsystems();
        foreach ($subsystems as $subsystemname => $subsystempath) {
            $componentname = 'core_' . $subsystemname;
            if (self::is_component_supported($componentname)) {
                $classname = '\\' . $plugin . '\\search';
                $searchclass = new $classname();
                if (!$enabled || ($enabled && $searchclass->is_enabled())) {
                    $searchcomponents[$componentname] = $searchclass;
                }

            }
        }

        // Cache results.
        if ($enabled) {
            self::$enabledsearchcomponents = $searchcomponents;
        } else {
            self::$allsearchcomponents = $searchcomponents;
        }

        return $searchcomponents;
    }

    /**
     * Clears all static attributes.
     *
     * @return void
     */
    public static function clear_static_instances() {

        self::$enabledsearchcomponents = array();
        self::$allsearchcomponents = array();
        self::$searchsubsystems = null;
        self::$instance = null;
    }

    protected function get_components_user_accesses() {

        // All results for admins. Eventually we could add a new capability for managers.
        if (is_siteadmin()) {
            return false;
        }

        $componentsbylevel = array();

        // Split components by context level so we only iterate only once through courses and cms.
        $componentslist = self::get_search_components_list(true);
        foreach ($componentslist as $component => $unused) {
            $classname = '\\' . $component . '\\search';
            $componentsbylevel[$classname::get_level()][$component] = new $classname();
        }

        // This will store item - allowed contexts relations.
        $componentscontexts = array();

        if (!empty($componentsbylevel[CONTEXT_SYSTEM])) {
            // No contexts at this level yet, each component item would decide what to do with it.
        }

        // TODO Change this get my courses for courses I can view and are visible, including the site.
        $usercourses = enrol_get_my_courses(array('id', 'cacherev'));
        $site = course_modinfo::instance(SITEID);
        foreach ($usercourses as $course) {

            // Info about the course modules.
            $modinfo = get_fast_modinfo($course);

            if (!empty($componentsbylevel[CONTEXT_COURSE])) {
                // No contexts at this level yet, each component item would decide what to do with it.
            }

            if (!empty($componentsbylevel[CONTEXT_MODULE])) {
                foreach ($componentsbylevel[CONTEXT_MODULE] as $componentname => $searchclass) {

                    // Removing the plugintype 'mod_' prefix.
                    $modulename = substr($componentname, 4);

                    // Module instances the user has access to.
                    $modinstances = $modinfo->get_instances_of($modulename);
                    foreach ($modinstances as $modinstance) {
                        $componentscontexts[$componentname][] = $modinstance->context->id;
                    }
                }
            }
        }
        return $componentscontexts;
    }

    public function search($data) {
        $componentscontexts = $this->get_components_user_accesses();
        return $this->engine->execute_query($data, $componentscontexts);
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

        $searchcomponents = $this->get_search_components_list(true);
        foreach ($searchcomponents as $componentname => $componentsearch) {

            // TODO This is called from search/admin.php too, not only CLI.
            mtrace('Processing ' . $componentsearch->get_component_visible_name() . ' component');
            $indexingstart = time();

            // This is used to store this component config.
            list($componentconfigname, $varname) = $componentsearch->get_config_var_name();

            $lastindexrun = get_config($componentconfigname, $varname . '_lastindexrun');

            // Iteration delegated to the component.
            $recordset = $componentsearch->search_iterator($lastindexrun);

            $numrecords = 0;
            $numdocs = 0;
            $numdocsignored = 0;

            foreach ($recordset as $record) {
                ++$numrecords;
                $timestart = microtime(true);
                $documents = $componentsearch->search_get_documents($record->id);
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
                set_config($varname . '_indexingstart', $indexingstart, $componentconfigname);
                set_config($varname . '_indexingend', $indexingend, $componentconfigname);
                set_config($varname . '_lastindexrun', $record->modified, $componentconfigname);
                set_config($varname . '_docsignored', $numdocsignored, $componentconfigname);
                set_config($varname . '_docsprocessed', $numdocs, $componentconfigname);
                set_config($varname . '_recordsprocessed', $numrecords, $componentconfigname);
            // TODO This is called from search/admin.php too, not only CLI.
                mtrace("Processed $numrecords records containing $numdocs documents for " . $componentname . ' component. ' .
                    'Commits completed.');
            }
        }
    }

    /**
     * Index all Rich Document files.
     */
    public function index_files() {
        global $CFG;

        // TODO This should use 
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
     * Resets components config tables after index deletion as re-indexing will be done from start.
     *
     * @param string $componentname Frankenstyle component name.
     */
    public function reset_config($componentname = false) {

        if (!empty($componentname)) {
            $components = array();
            if (!$components[$componentname] = self::get_search_component($componentname)) {
                throw new moodle_exception('errorcomponentnotavailable', 'search');
            }
        } else {
            // Only the enabled ones.
            $components = self::get_search_components_list(true);
        }

        foreach ($components as $componentsearch) {

            list($componentname, $varname) = $componentsearch->get_config_var_name();

            set_config($varname . '_indexingstart', 0, $componentname);
            set_config($varname . '_indexingend', 0, $componentname);
            set_config($varname . '_lastindexrun', 0, $componentname);
            set_config($varname . '_docsignored', 0, $componentname);
            set_config($varname . '_docsprocessed', 0, $componentname);
            set_config($varname . '_recordsprocessed', 0, $componentname);
        }
    }

    /**
     * Deletes a component index or all component indexes if no component provided.
     *
     * @param string $componentname The component frankenstyle name or false for all
     */
    public function delete_index($componentname = false) {
        if (!empty($componentname)) {
            $this->engine->delete($componentname);
            $this->reset_config($componentname);
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
     * Returns search components configuration.
     *
     * @param \core\search\base[]  $searchcomponents
     * @return stdClass[] $configsettings
     */
    public function get_components_config($searchcomponents) {

        $allconfigs = get_config('search');
        $vars = array('indexingstart', 'indexingend', 'lastindexrun', 'docsignored', 'docsprocessed', 'recordsprocessed');

        $configsettings =  array();
        foreach ($searchcomponents as $componentname => $componentsearch) {

            $configsettings[$componentname] = new stdClass();
            list($componentname, $varname) = $componentsearch->get_config_var_name();

            if (!$componentsearch->is_enabled()) {
                // We delete all indexed data on disable so no info.
                foreach ($vars as $var) {
                    $configsettings[$componentname]->{$var} = 0;
                }
            } else {
                foreach ($vars as $var) {
                    $configsettings[$componentname]->{$var} = get_config($componentname, $varname .'_' . $var);
                }
            }

            // Formatting the time.
            if (!empty($configsettings[$componentname]->lastindexrun)) {
                $configsettings[$componentname]->lastindexrun = userdate($configsettings[$componentname]->lastindexrun);
            } else {
                $configsettings[$componentname]->lastindexrun = get_string('never');
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
                                                   'lastname' => $username[1],
                                                   'deleted' => 0),
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
