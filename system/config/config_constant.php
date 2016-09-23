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
define('URI_SITE_BASE',URI_BASE.'/'.FOLDER_SITE_BASE.'/');

define('URI_ASSET', URI_SITE_BASE . FOLDER_ASSET. '/');
define('URI_CSS', URI_ASSET . FOLDER_CSS . '/');
define('URI_IMAGE', URI_ASSET . FOLDER_IMAGE . '/');
define('URI_JS', URI_ASSET . FOLDER_JS . '/');

define('URI_CONTENT', URI_SITE_BASE . FOLDER_ASSET. '/');
define('URI_CONTENT_CSS', URI_CONTENT . FOLDER_CSS . '/');
define('URI_CONTENT_IMAGE', URI_CONTENT . FOLDER_IMAGE . '/');
define('URI_CONTENT_JS', URI_CONTENT . FOLDER_JS . '/');

// Core Paths
define('PATH_BASE',str_replace(FOLDER_SITE_BASE.DIRECTORY_SEPARATOR,'',PATH_SITE_BASE));

define('PATH_SYSTEM', PATH_SITE_BASE . 'system' . DIRECTORY_SEPARATOR);
define('PATH_CLASS', PATH_SYSTEM . 'class' . DIRECTORY_SEPARATOR);
define('PATH_INCLUDE', PATH_SYSTEM . 'include' . DIRECTORY_SEPARATOR);
define('PATH_TEMPLATE', PATH_SYSTEM . 'template' . DIRECTORY_SEPARATOR);

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

// Site Request
//$global_preference->data_type = ['html','css','image','js','json'];    // html request as default
//$global_preference->module = ['default','listing','business','business-amp'];    // modules for html and json requests
//$global_preference->method = ['default','search','find'];    // modules for html and json requests

// View Page Size (number of rows fetched from db and render)
$global_preference->view_page_size = 100;
$global_preference->view_category_page_size = 12;
$global_preference->view_business_summary_page_size = 8;

// Image Size (width grid)
$global_preference->image_size_xxs = 45;
$global_preference->image_size_xs = 90;
$global_preference->image_size_s = 180;
$global_preference->image_size_m = 300;
$global_preference->image_size_l = 480;
$global_preference->image_size_xl = 800;
$global_preference->image_size_xxl = 1200;
$global_preference->image = array(
    'size'=>array(
        'xxs'=>45,
        'xs'=>90,
        's'=>180,
        'm'=>300,
        'l'=>480,
        'xl'=>800,
        'xxl'=>1200
    )
);

// Data Encode
$global_preference->ajax_data_encode = 'base64';

// Minify Text files, (remove unnecessary spaces, long variable name...)
$global_preference->minify_html = false;
$global_preference->minify_css = false;
$global_preference->minify_js = false;

// Enable Cache
$global_preference->page_cache = true;
$global_preference->format_cache = true;

// Server Environment
$global_preference->environment = 'production';

// Search Related
// Location Search, Max similar suburb returned
$global_preference->max_relevant_suburb = 5;

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
set_include_path(PATH_CLASS.'entity/'.PATH_SEPARATOR.PATH_CLASS.'view/'.PATH_SEPARATOR.PATH_CLASS.'index/');
spl_autoload_extensions(FILE_EXTENSION_CLASS);
spl_autoload_register();

// Load System Functions (Functions that may call Classes)
include_once(PATH_INCLUDE.'content'.FILE_EXTENSION_INCLUDE);

// Other configurations
// Google Analytic Tracking ID, set as '' to disable
$global_preference->ga_tracking_id = '';

// Google API credential
$global_preference->google_api_credential_server = '';
$global_preference->google_api_credential_browser = '';

?>