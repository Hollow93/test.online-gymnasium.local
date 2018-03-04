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
 * Form for editing category_enrol block instances.
 *
 * @package block_category_enrol
 * @author Nadav Kavalerchik <nadavkav@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

class block_category_enrol_edit_form extends block_edit_form {
    /**
     * The definition of the fields to use.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $DB;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $roles = $DB->get_records('role');
        $userroles = array();
        foreach($roles as $r)
            $userroles[$r->id] = (!empty($r->name)) ? $r->name : $r->shortname;

        $mform->addElement('select', 'config_roleid_incourse', get_string('courseroles', 'block_category_enrol'), $userroles);
        $mform->setType('roleid_incourse', PARAM_INT);

        $mform->addElement('select', 'config_roleid_atcategory', get_string('categoryroles', 'block_category_enrol'), $userroles);
        $mform->setType('roleid_atcategory', PARAM_INT);
    }
}
