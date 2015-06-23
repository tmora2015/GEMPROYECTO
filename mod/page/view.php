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
 * Page module version information
 *
 * @package mod_page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/page/forms.php');
global $USER, $DB;
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
if ($p) {
    if (!$page = $DB->get_record('page', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('page', $id)) {
        print_error('invalidcoursemodule');
    }
    $page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST);
}
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/page:view', $context);
// Trigger module viewed event.
$event = \mod_page\event\course_module_viewed::create(array(
   'objectid' => $page->id,
   'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('page', $page);
$event->trigger();
// Update 'viewed' state if required by completion system
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);
$PAGE->set_url('/mod/page/view.php', array('id' => $cm->id));
$baseurl = new moodle_url('/mod/page/view.php', array('id' => $cm->id));
$options = empty($page->displayoptions) ? array() : unserialize($page->displayoptions);
if ($inpopup and $page->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page);
}
echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])) {
    echo $OUTPUT->heading(format_string($page->name), 2);
}
if (!empty($options['printintro'])) {
    if (trim(strip_tags($page->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'pageintro');
        echo format_module_intro('page', $page, $cm->id);
        echo $OUTPUT->box_end();
    }
}
$content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $page->contentformat, $formatoptions);
$existingrate = $DB->get_record_sql("SELECT *
		FROM {wcd_rate} w 
		WHERE (w.courseid = '$course->id') and (w.userid = '$USER->id') and (w.pageid = '$cm->id') ");
$existingcontent = $DB->get_record_sql("SELECT *
		FROM {page} w 
		JOIN {course_modules} cm
		ON cm.instance = w.id
		WHERE (w.course = '$course->id') and (cm.id = '$cm->id') and (w.content LIKE '<p><a%')");
$existcom = $DB->get_record_sql("SELECT *
		FROM {comments} c
		WHERE (c.courseid = '$course->id') and (c.userid = '$USER->id') and (c.pageid = '$cm->id')");


$countvotes =  $DB->get_record_sql("SELECT count(w.rate) as 'count'
		FROM {wcd_rate} w 
		WHERE (w.courseid = '$course->id') and (w.pageid = '$cm->id') ");
$numberofvotes = $countvotes->count;
$meanvotes =  $DB->get_record_sql("SELECT avg(w.rate) as 'avg'
		FROM {wcd_rate} w 
		WHERE (w.courseid = '$course->id') and (w.pageid = '$cm->id') ");
$meanofvotes = floatval($meanvotes->avg);
if(empty($existingrate) && !empty($existingcontent)){
	echo "Votes:".'&nbsp'.$numberofvotes.".".'&nbsp'."Average".'&nbsp'."($meanofvotes)";
	echo "<br>";
	echo "<br>";
	$mform = new rating($baseurl);
if ($fromform = $mform->get_data()){
    $rate = $fromform->rate;
    
    $records->courseid = $course->id;
    $records->userid = $USER->id;
    $records->pageid = $cm->id;
    $records->rate = $rate;
    $DB->insert_record('wcd_rate', $records);
    echo "<br>";
    redirect($baseurl);
    
}
else {
	$mform->set_data($toform);
	$mform->display();
}
}
else if($existingrate){
	echo "Votes:".'&nbsp'.$numberofvotes.".".'&nbsp'."Average".'&nbsp'."($meanofvotes)";
	echo "<br>";
	echo "<br>";
	echo '<div class="alert alert-info">You have already voted for this video. Thanks.If you want to do your feedback please click here</div>';
if(empty($existcom)){
		$mform = new comment($baseurl);
		if ($fromform = $mform->get_data()){
			$comment = $fromform->comment;
			
			$records->courseid = $course->id;
			$records->userid = $USER->id;
			$records->pageid = $cm->id;
			$records->comments = $comment;
			$DB->insert_record('comments', $records);
			echo "<br>";
			redirect($baseurl);
		
	
		}
		else {
			$mform->set_data($toform);
			$mform->display();
			
		}
	}
	

}
echo $OUTPUT->box($content, "generalbox center clearfix");
$strlastmodified = get_string("lastmodified");


echo "<div class=\"modified\">$strlastmodified: ".userdate($page->timemodified)."</div>";
echo $OUTPUT->footer();

?>
<link rel="stylesheet" type="text/css" href="css/style.css"/>