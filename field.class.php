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
     * the id of the data_record currently being viewed
     */
    var $recordid = 0;

    /**
     * the template currently being viewed
     * one of "addtemplate", "singletemplate", "listtemplate", "rsstemplate"
     */
    var $template = '';

    /*
     * displays the settings for this admin field on the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        global $CFG, $OUTPUT;
        if (empty($this->field->id)) {
            $strman = get_string_manager();
            if (! $strman->string_exists($this->type, 'data')) {
                $msg = (object)array(
                    'langfile' => $CFG->dirroot.'/mod/data/lang/en/data.php',
                    'readfile' => $CFG->dirroot.'/mod/data/field/admin/README.txt',
                );
                $msg = get_string('fixlangpack', 'datafield_'.$this->type, $msg);
                $msg = format_text($msg, FORMAT_MARKDOWN);
                $msg = html_writer::tag('div', $msg, array('class' => 'alert', 'style' => 'width: 100%; max-width: 640px;'));
                echo $msg;
            }
        }
        parent::display_edit_field();
    }

    /*
     * add a new admin field from the "Fields" page
     */
    function insert_field() {
        $this->get_editor_content();
        parent::insert_field();
    }

    /*
     * update settings for this admin field sent from the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function update_field() {
        $this->get_editor_content();
        parent::update_field();
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

        global $DB;

        if (! $itemid = $this->field->id) {
            return '';
        }
        if (! $content = $this->field->param1) {
            return '';
        }
        if (! $format = $this->field->param2) {
            $format = FORMAT_MOODLE;
        }

        $options = $this->get_fileoptions();
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $this->context->id, 'mod_data', 'content', $itemid, $options);

        // these values may be needed by the replace_fieldname() method
        $this->userid = $DB->get_field('data_records', 'userid', array('id' => $recordid));
        $this->user = $DB->get_record('user', array('id' => $this->userid));
        $this->recordid = $recordid;
        $this->template = $template;

        // reduce IF-ELSE-ENDIF blocks
        $content = $this->replace_if_blocks($content);

        // replace all fieldnames
        $search = '/\[\[([^\]]+)]\][\r\n]*/';
        $callback = array($this, 'replace_fieldname');
        $content = preg_replace_callback($search, $callback, $content);

        $options = array('noclean' => true, 'filter' => false, 'para' => false);
        $content = format_text($content, $format, $options);

        return $content;
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

    ///////////////////////////////////////////
    // custom methods for parsing a template
    ///////////////////////////////////////////

    /**
     * callback function to replace [[square brackets]]
     * with content for the current data $recordid
     */
    protected function replace_fieldname($matches) {
        $name = $matches[1];

        // course id/url
        if (substr($name, 0, 6)=='course') {
            switch ($name) {
                case 'courseid'  : return $this->data->course;
                case 'courseurl' : return new moodle_url('/course/view.php', array('id' => $this->data->course));
            }
        }

        // data activity id/name/intro/url
        if (substr($name, 0, 4)=='data') {
            switch ($name) {
                case 'dataid'    : return $this->data->id;
                case 'dataname'  : return $this->data->name;
                case 'dataintro' : return format_text($this->data->intro, $this->data->introformat);
                case 'dataurl'   : return new moodle_url('/mod/data/view.php', array('d' => $this->data->id));
            }
        }

        // data record id/url
        if (substr($name, 0, 6)=='record') {
            switch ($name) {
                case 'recordid'  : return $this->recordid;
                case 'recordurl' : return new moodle_url('/mod/data/view.php', array('d' => $this->data->id, 'rid' => $this->recordid));
            }
        }

        // capabilities
        if (substr($name, 0, 4)=='can_') {
            switch (substr($name, 4)) {
                case 'addinstance':
                case 'viewentry':
                case 'writeentry':
                case 'comment':
                case 'rate':
                case 'viewrating':
                case 'viewanyrating':
                case 'viewrating':
                case 'viewallratings':
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
                    return has_capability('mod/data:'.substr($name, 4), $this->context);
            }
        }

        // user fields
        if (isset($this->user->$name)) {

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

            if (in_array($name, $names)) {
                return $this->user->$name;
            } else {
                return str_repeat('*', 12);
            }
        }

        if ($name==$this->field->name) {
            return ''; // oops, recursive loop
        }

        if (! $field = data_get_field_from_name($name, $this->data, $this->cm)) {
            return ''; // shouldn't happen !!
        }

        return $field->display_browse_field($this->recordid, $this->template);
    }

    /**
     * reduce all IF-THEN-ENDIF blocks in $content
     *
     * @param  string $content
     * @return string
     */
    function replace_if_blocks($content) {

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
                                if ($this->check_condition($tail)) {
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
                                if ($this->check_condition($tail)) {
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
    function check_condition($tail) {

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

        if ($fieldname==$this->field->name) {
            return false; // prevent infinite loops
        }

        if (! $field = data_get_field_from_name($fieldname, $this->data)) {
            return false; // unknown $fieldname - shouldn't happen !!
        }

        if (method_exists($field, 'get_condition_value')) {
            // special case to allow access to value of hidden "admin" fields
            $content = $field->get_condition_value($this->recordid, $this->template);
        } else {
            $content = $field->display_browse_field($this->recordid, $this->template);
        }
        list($operator, $content, $value) = $this->clean_condition($operator, $content, $value);

        switch ($operator) {
            case self::OP_EMPTY:         return empty($content);
            case self::OP_NOT_EMPTY:     return (! empty($content));
            case self::OP_EQUAL:         return ($content == $value);
            case self::OP_NOT_EQUAL:     return ($content != $value);
            case self::OP_MORE_THAN:     return ($content > $value);
            case self::OP_LESS_THAN:     return ($content < $value);
            case self::OP_CONTAIN:       return strpos($content, $value)!==false;
            case self::OP_NOT_CONTAIN:   return strpos($content, $value)===false;
            case self::OP_START_WITH:    return ($value == substr(0, strlen($value)));
            case self::OP_END_WITH:      return ($value == substr(- strlen($value)));
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
     * @return string
     * @todo Finish documenting this function
     */
    protected function clean_condition($operator, $content, $value) {

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

    ///////////////////////////////////////////
    // custom methods for mod.html
    ///////////////////////////////////////////

    /*
     * get options for fieldids (param1) for display in mod.html
     */
    public function get_fieldids() {
        global $DB;
        $select = 'dataid = ? AND type != ?';
        $params = array($this->data->id, $this->type);
        return $DB->get_records_select_menu('data_fields', $select, $params, 'id', 'id,name');
    }

    /*
     * get options for operators (param2) for display in mod.html
     */
    public function get_operators() {
        $plugin = 'datafield_template';
        return array(
            self::OP_EMPTY       => get_string('isempty',       'filters'),
            self::OP_NOT_EMPTY   => get_string('isnotempty',    $plugin),
            self::OP_EQUAL       => get_string('isequalto',     'filters'),
            self::OP_NOT_EQUAL   => get_string('isnotequalto',  $plugin),
            self::OP_MORE_THAN   => get_string('ismorethan',    $plugin),
            self::OP_LESS_THAN   => get_string('islessthan',    $plugin),
            self::OP_CONTAIN     => get_string('contains',      'filters'),
            self::OP_NOT_CONTAIN => get_string('doesnotcontain','filters'),
            self::OP_START_WITH  => get_string('startswith',    'filters'),
            self::OP_END_WITH    => get_string('endswith',      'filters')
        );
    }

    /*
     * get options for editor formats (param2) for display in mod.html
     */
    public function get_formats() {
        return array(
            FORMAT_MOODLE   => get_string('formattext',     'moodle'), // 0
            FORMAT_HTML     => get_string('formathtml',     'moodle'), // 1
            FORMAT_PLAIN    => get_string('formatplain',    'moodle'), // 2
            // FORMAT_WIKI  => get_string('formatwiki',     'moodle'), // 3 deprecated
            FORMAT_MARKDOWN => get_string('formatmarkdown', 'moodle')  // 4
        );
    }

    /**
     * Returns options for embedded files
     *
     * @return array
     */
    public function get_fileoptions() {
        return array('trusttext'  => false,
                     'forcehttps' => false,
                     'subdirs'    => false,
                     'maxfiles'   => -1,
                     'context'    => $this->context,
                     'maxbytes'   => $this->field->param2,
                     'changeformat' => 0,
                     'noclean'    => false);
    }

    /*
     * format a label in mod.html
     */
    public function format_table_row($name, $label, $text) {
        $label = $this->format_edit_label($name, $label);
        $output = $this->format_table_cell($label, 'c0').
                  $this->format_table_cell($text,  'c1');
        $output = html_writer::tag('tr', $output, array('class' => $name, 'style' => 'vertical-align: top;'));
        return $output;
    }

    /*
     * format a cell in mod.html
     */
    public function format_table_cell($text, $class) {
        return html_writer::tag('td', $text, array('class' => $class));
    }

    /*
     * format a label in mod.html
     */
    public function format_edit_label($name, $label) {
        return html_writer::tag('label', $label, array('for' => 'id_'.$name));
    }

    /*
     * format a text field in mod.html
     */
    public function format_edit_textfield($name, $value, $class, $size=10) {
        $params = array('type'  => 'text',
                        'id'    => 'id_'.$name,
                        'name'  => $name,
                        'value' => $value,
                        'class' => $class,
                        'size'  => $size);
        return html_writer::empty_tag('input', $params);
    }

    /*
     * format a textarea field in mod.html
     */
    public function format_edit_textarea($name, $value, $class, $rows=3, $cols=40) {
        $params = array('id'    => 'id_'.$name,
                        'name'  => $name,
                        'class' => $class,
                        'rows'  => $rows,
                        'cols'  => $cols);
        return html_writer::tag('textarea', $value, $params);
    }

    /*
     * format an html editor for display in mod.html
     */
    public function format_edit_editor($title, $content, $format, $rows=3, $cols=40) {

        editors_head_setup();
        $options = $this->get_fileoptions();

        $itemid = $this->field->id;
        $name = 'field_'.$itemid;

        if ($itemid){
            $draftitemid = 0;
            $text = clean_text($content, $format);
            $text = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_data', 'content', $itemid, $options, $text);
        } else {
            $draftitemid = file_get_unused_draft_itemid();
            $text = '';
        }

        // get filepicker options, if required
        if (empty($options['maxfiles'])) {
            $filepicker_options = array();
        } else {
            $filepicker_options = $this->get_filepicker_options($draftitemid, $options['maxbytes']);
        }


        // set up editor
        $editor = editors_get_preferred_editor($format);
        $editor->set_text($text);
        $editor->use_editor('id_'.$name.'_content', $options, $filepicker_options);

        // format editor
        $output = '';
        $output .= $this->format_editor_content($draftitemid, $name, $content, $rows, $cols);
        $output .= $this->format_editor_formats($editor, $name, $format);
        return html_writer::tag('div', $output, array('title' => $title));
    }

    /*
     * get filepicker options for editor in mod.html
     */
    public function get_filepicker_options($draftitemid, $maxbytes) {

        // common filepicker arguments
        $args = (object)array(
            // need these three to filter repositories list
            'return_types'   => (FILE_INTERNAL | FILE_EXTERNAL),
            'context'        => $this->context,
            'env'            => 'filepicker'
        );

        // advimage plugin
        $args->accepted_types = array('web_image');
        $image_options = initialise_filepicker($args);
        $image_options->context = $this->context;
        $image_options->client_id = uniqid();
        $image_options->maxbytes = $maxbytes;
        $image_options->env = 'editor';
        $image_options->itemid = $draftitemid;

        // moodlemedia plugin
        $args->accepted_types = array('video', 'audio');
        $media_options = initialise_filepicker($args);
        $media_options->context = $this->context;
        $media_options->client_id = uniqid();
        $media_options->maxbytes  = $maxbytes;
        $media_options->env = 'editor';
        $media_options->itemid = $draftitemid;

        // advlink plugin
        $args->accepted_types = '*';
        $link_options = initialise_filepicker($args);
        $link_options->context = $this->context;
        $link_options->client_id = uniqid();
        $link_options->maxbytes  = $maxbytes;
        $link_options->env = 'editor';
        $link_options->itemid = $draftitemid;

        return array(
            'image' => $image_options,
            'media' => $media_options,
            'link'  => $link_options
        );
    }

    /*
     * format editor content display in mod.html
     */
    public function format_editor_content($draftitemid, $name, $content, $rows, $cols) {
        $output = '';

        // hidden element to store $draftitemid
        $params = array('name'  => $name.'_itemid',
                        'value' => $draftitemid,
                        'type'  => 'hidden');
        $output .= html_writer::empty_tag('input', $params);

        // textarea element to be converted to editor
        $output .= html_writer::start_tag('div');
        $params = array('id'   => 'id_'.$name.'_content',
                        'name' => $name.'_content',
                        'rows' => $rows,
                        'cols' => $cols,
                        'spellcheck' => 'true');
        $output .= html_writer::tag('textarea', $content, $params);
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /*
     * format list of editor formats for display in mod.html
     */
    public function format_editor_formats($editor, $name, $format) {

        // get the valid formats
        $strformats = format_text_menu();
        $formatids =  $editor->get_supported_formats();
        foreach ($formatids as $formatid) {
            $formats[$formatid] = $strformats[$formatid];
        }

        // get label and select element for the formats
        $output = '';
        $params = array('for'   => 'id_'.$name.'_format',
                        'class' => 'accesshide');
        $output .= html_writer::tag('label', get_string('format'), $params);
        $output .= html_writer::select($formats, $name.'_format', $format);

        // wrap it all in a DIV ... not sure why :-)
        return html_writer::tag('div', $output);
    }

    /*
     * receive editor content from mod.html
     */
    public function get_editor_content() {
        $itemid = $this->field->id;
        $name = 'field_'.$itemid;
        $this->field->param1 = optional_param($name.'_content', FORMAT_HTML, PARAM_RAW);
        $this->field->param2 = optional_param($name.'_format',  FORMAT_HTML, PARAM_INT);
        if ($this->field->param1) {
            $options = $this->get_fileoptions();
            $draftitemid = file_get_submitted_draft_itemid($name.'_itemid');
            $this->field->param1 = file_save_draft_area_files($draftitemid, $this->context->id, 'mod_data', 'content', $itemid, $options, $this->field->param1);
        }
    }

    /*
     * add js to required in mod.html
     */
    public function require_edit_js($fieldid, $operatorid, $valueid) {
        global $PAGE;
        $module = array('name' => 'M.datafield_template', 'fullpath' => '/mod/data/field/template/template.js');

        $options = array('sourceid' => $fieldid, 'targetid' => $operatorid, 'values' => array(''));
        $PAGE->requires->js_init_call('M.datafield_template.disable_condition', $options, false, $module);

        $options = array('sourceid' => $operatorid, 'targetid' => $valueid, 'values' => array('', '1', '2'));
        $PAGE->requires->js_init_call('M.datafield_template.disable_condition', $options, false, $module);
    }
}

