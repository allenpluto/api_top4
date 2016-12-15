/**
 * Created by User on 11/11/2016.
 */
var delete_credential_data = {
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
                if (status == 'success')
                {
                    var data = callback_obj;
                    var xhr = info_obj;
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

                        overlay_wrapper.fadeOut(500,function(){$(this).trigger('close');});
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
var update_credential_data = {
    'title':'<h2>Update Credential Detail</h2>',
    'html_content':'<div class="overlay_content"><div class="overlay_info ajax_info"></div><div class="update_form_message_container"><h2>API Key</h2><p>This API key can be used with any API you get accessed to. To use this key in your application, pass it with <strong>Auth-Key: API_KEY</strong> in the header of the request</p></div><div class="update_form"></div><div class="update_form_button_container"><div class="update_form_button_submit general_style_input_button general_style_input_button_gray">Save</div><div class="update_form_button_reset general_style_input_button general_style_input_button_gray overlay_close">Cancel</div></div></div>',
    'init_function':function(overlay_trigger){
        var overlay_wrapper = $('#api_key_update_form_overlay_wrapper');
        var ajax_info = overlay_wrapper.find('.overlay_info');
        var update_form = overlay_wrapper.find('.update_form');

        var api_key_container = overlay_trigger.closest('.api_key_container');
        update_form.append('<div class="update_row update_row_key"><div class="update_row_label">API Key</div><div class="update_row_value">'+api_key_container.find('.api_key_name').html()+'</div></div>');
        update_form.append('<div class="update_row update_row_name"><div class="update_row_label">Name</div><div class="update_row_value"><input name="alternate_name" type="text" value="'+api_key_container.find('.api_key_alternate_name').html()+'" ></div></div>');
        update_form.append('<div class="update_row update_row_ip_restriction"><div class="update_row_label">IP Restriction</div><div class="update_row_message">Accept requests from following server IP addresses, accept * as wild card.<br>e.g. 192.168.0.1, 177.168.2.*, *.*.*.*</div><div class="update_row_value"><input class="ip_add_new" type="text" value="" ></div><div class="update_row_ip_container"></div></div>');

        api_key_container.find('.api_key_ip_restriction > div').each(function(index,element){
            var ip_row = $('<div />',{
                'class':'ip_row_container'
            }).html('<div class="ip_value">'+$(element).html()+'</div><div class="ip_delete">&#xf00d;</div>').appendTo(update_form.find('.update_row_ip_container'));
            ip_row.find('.ip_delete').click(function(event){
                event.preventDefault();
                ip_row.remove();
            });
        });


        $('.ip_add_new').keydown(function(event){
            if ($.inArray(event.keyCode, [46, 8, 9, 27, 110, 32, 189]) !== -1 ||
                    // Allow: Ctrl+A
                (event.keyCode == 65 && (event.ctrlKey === true || event.metaKey === true)) ||
                    // Allow: Ctrl+C
                (event.keyCode == 67 && (event.ctrlKey === true || event.metaKey === true)) ||
                    // Allow: Ctrl+V
                (event.keyCode == 86 && (event.ctrlKey === true || event.metaKey === true)) ||
                    // Allow: Ctrl+X
                (event.keyCode == 88 && (event.ctrlKey === true || event.metaKey === true)) ||
                    // Allow: Ctrl+Z
                (event.keyCode == 90 && (event.ctrlKey === true || event.metaKey === true)) ||
                    // Allow: *
                (event.keyCode == 56 && event.shiftKey === true) ||
                    // Allow: home, end, left, right
                (event.keyCode >= 35 && event.keyCode <= 39) ||
                    // Allow: .
                (event.keyCode == 190 && event.shiftKey === false)
            )
            {
                // let it happen, don't do anything
                return;
            }

            if (event.keyCode == 13)
            {
                // Key Enter Pressed
                event.preventDefault();
                var validate_string = $(this).val();
                validate_string = validate_string.trim();
                var reg_pattern = /^(?:(\*|[0-9]{1,3})\.){3}(\*|[0-9]{1,3})$/;
                if (!reg_pattern.test(validate_string))
                {
                    ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('Invalid IP Address');
                    overlay_wrapper.scrollTop(100);
                    return false;
                }

                var flag_repeat = false;
                update_form.find('.update_row_ip_container .ip_row_container .ip_value').each(function(index,element){
                    if ($(this).html() == validate_string)
                    {
                        var ip_row_container = $(this).closest('.ip_row_container');
                        ip_row_container.addClass('ip_row_container_highlight');
                        setTimeout(function(){ip_row_container.removeClass('ip_row_container_highlight')},3000);
                        flag_repeat = true;
                    }
                });

                if (!flag_repeat)
                {
                    var ip_row = $('<div />',{
                        'class':'ip_row_container'
                    }).html('<div class="ip_value">'+$(this).val()+'</div><div class="ip_delete">&#xf00d;</div>').appendTo(update_form.find('.update_row_ip_container'));
                    ip_row.find('.ip_delete').click(function(event){
                        event.preventDefault();
                        ip_row.remove();
                    });
                    $(this).val('');
                }

                return;
            }

            // Ensure that it is a number and stop the keypress
            if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105))
            {
                event.preventDefault();
            }
        });

        $('.ip_add_new').blur(function(){
            if ($(this).val())
            {
                var event = $.Event('keydown',{keyCode: 13});

                $(this).trigger(event);
            }
        });

        $('.update_form_button_submit').click(function(){
            var base_uri = $('base').attr('href');
            if (!base_uri) base_uri = '/';

            var post_value = {};
            post_value['name'] = overlay_trigger.closest('.api_key_container').find('.api_key_name').html();
            post_value['alternate_name'] = update_form.find('input[name="alternate_name"]').val();

            var ip_restriction = [];
            update_form.find('.ip_row_container').each(function(){
                ip_restriction.push($(this).find('.ip_value').html());
            });
            ip_restriction.sort();
            post_value['ip_restriction'] = ip_restriction;

            var overlay_wrapper = $(this).parents('.overlay_wrapper');

            $.ajax({
                'type': 'POST',
                'url': base_uri + 'ajax/credential_update',
                'data': post_value,
                'timeout': 10000
            }).always(function(callback_obj, status, info_obj) {
console.log(callback_obj);
                if (status == 'success')
                {
                    var data = callback_obj;
                    var xhr = info_obj;
                    if (data.status == 'OK')
                    {
                        overlay_trigger.closest('.api_key_container').find('.api_key_alternate_name').html(post_value['alternate_name']);
                        overlay_trigger.closest('.api_key_container').find('.api_key_ip_restriction').html('');

                        for (var i=0;i<ip_restriction.length;i++)
                        {
                            $('<div />',{
                                'class':'general_style_inline_block'
                            }).html(ip_restriction[i]).appendTo(overlay_trigger.closest('.api_key_container').find('.api_key_ip_restriction'));
                        }
                        overlay_wrapper.fadeOut(500,function(){$(this).trigger('close');});
                    }
                    else
                    {
                        if (data.status == 'ZERO_RESULTS')
                        {
                            overlay_wrapper.fadeOut(500,function(){$(this).trigger('close');});
                            //ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message+' <a href="javascript:location.reload();">Refresh Page</a> to continue');
                        }
                        else
                        {
                            ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html(data.message);
                        }
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
    'overlay_wrapper_id':'api_key_update_form_overlay_wrapper',
    'close_on_click_wrapper':false
};

$('.api_key_button_add').click(function(event){
    event.preventDefault();

    var base_uri = $('base').attr('href');
    if (!base_uri) base_uri = '/';

    var ajax_info = $('.api_key_message_container');

    var post_value = {'remote_ip':$('input[name="remote_ip"]').val()};
    $.ajax({
        'type': 'POST',
        'url': base_uri + 'ajax/credential_add',
        'data': post_value,
        'timeout': 10000
    }).always(function(callback_obj, status, info_obj) {
        if (status == 'success')
        {
            var data = callback_obj;
            var xhr = info_obj;
            if (data.status == 'OK')
            {
                //$('.api_key_wrapper .api_key_container').not('.api_key_name_container').remove();

                var result_length = data.result.length;
                for(var i=0;i<result_length;i++)
                {
                    var row = data.result[i];
                    var api_key_container = $('<div />',{
                        'class':'api_key_container'
                    });
                    api_key_container.append($('<div />',{'class':'api_key_name'}).html(row['name']));
                    api_key_container.append($('<div />',{'class':'api_key_alternate_name'}).html(row['alternate_name']));
                    var api_ip_restriction_container = $('<div />',{'class':'api_key_ip_restriction'});
                    if (row['ip_restriction'])
                    {
                        var ip_restriction_length = row['ip_restriction'].length;
                        for(var j=0;j<ip_restriction_length;j++)
                        {
                            api_ip_restriction_container.append('<div class="general_style_inline_block">'+row['ip_restriction'][j]+'</div>');
                        }
                    }
                    api_key_container.append(api_ip_restriction_container);
                    var api_key_controller = $('<div />',{'class':'api_key_controller'});
                    var api_key_edit = $('<a />',{
                        'href':'javascript:void(0);',
                        'class':'api_key_button_edit'
                    }).html('&#xf040;<span class="api_key_controller_text"> Edit</span>').overlay_popup(update_credential_data).appendTo(api_key_controller);
                    var api_key_delete = $('<a />',{
                        'href':'javascript:void(0);',
                        'class':'api_key_button_delete'
                    }).html('&#xf00d;<span class="api_key_controller_text"> Delete</span>').overlay_popup(delete_credential_data).appendTo(api_key_controller);
                    api_key_container.append(api_key_controller);
                    api_key_edit.click();
                }
                api_key_container.appendTo('.api_key_wrapper');
                $('.api_key_wrapper').removeClass('api_key_wrapper_empty');
                ajax_info.removeClass('ajax_info_success').removeClass('ajax_info_error').html('');
            }
            else
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p><strong>'+data.status+': </strong>'+data.message+'</p>');
            }
        }
        else
        {
            var xhr = callback_obj;
            var error = info_obj;

            ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p><strong>'+status+': </strong>'+error+'</p>');

            /*if (status == 'timeout')
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p>Get Rating Page Failed, Try again later</p>');
            }
            else
            {
                ajax_info.removeClass('ajax_info_success').addClass('ajax_info_error').html('<p>Get Rating Page Failed, Error Unknown, Try again later</p>');
            }*/
        }
    });
 });

$('.api_key_button_edit').overlay_popup(update_credential_data);
$('.api_key_button_delete').overlay_popup(delete_credential_data);
