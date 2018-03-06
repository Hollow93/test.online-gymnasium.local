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
 * Mandatory public API of webinar module
 *
 * @package    mod_webinar
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in webinar module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function webinar_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function webinar_get_extra_capabilities()
{
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function webinar_reset_userdata($data)
{
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function webinar_get_view_actions()
{
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function webinar_get_post_actions()
{
    return array('update', 'add');
}

/**
 * Add webinar instance.
 * @param object $data
 * @param object $mform
 * @return int new webinar instance id
 */
function webinar_add_instance($data, $mform)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/webinar/locallib.php');

    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth'] = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro'] = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = webinar_fix_submitted_webinar($data->externalurl);

    $data->timemodified = time();
    $data->id = $DB->insert_record('webinar', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'webinar', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update webinar instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function webinar_update_instance($data, $mform)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/webinar/locallib.php');

    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth'] = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro'] = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = webinar_fix_submitted_webinar($data->externalurl);

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('webinar', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'webinar', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete webinar instance.
 * @param int $id
 * @return bool true
 */
function webinar_delete_instance($id)
{
    global $DB;

    if (!$webinar = $DB->get_record('webinar', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('webinar', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'webinar', $id, null);

    // note: all context files are deleted automatically

    $DB->delete_records('webinar', array('id' => $webinar->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function webinar_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/webinar/locallib.php");

    if (!$webinar = $DB->get_record('webinar', array('id' => $coursemodule->instance),
        'id, name, display, displayoptions, externalurl, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $webinar->name;

    //note: there should be a way to differentiate links from normal resources
    $info->icon = webinar_guess_icon($webinar->externalurl, 24);

    $display = webinar_get_final_display_type($webinar);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullwebinar = "$CFG->wwwroot/mod/webinar/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($webinar->displayoptions) ? array() : unserialize($webinar->displayoptions);
        $width = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullwebinar', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullwebinar = "$CFG->wwwroot/mod/webinar/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullwebinar'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('webinar', $webinar, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function webinar_page_type_list($pagetype, $parentcontext, $currentcontext)
{
    $module_pagetype = array('mod-webinar-*' => get_string('page-mod-webinar-x', 'webinar'));
    return $module_pagetype;
}

/**
 * Export webinar resource contents
 *
 * @return array of file content
 */
function webinar_export_contents($cm, $basewebinar)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/webinar/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $webinarrecord = $DB->get_record('webinar', array('id' => $cm->instance), '*', MUST_EXIST);

    $fullwebinar = str_replace('&amp;', '&', webinar_get_full_webinar($webinarrecord, $cm, $course));
    $iswebinar = clean_param($fullwebinar, PARAM_webinar);
    if (empty($iswebinar)) {
        return null;
    }

    $webinar = array();
    $webinar['type'] = 'webinar';
    $webinar['filename'] = clean_param(format_string($webinarrecord->name), PARAM_FILE);
    $webinar['filepath'] = null;
    $webinar['filesize'] = 0;
    $webinar['filewebinar'] = $fullwebinar;
    $webinar['timecreated'] = null;
    $webinar['timemodified'] = $webinarrecord->timemodified;
    $webinar['sortorder'] = null;
    $webinar['userid'] = null;
    $webinar['author'] = null;
    $webinar['license'] = null;
    $contents[] = $webinar;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function webinar_dndupload_register()
{
    return array('types' => array(
        array('identifier' => 'url', 'message' => get_string('createwebinar', 'webinar'))
    ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function webinar_dndupload_handle($uploadinfo)
{
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>' . $uploadinfo->displayname . '</p>';
    $data->introformat = FORMAT_HTML;
    $data->externalurl = clean_param($uploadinfo->content, PARAM_webinar);
    $data->timemodified = time();

    // Set the display options to the site defaults.
    $config = get_config('webinar');
    $data->display = $config->display;
    $data->popupwidth = $config->popupwidth;
    $data->popupheight = $config->popupheight;
    $data->printintro = $config->printintro;

    return webinar_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $webinar webinar object
 * @param  stdClass $course course object
 * @param  stdClass $cm course module object
 * @param  stdClass $context context object
 * @since Moodle 3.0
 */
function webinar_view($webinar, $course, $cm, $context)
{

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $webinar->id
    );

    $event = \mod_webinar\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('webinar', $webinar);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function webinar_check_updates_since(cm_info $cm, $from, $filter = array())
{
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_webinar_core_calendar_provide_event_action(calendar_event $event,
                                                    \core_calendar\action_factory $factory)
{
    $cm = get_fast_modinfo($event->courseid)->instances['webinar'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/webinar/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
