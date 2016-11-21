/**
 * Created by User on 11/11/2016.
 */
$('.api_key_button_add').click(function(event){
    event.preventDefault();

    var base_uri = $('base').attr('href');
    if (!base_uri) base_uri = '/';

    var post_value = {};
    $.ajax({
        'type': 'POST',
        'url': base_uri + 'ajax/credential_add',
        //'headers':{
        //    'Auth-Key': '36ca-e750-31af-9f80-4018-0f31-b7eb-3f03'
        //},
        'data': post_value,
        'timeout': 10000
    }).always(function(callback_obj, status, info_obj) {
        console.log(status);
        if (status == 'success')
        {
            var data = callback_obj;
            var xhr = info_obj;

            if (data.status == 'ok')
            {
                $('.api_key_wrapper .api_key_container').not('.api_key_name_container').remove();

                data.result.each(function(row_index, row)
                {
                    var api_key_container = $('<div />',{
                        'class':'api_key_container'
                    });
                    api_key_container.append($('<div />',{'class':'api_key_name'}).html(row['name']));
                    api_key_container.append($('<div />',{'class':'api_key_alternate_name'}).html(row['alternate_name']));
                    if (row['ip_restriction'])
                    {
                        var api_ip_restriction_container = $('<div />',{'class':'api_key_ip_restriction'});
                        var ip_restriction_length = row['ip_restriction'].length;
                        for(var i=0;i<ip_restriction_length;i++)
                        {
                            api_ip_restriction_container.append('<div class="general_style_inline_block">'+row['ip_restriction'][i]+'</div>');
                        }
                        api_key_container.append(api_ip_restriction_container);
                    }
                    api_key_container.append($('<div />',{'class':'api_key_controller'}).html('<a href="javascript:void(0);" class="api_key_button_edit">&#xf040;<span class="api_key_controller_text"> Edit</span></a><a href="javascript:void(0);" class="api_key_button_delete">&#xf00d;<span class="api_key_controller_text"> Delete</span></a>'));

                });
            }

console.log(data);
console.log($('base').attr('href'));
        }
        else
        {
console.log(callback_obj);
console.log(info_obj);
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