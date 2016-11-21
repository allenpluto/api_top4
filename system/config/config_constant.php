<?php
// FOLDER
define('FOLDER_ASSET', 'asset');
define('FOLDER_CONTENT', 'content');
define('FOLDER_CSS', 'css');
define('FOLDER_HTML', 'html');
define('FOLDER_IMAGE', 'image');
define('FOLDER_JAR', 'jar');
define('FOLDER_JS', 'js');
define('FOLDER_JSON', 'json');

// URI
if (FOLDER_SITE_BASE != '') define('URI_SITE_BASE',URI_BASE.FOLDER_SITE_BASE.'/');
else define('URI_SITE_BASE',URI_BASE);

define('URI_ASSET', URI_SITE_BASE);
define('URI_CSS', URI_ASSET . FOLDER_CSS . '/');
define('URI_IMAGE', URI_ASSET . FOLDER_IMAGE . '/');
define('URI_JS', URI_ASSET . FOLDER_JS . '/');

define('URI_CONTENT', URI_SITE_BASE . FOLDER_CONTENT. '/');
define('URI_CONTENT_CSS', URI_CONTENT . FOLDER_CSS . '/');
define('URI_CONTENT_IMAGE', URI_CONTENT . FOLDER_IMAGE . '/');
define('URI_CONTENT_JS', URI_CONTENT . FOLDER_JS . '/');

// Core Paths
//define('PATH_BASE',str_replace(FOLDER_SITE_BASE.DIRECTORY_SEPARATOR,'',PATH_SITE_BASE));

define('PATH_SYSTEM', PATH_SITE_BASE . 'system' . DIRECTORY_SEPARATOR);
define('PATH_CLASS', PATH_SYSTEM . 'class' . DIRECTORY_SEPARATOR);
define('PATH_TEMPLATE', PATH_SYSTEM . 'template' . DIRECTORY_SEPARATOR);

define('PATH_INCLUDE', PATH_SYSTEM . 'include' . DIRECTORY_SEPARATOR);
define('PATH_PREFERENCE', PATH_INCLUDE . 'preference' . DIRECTORY_SEPARATOR);

define('PATH_ASSET', PATH_SITE_BASE . FOLDER_ASSET . DIRECTORY_SEPARATOR);
define('PATH_CSS', PATH_ASSET . FOLDER_CSS . DIRECTORY_SEPARATOR);
define('PATH_HTML', PATH_ASSET . FOLDER_HTML . DIRECTORY_SEPARATOR);
define('PATH_IMAGE', PATH_ASSET . FOLDER_IMAGE . DIRECTORY_SEPARATOR);
define('PATH_JS', PATH_ASSET . FOLDER_JS . DIRECTORY_SEPARATOR);

define('PATH_CONTENT', PATH_SITE_BASE . FOLDER_CONTENT . DIRECTORY_SEPARATOR);
define('PATH_CONTENT_CSS', PATH_CONTENT . FOLDER_CSS . DIRECTORY_SEPARATOR);
define('PATH_CONTENT_IMAGE', PATH_CONTENT . FOLDER_IMAGE . DIRECTORY_SEPARATOR);
define('PATH_CONTENT_JAR', PATH_CONTENT . FOLDER_JAR . DIRECTORY_SEPARATOR);
define('PATH_CONTENT_JS', PATH_CONTENT . FOLDER_JS . DIRECTORY_SEPARATOR);

// File Extensions
define('FILE_EXTENSION_CLASS', '.class.php');
define('FILE_EXTENSION_INCLUDE', '.inc.php');
define('FILE_EXTENSION_TEMPLATE', '.tpl');

// Prefix
define('PREFIX_TEMPLATE_PAGE', 'page_');

// Load Pre-Include Functions (Functions that Classes May Use)
// Preference (Global variables, can be overwritten)
include_once(PATH_INCLUDE.'preference'.FILE_EXTENSION_INCLUDE);
$global_preference = preference::get_instance();
include_once(PATH_PREFERENCE.'general'.FILE_EXTENSION_INCLUDE);

// Message (Global message, record handled errors)
include_once(PATH_INCLUDE.'message'.FILE_EXTENSION_INCLUDE);
$global_message = message::get_instance();

// Database Connection, by default, all connect using a single global variable to avoid multiple db connections
include_once(PATH_INCLUDE.'db'.FILE_EXTENSION_INCLUDE);
$db = new db;

// Format adjust, such as friendly url, phone number, abn...
include_once(PATH_INCLUDE.'format'.FILE_EXTENSION_INCLUDE);
$format = format::get_obj();

// Load Classes
// Each Entity Class represents one and only one table, handle table operations
// View Classes are read only classes, display to front end
// Index Classes are indexed tables for search only
set_include_path(PATH_CLASS.PATH_SEPARATOR.PATH_CLASS.'entity/'.PATH_SEPARATOR.PATH_CLASS.'view/'.PATH_SEPARATOR.PATH_CLASS.'index/');
spl_autoload_extensions(FILE_EXTENSION_CLASS);
spl_autoload_register();

// Load System Functions (Functions that may call Classes)
include_once(PATH_INCLUDE.'function'.FILE_EXTENSION_INCLUDE);
include_once(PATH_INCLUDE.'content'.FILE_EXTENSION_INCLUDE);

// Other configurations
// Google Analytic Tracking ID, set as '' to disable
$global_preference->ga_tracking_id = '';

// Google API credential
$global_preference->google_api_credential_server = '';
$global_preference->google_api_credential_browser = '';

?>