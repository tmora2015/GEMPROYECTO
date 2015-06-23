
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
 //a

/**
 * 
 * @package    mod
 * @subpackage page
 * @copyright  2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->libdir/formslib.php");

class rating extends moodleform {
	function definition(){
		global $CFG, $DB, $USER, $PAGE;
		
		$array = array_combine(range(1,7,1),range(1,7,1));
		
		$mform = $this->_form;
		$mform->addElement('select', 'rate', 'Rate the video', $array); //crea el select 
		$this->add_action_buttons($cancel = false, $submit = 'Vote'); //boton para accionar
	}
}
//formulario para el voto de los videos

class comment extends moodleform{
	function definition() {
		global $CFG, $DB, $USER, $PAGE;
		
		$mform = $this->_form;
		$mform->addElement('textarea','comment', 'Make a comment for the video.','wrap="virtual" rows="3" cols="70"');
		$mform->addRule('comment','You need to insert some text','required');
		$mform->setType('comment', PARAM_ALPHANUM);
		$this->add_action_buttons($cancel = false, $submit = 'Send'); //action buttons
		
	}
}
//formulario para el comentario