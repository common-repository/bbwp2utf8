=== Plugin Name ===
Contributors: BjornW
Donate link: http://burobjorn.nl/
Tags: database, convert, utf8 
Requires at least: wordpress mu 1.2.5 or wordpress 2.2.3
Tested up to: wordpress mu 1.5.1 or wordpress 2.5.1
Stable tag: 1.0

This plugin will attempt to convert a wordpress or wordpress mu database with content in whatever character set to utf8

== Description ==

**If you need help using this plugin please contact me using my [website](http://www.burobjorn.nl). I do not regularly read the wordpress.org forum.**

This plugin will attempt to convert a wordpress or wordpress mu database with content in whatever character set to utf8. It uses the information provided
by this [guide](http://codex.wordpress.org/Converting_Database_Character_Sets/ "Converting Database Character Sets") on
the wordpress codex site. Before using this plugin it is **mandatory to have a working and recent backup of your database**. 
If anything goes wrong you will need this! 

Keep in mind that this plugin has been tested thoroughly for wordpress mu, but only minimaly for wordpress. 
Please contact me if you encounter any issues with Wordpress, patches are always welcome.

The plugin performs the following steps. First it will show you all of the tables within the database used by wordpress or wordpress mu. Between the brackets 
it shows the current character set. The plugin allows you to uncheck tables you do **not** want to convert to UTF8. Unless you know what you're doing it is 
advised to convert the complete wordpress (mu) database to UTF8. After you have selected the tables for conversion and pressed the button *convert columns 
to binary counterparts for checked tables* the plugin will go through all tables and look for columns of the type: char, varchar, tinytext, text, mediumtext,
longtext, enum and set. Columns of these types will be converted to their binary counterparts according the list below: 

- char       --> binary
- varchar    --> varbinary
- tinytext   --> tinyblob
- text       --> blob
- mediumtext --> mediumblob
- longtext   --> longblob 

As you might have noticed the column types enum and set are not changed to a different type, instead the plugin will set those columns character set to binary. 

The next step is setting all selected tables to the UTF8 character set (the plugin uses collation utf8_general_ci by default). Third, the plugin will set the 
database's default character set to UTF8. Finally the plugin will switch all the columns back from their binary to their original types. Now you should have a 
database converted to UTF8. 

Note: Development for this plugin is made possible by VPRO Digitaal and started after encountering numerous solutions 
including other plugins and none providing a 100% workable solution. If you need any help with this plugin, do not hesistate
to contact me. My company is available for hire ;) 


== Installation ==

For the installation it is presumed you have either Wordpress version 2.2.2 or Wordpress Mu 1.2.5. 
and you want to upgrade to Wordpress Mu version 1.5.1. or Wordpres version 2.5.1.

1. **Backup** your original files and database
2. Make sure your backup works. If something goes wrong you'll need a backup!!
3. Update your currently installed wordpress or wordpress mu to version either 2.5.1 or 1.5.1 See this 
[guide](http://codex.wordpress.org/Upgrading_WordPress "Upgrading Wordpress").
4. Add the following to your wp-config.php if not already there:

    define('DB_CHARSET', 'utf8');
    define('DB_COLLATE', '');

5. Check your blog database character set, if its not utf8 this plugin might be help you out
6. Upload the plugin in its own directory within the plugins directory (e.g. /wp-content/plugins/bbWP2UTF8)
7. Login into Wordpress or Wordpress Mu as admin and activate the plugin called 'bbWP2UTF8' through the 'Plugins' menu
8. On Wordpress Mu go to 'Site Admin' and you'll see a menu item called 'bbWP2UTF8'. 
On Wordpress go to 'Plugins' and you'll see a menu item called 'bbWP2UTF8'  
9. Click on the menu item 'bbWP2UTF8' and follow the instructions
10. Its a good idea to deactivate and remove this plugin after the conversion is finished

== Frequently Asked Questions ==

= What is the default collation? =

By default the plugin uses the utf8_general_ci collation. 
You can change this in the code before installing the plugin. 

= Does this plugin work with Wordpress Mu? =

Yes, in fact it started out as a Wordpress Mu plugin. Keep in mind though that it has been developed for use
with the default wordpress 'database abstraction layer' for only one database. If you use HyperDB or multiple databases
it will probably fail. If you happen to have multiple databases or use HyperDB I'd to receive a patch to make this work
as well.

= What license is this plugin under? =

This license is by default licensed under the GPL. See the LICENSE file for the details. 


== Screenshots ==

1.  In this screenshot you will notice some weird characters (made extra visible using red) which in fact was an issue with UTF8 encoding 
screenshot-utf8-character-issue.png. After using bbWP2UTF8 the problem was solved and characters were shown correctly. 
