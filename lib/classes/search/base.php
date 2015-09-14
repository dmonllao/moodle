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
 * Search base class to extend by moodle components implementing search.
 *
 * @package    core_search
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Base search implementation.
 *
 * @package    core_search
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /**
     * The context level the search implementation is working on.
     *
     * @static
     * @var int
     */
    protected static $level = CONTEXT_SYSTEM;

    /**
     * Constructor.
     *
     * @throws \coding_exception
     * @return void
     */
    public final function __construct() {

        $classname = get_class($this);

        // Detect possible issues when defining the class.
        if (strpos($classname, '\search') === false) {
            throw new \coding_exception($classname . ' class should specify its component namespace and it should be named search.');
        } else if (strpos($classname, '_') === false) {
            throw new \coding_exception($classname . ' class namespace should be its component frankenstyle name');
        }

        $this->componentname = substr($classname, 0, strpos($classname, '\\'));
        $this->componenttype = substr($this->componentname, 0, strpos($this->componentname, '_'));
    }

    /**
     * Whether the component supports global search or not.
     *
     * Initially returning true as there is no point on implementing a class
     * if it is not supported. Components might override it if they have to
     * deal with specific requirements.
     *
     * @static
     * @return bool
     */
    public static function is_supported() {
        return true;
    }

    /**
     * Specifies whether or not the component allows files to be indexed.
     *
     * @return bool
     */
    public static function supports_files_indexing() {
        return false;
    }

    /**
     * Returns context level property.
     *
     * @static
     * @return int
     */
    public static function get_level() {
        return static::$level;
    }

    protected function get_component_name() {
        return $this->componentname;
    }

    public function get_component_type() {
        return $this->componenttype;
    }

    /**
     * Returns the component visible name.
     *
     * @param bool $lazyload Usually false, unless when in admin settings.
     * @return void
     */
    public function get_component_visible_name($lazyload = false) {
        if ($this->componenttype === 'core') {
            // Stripping the component type. Would be better to have a proper name for each
            // moodle subsystem, but we can defer this when implementing subsystems search.
           return get_string('subsystemname', '', substr($this->componentname, 5));
        } else {
            return get_string('pluginname', $this->componentname, null, $lazyload);
        }
    }

    public function get_component_type_visible_name($lazyload = false) {
        if ($this->componenttype === 'core') {
            return get_string('core');
        } else {
            return get_string('type_' . $this->componenttype, 'core_plugin', $lazyload);
        }
    }
    /**
     * Returns the config var name.
     *
     * It depends on whether it is a moodle subsystem or a plugin as plugin-related config should remain in their own scope.
     *
     * @return string Config var path including the plugin (or component) and the varname where 
     */
    public function get_config_var_name() {

        if ($this->componenttype === 'core') {
            // Core subsystems config in search.
            return array('search', $this->componentname);
        }

        // Plugins config in the plugin scope.
        return array($this->componentname, 'search');
    }

    public function is_enabled() {
        list($componentname, $varname) = $this->get_config_var_name();
        return (bool)get_config($componentname, 'enable' . $varname);
    }

    abstract public function search_iterator($from = 0);

    abstract public function search_get_documents($id);

    abstract public function search_access($id);
}
