===============================================
The Template database field for Moodle >= 2.3
===============================================

   The Template database field allows the specification of a formatted block of output
   to be displayed on the "View list" or "View single" pages of a Database activity.

   The template can include other fields from this Database activity, as well as fields
   from the course, database activity, data record, capabilities page, and user profile
   - excluding the login password and other sensitive information.

   The following special fields are available:

   courseid - the id of the current course
   courseurl - the view url of the current course

   dataid - the id of the current database activity
   dataname - the name of the current database activity
   dataintro - the intro text for the current database activity
   dataurl - the view url of the current database activity

   recordid - the id of the current record within this database activity
   recordurl - the view url of the current record within this database activity

   The following capability fields are available. They return TRUE or FALSE
   depending on whether or not the viewing user has the specified capability.

   can_addinstance
   can_viewentry
   can_writeentry
   can_comment
   can_rate
   can_viewrating
   can_viewanyrating
   can_viewrating
   can_viewallratings
   can_viewrating
   can_approve
   can_manageentries
   can_managecomments
   can_managetemplates
   can_viewalluserpresets
   can_manageuserpresets
   can_exportentry
   can_exportownentry
   can_exportallentries
   can_exportuserinfo

   The following user fields are available. The values are taken from
   the profile page of the user who added the database record - not the
   user who is viewing the record.

   firstname, lastname,
   department, institution,
   address, city, country,
   picture, imagealt,
   description, descriptionformat,
   firstnamephonetic, lastnamephonetic,
   alternatename, middlename,
   email, url,
   phone1, phone2,
   icq, skype, yahoo, aim, msn

   Templates can include if-then-else blocks using the following syntax:

   [[IF condition]]

   [[ELIF condition]]

   [[ELSE]]

   [[ENDIF]]

   The [[IF ...]] and [[ENDIF]] items are required but
   the [[ELIF ...]] and [[ELSE]] items are optional

   A "condition" may be one of the folowing:

   field EMPTY
   field NOT_EMPTY

   field EQUAL        value
   field NOT_EQUAL    value
   field MORE_THAN    value
   field LESS_THAN    value
   field CONTAIN      value
   field NOT_CONTAIN  value
   field START_WITH   value
   field END_WITH     value

   field NUM_EQUAL      numeric_value
   field NUM_NOT_EQUAL  numeric_value
   field NUM_MORE_THAN  numeric_value
   field NUM_LESS_THAN  numeric_value

   If a value contains spaces or punctuation, it should be contained
   within single or double quotes, e.g. "O'hara" or 'New York'

   A number of alternatives for these conditions are available.
   The alternatives cater for people who prefer abbreviations,
   mathematical symbols or correct English grammar :-)

   [[IF field EMPTY]]
   (a) [[IF field IS_EMPTY]]

   [[IF field NOT_EMPTY]]
   (a) [[IF field]]
   (b) [[IF field IS_NOT_EMPTY]]

   [[IF field EQUAL value]]:
   (a) [[IF field = value]]
   (b) [[IF field == value]]
   (c) [[IF field === value]]
   (d) [[IF field EQ value]]
   (e) [[IF field IS_EQUAL value]]
   (f) [[IF field EQUAL_TO value]]
   (g) [[IF field IS_EQUAL_TO value]]

   [[IF field NOT_EQUAL value]]:
   (a) [[IF field <> value]]
   (b) [[IF field != value]]
   (c) [[IF field !== value]]
   (d) [[IF field NE value]]
   (e) [[IF field NEQ value]]
   (f) [[IF field IS_NOT_EQUAL value]]
   (g) [[IF field NOT_EQUAL_TO value]]
   (h) [[IF field IS_NOT_EQUAL_TO value]]

   [[IF field MORE_THAN value]]
   (a) [[IF field > value]]
   (b) [[IF field MT value]]
   (c) [[IF field IS_MORE_THAN value]]
   (d) [[IF field GT value]]
   (e) [[IF field GREATER_THAN value]]
   (f) [[IF field IS_GREATER_THAN value]]

   [[IF field LESS_THAN value]]
   (a) [[IF field < value]]
   (b) [[IF field LT value]]
   (c) [[IF field IS_LESS_THAN value]]

   [[IF field CONTAIN value]]
   (a) [[IF field CONTAINS value]]

   [[IF field NOT_CONTAIN value]]
   (a) [[IF field NOT_CONTAINS value]]
   (a) [[IF field DOES_NOT_CONTAIN value]]

   [[IF field START_WITH value]]
   (a) [[IF field STARTS_WITH value]]

   [[IF field END_WITH value]]
   (a) [[IF field ENDS_WITH value]]

   Note that these if-then-else blocks can be nested, as in the following example:

   [[IF country EQUAL Japan]]
      [[IF city EQUAL Kochi]]
         [[IF institution EQUAL "Kochi University of Technology"]]
            You are from KUT.
         [[ENDIF]]
      [[ENDIF]]
   [[ENDIF]]

=================================================
To INSTALL this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Clone this plugin to your server

       cd /PATH/TO/MOODLE
       git clone -q https://github.com/gbateson/moodle-datafield_template.git mod/data/field/template

    2. Add this plugin to the GIT exclude file

       cd /PATH/TO/MOODLE
       echo '/mod/data/field/template/' >> '.git/info/exclude'

       (continue with steps 3 and 4 below)

    ----------------
    Using ZIP
    ----------------

    1. download the zip file from one of the following locations

        * https://github.com/gbateson/moodle-datafield_template/archive/master.zip
        * http://bateson.kanazawa-gu.ac.jp/moodle/zip/plugins_datafield_template.zip

    2. Unzip the zip file - if necessary renaming the resulting folder to "template".
       Then upload, or move, the "template" folder into the "mod/data/field" folder on
       your Moodle >= 2.3 site, to create a new folder at "mod/data/field/template"

       (continue with steps 3 and 4 below)

    ----------------
    Using GIT or ZIP
    ----------------

    3. In Moodle <= 3.1, database plugin strings aren't fully modularised, so the
       following two strings need be added manually to the language pack for the
       Database activity module, in file "/PATH/TO/MOODLE/mod/data/lang/en/data.php"

          $string['template'] = 'Template';
          $string['nametemplate'] = 'Template field';

    4. Log in to Moodle as administrator to initiate the install/update

       If the install/update does not begin automatically, you can initiate it
       manually by navigating to the following Moodle administration page:

          Settings -> Site administration -> Notifications

    ----------------
    Troubleshooting
    ----------------

    If you have a white screen when trying to view your Moodle site
    after having installed this plugin, then you should remove the
    plugin folder, enable Moodle debugging, and try the install again.

    With Moodle debugging enabled you should get a somewhat meaningful
    message about what the problem is.

    The most common issues with installing this plugin are:

    (a) the "template" folder is put in the wrong place
        SOLUTION: make sure the folder is at "mod/data/field/template"
                  under your main Moodle folder, and that the file
                  "mod/data/field/template/field.class.php" exists

    (b) permissions are set incorrectly on the "mod/data/field/template" folder
        SOLUTION: set the permissions to be the same as those of other folders
                  within the "mod/data/field" folder

    (c) there is a syntax error in the Database language file
        SOLUTION: remove your previous edits, and then copy and paste
                  the language strings from this README file

    (d) the PHP cache is old
        SOLUTION: refresh the cache, for example by restarting the web server,
                  or the PHP accelerator, or both

=================================================
To UPDATE this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Get the latest version of this plugin

       cd /PATH/TO/MOODLE/mod/data/field/template
       git pull

    2. Log in to Moodle as administrator to initiate the update

    ----------------
    Using ZIP
    ----------------

    Repeat steps 1, 2 and 4 of the ZIP install procedure (see above)


===============================================
To ADD an Template field to a database activity
===============================================

    1. Login to Moodle, and navigate to a course page in which you are a teacher (or admin)

    2. Locate, or create, the Database activity to which you wish to add a Template field

    4. click the link to view the Database activity, and then click the "Fields" tab

    5. From the "Field type" menu at the bottom of the page, select "Template"

    6. Enter values for "Field name" and "Field description"

    7. Enter the text for the template content and select the content format.

    8. Click the "Save changes" button at the bottom of the page.
