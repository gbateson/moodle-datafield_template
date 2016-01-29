===============================================
The Template database field for Moodle >= 2.3
===============================================

   The Template database field allows the specification of a formatted block of output
   to be displayed on the "View list" or "View single" pages of a Database activity.

   The template can include other fields from this Database activity, as well as fields
   from the user profile - excluding the login password and other sensitive information.

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

    3. Currently database plugin strings aren't fully modularised, so the following
       two strings need be added manually to the language pack for the Database
       activity module, in file "/PATH/TO/MOODLE/mod/data/lang/en/data.php"

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

    6. Enter values for "Field name" and "Field description" and select the subtype of this field

    7. Click the "Save changes" button at the bottom of the page.

    8. If necessary, you may need to further edit the field in order to add settings
       that are specific to the selected subtype
