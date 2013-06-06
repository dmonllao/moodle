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
 * Search engine Elasticsearch plugin settings.
 *
 * @package    search_solr
 * @copyright  2015 Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('search_solr_settings', '', get_string('pluginname_desc', 'search_solr')));

    if (!during_initial_install()) {
        if (function_exists('solr_get_version')) {

            $version = solr_get_version();
            $solr_installed = true;

            $options = array('4.0'=>'4.x', '3.0'=>'3.x');
            $settings->add(new admin_setting_configselect('solr_version', new lang_string('solrversion', 'search_solr'), new lang_string('solrversion_desc', 'search_solr', $version), (substr($version, 0, 2) == '2.' ? '4.0' : '3.0'), $options));
            $settings->add(new admin_setting_configtext('solr_server_hostname', new lang_string('solrserverhostname', 'search_solr'), new lang_string('solrserverhostname_desc', 'search_solr'), '127.0.0.1', PARAM_TEXT));
            $settings->add(new admin_setting_configcheckbox('solr_secure', new lang_string('solrsecuremode', 'search_solr'), new lang_string('solrsecuremode_desc', 'search_solr'), 0, 1, 0));
            $settings->add(new admin_setting_configtext('solr_server_port', new lang_string('solrhttpconnectionport', 'search_solr'), new lang_string('solrhttpconnectionport_desc', 'search_solr'), (!empty($CFG->solr_secure) ? 8443 : 8983), PARAM_INT));
            $settings->add(new admin_setting_configtext('solr_server_username', new lang_string('solrauthuser', 'search_solr'), new lang_string('solrauthuser_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_server_password', new lang_string('solrauthpassword', 'search_solr'), new lang_string('solrauthpassword_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_server_timeout', new lang_string('solrhttpconnectiontimeout', 'search_solr'), new lang_string('solrhttpconnectiontimeout_desc', 'search_solr'), 30, PARAM_INT));
            $settings->add(new admin_setting_configtext('solr_ssl_cert', new lang_string('solrsslcert', 'search_solr'), new lang_string('solrsslcert_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_ssl_cert_only', new lang_string('solrsslcertonly', 'search_solr'), new lang_string('solrsslcertonly_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_ssl_key', new lang_string('solrsslkey', 'search_solr'), new lang_string('solrsslkey_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_ssl_keypassword', new lang_string('solrsslkeypassword', 'search_solr'), new lang_string('solrsslkeypassword_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_ssl_cainfo', new lang_string('solrsslcainfo', 'search_solr'), new lang_string('solrsslcainfo_desc', 'search_solr'), '', PARAM_RAW));
            $settings->add(new admin_setting_configtext('solr_ssl_capath', new lang_string('solrsslcapath', 'search_solr'), new lang_string('solrsslcapath_desc', 'search_solr'), '', PARAM_RAW));
        }
    }
}
