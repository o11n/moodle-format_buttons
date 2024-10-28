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

namespace format_buttons\output\courseformat;

require_once($CFG->dirroot.'/course/format/topics/classes/output/renderer.php');

use core_courseformat\output\local\content as content_base;
use renderer_base;
use moodle_url;

/**
 * Base class to render a course content.
 */
class content extends content_base {

    /**
     * @var bool Topic format has also add section after each topic.
     */
    protected $hasaddsection = true;

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *                      
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $widget)
    {
        global $PAGE;

        $format = $this->format;
        $course = $format->get_course();

        $data = parent::export_for_template($widget);
        
        // set the page title and whether the buttons are above or below
        $data->pageTitle = $widget->page_title();
        $data->above = true;
        if ($course->sectionposition == 1) {
            $data->above = false;
        }

        // buttons format - ini
        if (isset($_COOKIE['sectionvisible_' . $course->id])) {
            $sectionvisible = $_COOKIE['sectionvisible_' . $course->id];
        } else if ($course->marker > 0) {
            $sectionvisible = $course->marker;
        } else {
            $sectionvisible = 1;
        }

        // color profile for buttons
        $css = '';
        if ($colorcurrent = $format->get_color_config($course, 'colorcurrent')) {
            $css .=
            '#buttonsectioncontainer .buttonsection.current {
                background: ' . $colorcurrent . ';
            }
            ';
        }
        if ($colorvisible = $format->get_color_config($course, 'colorvisible')) {
            $css .=
            '#buttonsectioncontainer .buttonsection.sectionvisible {
                background: ' . $colorvisible . ';
            }
            ';
        }
        // set the style of the buttons
        $data->buttonstyle = $css;
        $data->shape = $course->buttonstyle;
        $data->buttonsections = array();

        // var_dump($data->sections[0]);
        $sections = $data->sections;
        // how many buttons do we need?
        $buttoncount = sizeof($sections) - 1;

        if ($buttoncount > 0) {
            $b = 1;
            foreach ($sections as $section) {
                // echo '<pre>'; var_dump($section); echo '</pre>';
                if ($section->num == 0) {
                    continue;
                }
                if ($section->num > sizeof($sections)) {
                    continue;
                }
                if ($course->hiddensections && !(int)$section->visible) {
                    continue;
                }

                // create a new button
                $div = new \stdClass;

                $text = format_string($course->{'divisortext' . $b});
                $text = str_replace('[br]', '<br>', $text);
                $div->text = $text;
                if ($course->inlinesections) {
                    $div->inline = ' inlinebuttonsections';
                }

                if ($course->sequential) {
                    $name = $section->num;
                } else {
                    if (isset($course->{'divisor' . $b}) &&
                    $course->{'divisor' . $b} == 1) {
                        $name = '&bull;&bull;&bull;';
                    } else {
                        $name = $b;
                    }
                }
                if ($course->sectiontype == 'alphabet' && is_numeric($name)) {
                    $name = $format->number_to_alphabet($name);
                }
                if ($course->sectiontype == 'roman' && is_numeric($name)) {
                    $name = $format->number_to_roman($name);
                }
                $class = 'buttonsection';
                if (!$section->hasavailability) {
                    $class .= ' sectionhidden';
                } else if (empty($section->visibility)) {
                    $class .= ' sectionhidden';
                    $onclick = false;
                }
                if ($course->marker == $section->num) {
                    $class .= ' current';
                }
                if ($sectionvisible == $section->num) {
                    $class .= ' sectionvisible';
                }
                if ($PAGE->user_is_editing()) {
                    $onclick = false;
                }
                $div->class = $class;
                $div->name = $name;
                $div->id = $section->num;
                $div->index = $b;
                $div->courseid = $course->id;
                // add this button to the page
                array_push($data->buttonsections, $div);
                $b++;
            }
        }
        // end button creation

        // section 0 and sections 1..n will be rendered
        $data->section0 = $data->sections[0];
        $data->numberedsections = array_slice($sections, 1);

        // begin footer buttons for adding/reducing number of sections (edit mode only)
        $strediting = get_string('editing', 'format_buttons');
        $straddsection = get_string('increasesections', 'moodle');
        $strremovesection = get_string('reducesections', 'moodle');
        $plusurl = $widget->pix_icon('t/switch_plus', $straddsection);
        $minusurl = $widget->pix_icon('t/switch_minus', $strremovesection);
        $increaseurl = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
            'increase' => true, 'sesskey' => sesskey()]);
        $decreaseurl = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
            'increase' => false, 'sesskey' => sesskey()]);

        if ($buttoncount > 0) {
            $data->decrease = true;
        }
        $data->increaseurl = $increaseurl;
        $data->decreaseurl = $decreaseurl;
        $data->plusurl = $plusurl;
        $data->minusurl = $minusurl;
        $data->straddsection = $straddsection;
        $data->strremovesection = $strremovesection;
        // end footer buttons

        if (!$PAGE->user_is_editing()) {
            $PAGE->requires->js_init_call('M.format_buttons.init', [$course->numsections, $sectionvisible, $course->id]);
        } else {
            $data->editing = true;
            $data->strediting = $strediting;
        }
        // Button format - end

        $PAGE->requires->js_call_amd('format_buttons/mutations', 'init');
        $PAGE->requires->js_call_amd('format_buttons/section', 'init');
        return $data;
    }
}