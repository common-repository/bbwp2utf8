<?php
session_start();
/*
Plugin Name: bbWP2UTF8
Plugin URI: http://www.burobjorn.nl
Description: This plugin will attempt to convert a wordpress or wordpress mu database with content in whatever character set to utf8
Version: $Id$
Author: Bjorn Wijers <burobjorn [at] burobjorn [dot] nl>
Author URI: http://www.burobjorn.nl
*/

/*
* Copyright 2008 Bjorn Wijers
* This file is part of bbWP2UTF8.
* bbWP2UTF8 is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
* bbWP2UTF8 is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
* You should have received a copy of the GNU General Public License along with bbWP2UTF8; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
* The latest source code is available at http://www.burobjorn.nl
* Contact: Bjorn Wijers <burobjorn [AT] burobjorn [DOT] nl>
* 
* Description: This plugin will attempt to convert a wordpress or wordpress mu database with content in whatever character set to utf8 
* 
*/


/**
 * bbWP2UTF8 is a class/plugin which strives to convert wordpress / wordpress mu database content in whatever character set to utf8
 * @package bbWP2UTF8
 * @author Bjorn Wijers
 * @copyright Bjorn Wijers / VPRO Digitaal
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: bbWP2UTF8.php 7 2008-07-01 14:35:09Z bjorn $
 **/
class bbWP2UTF8 {

  /**
   * reference to the wordpress wpdb database object
   * @var object reference to wpdb
   * @access private
  **/
  var $_wpdb;
  
  
  
  
  /**
   * Constructor (backwards compatible with php4..)
   * Calls the _setClassVars function to set the class variables
   * and calls the _setWPHooks in order to set the appropriate hooks.
   * @access public
   **/
  function bbWP2UTF8() 
  {
    
    $this->_setupClassVars();
    $this->_setWPHooks();
  }  
  
  
  /**
   * Set the defaults for the class variables
   * @access private
  **/
  function _setupClassVars() 
  {
    global $wpdb;
    $this->_wpdb =& $wpdb;  // reference to the wordpress database object
  }
  
  
  /**
   * Set the Wordpress specific filter and action hooks
   * @access private
  **/
  function _setWPHooks()
  {
    // Insert the addToMenu sink into the plugin hook list for 'admin_menu'
    add_action('admin_menu', array(&$this, '_addToMenu'));
  }
  
  /**
   * Sink function for the 'admin_menu' hook
   * Makes it easy to add optional pages as a sub-option of the top level menu items
   * @access private
   **/
  function _addToMenu() 
  {
    if( $this->isWPMU() ) {
      add_submenu_page('wpmu-admin.php', __('bbWP2UTF8'), __('bbWP2UTF8'), 'edit_plugins', basename(__FILE__), array(&$this, 'showInterface') );
    } else {
      add_submenu_page('plugins.php', __('bbWP2UTF8'), __('bbWP2UTF8'), 'edit_plugins', basename(__FILE__), array(&$this, 'showInterface') );  
    }
  }
  
  /**
   * Builds the interface and handles the different steps of the conversion.
   * Basically the core of this plugin
   * Called by the add_submenu_page hook.
   * @access public
   **/
  function showInterface() 
  {
    $html  = "<div class='wrap'>\n";
    $html  .= "<h2>bbWP2UTF8</h2>\n";
    $queries ='';
    // step 1: binarize all necessary columns
    if( isset($_POST['step1']) ) {
      if( isset($_POST['tables']) && is_array($_POST['tables']) ){
        $_SESSION['selected_tables'] = $_POST['tables']; // keep track of the chosen tables
        foreach($_POST['tables'] as $table_index => $table_name) {
          if( is_string($table_name) ) {
            $columns = $this->_getTableColumns($table_name);
            if( is_array($columns) && sizeof($columns) > 0 ) {
              foreach($columns as $col) {
                if( is_object($col) ) {
                  $queries .= $this->_binarizeTableColumn($table_name, $col);
                }
              }
            }
          }
        }
      }
      $html .= '<h3>' . __('Processed step 1. Proceed with step 2') . "</h3>\n";
      $html .= '<strong>Queries log</strong><br />';
      $html .= $queries;
      $html .= "<form name='tables_overview' method='post'>\n";
      $html  .= __("<p>Converted all columns to their binary counterparts using the queries above. Next step will change all chosen tables' characterset to UTF8.</p>\n");
      $html .= "<input type='submit' name='step2' value='Step 2: Convert tables to UTF8 character set' />\n";
      $html .= "</form>\n";
      echo $html;
    } else if( isset($_POST['step2']) ) {
        // step 2: convert chosen tables to UTf8 character set
        $selected_tables = $_SESSION['selected_tables'];
        foreach($selected_tables as $table_index => $table_name) {
          $performed_query = $this->convertTable2UTF8($table_name, $collation = 'utf8_general_ci');
          $queries .= $performed_query;
        }
        $html .= '<h3>' . __('Processed step 2. Proceed with step 3') . "</h3>\n";
        $html .= '<strong>Queries log</strong><br />';
        $html .= $queries;
        $html .= "<form name='tables_overview' method='post'>\n";
        $html  .= __("<p>Converted all tables to UTF8 using the queries above. Next step will change the database's characterset to UTF8.</p>\n");
        $html .= "<input type='submit' name='step3' value='Step 3: Convert database default character set to UTF8' />\n";
        $html .= "</form>\n";
        echo $html;
    } else if ( isset($_POST['step3']) ) {
        // step 3: convert database default character set to UTF8
        $queries .= $this->convertDatabase2UTF8($database_name = DB_NAME, $collation = 'utf8_general_ci');
        $html .= '<h3>' . __('Processed step 3. Proceed with step 4') . "</h3>\n";
        $html .= '<strong>Queries log</strong><br />';
        $html .= $queries;
        $html .= "<form name='tables_overview' method='post'>\n";
        $html  .= __("<p>Converted database to UTF8 using the query above. Next step will change the columns back to their original types.</p>\n");
        $html .= "<input type='submit' name='step4' value='Step 4: Convert columns back from binary to original types' />\n";
        $html .= "</form>\n";
        echo $html;
    } else if ( isset($_POST['step4']) ) {
      // step 4: set all columns back to their original type
        $selected_tables = $_SESSION['selected_tables'];
        foreach($selected_tables as $table_index => $table_name) {
          if( is_string($table_name) ) {
            $columns = $this->_getTableColumns($table_name);
            if( is_array($columns) && sizeof($columns) > 0 ) {
              foreach($columns as $col) {
                if( is_object($col) ) {
                  $performed_query = $this->_deBinarizeTableColumn($table_name, $col);
                  $queries .= $performed_query;
                }
              }
            }
          }
        }
        $available_tables = $this->_getAllTables();
        $tables_list      = $this->_createTablesList($available_tables, FALSE);
        $database_char_set = $this->showCurrentDatabaseCharacterSet(); 
        $db_char_set_info  = ($database_char_set) ? __('<strong>Current database character set: ') . $database_char_set->Value . '</strong>' : '';
        $html .= '<h3>' . __('Finished!') . "</h3>\n";
        $html .= __("<p>Converted columns back to their original types. The database should now be converted to UTF8, see the list below for the
                     current database character set and its tables collation. Don't forgot to de-activate this plugin and preferably remove it, 
                     because it has no use anymore after converting the database.</p>\n");
        $html .= '<strong>Queries log</strong><br />';
        $html .= $queries;
        $html .= $db_char_set_info;
        $html .= $tables_list;
        echo $html;
    } else {
      // step 0: Display available tables for conversion
        $available_tables  = $this->_getAllTables();
        $tables_list       = $this->_createTablesList($available_tables);
        $database_char_set = $this->showCurrentDatabaseCharacterSet(); 
        $db_char_set_info  = ($database_char_set) ? __('<strong>Current database character set: ') . $database_char_set->Value . '</strong>' : '';
        if(is_string($tables_list) ) {
          $html  .= __("<p>Before doing anything else, <strong>backup your database</strong> and <strong>make sure that your backup works</strong>. 
                   If anything goes wrong you will need it to restore your Wordpress install!</p>
                   <p>The list below shows the database's character set and the available tables including their current collation between brackets.</p>\n");
          $html .= '<h3>' . __('Select the tables to convert') . "</h3>\n";
          $html .= $db_char_set_info; 
          $html .= "<form name='tables_overview' method='post'>\n";
          $html .= $tables_list;
          $html .= "<input type='submit' name='step1' value='Step 1: Convert columns to binary counterparts for checked tables' />\n";
          $html .= "</form>\n";
          echo $html;
        }
    }
  }

  
  /**
   * Creates a list with all available tables
   * @param array tables 
   * @param boolean useCheckboxes default TRUE; switches checkboxes on/off
   * @todo should remove collation function from this function and make part of _getAllTables 
   * @return string html 
   * @access private
   **/
  function _createTablesList($tables, $useCheckboxes = TRUE) 
  {
    if( ! is_array($tables) ) { return -1; }    
    if(sizeof($tables) < 0) { return null; }
    
    $html = "<ol>\n";
    foreach($tables as $element) {
      $collation = $this->_getTableCollation($element[0]); 
      if($useCheckboxes) { 
        $html .= sprintf("<li><input type='checkbox' checked='checked' name='tables[]' value='%s'>%s (%s)</li>\n", $element[0], $element[0], $collation);
      }else { 
        $html .= sprintf("<li>%s (%s)</li>\n", $element[0], $collation);
      }
    }
    $html .= "</ol>\n";
    return $html;
  }
  
  
  
  /**
   * Retrieves all tables from database defined by DB_NAME in wp-config.php
   * @return mixed either an array with tables or a boolean FALSE
   * @access private
   **/
  function _getAllTables() 
  {
    $wpdb = $this->_wpdb;
    $query = sprintf("SHOW TABLES");
    $result = $wpdb->get_results($query, $output = ARRAY_N); // note that we grab the results as an indexed array and not as an object.. 
    $return = is_array($result) ? $result : FALSE;
    return $return;
  }
  
  
  /**
   * Retrieves all columns from a given table
   * @return mixed either an array with columns or a boolean FALSE
   * @access private
   **/
  function _getTableColumns($table_name) 
  {
    $wpdb = $this->_wpdb;
    $query = sprintf("DESCRIBE %s", $table_name);
    $result = $wpdb->get_results($query);
    $return = is_array($result) ? $result : FALSE;
    return $return;
  }
  
  /**
   * Converts a given column's type of a given table to its binary counterpart according to 
   * the list below:
   *
   *  CHAR -> BINARY
   *  VARCHAR -> VARBINARY
   *  TINYTEXT -> TINYBLOB
   *  TEXT -> BLOB
   *  MEDIUMTEXT -> MEDIUMBLOB
   *  LONGTEXT -> LONGBLOB 
   *
   * Columns with the type ENUM and SET will be set to character set binary
   * @return string performed query 
   * @access private
   **/
  function _binarizeTableColumn($table_name, $column) 
  {
    if( ! is_string($table_name) || ! is_object($column) ) { return -1; } // make sure we get the right types to work with
      $wpdb = $this->_wpdb;
      // text columns do not need any other processing...so we'll switch them to their binary counterparts directly
      switch ($column->Type) {
        case 'tinytext':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s TINYBLOB NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        case 'text':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s BLOB NOT NULL", $table_name, $column->Field, $column->Field);
        break;
      
        case 'mediumtext':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s MEDIUMBLOB NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        case 'longtext':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s LONGBLOB NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        // some other column types we're interested in need some additional processing 
        default:
          $col_type_info = $this->_processColumnInfo($column);
          if( is_array($col_type_info) ) {
            switch($col_type_info[0]) {
            
              case 'char':
                $query = sprintf("ALTER TABLE %s CHANGE %s %s BINARY ( %d ) NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[1]);
              break;
                
              case 'varchar':
                $query = sprintf("ALTER TABLE %s CHANGE %s %s VARBINARY ( %d ) NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[1]);
              break;
              
              case 'enum':
                if( $this->isColumnCharSetBinary($table_name, $column->Field) ) {
                  $query = null;
                } else {
                  $query = sprintf("ALTER TABLE %s CHANGE %s %s %s( %s ) CHARACTER SET binary NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[0], $col_type_info[1]);
                }
              break;
                
              case 'set':
                if( $this->isColumnCharSetBinary($table_name, $column->Field) ) {
                  $query = null;
                } else {
                  $query = sprintf("ALTER TABLE %s CHANGE %s %s %s( %s ) CHARACTER SET binary NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[0], $col_type_info[1]);
                }
              break;
            }
          }
        break;
      }
      
      if( ! is_null($query) ) {
        $result = $wpdb->query($query);
        return "<span style='font-size: 50%';>Performed query: $query</span><br />\n";
      }
  }
  
  
  
  /**
   * Converts a given column's binary type of a given table to its previous non-binary type according to 
   * the list below:
   *
   *  BINARY -> CHAR
   *  VARBINARY -> VARCHAR
   *  TINYBLOB -> TINYTEXT 
   *  BLOB -> TEXT  
   *  MEDIUMBLOB -> MEDIUMTEXT 
   *  LONGBLOB -> LONGTEXT  
   *
   * Columns with the type ENUM and SET will be set to character set utf8
   * @return string performed query 
   * @access private
   **/
  function _deBinarizeTableColumn($table_name, $column) 
  {
    if( ! is_string($table_name) || ! is_object($column) ) { return -1; } // make sure we get the right types to work with
      $wpdb = $this->_wpdb;
      // these columns do not need any other processing...so we'll switch them to their original type directly
      switch ($column->Type) {
        case 'tinyblob':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s TINYTEXT NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        case 'blob':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s TEXT NOT NULL", $table_name, $column->Field, $column->Field);
        break;
      
        case 'mediumblob':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s MEDIUMTEXT NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        case 'longblob':
          $query = sprintf("ALTER TABLE %s CHANGE %s %s LONGTEXT NOT NULL", $table_name, $column->Field, $column->Field);
        break;
        
        // some other column types we're interested in need some additional processing 
        default:
          $col_type_info = $this->_processColumnInfo($column);
          if( is_array($col_type_info) ) {
            switch($col_type_info[0]) {
            
              case 'binary':
                $query = sprintf("ALTER TABLE %s CHANGE %s %s CHAR ( %d ) NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[1]);
              break;
                
              case 'varbinary':
                $query = sprintf("ALTER TABLE %s CHANGE %s %s VARCHAR ( %d ) NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[1]);
              break;
              
              case 'enum':
                if( $this->isColumnCharSetBinary($table_name, $column->Field) ) {
                  $query = sprintf("ALTER TABLE %s CHANGE %s %s %s( %s ) CHARACTER SET utf8 NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[0], $col_type_info[1]);
                } else {
                  $query = null;
                }
              break;
                
              case 'set':
                if( $this->isColumnCharSetBinary($table_name, $column->Field) ) {
                  $query = sprintf("ALTER TABLE %s CHANGE %s %s %s( %s ) CHARACTER SET utf8 NOT NULL", $table_name, $column->Field, $column->Field, $col_type_info[0], $col_type_info[1]);
                } else {
                  $query = null;                  
                }
              break;
            }
          }
        break;
      }
      
      if( ! is_null($query) ) {
        $result = $wpdb->query($query);
        return "<span style='font-size: 50%';>Performed query: $query</span><br />\n";
      }
  } 
  
  /**
   * Expects a column object and returns the column type and extra info in an array
   * @param object column
   * @return mixed int -1 when not using the appropriate parameter or an array with column data: $array[0] = column type such as vaechar, 
   * $array[1] = type size info such as 255 or a boolean FALSE when no info could be retrieved
   * @access private
   **/
  function _processColumnInfo($column) 
  {
    if( ! is_object($column) ) { return -1; } // make sure we get an object
    $col_type_info = explode('(', $column->Type);
    if( is_array($col_type_info) && sizeof($col_type_info) == 2) {
      $col_type_info[1] = trim($col_type_info[1], ")");
      $col_type_info[1] = trim($col_type_info[1], ") unsigned");
      $col_type_info[1] = trim($col_type_info[1], ") signed");
      return $col_type_info; 
    }
    // fall thru
    return FALSE;
  } 
  
  
  /**
   * Retrieves a column's character set
   * @param string table name
   * @param string column name
   * @param string database name; Optional, defaults to DB_NAME set in wp-config.php
   * @return mixed string character set or a boolean FALSE when no info could be retrieved
   * @access private
   **/
  function _getColumnCharacterSet($table_name, $column_name, $database_name = DB_NAME) 
  {
    $wpdb = $this->_wpdb;
    $query = sprintf("SELECT CHARACTER_SET_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '%s' AND table_schema = '%s' AND column_name = '%s'", $table_name, $database_name, $column_name);
    $result = $wpdb->get_var($query);
    $return = is_null($result) ? FALSE : $result;
    return $return; 
  } 
  
  
  /**
   * Retrieves a table's collation 
   * @param string table name
   * @param string database name; Optional, defaults to DB_NAME set in wp-config.php
   * @return mixed string collation type or a boolean FALSE when no info could be retrieved
   * @access private
   **/
  function _getTableCollation($table_name, $database_name = DB_NAME) 
  {
    $wpdb = $this->_wpdb;
    $query = sprintf("SELECT TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '%s' AND table_schema = '%s'", $table_name, $database_name);
    $result = $wpdb->get_var($query);
    $return = is_null($result) ? FALSE : $result;
    return $return;
  }
  
  /**
   * Check if a column's character set is binary or not (wraps _getColumnCharacterSet in it..)
   * @param string table name
   * @param string column name
   * @param string database name; Optional, defaults to DB_NAME set in wp-config.php
   * @return boolean FALSE when no info could be retrieved or character set is not binary. TRUE if character set is binary
   * @access public
   **/
  function isColumnCharSetBinary($table_name, $column_name, $database_name = DB_NAME) 
  {
    $result = $this->_getColumnCharacterSet($table_name, $column_name, $database_name = DB_NAME);
    if( is_string($result) ) {
      if( strtolower($result) === 'binary') {
        return TRUE;
      }
    }
    // fall thru..
    return FALSE;
  }
  
  /**
   * Sets a table's character set to utf8 
   * @param string table name
   * @param string collation; Optional defaults to utf8_general_ci 
   * @return string the performed query
   * @access public
   **/
   function convertTable2UTF8($table_name, $collation = 'utf8_general_ci') 
   {
     $wpdb = $this->_wpdb;
     $query = sprintf("ALTER TABLE `%s` DEFAULT CHARACTER SET utf8 COLLATE %s", $table_name, $collation);
     $result = $wpdb->query($query);
     return "<span style='font-size: 50%';>Performed query: $query</span><br />\n";
   }

   
   
  /**
   * Sets a database's character set to utf8 
   * @param string database name; Optional defaults to DB_NAME set in wp-config.php
   * @param string collation; Optional defaults to utf8_general_ci 
   * @return string the performed query
   * @access public
   **/
   function convertDatabase2UTF8($database_name = DB_NAME, $collation = 'utf8_general_ci') 
   {
     $wpdb = $this->_wpdb;
     $query = sprintf("ALTER DATABASE `%s` DEFAULT CHARACTER SET utf8 COLLATE %s", $database_name, $collation);
     $result = $wpdb->query($query);
     return "<span style='font-size: 50%';>Performed query: $query</span><br />\n";
   }
   
   /**
    * Retrieve the current database's character set
    * @access public
    * @return mixed object with character set or boolean FALSE 
   **/
   function showCurrentDatabaseCharacterSet()
   {
     $wpdb = $this->_wpdb;
     $result = $wpdb->get_row('SHOW VARIABLES LIKE "character_set_database"');
     $return = is_object($result) ? $result : FALSE;
     return $return;
   }
   
   
   /**
   * Checks if we're dealing with Wordpress MU or not by checking the existance of wpmu-settings.php 
   * @return boolean TRUE when its Wordpress MU or FALSE when not 
   * @access public
   **/
   function isWPMU() { return file_exists( ABSPATH . '/wpmu-settings.php'); }
}

// initialize..
$bbWP2UTF8 = new bbWP2UTF8();
?>
