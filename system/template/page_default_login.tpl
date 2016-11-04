<!doctype html>
<html lang="en">
[[$chunk_head]]
<body>
<div id="off_canvas_wrapper" class="wrapper">
    <div id="off_canvas_container" class="wrapper">
        <div id="header_wrapper" class="wrapper">
            [[$chunk_header_banner]]
        </div><!-- #header_wrapper -->
        <div id="body_wrapper" class="wrapper">
            <div id="banner_wrapper" class="wrapper">
                <div id="banner_mask"></div>
                <div id="banner_container" class="container">
                    <p id="banner_title">Find a business</p>
                    <p id="banner_slogan">Find, share, promote and connect with top business in your area like never before</p>
                </div>
            </div><!-- #banner_wrapper -->
            <div class="login_form_container">
                <form method="post" action="">
                    <input type="text" value="[[*username]]" placeholder="Username">
                    <input type="password" value="" placeholder="Password">
                    <input type="submit" value="Login">
                </form>
            </div>
            [[$body has some static text]]
        </div><!-- #body_wrapper -->
        <div id="footer_wrapper" class="wrapper">
        </div><!-- #footer_wrapper -->
    </div>
</div>
[[+script]]
</body>
</html>
