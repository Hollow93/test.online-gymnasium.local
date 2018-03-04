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
 * @package block_category_enrol
 * @author Nadav Kavalerchik <nadavkav@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace block_category_enrol;

defined('MOODLE_INTERNAL') || die();

/**
 * block_category_enrol event handler.
 */
class observer {

    /**
     * Triggered via role_assigned event.
     * - Enrol user with specific role1 to a specific role2 on the course current category.
     *
     * @param \core\event\role_assigned $event
     * @return bool role assignment id on success
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        // $event->objectid == roleid
        $category_enrol_blockinstance = $DB->get_record('block_instances', array('parentcontextid' => $event->contextid));
        if (!empty($category_enrol_blockinstance->configdata)) {
            $category_enrol_config = unserialize(base64_decode($category_enrol_blockinstance->configdata));
        }
        $roleassignmentid = false;
        if ($event->action === 'assigned' && $event->target === 'role' && $event->objectid == $category_enrol_config->roleid_incourse) {
            $course = $DB->get_record('course', array('id' => $event->courseid));
            $contextcategory = \context_coursecat::instance($course->category);
            $roleassignmentid = role_assign($category_enrol_config->roleid_atcategory, $event->relateduserid, $contextcategory->id);
        }

        return $roleassignmentid;
    }
}
