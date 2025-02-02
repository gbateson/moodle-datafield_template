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
 * @package    data
 * @subpackage datafield_template
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

// get required files
require_once($CFG->dirroot.'/mod/data/field/admin/lib.php');

/**
 * Serve the files from the template file area
 */
function datafield_template_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    return datafield_admin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options, 'template');
}

/**
 * Get icon mapping for font-awesome.
 */
function datafield_template_get_fontawesome_icon_map() {
    // The hex code for "file-code" is "f1c9".
    return ['mod_data:field/template' => 'fa-solid fa-file-code'];
}
