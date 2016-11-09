<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
[[$chunk_head]]
<body>
<div id="off_canvas_wrapper" class="wrapper">
    <div id="off_canvas_container" class="wrapper">
        <div id="off_canvas_container_mask" class="off_canvas_halt"></div>
        <div id="off_canvas_menu">
            [[$chunk_menu]]
        </div><!-- #off_canvas_menu -->
        <div class="wrapper header_wrapper">
            [[$chunk_header_console]]
        </div><!-- #header_wrapper -->
        <div class="wrapper body_wrapper">
            [[*content]]
        </div><!-- #body_wrapper -->
        <div class="wrapper footer_wrapper">
        </div><!-- #footer_wrapper -->
    </div>
</div>
[[+script]]
</body>
</html>
