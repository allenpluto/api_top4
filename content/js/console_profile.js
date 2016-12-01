/**
 * Created by User on 11/11/2016.
 */
var change_password_data = {
    'title':'<h2>Change Password</h2>',
    'html_content':'<div class="overlay_content"><div class="overlay_info ajax_info"></div><div class="change_password_message_container"><p>Password should be minimum 8 characters length. It should contain at least 1 alphabet and 1 number.</p></div><div class="change_password_form_container"><div class="change_password_form"><div class="change_password_row"><div class="change_password_row_label">Password</div><div class="change_password_row_value"><input name="password" type="password" class="field_password"></div></div><div class="change_password_row"><div class="change_password_row_label">Confirm Password</div><div class="change_password_row_value"><input name="password_repeat" type="password" class="field_password_repeat"></div></div></div></div><div class="change_password_button_container"><div class="change_password_button_submit general_style_input_button general_style_input_button_gray">Submit</div><div class="change_password_button_reset general_style_input_button general_style_input_button_gray overlay_close">Cancel</div></div></div>',
    'init_function':function(overlay_trigger){
        var overlay_wrapper = $('#change_password_form_overlay_wrapper');
        var ajax_info = overlay_wrapper.find('.overlay_info');
        var update_form = overlay_wrapper.find('.update_form');

        overlay_wrapper.find('.field_password').keydown(function(event) {
            switch (event.which) {
                case 13:
                    // Key Enter Pressed
                    $('.field_password_repeat').focus();
            }
        }).keyup(function(event){
            var validate_string = $(this).val();
            if (validate_string.length < 8)
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('Password too short');
                overlay_wrapper.data('validate_password',false);
                return false;
            }

            var reg_pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!reg_pattern.test(validate_string))
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('Password should contain at least 1 alphabet and 1 number');
                overlay_wrapper.data('validate_password',false);
                return false;
            }

            if (overlay_wrapper.find('.field_password').val() == overlay_wrapper.find('.field_password_repeat').val())
            {
                ajax_info.removeClass('ajax_info_error').addClass('ajax_info_success').html('Password accepted,&nbsp;press submit button to change');
                overlay_wrapper.data('validate_password',true);
                return true;
            }
            else
            {
                ajax_info.removeClass('ajax_info_success').removeClass('ajax_info_error').html('');
                overlay_wrapper.data('validate_password',false);
                return false;
            }
        });
        overlay_wrapper.find('.field_password_repeat').keydown(function(event) {
            switch (event.which) {
                case 13:
                    // Key Enter Pressed
                    $('.change_password_button_submit').click();
            }
        }).keyup(function(event){
            if (overlay_wrapper.find('.field_password').val() == overlay_wrapper.find('.field_password_repeat').val())
            {
                ajax_info.removeClass('ajax_info_error').addClass('ajax_info_success').html('Password accepted,&nbsp;press submit button to change');
                overlay_wrapper.data('validate_password',true);
                return true;
            }
            else
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('Passwords do not match');
                overlay_wrapper.data('validate_password',false);
                return false;
            }
        });

        $('.change_password_button_submit').click(function(event){
            event.preventDefault();
            var overlay_wrapper = $(this).parents('.overlay_wrapper');
            var ajax_info = overlay_wrapper.find('.overlay_info');

            // Only post after password is validated
            if (!overlay_wrapper.data('validate_password')) return false;

            var base_uri = $('base').attr('href');
            if (!base_uri) base_uri = '/';

            var post_value = {};
            post_value['password'] = overlay_wrapper.find('.field_password').val();

            $.ajax({
                'type': 'POST',
                'url': base_uri + 'ajax/profile_update_password',
                'data': post_value,
                'timeout': 10000
            }).always(function(callback_obj, status, info_obj) {
                if (status == 'success')
                {
                    var data = callback_obj;
                    var xhr = info_obj;

                    if (data.status == 'OK')
                    {
                        $('.api_profile_message_container').removeClass('ajax_info_error').addClass('ajax_info_success').html('Password updated');

                        overlay_wrapper.fadeOut(500,function(){$(this).trigger('close');});
                    }
                    else
                    {
                        ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message);
                    }
                }
                else
                {
                    var xhr = callback_obj;
                    var error = info_obj;

                    ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p><strong>'+status+': </strong>'+error+'</p>');
                }
            });
        });
    },
    'overlay_wrapper_id':'change_password_form_overlay_wrapper',
    'close_on_click_wrapper':false
};

$('.inline_editor').inline_editor({
    'default_text':'N/A',
    'callback_function':function(inline_editor_input, original_value) {
        var base_uri = $('base').attr('href');
        if (!base_uri) base_uri = '/';

        var post_value = {};
        post_value['alternate_name'] = inline_editor_input.val();

        var ajax_info = $('.api_profile_message_container');

        if (ajax_info.data('ajax_info_timeout'))
        {
            clearTimeout(ajax_info.data('ajax_info_timeout'));
            ajax_info.removeData('ajax_info_timeout');
        }

        var flag_success = null;

        $.ajax({
            'type': 'POST',
            'url': base_uri + 'ajax/profile_update_alternate_name',
            'data': post_value,
            'timeout': 10000
        }).always(function(callback_obj, status, info_obj) {
            if (status == 'success')
            {
                var data = callback_obj;
                var xhr = info_obj;
                if (data.status == 'OK')
                {
                    ajax_info.removeClass('ajax_info_error').addClass('ajax_info_success').html(data.message);
                }
                else
                {
                    ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message);
                    inline_editor_input.val(inline_editor_input.parent().data('original_value'));
                }
            }
            else
            {
                var xhr = callback_obj;
                var error = info_obj;

                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p><strong>'+status+': </strong>'+error+'</p>');
                inline_editor_input.val(inline_editor_input.parent().data('original_value'));
            }
            ajax_info.data('ajax_info_timeout',setTimeout(function(){
                ajax_info.fadeOut(300);
            },8000))
        });
    }
});
$('.tool_tip_wrapper').tool_tip({'auto_close_delay':3000});
$('.api_profile_button_change_password').overlay_popup(change_password_data);