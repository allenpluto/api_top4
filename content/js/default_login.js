/**
 * Created by User on 7/11/2016.
 *//*
$('.login_form').on('submit', function(event){
    event.preventDefault();

    var post_value = $(this).serialize();
    post_value['handler'] = 'handler_true';
    console.log($('.login_form'));
    console.log('post_value: ');
    console.log(post_value);
    $.ajax({
        'type': 'POST',
        //'url': $(this).attr('action') || window.location.pathname,
        'url': 'http://localhost/allen_frame_trial/login',
        'data': post_value,
        'timeout': 10000
    }).always(function (callback_obj, status, info_obj) {
        //ajax_loader_container.removeClass('ajax_loader_container_loading');
//console.log(status);
        console.log('result: ');
        console.log(callback_obj);
        if (status == 'success') {
            var data = callback_obj;
            var xhr = info_obj;
        }
        else {
 var xhr = callback_obj;
            var error = info_obj;

            if (status == 'timeout') {
                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Try again later</p>');
            }
            else {
                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Error Unknown, Try again later</p>');
            }
        }
    });
});*/