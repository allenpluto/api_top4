/**
 * Created by User on 11/11/2016.
 */
$('.api_key_button_add').click(function(event){
    event.preventDefault();

    var base_uri = $('base').attr('href');
    if (!base_uri) base_uri = '/';

    var post_value = {
        'account_key':'abc123'
    };
    $.ajax({
        'type': 'POST',
        'url': base_uri + 'developer/print_server.php',
        'headers':{
            'Auth-Key': '36ca-e750-31af-9f80-4018-0f31-b7eb-3f03'
        },
        'data': post_value,
        'timeout': 10000
    }).always(function(callback_obj, status, info_obj) {
        console.log(status);
        if (status == 'success')
        {
            var data = callback_obj;
            var xhr = info_obj;

console.log(data);
console.log($('base').attr('href'));
        }
        else
        {
            var xhr = callback_obj;
            var error = info_obj;

            if (status == 'timeout')
            {
                //overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Try again later</p>');
            }
            else
            {
                //overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Error Unknown, Try again later</p>');
            }
        }
    });

 });