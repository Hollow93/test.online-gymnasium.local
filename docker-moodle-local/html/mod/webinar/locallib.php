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
 * Private webinar module utility functions
 *
 * @package    mod_webinar
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/webinar/lib.php");

/**
 * This methods does weak webinar validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $webinar
 * @return bool true is seems valid, false if definitely not valid webinar
 */
function webinar_appears_valid_webinar($webinar) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $webinar)) {
        // note: this is not exact validation, we look for severely malformed webinars only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $webinar);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $webinar);
    }
}

/**
 * Fix common webinar problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $webinar
 * @return string
 */
function webinar_fix_submitted_webinar($webinar) {
    // note: empty webinars are prevented in form validation
    $webinar = trim($webinar);

    // remove encoded entities - we want the raw URI here
    $webinar = html_entity_decode($webinar, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $webinar) and !preg_match('|^/|', $webinar)) {
        // invalid URI, try to fix it by making it normal webinar,
        // please note relative webinars are not allowed, /xx/yy links are ok
        $webinar = 'http://'.$webinar;
    }

    return $webinar;
}

/**
 * Return full webinar with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $webinar
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string webinar with & encoded as &amp;
 */
function webinar_get_full_webinar($webinar, $cm, $course, $config=null) {

    $parameters = empty($webinar->parameters) ? array() : unserialize($webinar->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullwebinar = html_entity_decode($webinar->externalurl, ENT_QUOTES, 'UTF-8');

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullwebinar) or preg_match('|^/|', $fullwebinar)) {
        // encode extra chars in webinars - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullwebinar = preg_replace_callback("/[^$allowed]/", 'webinar_filter_callback', $fullwebinar);
    } else {
        // encode special chars only
        $fullwebinar = str_replace('"', '%22', $fullwebinar);
        $fullwebinar = str_replace('\'', '%27', $fullwebinar);
        $fullwebinar = str_replace(' ', '%20', $fullwebinar);
        $fullwebinar = str_replace('<', '%3C', $fullwebinar);
        $fullwebinar = str_replace('>', '%3E', $fullwebinar);
    }

    // add variable webinar parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('webinar');
        }
        $paramvalues = webinar_get_variable_values($webinar, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawurlencode($parse).'='.rawurlencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullwebinar, 'teamspeak://') === 0) {
                $fullwebinar = $fullwebinar.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullwebinar, '?') === false) ? '?' : '&';
                $fullwebinar = $fullwebinar.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullwebinar = str_replace('&', '&amp;', $fullwebinar);

    return $fullwebinar;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function webinar_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print webinar header.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @return void
 */
function webinar_print_header($webinar, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$webinar->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($webinar);
    echo $OUTPUT->header();
}

/**
 * Print webinar heading.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function webinar_print_heading($webinar, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($webinar->name), 2);
}

/**
 * Print webinar introduction.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function webinar_print_intro($webinar, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($webinar->displayoptions) ? array() : unserialize($webinar->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($webinar->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'webinarintro');
            echo format_module_intro('webinar', $webinar, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display webinar frames.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function webinar_display_frame($webinar, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        webinar_print_header($webinar, $cm, $course);
        webinar_print_heading($webinar, $cm, $course);
        webinar_print_intro($webinar, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('webinar');
        $context = context_module::instance($cm->id);
        $extewebinar = webinar_get_full_webinar($webinar, $cm, $course, $config);
        $navwebinar = "$CFG->wwwroot/mod/webinar/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($webinar->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','webinar'));
        $contentframetitle = s(format_string($webinar->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navwebinar" title="$modulename"/>
    <frame src="$extewebinar" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print webinar info and link.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function webinar_print_workaround($webinar, $cm, $course) {
    global $OUTPUT;

    webinar_print_header($webinar, $cm, $course);
    webinar_print_heading($webinar, $cm, $course, true);
    webinar_print_intro($webinar, $cm, $course, true);

    $fullwebinar = webinar_get_full_webinar($webinar, $cm, $course);

    $display = webinar_get_final_display_type($webinar);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullwebinar = addslashes_js($fullwebinar);
        $options = empty($webinar->displayoptions) ? array() : unserialize($webinar->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullwebinar', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="webinarworkaround">';
    print_string('clicktoopen', 'webinar', "<a href=\"$fullwebinar\" $extra>$fullwebinar</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded webinar file.
 * @param object $webinar
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function webinar_display_embed($webinar, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = resourcelib_guess_url_mimetype($webinar->externalurl);
    $fullwebinar  = webinar_get_full_webinar($webinar, $cm, $course);
    $title    = $webinar->name;

    $link = html_writer::tag('a', $fullwebinar, array('href'=>str_replace('&amp;', '&', $fullwebinar)));
    $clicktoopen = get_string('clicktoopen', 'webinar', $link);
    $moodlewebinar = new moodle_url($fullwebinar);

    $extension = resourcelib_get_extension($webinar->externalurl);

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullwebinar, $title);

    } else if ($mediamanager->can_embed_webinar($moodlewebinar, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_webinar($moodlewebinar, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullwebinar, $title, $clicktoopen, $mimetype);
    }

    webinar_print_header($webinar, $cm, $course);
    webinar_print_heading($webinar, $cm, $course);

    echo $code;

    webinar_print_intro($webinar, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $webinar
 * @return int display type constant
 */
function webinar_get_final_display_type($webinar) {
    global $CFG;

    if ($webinar->display != RESOURCELIB_DISPLAY_AUTO) {
        return $webinar->display;
    }

    // detect links to local moodle pages
    if (strpos($webinar->externalurl, $CFG->wwwroot) === 0) {
        if (strpos($webinar->externalurl, 'file.php') === false and strpos($webinar->externalurl, '.php') !== false ) {
            // most probably our moodle page with navigation
            return RESOURCELIB_DISPLAY_OPEN;
        }
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = resourcelib_guess_url_mimetype($webinar->externalurl);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to webinar
 * @param object $config webinar module config options
 * @return array array describing opt groups
 */
function webinar_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'webinar'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'webinar')] = array(
        'webinarinstance'     => 'id',
        'webinarcmid'         => 'cmid',
        'webinarname'         => get_string('name'),
        'webinaridnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverwebinar'       => get_string('serverwebinar', 'webinar'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userwebinar'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to webinar
 * @param object $webinar module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function webinar_get_variable_values($webinar, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverwebinar'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'webinarinstance'     => $webinar->id,
        'webinarcmid'         => $cm->id,
        'webinarname'         => format_string($webinar->name),
        'webinaridnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userwebinar']         = $USER->webinar;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = webinar_get_encrypted_parameter($webinar, $config);
    }

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $webinar
 * @param object $config
 * @return string
 */
function webinar_get_encrypted_parameter($webinar, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($webinar, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general webinar
 * @param $fullwebinar
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function webinar_guess_icon($fullwebinar, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullwebinar, '/') < 3 or substr($fullwebinar, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullwebinar, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
