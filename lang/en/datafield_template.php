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
 * Strings for component 'datafield_template', language 'en', branch 'master'
 *
 * @package    datafield
 * @subpackage template
 * @copyright  2011 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Template';

$string['displaycondtions'] = 'Conditions for displaying this field';
$string['islessthan'] = 'is less than';
$string['ismorethan'] = 'is more than';
$string['isnotempty'] = 'is not empty';
$string['isnotequalto'] = 'is not equal to';
$string['fixlangpack'] = '**The Template field is not yet properly installed**

Please append language strings for the Template field to Database language file:

* EDIT: {$a->langfile}
* ADD: $string[\'admin\'] = \'Template\';
* ADD: $string[\'nameadmin\'] = \'Template field\';

Then purge the Moodle caches:

* Administration -> Site administration -> Development -> Purge all caches

See {$a->readfile} for more details.';
