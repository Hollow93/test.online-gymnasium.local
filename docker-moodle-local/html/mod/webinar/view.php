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
 * webinar module main user interface
 *
 * @package    mod_webinar
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/webinar/lib.php");
require_once("$CFG->dirroot/mod/webinar/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // webinar instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $webinar = $DB->get_record('webinar', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('webinar', $webinar->id, $webinar->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('webinar', $id, 0, false, MUST_EXIST);
    $webinar = $DB->get_record('webinar', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/webinar:view', $context);

// Completion and trigger events.
webinar_view($webinar, $course, $cm, $context);

$PAGE->set_url('/mod/webinar/view.php', array('id' => $cm->id));

// Make sure webinar exists before generating output - some older sites may contain empty webinars
// Do not use PARAM_webinar here, it is too strict and does not support general URIs!
$extwebinar = trim($webinar->externalurl);
if (empty($extwebinar) or $extwebinar === 'http://') {
    webinar_print_header($webinar, $cm, $course);
    webinar_print_heading($webinar, $cm, $course);
    webinar_print_intro($webinar, $cm, $course);
    notice(get_string('invalidstoredwebinar', 'webinar'), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($extwebinar);

$displaytype = webinar_get_final_display_type($webinar);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (strpos(get_local_referer(false), 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or webinar index page,
    // the redirection is needed for completion tracking and logging
    $fullwebinar = str_replace('&amp;', '&', webinar_get_full_webinar($webinar, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external webinar without any possibility to edit activity or course settings.
        $editwebinar = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editwebinar = new moodle_url('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editwebinar = new moodle_url('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editwebinar) {
            redirect($fullwebinar, html_writer::link($editwebinar, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullwebinar);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        webinar_display_embed($webinar, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        webinar_display_frame($webinar, $cm, $course);
        break;
    default:
        webinar_print_workaround($webinar, $cm, $course);
        break;
}
