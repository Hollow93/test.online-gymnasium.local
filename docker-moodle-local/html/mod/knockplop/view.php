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
 * Prints an instance of knockplop.
 *
 * @package     knockplop
 * @copyright   2017 Misi <bakfitty@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$k  = optional_param('k', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('knockplop', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('knockplop', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($k) {
    $moduleinstance = $DB->get_record('knockplop', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('knockplop', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', knockplop));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_knockplop\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('knockplop', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/knockplop/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$url = $CFG->knockplop_baseurl."/".$moduleinstance->room;

if ($moduleinstance->pageredirect){
	redirect($url);
}

?>
<div class="knockplop_home">
	<div>
		<a class="btn btn-lg btn-primary" target="_blank" href="<?php echo $url ?>"><i class="fa fa-users"></i> <?php echo get_string('join_room', 'knockplop');?></a>
	<div>
</div>

<?php

echo $OUTPUT->footer();
