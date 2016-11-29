<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
[[$chunk_head]]
<body id="login">
<div class="wrapper body_wrapper">
    <div class="container body_title_container">
        <h1><span class="body_title_logo"><svg width="100%" height="100%"><image xlink:href="content/image/the-new-australian-social-business-directory_logo.svg" src="content/image/the-new-australian-social-business-directory_logo_small.png" alt="Top4 - The New Australian Social Business Directory" width="100%" height="100%" /></svg></span> API Console</h1>
    </div>
    <div class="container login_form_container">
        <div class="login_form_message">[[*post_result_message]]</div>
        <form class="login_form" method="post" action="">
            <input type="hidden" name="complementary" value="[[*complementary]]">
            <input type="text" name="username" value="[[*username]]" placeholder="Username">
            <input type="password" name="password" value="" placeholder="Password">
            <input type="submit" value="Login">
        </form>
    </div>
</div><!-- #body_wrapper -->
<div class="wrapper footer_wrapper">
</div><!-- #footer_wrapper -->
[[+script]]
</body>
</html>
