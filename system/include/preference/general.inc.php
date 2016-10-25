<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 25/10/2016
 * Time: 3:08 PM
 */

// View Page Size (number of rows fetched from db and render)
$global_preference->view_page_size = 100;
$global_preference->view_category_page_size = 12;
$global_preference->view_business_summary_page_size = 8;

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