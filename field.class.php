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
 * Class to represent a "datafield_admin" field
 *
 * this field acts as an extra API layer to restrict view and
 * edit access to any other type of field in a database activity
 *
 * @package    data
 * @subpackage datafield_template
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

// get required files
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/mod/data/field/admin/field.class.php');

class data_field_template extends data_field_base {

    var $type = 'template';

    const STATUS_OPEN = 0;
    const STATUS_KEEP = 1;
    const STATUS_MORE = 2;
    const STATUS_DROP = 3;

    const OP_EMPTY         = 'EMPTY';
    const OP_NOT_EMPTY     = 'NOT_EMPTY';

    const OP_EQUAL         = 'EQUAL';
    const OP_NOT_EQUAL     = 'NOT_EQUAL';
    const OP_MORE_THAN     = 'MORE_THAN';
    const OP_LESS_THAN     = 'LESS_THAN';

    const OP_NUM_EQUAL     = 'NUM_EQUAL';
    const OP_NUM_NOT_EQUAL = 'NUM_NOT_EQUAL';
    const OP_NUM_MORE_THAN = 'NUM_MORE_THAN';
    const OP_NUM_LESS_THAN = 'NUM_LESS_THAN';

    const OP_CONTAIN       = 'CONTAIN';
    const OP_NOT_CONTAIN   = 'NOT_CONTAIN';
    const OP_START_WITH    = 'START_WITH';
    const OP_END_WITH      = 'END_WITH';

    /**
     * the names of the content and format param fields
     */
    public $contentparam = 'param1';
    public $formatparam  = 'param2';

    /**
     * the template currently being viewed
     * one of "addtemplate", "singletemplate", "listtemplate", "rsstemplate"
     */
    protected $template = '';

    /**
     * the id of the "data_record" currently being viewed
     */
    protected $recordid = 0;

    /**
     * "data_records" ids for records which have
     * at least one "data_content" record for this field
     * (only necessary by export_text_value)
     */
    protected $recordids = null;

    /**
     * displays the settings for this action field on the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        data_field_admin::check_lang_strings($this);
        parent::display_edit_field();
    }

    /*
     * add a new admin field from the "Fields" page
     */
    function insert_field() {
        data_field_admin::get_editor_content($this);
        parent::insert_field();
    }

    /*
     * update settings for this admin field sent from the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function update_field() {
        data_field_admin::get_editor_content($this);
        parent::update_field();
    }

    /**
     * delete content associated with a template field
     * when the field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        data_field_admin::delete_content_files($this);
        return parent::delete_content($recordid);
    }

    function display_add_field($recordid = 0, $formdata = NULL) {
        return '';
    }

    function display_search_field($value = '') {
        return '';
    }

    function parse_search_field() {
        return '';
    }

    function generate_sql($tablealias, $value) {
        return array('', array());
    }

    function print_after_form() {
    }

    function update_content($recordid, $value, $name='') {
        return false;
    }

    /**
     * Display the content of the field in browse mode
     *
     * @param integer $recordid
     * @param string  $template
     * @return bool|string
     */
    function display_browse_field($recordid, $template) {
        global $DB, $USER;

        $param = $this->contentparam;
        if (! $content = $this->field->$param) {
            return '';
        }

        $param = $this->formatparam;
        if (! $format = $this->field->$param) {
            $format = FORMAT_MOODLE;
        }

        // these values may be needed by the replace_fieldnames() method
        $userid = $DB->get_field('data_records', 'userid', array('id' => $recordid));
        if ($userid==$USER->id) {
            $user = $USER;
        } else {
            $user = $DB->get_record('user', array('id' => $userid));
        }

        // reduce IF-ELSE-ENDIF blocks
        $content = self::replace_if_blocks($this->context, $this->cm,
                                           $this->data, $this->field,
                                           $recordid, $template,
                                           $user, $content);

        // replace all fieldnames
        $content = self::replace_fieldnames($this->context, $this->cm,
                                            $this->data, $this->field,
                                            $recordid, $template,
                                            $user, $content);

        return data_field_admin::format_browse_field($this, $content, $format);
    }

    /**
     * Whether this module support files
     *
     * @param string $relativepath
     * @return bool
     */
    function file_ok($relativepath) {
        return true;
    }

    /**
     * text export return that formated field value
     * that would be displayed by the "singletemplate"
     */
    function export_text_value($record) {
        global $DB;
        if (empty($record) || empty($record->id)) {
            if ($this->recordids===null) {
                $sql = 'SELECT dr.id, dr.dataid, COUNT(*) AS contentcount ';
                $sql .= 'FROM {data_records} dr RIGHT JOIN {data_content} dc ON dr.id = dc.recordid ';
                $sql .= 'WHERE dr.dataid = :dataid AND dc.fieldid = :fieldid ';
                $sql .= 'GROUP BY dc.recordid ';
                $sql .= 'HAVING contentcount > 0';
                $params = array('dataid' => $this->data->id,
                                'fieldid' => $this->field->id);
                $this->recordids = $DB->get_records_sql_menu($sql, $params);
                $this->recordids = array_keys($this->recordids);
            }
            $recordid = array_shift($this->recordids);
        } else {
            $recordid = $record->id;
        }
        $text = $this->display_browse_field($recordid, 'singletemplate');
        $text = preg_replace('/(<\/?(br|div|p)[^>]*>)\s+/', '$1', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    ///////////////////////////////////////////
    // static methods for parsing a template
    ///////////////////////////////////////////

    /**
     * reduce all IF-THEN-ENDIF blocks in $content
     *
     * @param  string $content
     * @return string
     */
    static public function replace_if_blocks($context, $cm, $data, $field, $recordid, $template, $user, $content) {

        // regular expression to detect IF-ELSE-ENDIF token
        // preceding spaces/tabs and following newlines
        // are also grabbed, and will later be removed
        $search = '(IF|ELIF|ELSE|ENDIF)';
        $search = '/[ \t]*\[\['.$search.'([^\]]*)\]\][\n\r]*/s';
        // $1 : token head
        // $2 : token tail ($fieldname and optional $value)

        // current nesting level of IF-ELSE-ENDIF
        $level = 0;

        // keep track of status at each level
        $status = array($level => self::STATUS_OPEN);

        // substrings of $content to drop
        $drops = array();

        // start of substring to be dropped
        $drop = 0;

        if (preg_match_all($search, $content, $matches, PREG_OFFSET_CAPTURE)) {

            $i_max = count($matches[0]);
            for ($i=0; $i<$i_max; $i++) {

                // cache status of current level
                $oldstatus = $status[$level];

                // get current IF-THEN-ENDIF token
                list($token, $start) = $matches[0][$i];
                $length = strlen($token);

                // drop previous block, if necessary
                if ($drop && $drop < $start) {
                    $drops[] = array($drop, $start);
                }

                // drop this IF-ELSE-ENDIF token
                $drops[] = array($start, $start + $length);

                $head = $matches[1][$i][0];
                $tail = $matches[2][$i][0];
                $head = strtoupper($head);
                $tail = trim($tail);
                switch ($head) {

                    case 'IF':
                        $level++;
                        switch ($oldstatus) {
                            case self::STATUS_OPEN:
                            case self::STATUS_KEEP:
                                if (self::check_condition($context, $cm, $data, $field, $recordid, $template, $tail, $user)) {
                                    $status[$level] = self::STATUS_KEEP;
                                } else {
                                    $status[$level] = self::STATUS_MORE;
                                }
                                break;
                            case self::STATUS_MORE:
                            case self::STATUS_DROP:
                                $status[$level] = self::STATUS_DROP;
                                break;
                        }
                        break;

                    case 'ELIF':
                        switch ($status[$level]) {
                            case self::STATUS_KEEP:
                                $status[$level] = self::STATUS_DROP;
                                break;
                            case self::STATUS_MORE:
                                if (self::check_condition($context, $cm, $data, $field, $recordid, $template, $tail, $user)) {
                                    $status[$level] = self::STATUS_KEEP;
                                }
                                break;
                        }
                        break;

                    case 'ELSE':
                        switch ($status[$level]) {
                            case self::STATUS_KEEP:
                                $status[$level] = self::STATUS_DROP;
                                break;
                            case self::STATUS_MORE:
                                $status[$level] = self::STATUS_KEEP;
                                break;
                        }
                        break;

                    case 'ENDIF':
                        unset($status[$level]);
                        $level--;
                        break;
                }

                switch ($status[$level]) {
                    case self::STATUS_OPEN:
                    case self::STATUS_KEEP:
                        $drop = 0;
                        break;
                    case self::STATUS_MORE:
                    case self::STATUS_DROP:
                        $drop = ($start + $length);
                        break;
                }
            }
        }

        if ($drop) {
            $drops[] = array($drop, strlen($content));
        }

        // remove all unwanted substrings
        $i_max = (count($drops) - 1);
        for ($i=$i_max; $i>=0; $i--) {
            list($start, $end) = $drops[$i];
            $head = substr($content, 0, $start);
            $tail = substr($content, $end);
            $content = $head.$tail;
        }

        return $content;
    }

    /**
     * determine whether an if-condition is satisfied or not
     *
     * @param string $tail
     * @return bool
     */
    static public function check_condition($context, $cm, $data, $field, $recordid, $template, $tail, $user) {
        global $DB, $USER;

        // expand $tail to get $fieldname, $operator and $value
        $tail = explode(' ', $tail, 3);
        switch (count($tail)) {
            case 0: return false; // shouldn't happen !!
            case 1: list($fieldname) = $tail;
                    $operator = 'NOT_EMPTY';
                    $value = '';
                    break;
            case 2: list($fieldname, $operator) = $tail;
                    $value = '';
                    break;
            case 3: list($fieldname, $operator, $value) = $tail;
                    break;
        }

        if ($fieldname==$field->name) {
            return false; // prevent infinite loops
        }

        if ($targetfield = data_get_field_from_name($fieldname, $data)) {
            if (method_exists($field, 'get_condition_value')) {
                // special case to allow access to value of hidden "admin" fields
                $content = $targetfield->get_condition_value($recordid, $template);
            } else {
                $content = $targetfield->display_browse_field($recordid, $template);
            }
        } else {
            $content = self::replace_fieldname($context, $cm,
                                               $data, $field,
                                               $recordid, $template,
                                               $user, $fieldname);
        }
        list($operator, $content, $value) = self::clean_condition($operator, $content, $value);

        switch ($operator) {
            case self::OP_EMPTY:         return empty($content);
            case self::OP_NOT_EMPTY:     return (! empty($content));
            case self::OP_EQUAL:         return ($content == $value);
            case self::OP_NOT_EQUAL:     return ($content != $value);
            case self::OP_MORE_THAN:     return ($content > $value);
            case self::OP_LESS_THAN:     return ($content < $value);
            case self::OP_CONTAIN:       return strpos($content, $value)!==false;
            case self::OP_NOT_CONTAIN:   return strpos($content, $value)===false;
            case self::OP_START_WITH:    return ($value == substr($content, 0, strlen($value)));
            case self::OP_END_WITH:      return ($value == substr($content, -strlen($value)));
            case self::OP_NUM_EQUAL:     return ($content == $value);
            case self::OP_NUM_NOT_EQUAL: return ($content != $value);
            case self::OP_NUM_MORE_THAN: return ($content > $value);
            case self::OP_NUM_LESS_THAN: return ($content < $value);
            default:                     return false;
        }
    }

    /**
     * clean_condition
     *
     * @param  string $operator
     * @param  string $content
     * @param  string $value
     * @return array($operator, $content, $value)
     */
    static public function clean_condition($operator, $content, $value) {

        // remove enclosing quotes, if any from $value
        $value = trim($value);
        if ((substr($value, 0, 1)=='"' && substr($value, -1)=='"') ||
            (substr($value, 0, 1)=="'" && substr($value, -1)=="'")) {
            $value = substr($value, 1, -1);
        }

        // convert operator aliasses
        $operator = strtoupper($operator);
        switch ($operator) {

            case 'IS_EMPTY':
                $operator = self::OP_EMPTY;
                break;

            case 'IS_NOT_EMPTY':
                $operator = self::OP_NOT_EMPTY;
                break;

            case '=':
            case '==':
            case '===':
            case 'EQ':
            case 'IS_EQUAL':
            case 'EQUAL_TO':
            case 'IS_EQUAL_TO':
                $operator = self::OP_EQUAL;
                break;

            case '<>':
            case '!=':
            case '!==':
            case 'NE':
            case 'NEQ':
            case 'IS_NOT_EQUAL':
            case 'NOT_EQUAL_TO':
            case 'IS_NOT_EQUAL_TO':
                $operator = self::OP_NOT_EQUAL;
                break;

            case '>':
            case 'MT':
            case 'IS_MORE_THAN':
            case 'GT':
            case 'GREATER_THAN':
            case 'IS_GREATER_THAN':
                $operator = self::OP_MORE_THAN;
                break;

            case '<':
            case 'LT':
            case 'IS_LESS_THAN':
                $operator = self::OP_LESS_THAN;
                break;

            case 'CONTAINS':
                $operator = self::OP_CONTAIN;
                break;

            case 'NOT_CONTAINS':
            case 'DOES_NOT_CONTAIN':
                $operator = self::OP_NOT_CONTAIN;
                break;

            case 'STARTS_WITH':
                $operator = self::OP_START_WITH;
                break;

            case 'ENDS_WITH':
                $operator = self::OP_END_WITH;
                break;

            case self::OP_NUM_EQUAL:
            case self::OP_NUM_NOT_EQUAL:
            case self::OP_NUM_LESS_THAN:
            case self::OP_NUM_MORE_THAN:

                // get locale-specific characters for
                // decimal point and thousands separator
                $info = localeconv();
                $point = $info['decimal_point'];
                $separator = $info['thousands_sep'];
                if ($separator=='' || $point != ',') {
                    $separator = ',';
                }

                // build regular expression to extract numbers
                $search = '0-9';
                if ($point) {
                    $search .= $point;
                }
                if ($separator) {
                    $search .= $separator;
                }
                $search = '/^.*?(['.$search.']+).*?$/';

                // extract numbers from $value and $content
                $value = preg_replace($search, '$1', $value);
                $content = preg_replace($search, '$1', $content);
                if ($separator) {
                    $value = str_replace($separator, '', $value);
                    $content = str_replace($separator, '', $content);
                }
                $value = floatval($value);
                $content = floatval($content);
                break;
        }

        return array($operator, $content, $value);
    }

    /**
     * replace all fieldnames in [[square brackets]]
     * in content from the current data $recordid
     */
    static public function replace_fieldnames($context, $cm, $data, $field, $recordid, $template, $user, $content) {
        $search = 'TITLECASE|CAMELCASE|PROPERCASE|'.
                  'UPPERCASE|LOWERCASE|'.
                  'TRIM|LTRIM|RTRIM|'.
                  'UL|BULLETLIST|'.
                  'OL|NUMBERLIST|'.
                  'COMMALIST|INDENTLIST|'.
                  'FORMATTEXT|FORMATHTML';
        $search = '/\[\[('.$search.')? *([^\]]+)]\][\r\n]*/';
        if (preg_match_all($search, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $i_max = count($matches[0]) - 1;
            for ($i=$i_max; $i>=0; $i--) {
                $match = $matches[0][$i][0];
                $start = $matches[0][$i][1];
                $case  = $matches[1][$i][0];
                $fieldname = $matches[2][$i][0];
                $replace = self::replace_fieldname($context, $cm, $data, $field, $recordid, $template, $user, $fieldname);
                switch ($case) {
                    case 'CAMELCASE' : // same as TITLECASE
                    case 'PROPERCASE': // same as TITLECASE
                    case 'TITLECASE' : $replace = self::textlib('strtotitle', $replace); break;
                    case 'UPPERCASE' : $replace = self::textlib('strtoupper', $replace); break;
                    case 'LOWERCASE' : $replace = self::textlib('strtolower', $replace); break;
                    case 'TRIM'      : $replace = trim($replace); break;
                    case 'LTRIM'     : $replace = ltrim($replace); break;
                    case 'RTRIM'     : $replace = rtrim($replace); break;
                    case 'BULLETLIST': // same as UL (unordered list)
                    case 'UL'        : $replace = self::text2list($replace, 'ul'); break;
                    case 'NUMBERLIST': // same as OL (ordered list)
                    case 'OL'        : $replace = self::text2list($replace, 'ol'); break;
                    case 'COMMALIST' : $replace = self::text2list($replace, ', '); break;
                    case 'INDENTLIST': $replace = self::text2list($replace, "\n\t", "\n\t"); break;
                    case 'FORMATTEXT': $replace = self::format_field($cm, $data, $fieldname, $replace); break;
                    case 'FORMATHTML': $replace = self::format_field($cm, $data, $fieldname, $replace, 'b'); break;
                }
                $content = substr_replace($content, $replace, $start, strlen($match));
            }
        }
        return $content;
    }

    /**
     * replace a single fieldname with content
     * from the current data record (id=$recordid)
     */
    static public function replace_fieldname($context, $cm, $data, $field, $recordid, $template, $user, $fieldname) {

        // course id/url
        if (substr($fieldname, 0, 6)=='course') {
            switch ($fieldname) {
                case 'courseid'  : return $data->course;
                case 'courseurl' : return new moodle_url('/course/view.php', array('id' => $data->course));
            }
        }

        // data activity id/name/intro/url
        if (substr($fieldname, 0, 4)=='data') {
            switch ($fieldname) {
                case 'dataid'    : return $data->id;
                case 'dataname'  : return $data->name;
                case 'dataintro' : return format_text($data->intro, $data->introformat);
                case 'dataurl'   : return new moodle_url('/mod/data/view.php', array('d' => $data->id));
            }
        }

        // data record id/url
        if (substr($fieldname, 0, 6)=='record') {
            switch ($fieldname) {
                case 'recordid'  : return $recordid;
                case 'recordurl' : return new moodle_url('/mod/data/view.php', array('d' => $data->id, 'rid' => $recordid));
                case 'recordrating' : return self::get_recordrating($data, $recordid); // check permissions ?
            }
        }

        // capabilities
        if (substr($fieldname, 0, 4)=='can_') {
            $capability = substr($fieldname, 4);
            switch ($capability) {
                case 'addinstance':
                case 'viewentry':
                case 'writeentry':
                case 'comment':
                case 'rate':
                case 'viewallratings':
                case 'viewanyrating':
                case 'viewrating':
                case 'approve':
                case 'manageentries':
                case 'managecomments':
                case 'managetemplates':
                case 'viewalluserpresets':
                case 'manageuserpresets':
                case 'exportentry':
                case 'exportownentry':
                case 'exportallentries':
                case 'exportuserinfo':
                    return has_capability('mod/data:'.$capability, $context);
            }
        }

        // course id/url
        if (substr($fieldname, 0, 7)=='current') {
            switch ($fieldname) {
                case 'currentlang':
                case 'currentlanguage': return current_language();
            }
        }

        // user fields
        if (isset($user->$fieldname)) {

            // these fields are accessible
            $names = array('firstname', 'lastname',
                           'email', 'phone1', 'phone2',
                           'icq', 'skype', 'yahoo', 'aim', 'msn',
                           'institution', 'department',
                           'address', 'city', 'country',
                           'picture', 'imagealt', 'url',
                           'description', 'descriptionformat',
                           'lastnamephonetic', 'firstnamephonetic',
                           'middlename', 'alternatename');

            // the following user fields are not accessible
            // 'id', 'auth', 'confirmed', 'policyagreed',
            // 'deleted', 'suspended', 'mnethostid',
            // 'username', 'password', 'idnumber',
            // 'emailstop', 'lang', 'theme', 'timezone',
            // 'firstaccess', 'lastaccess', 'lastlogin',
            // 'currentlogin', 'lastip', 'secret',
            // 'mailformat', 'maildigest', 'maildisplay',
            // 'autosubscribe', 'trackforums', 'timecreated',
            // 'timemodified', 'trustbitmask', 'calendartype',

            if (in_array($fieldname, $names)) {
                return $user->$fieldname;
            } else {
                return str_repeat('*', 12);
            }
        }

        if ($fieldname==$field->name) {
            return ''; // oops, recursive loop
        }

        if (! $targetfield = data_get_field_from_name($fieldname, $data, $cm)) {
            return ''; // shouldn't happen !!
        }

        return $targetfield->display_browse_field($recordid, $template);
    }

    /**
     * shortcut for making an HTML list using
     * the display output for a radio/checkbox field
     *
     * @param array $items
     * @param string $type ul or ol
     */
    static public function text2list($text, $type='ul', $before='', $after='') {
        $list = preg_split('/(\r|\n|(<br[^>]*>))+/', $text);
        $list = array_map('trim', $list);
        $list = array_filter($list);
        if (empty($list)) {
            return '';
        }
        if ($type=='ul' || $type=='ol') {
            $list = html_writer::alist($list, null, $type);
            $list = str_replace("\n", '', $list);
        } else {
            $list = implode($type, $list);
        }
        return $before.$list.$after;
    }

    /**
     * format a fieldname and value
     */
    static public function format_field($cm, $data, $fieldname, $value, $tag='') {
        global $DB;

        // remove trailing currency info e.g. (¥10,000 yen)
        $search = '/\([^)]*\)$/';
        if (preg_match($search, $value, $currency)) {
            $currency = $currency[0];
            $strlen = self::textlib('strlen', $currency);
            $value = self::textlib('substr', $value, 0, -$strlen);
        } else {
            $currency = '';
        }

        // search $search and $replace string for multilingual strings
        $search = self::bilingual_string();
        if (self::is_low_ascii_language()) {
            $replace = '$2'; // low-ascii language e.g. English
        } else {
            $replace = '$1'; // high-ascii/multibyte language
        }

        // set default description text
        $text = $fieldname;

        $params = array('name' => $fieldname,
                        'dataid' => $data->id);
        if ($field = $DB->get_record('data_fields', $params)) {
            if ($field->description) {
                $text = $field->description;
                $text = preg_replace($search, $replace, $text);
            }
            if ($field->type=='menu' || $field->type=='radiobutton') {
                $value = preg_replace($search, $replace, $value);
            } if ($field->type=='checkbox') {
                $value = preg_split('/(\r|\n|(<br[^>]*>))+/', $value);
                $value = array_map('trim', $value);
                $value = array_filter($value);
                foreach (array_keys($value) as $v) {
                    $value[$v] = preg_replace($search, $replace, $value[$v]);
                }
                $value = implode(html_writer::empty_tag('br'), $value);
            }
        }

        if ($tag) {
            $text = html_writer::tag($tag, $text);
        }

        return "$text: $value$currency";
    }

    /**
     * Return a regexp sub-string to match a sequence of low ascii chars.
     */
    static public function low_ascii_substring() {
        // 0000 - 001F Control characters e.g. tab
        // 0020 - 007F ASCII basic e.g. abc
        // 0080 - 009F Control characters
        // 00A0 - 00FF ASCII extended (1) e.g. àáâãäå
        // 0100 - 017F ASCII extended (2) e.g. āăą
        return '\\x{0000}-\\x{007F}';
    }

    /**
     * is_low_ascii_language
     *
     * @param string $lang (optional, defaults to name current language)
     * @return boolean TRUE if $lang appears to be ascii language e.g. English; otherwise, FALSE
     */
    static public function is_low_ascii_language($lang='') {
        if ($lang=='') {
            $lang = get_string('thislanguage', 'langconfig');
        }
        $ascii = self::low_ascii_substring();
        return preg_match('/^['.$ascii.']+$/u', $lang);
    }

    /**
     * Return a regexp string to match string made up of
     * non-ascii chars at the start and ascii chars at the end.
     */
    static public function bilingual_string() {
        $ascii = self::low_ascii_substring();
        return '/^(.*[^'.$ascii.']) *(['.$ascii.']+?)$/u';
    }

    /**
     * get_recordrating
     *
     * @use $CFG
     * @use $DB
     * @use $PAGE
     * @use $USER
     * @param object $data
     * @param integer $recordid
     * @return string
     */
    static public function get_recordrating($data, $recordid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        require_once($CFG->dirroot.'/rating/lib.php');

        if ($data->assessed==RATING_AGGREGATE_NONE) {
            return '';
        }

        // get names fields to select (remove "u.id")
        $select = user_picture::fields('u');
        $select = str_replace('u.id,', '', $select);

        $select = "dr.id, dr.approved, dr.timecreated, dr.timemodified, dr.userid, $select";
        $from   = '{data_records} dr LEFT JOIN {user} u ON dr.userid = u.id';
        $where  = 'dr.id = ? AND dr.dataid = ?';
        $params = array($recordid, $data->id);
        if ($records = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params)) {

            if (empty($data->coursemodule)) {
                $data->coursemodule = get_coursemodule_from_instance('data', $data->id);
            }
            if (empty($data->context)) {
                $data->context = context_module::instance($data->coursemodule->id);
            }

            $records = (object)array(
                'context'    => $data->context,
                'component'  => 'mod_data',
                'ratingarea' => 'entry',
                'items'      => $records,
                'aggregate'  => $data->assessed,
                'scaleid'    => $data->scale,
                'userid'     => $USER->id,
                'returnurl'  => $PAGE->url,
                'assesstimestart' => $data->assesstimestart,
                'assesstimefinish' => $data->assesstimefinish,
            );

            // convert $records to ratings
            $rm = new rating_manager();
            $records = $rm->get_ratings($records);

            $recordrating = array();
            foreach ($records as $record) {
                if (empty($record->rating)) {
                    continue;
                }
                if ($record->rating->user_can_view_aggregate()) {
                    $recordrating[] = $record->rating->get_aggregate_string();
                }
            }
            $recordrating = array_filter($recordrating);
            $recordrating = implode(', ', $recordrating);
        } else {
            $recordrating = '';
        }

        return $recordrating;
    }

    /**
     * textlib
     *
     * a wrapper method to offer consistent API for textlib class
     * in Moodle 2.0 - 2.1, $textlib is first initiated, then called
     * in Moodle 2.2 - 2.5, we use only static methods of the "textlib" class
     * in Moodle >= 2.6, we use only static methods of the "core_text" class
     *
     * @param string $method
     * @param mixed any extra params that are required by the textlib $method
     * @return result from the textlib $method
     * @todo Finish documenting this function
     */
    static public function textlib() {
        if (class_exists('core_text')) {
            // Moodle >= 2.6
            $textlib = 'core_text';
        } else if (method_exists('textlib', 'textlib')) {
            // Moodle 2.0 - 2.1
            $textlib = textlib_get_instance();
        } else {
            // Moodle 2.3 - 2.5
            $textlib = 'textlib';
        }
        $args = func_get_args();
        $method = array_shift($args);
        $callback = array($textlib, $method);
        return call_user_func_array($callback, $args);
    }
}
