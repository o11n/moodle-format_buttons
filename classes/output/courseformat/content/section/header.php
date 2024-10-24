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
 * Contains the default content output class.
 *
 * @package    format_buttons
 * @author     Dave Scott
 * @copyright  2024 Dave <dave@blockarts.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_buttons\output\courseformat\content\section;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section\header as header_base;
use stdClass;
use moodle_page;
 
/**
 * Base class to render a course content.
 */
class header extends header_base {

    /** @var course_format the course format */
    protected $format;

    /** @var section_info the course section class */
    protected $section;

    public function export_for_template(\renderer_base $output): stdClass {

        $data = parent::export_for_template($output);

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!empty($section->visibility)) {
                $sectionstyle = ' hidden';
            }
            if ($format->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        if (!$data->editing) {
            // output the buttons first and then the title

            $o = '';
            $currenttext = '';
            $sectionstyle = '';
    
            if ($section->section != 0) {
                // Only in the non-general sections.
                if (!$section->visible) {
                    $sectionstyle = ' hidden';
                }
                if (course_get_format($course)->is_section_current($section)) {
                    $sectionstyle = ' current';
                }
            }
    
            $data->title = $output->section_title($section, $course);
        } else {
            $data->title = $output->section_title_without_link($section, $course);
        }

        // left side and right-side navigation superceeded

        // $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        // $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        // $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        // $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        // $o.= html_writer::start_tag('div', array('class' => 'content'));

        // keep these concepts but not necessarily here

        // // When not on a section page, we display the section titles except the general section if null
        // $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // // When on a section page, we only display the general section title, if title is not the default one
        // $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        // $classes = ' accesshide';
        // if ($hasnamenotsecpg || $hasnamesecpg) {
        //     $classes = '';
        // }
        // $sectionname = html_writer::tag('span', $this->section_title($section, $course));

        // // Button format - ini
        // if ($course->showdefaultsectionname) {
        //     $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);
        // }
        // // Button format - end

        // $o .= $this->section_availability($section);

        // $o .= html_writer::start_tag('div', array('class' => 'summary'));
        // if ($section->uservisible || $section->visible) {
        //     // Show summary if section is available or has availability restriction information.
        //     // Do not show summary if section is hidden but we still display it because of course setting
        //     // "Hidden sections are shown in collapsed form".
        //     $o .= $this->format_summary_text($section);
        // }
        // $o .= html_writer::end_tag('div');

        // return $o;

        // if ($section->section > 0 && $section->section <= $course->numsections) {
        //     /* If is not editing verify the rules to display the sections */
        //     if (!$PAGE->user_is_editing()) {
        //         if ($course->hiddensections && !(int)$section->visible) {
        //             ;
        //         } else if (!$section->available && !empty($section->availableinfo)) {
        //             $this->section_header($section, $course, false, 0);
        //         } else if (!$section->uservisible || !$section->visible) {
        //             $this->section_hidden($section, $course->id);
        //         }
        //     } else {
        //         $this->section_header($section, $course, false, 0);
        //         if ($section->uservisible) {
        //             $this->courserenderer->course_section_cm_list($course, $section, 0);
        //             $this->courserenderer->course_section_add_cm_control($course, $section, 0);
        //         }
        //         $this->section_footer();
        //     }
        // }

        return $data;
    }
}
