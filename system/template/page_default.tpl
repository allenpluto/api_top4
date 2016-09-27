<!doctype html>
<html lang="en">
[[$chunk_head]]
<body>
<div id="off_canvas_wrapper" class="wrapper">
    <div id="off_canvas_container" class="wrapper">
        <div id="off_canvas_container_mask" class="off_canvas_halt"></div>
        <div id="off_canvas_menu">
            [[$chunk_menu]]
        </div><!-- #off_canvas_menu -->
        <div id="header_wrapper" class="wrapper">
            [[$chunk_header_banner]]
            <div id="search_wrapper" class="wrapper">
                <div id="search_container" class="container">
                    <div id="search_wrapper_close" class="search_halt"></div>
                    <div id="search_keyword_container" class="search_form_row">
                        <label for="search_keyword">What are you looking for?</label>
                        <input name="keyword" type="text" placeholder="What are you looking for?" id="search_keyword" class="general_style_input_text" value="[[&search_what]]">
                        <input name="category_id" type="hidden">
                    </div>
                    <div id="search_location_container" class="search_form_row">
                        <label for="search_location">In which Suburb?</label>
                        <input name="location" type="text" placeholder="In which Suburb?" id="search_location" class="general_style_input_text" value="[[&search_where]]">
                        <input name="location_id" type="hidden" id="search_location_place_id">
                        <input name="geo_location" type="hidden" id="search_location_geometry_location">
                    </div>
                    <div id="search_submit_container" class="search_form_row">
                        <a id="search_submit" class="general_style_input_button general_style_input_button_orange"><span>Search</span></a>
                    </div>
                </div>
            </div><!-- #search_wrapper -->
        </div><!-- #header_wrapper -->
        <div id="body_wrapper" class="wrapper">
            <div id="banner_wrapper" class="wrapper">
                <div id="banner_mask"></div>
                <div id="banner_container" class="container">
                    <p id="banner_title">Find a business</p>
                    <p id="banner_slogan">Find, share, promote and connect with top business in your area like never before</p>
                </div>
            </div><!-- #banner_wrapper -->
            <div id="action_button_wrapper" class="wrapper">
                <a href="listing/" id="action_button_sign_up" class="action_button"><span class="font_icon font_icon_tags general_style_colour_orange"></span><span class="text">View Popular Categories</span></a>
            </div><!-- #action_button_wrapper -->
            [[$body has some static text]]
        </div><!-- #body_wrapper -->
        <div id="footer_wrapper" class="wrapper">
        </div><!-- #footer_wrapper -->
    </div>
</div>
[[+script]]
</body>
</html>
