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
 * This page lets users to manage rules for a given course.
 *
 * @package    tool_monitor
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$cmid = optional_param('cmid', 0, PARAM_INT);
$ruleid = optional_param('ruleid', 0, PARAM_INT);
$subscriptionid = optional_param('subscriptionid', 0, PARAM_INT);

// Validate course id.
if (empty($courseid)) {
    require_login();
} else {
    // They might want to see rules for this course.
    $course = get_course($courseid);
    require_login($course);
    $coursecontext = context_course::instance($course->id);
    // Check for caps.
    require_capability('tool/monitor:subscribe', $coursecontext);
    $coursename = format_string($course->fullname, true, array('context' => $coursecontext));
}

// Always build the page in site context.
$context = context_system::instance();
$sitename = format_string($SITE->fullname, true, array('context' => $context));
$PAGE->set_context($context);

// Set up the page.
$a = new stdClass();
$a->coursename = $sitename;
$a->reportname = get_string('pluginname', 'tool_monitor');
$title = get_string('title', 'tool_monitor', $a);
$indexurl = new moodle_url("/admin/tool/monitor/index.php", array('courseid' => $courseid));

$PAGE->set_url($indexurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

// Create/delete subscription if needed.
if (!empty($action)) {
    require_sesskey();
    switch ($action) {
        case 'subscribe' :
            $rule = \tool_monitor\rule_manager::get_rule($ruleid);
            $rule->subscribe_user($courseid, $cmid);
            echo $OUTPUT->notification(get_string('subcreatesuccess', 'tool_monitor'), 'notifysuccess');
            break;
        case 'unsubscribe' :
            \tool_monitor\subscription_manager::delete_subscription($subscriptionid);
            echo $OUTPUT->notification(get_string('subdeletesuccess', 'tool_monitor'), 'notifysuccess');
            break;
        default:
    }
}

// Render the current subscriptions list.
$totalsubs = \tool_monitor\subscription_manager::count_user_subscriptions();
$renderer = $PAGE->get_renderer('tool_monitor', 'managesubs');
if (!empty($totalsubs)) {
    // Show the subscriptions section only if there are subscriptions.
    $subs = new \tool_monitor\output\managesubs\subs('toolmonitorsubs', $indexurl, $courseid);
    echo $OUTPUT->heading(get_string('currentsubscriptions', 'tool_monitor'));
    echo $renderer->render($subs);
}

// Render the potential rules list.
$totalrules = \tool_monitor\rule_manager::count_rules_by_courseid($courseid);
echo $OUTPUT->heading(get_string('rulescansubscribe', 'tool_monitor'));
$rules = new \tool_monitor\output\managesubs\rules('toolmonitorrules', $indexurl, $courseid);
echo $renderer->render($rules);
if (empty($totalrules)) {
    // No rules present. Show a link to manage rules page if permissions permit.
    echo html_writer::start_div();
    echo html_writer::tag('span', get_string('norules', 'tool_monitor'));
    if (has_capability('tool/monitor:managerules', $context)) {
        $manageurl = new moodle_url("/admin/tool/monitor/managerules.php", array('courseid' => $courseid));
        $a = html_writer::link($manageurl, get_string('managerules', 'tool_monitor'));
        $link = "&nbsp;";
        $link .= html_writer::tag('span', get_string('manageruleslink', 'tool_monitor', $a));
        echo $link;
    }
    echo html_writer::end_div();
} else if (has_capability('tool/monitor:managerules', $context)) {
    $manageurl = new moodle_url("/admin/tool/monitor/managerules.php", array('courseid' => $courseid));
    echo $renderer->render_rules_link($manageurl);
}
echo $OUTPUT->footer();
