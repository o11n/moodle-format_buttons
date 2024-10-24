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
use completion_info;

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
        
        $data->pageTitle = $widget->page_title();
        $data->hasAbove = true;

        // Title with completion help icon.
        $completioninfo = new completion_info($course);

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
        $data->buttonstyle = $css;
        $data->shape = $course->buttonstyle;
        $data->buttonsections = array();

        // var_dump($data->sections[0]);
        $sections = $data->sections;
        $buttoncount = 0;
        // how many buttons do we need?
        foreach ($sections as $section) {
//            if ($section->uservisible) {
                $buttoncount++;
//            }
        }

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
                array_push($data->buttonsections, $div);
                $b++;
            }
        }
        // end button creation

        $data->section0 = $data->sections[0];
        $data->numberedsections = array_slice($sections, 1);

        if (!$PAGE->user_is_editing()) {
            $PAGE->requires->js_init_call('M.format_buttons.init', [$course->numsections, $sectionvisible, $course->id]);
        }
        // Button format - end

        $PAGE->requires->js_call_amd('format_buttons/mutations', 'init');
        $PAGE->requires->js_call_amd('format_buttons/section', 'init');
        return $data;
    }

    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);

        // buttons format - ini
        if (isset($_COOKIE['sectionvisible_' . $course->id])) {
            $sectionvisible = $_COOKIE['sectionvisible_' . $course->id];
        } else if ($course->marker > 0) {
            $sectionvisible = $course->marker;
        } else {
            $sectionvisible = 1;
        }
        $htmlsection = false;
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            $htmlsection[$section] = '';
            if ($section == 0) {
                $section0 = $thissection;
                continue;
            }
            if ($section > $course->numsections) {
                continue;
            }
            /* If is not editing verify the rules to display the sections */
            if (!$PAGE->user_is_editing()) {
                if ($course->hiddensections && !(int)$thissection->visible) {
                    continue;
                }
                if (!$thissection->available && !empty($thissection->availableinfo)) {
                    $htmlsection[$section] .= $this->section_header($thissection, $course, false, 0);
                    continue;
                }
                if (!$thissection->uservisible || !$thissection->visible) {
                    $htmlsection[$section] .= $this->section_hidden($section, $course->id);
                    continue;
                }
            }
            $htmlsection[$section] .= $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible) {
                $htmlsection[$section] .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                $htmlsection[$section] .= $this->courserenderer->course_section_add_cm_control($course, $section, 0);
            }
            $htmlsection[$section] .= $this->section_footer();
        }
        if ($section0->summary || !empty($modinfo->sections[0]) || $PAGE->user_is_editing()) {
            $htmlsection0 = $this->section_header($section0, $course, false, 0);
            $htmlsection0 .= $this->courserenderer->course_section_cm_list($course, $section0, 0);
            $htmlsection0 .= $this->courserenderer->course_section_add_cm_control($course, 0, 0);
            $htmlsection0 .= $this->section_footer();
        }
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');
        echo $this->course_activity_clipboard($course, 0);
        echo $this->start_section_list();
        if ($course->sectionposition == 0 and isset($htmlsection0)) {
            echo html_writer::tag('span', $htmlsection0, ['class' => 'above']);
        }
        echo $this->get_button_section($course, $sectionvisible);
        foreach ($htmlsection as $current) {
            echo $current;
        }
        if ($course->sectionposition == 1 and isset($htmlsection0)) {
            echo html_writer::tag('span', $htmlsection0, ['class' => 'below']);
        }
        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }
            echo $this->end_section_list();
            echo html_writer::start_tag('div', ['id' => 'changenumsections', 'class' => 'mdl-right']);
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
                'increase' => true, 'sesskey' => sesskey()]);
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon.get_accesshide($straddsection), ['class' => 'increase-sections']);
            if ($course->numsections > 0) {
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
                    'increase' => false, 'sesskey' => sesskey()]);
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link(
                    $url,
                    $icon.get_accesshide($strremovesection),
                    ['class' => 'reduce-sections']
                );
            }
            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
        if (!$PAGE->user_is_editing()) {
            $PAGE->requires->js_init_call('M.format_buttons.init', [$course->numsections, $sectionvisible, $course->id]);
        }
        // Button format - end
    }
}