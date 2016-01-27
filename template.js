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
 * mod/data/field/template/template.js
 *
 * @package    mod_data
 * @subpackage datafield_template
 * @copyright  2015 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.datafield_template = {

    /**
     * disable_condition_value
     *
     * @param object
     * @param string id1 : the id of the condition element
     * @param string id2 : the id of the element to be disabled
     * @return void, but may add event handler(s) to the id1 element
     */
    disable_condition : function(Y, sourceid, targetid, values) {
        var obj = document.getElementById(sourceid);
        if (obj) {
            var fnc = function(evt) {
                var i = this.options[this.selectedIndex].value;
                var d = (values.indexOf(i) >= 0);
                document.getElementById(targetid).disabled = d;
            };
            var eventtype = "change";
            if (obj.addEventListener) {
                obj.addEventListener(eventtype, fnc, false);
            } else if (obj.attachEvent) { // IE
                obj.attachEvent("on" + eventtype, fnc);
            }
            // trigger event
            if (document.createEvent) {
                var evt = document.createEvent("HTMLEvents");
                evt.initEvent(eventtype, true, true);
                obj.dispatchEvent(evt);
            } else if (document.createEventObject) { // IE
                var evt = document.createEventObject();
                obj.fireEvent("on" + eventtype, evt);
            }
        }
    }
};
