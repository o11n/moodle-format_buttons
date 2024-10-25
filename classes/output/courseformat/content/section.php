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
 * Contains the default section output class.
 *
 * @package    format_buttons
 * @author     Dave Scott
 * @copyright  2024 Dave <dave@blockarts.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_buttons\output\courseformat\content;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use stdClass;
use moodle_url;

/**
 * Base class to render a course content.
 * 
 * Notes: 
 * The section maintains the navigation to prev/next section.
 * The header maintains the header and buttons.
 */
class section extends section_base {

    /** @var course_format the course format */
    protected $format;

    public function export_for_template(\renderer_base $widget): stdClass {
        global $PAGE;

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $data = parent::export_for_template($widget);
        
        // echo "<pre>"; var_dump($data); echo "</pre>";

        if ($PAGE->user_is_editing()) {
            $data->editinline = $widget->section_title_without_link($section, $course);
        } else {
            $data->editinline = $widget->section_title($section, $course);
        }

        if (!$this->format->get_sectionnum()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format);
            $data->numsections = $addsection->export_for_template($widget);
            $data->insertafter = true;
        }

        $style = '';
        if ($section->section != 0) {
            if (!$section->visible) {
                $style = ' hidden';
            }
            if ($format->is_section_current($section)) {
                $style = ' current';
            }
        }
        $data->style = $style;
        $data->iscurrent = $format->is_section_current($section);
        
        $hide = true;

        $hasnamenotsecpg = ($section->section != 0 || !is_null($section->name));
        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($section->section == 0 && !is_null($section->name));

        if ($hasnamenotsecpg || $hasnamesecpg) {
            $hide = false;
        }
        $data->hide = $hide;

        $display = 'none';
        if (!$data->hide) {
            $display = 'block';
        }
        $data->display = $display;

        $data->sectionname = $format->get_section_name($section);
        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $data->sectionurl = $url . '#section-' . $section->section;
        return $data;
    }
}