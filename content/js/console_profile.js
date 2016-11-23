/**
 * Created by User on 11/11/2016.
 */
var change_password_data = {
    'title':'<h2>Delete Credential?</h2>',
    'html_content':'<div class="overlay_content"><div class="overlay_info ajax_info"></div><div class="delete_confirm_message_container"><p>Once you delete the credential, all API calls associated with this API-KEY would not be able to send request.</p><p>The process is irreversible, ARE YOU SURE?</p></div><div class="delete_confirm_button_container"><div class="delete_confirm_button_submit general_style_input_button general_style_input_button_gray">Yes</div><div class="delete_confirm_button_reset general_style_input_button general_style_input_button_gray overlay_close">Cancel</div></div></div>',
    'init_function':function(overlay_trigger){
        $('.delete_confirm_button_submit').click(function(){
            var base_uri = $('base').attr('href');
            if (!base_uri) base_uri = '/';

            var post_value = {};
            post_value['name'] = overlay_trigger.closest('.api_key_container').find('.api_key_name').html();

            var overlay_wrapper = $(this).parents('.overlay_wrapper');
            var ajax_info = overlay_wrapper.find('.overlay_info');

            $.ajax({
                'type': 'POST',
                'url': base_uri + 'ajax/credential_delete',
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
                    if (data.status == 'OK')
                    {
                        overlay_trigger.closest('.api_key_container').animate({
                            'height':0,
                            'opacity':0
                        },500,function(){
                            if ($(this).parent().children().length <= 2)
                            {
                                $('.api_key_wrapper').addClass('api_key_wrapper_empty');
                                $('.api_key_message_container').removeClass('ajax_info_success').removeClass('ajax_info_error').html('No API Key Available, click "Create Credential" button to create one');
                            }
                            $(this).remove();
                        });

                        overlay_wrapper.fadeOut(500,function(){$(this).remove();});
                    }
                    else
                    {
                        if (data.status == 'ZERO_RESULTS')
                        {
                            ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message+' <a href="javascript:location.reload();">Refresh Page</a> to continue');
                        }
                        else
                        {
                            ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message);
                        }
                    }
                }
                else
                {
                    console.log(callback_obj);
                    console.log(info_obj);
                    var xhr = callback_obj;
                    var error = info_obj;

                    ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p><strong>'+status+': </strong>'+error+'</p>');
                }
            });

        });
    },
    'overlay_wrapper_id':'delete_confirm_overlay_wrapper',
    'close_on_click_wrapper':false
};

$('.form_inline_editor').form_inline_editor();