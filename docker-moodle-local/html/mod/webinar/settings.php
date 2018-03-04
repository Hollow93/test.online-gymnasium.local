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
 * webinar module admin settings and defaults
 *
 * @package    mod_webinar
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('webinar/framesize',
        get_string('framesize', 'webinar'), get_string('configframesize', 'webinar'), 130, PARAM_INT));
    $settings->add(new admin_setting_configpasswordunmask('webinar/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'webinar'), ''));
    $settings->add(new admin_setting_configcheckbox('webinar/rolesinparams',
        get_string('rolesinparams', 'webinar'), get_string('configrolesinparams', 'webinar'), false));
    $settings->add(new admin_setting_configmultiselect('webinar/displayoptions',
        get_string('displayoptions', 'webinar'), get_string('configdisplayoptions', 'webinar'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('webinarmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('webinar/printintro',
        get_string('printintro', 'webinar'), get_string('printintroexplain', 'webinar'), 1));
    $settings->add(new admin_setting_configselect('webinar/display',
        get_string('displayselect', 'webinar'), get_string('displayselectexplain', 'webinar'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    $settings->add(new admin_setting_configtext('webinar/popupwidth',
        get_string('popupwidth', 'webinar'), get_string('popupwidthexplain', 'webinar'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('webinar/popupheight',
        get_string('popupheight', 'webinar'), get_string('popupheightexplain', 'webinar'), 450, PARAM_INT, 7));
}
