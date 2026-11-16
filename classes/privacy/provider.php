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
 * Privacy provider for the "datafield_template" plugin.
 *
 * @package    datafield_template
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace datafield_template\privacy;

use core_privacy\local\metadata\null_provider;
use core_privacy\local\request\writer;
use mod_data\privacy\datafield_provider;

/**
 * A template field shows text, e.g. instructions, in the templates of a database activity.
 * It stores no personal data.
 *
 * @package    datafield_template
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements datafield_provider, null_provider {
    /**
     * Get the string that explains why this plugin stores no personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Export the content of this field in an entry of a database activity.
     *
     * @param \context_module $context
     * @param \stdClass $recordobj record from the {data_records} table
     * @param \stdClass $fieldobj record from the {data_fields} table
     * @param \stdClass $contentobj record from the {data_content} table
     * @param \stdClass $defaultvalue the value that the database activity would export
     */
    public static function export_data_content($context, $recordobj, $fieldobj, $contentobj, $defaultvalue) {
        writer::with_context($context)->export_data([$recordobj->id, $contentobj->id], $defaultvalue);
    }

    /**
     * Delete data that this field stores itself: there is none, as the content
     * is stored, and deleted, by the database activity.
     *
     * @param \context_module $context
     * @param \stdClass $recordobj record from the {data_records} table
     * @param \stdClass $fieldobj record from the {data_fields} table
     * @param \stdClass $contentobj record from the {data_content} table
     */
    public static function delete_data_content($context, $recordobj, $fieldobj, $contentobj) {
    }
}
