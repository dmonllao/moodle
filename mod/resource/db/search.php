<?php

/**
 * Global Search API
 * @package mod_resource
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function resource_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {resource} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function resource_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $resource = $DB->get_record('resource', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('resource', $resource->id, $resource->course);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    $contextlink = '/mod/resource/view.php?r=' . $resource->id;
    $modulelink = '/mod/resource/view.php?id=' . $cm->id;

    // Prepare associative array with data from DB.
    $doc = array();
    $doc['type'] = SEARCH_TYPE_HTML;
    $doc['id']          = 'resource_' . $resource->id;
    $doc['modified']    = gmdate('Y-m-d\TH:i:s\Z', $resource->timemodified);
    $doc['intro']       = strip_tags($resource->intro);
    $doc['name']        = $resource->name;
    $doc['courseid']    = $resource->course;
    $doc['contextlink'] = $contextlink;
    $doc['modulelink']  = $modulelink;
    $doc['module']      = 'resource';
    $docs[] = $doc;

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
    if (count($files) > 0) {
        $mainfile = reset($files);
    }

    if (strpos($mime = $mainfile->get_mimetype(), 'image') === false) {
        $filename = urlencode($mainfile->get_filename());
        $directlink = '/pluginfile.php/' . $context->id . '/mod_resource/content/' . $resource->id . '/' . $filename;
        $url = 'literal.id=' . 'resource_' . $id . '_file_1' . '&literal.module=resource&literal.type=3' .
                '&literal.directlink=' . $directlink . '&literal.courseid=' . $resource->course .
                '&literal.contextlink=' . $contextlink . '&literal.modulelink=' . $modulelink;

        $globalsearch = new core_search();
        $globalsearch->post_file($mainfile, $url);
    }

    return $docs;
}

function resource_search_access($id) {
    global $DB, $USER;

    try {
        $resource = $DB->get_record('resource', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('resource', $resource->id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    if (!can_access_course($course, null, '', true)) {
        return SEARCH_ACCESS_DENIED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/resource:view', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
