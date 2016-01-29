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

    const OP_EMPTY       = 1;
    const OP_NOT_EMPTY   = 2;
    const OP_EQUAL       = 3;
    const OP_NOT_EQUAL   = 4;
    const OP_MORE_THAN   = 5;
    const OP_LESS_THAN   = 6;
    const OP_CONTAIN     = 7;
    const OP_NOT_CONTAIN = 8;
    const OP_START_WITH  = 9;
    const OP_END_WITH    = 10;

    /**
     * the id of the data_record currently being viewed
     */
    var $recordid = 0;

    /**
     * the date format used to format date fields
     */
    var $dateformat = 0;

    /**
     * This field just sets up a default field object
     *
     * @return bool
     */
    function define_default_field() {
        parent::define_default_field();
        $this->field->param1 = ''; // fieldid
        $this->field->param2 = ''; // operator
        $this->field->param3 = ''; // value
        $this->field->param4 = ''; // content
        $this->field->param5 = ''; // format
        return true;
    }

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
     * @param int $recordid
     * @param object $template
     * @return bool|string
     */
    function display_browse_field($recordid, $template) {
        global $DB;

        $fieldid  = (empty($this->field->param1) ? 0  : $this->field->param1);
        $operator = (empty($this->field->param2) ? 0  : $this->field->param2);
        $value    = (empty($this->field->param3) ? '' : $this->field->param3);

        // set $is_viewable flag
        if ($fieldid && $recordid) {
            $params = array('fieldid' => $fieldid, 'recordid' => $recordid);
            $content = $DB->get_field('data_content', 'content', $params);
            $content = trim($content);
            switch ($operator) {
                case self::OP_EMPTY:       $is_viewable = empty($content); break;
                case self::OP_NOT_EMPTY:   $is_viewable = (! empty($content)); break;
                case self::OP_EQUAL:       $is_viewable = ($content == $value); break;
                case self::OP_NOT_EQUAL:   $is_viewable = ($content != $value); break;
                case self::OP_MORE_THAN:   $is_viewable = ($content > $value); break;
                case self::OP_LESS_THAN:   $is_viewable = ($content < $value); break;
                case self::OP_CONTAIN:     $is_viewable = strpos($value, $content)!==false; break;
                case self::OP_NOT_CONTAIN: $is_viewable = strpos($value, $content)===false; break;
                case self::OP_START_WITH:  $is_viewable = ($value == substr(0, strlen($value))); break;
                case self::OP_END_WITH:    $is_viewable = ($value == substr(- strlen($value))); break;
                default:                   $is_viewable = false;
            }
        }

        if (! $is_viewable) {
            return '';
        }
        if (! $itemid = $this->field->id) {
            return '';
        }
        if (! $content = $this->field->param4) {
            return '';
        }
        if (! $format = $this->field->param5) {
            $format = FORMAT_MOODLE;
        }

        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $this->context->id, 'mod_data', 'content', $itemid, $this->get_fileoptions());
        $content = format_text($content, $format, (object)array('filter' => false, 'para' => false));

        // these values may be needed by the replace_fieldname() method
        $this->userid = $DB->get_field('data_records', 'userid', array('id' => $recordid));
        $this->user = $DB->get_record('user', array('id' => $this->userid));
        $this->dateformat = get_string('strftimedate', 'langconfig');
        $this->recordid = $recordid;

        $search = '/\[\[([^\]]+)]\]/';
        $callback = array($this, 'replace_fieldname');
        $content = preg_replace_callback($search, $callback, $content);

        return $content;
    }

    protected function replace_fieldname($matches) {
        global $DB, $USER;

        if (! $this->recordid) {
            return '';
        }

        if (! $name = $matches[1]) {
            return '';
        }

        if (isset($this->user->$name)) {

            // these fields are accessible
            $names = array(
                'firstname', 'lastname', 'email',
                'icq', 'skype', 'yahoo', 'aim', 'msn',
                'phone1', 'phone2', 'institution', 'department',
                'address', 'city', 'country', 'picture', 'imagealt',
                'url', 'description', 'descriptionformat',
                'lastnamephonetic', 'firstnamephonetic',
                'middlename', 'alternatename'
            );

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

        $params = array('dataid' => $this->data->id, 'name' => $name);
        if (! $field = $DB->get_record('data_fields', $params)) {
            return '';
        }

        $params = array('recordid' => $this->recordid, 'fieldid' => $field->id);
        $content = $DB->get_field('data_content', 'content', $params);
        if ($content===false) {
            return '';
        }

        if ($field->type=='admin') {
            $type = $field->param10;
        } else {
            $type = $field->type;
        }

        switch ($type) {
            case 'date': $content = userdate($content, $this->dateformat); break;
        }

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
    // custom methods
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
     * get options for editor formats (param4) for display in mod.html
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
                     'maxbytes'   => $this->field->param5,
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
     * format a select field in mod.html
     */
    public function format_edit_selectfield($name, $values, $default) {
        return html_writer::select($values, $name, $default);
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
        $this->field->param4 = optional_param($name.'_content', FORMAT_HTML, PARAM_RAW);
        $this->field->param5 = optional_param($name.'_format',  FORMAT_HTML, PARAM_INT);
        if ($this->field->param4) {
            $options = $this->get_fileoptions();
            $draftitemid = file_get_submitted_draft_itemid($name.'_itemid');
            $this->field->param4 = file_save_draft_area_files($draftitemid, $this->context->id, 'mod_data', 'content', $itemid, $options, $this->field->param4);
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

