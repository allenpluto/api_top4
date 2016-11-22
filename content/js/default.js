// JavaScript Document

// jQuery Plugins
$.fn.ajax_loader = function(user_option) {
    var default_option = {};
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);
    if ($('head style').length == 0)
    {
        var style_tag = $('<style />');
        style_tag.appendTo('head');
    }
    else
    {
        var style_tag = $('head style:last');
    }

    return this.each(function() {

        var ajax_loader_container = $(this);
        ajax_loader_container.data('option',option);
        $('<div class="ajax_loader_bottom"><div class="ajax_loader_bottom_text_container"><span class="ajax_loader_bottom_icon"></span><span class="ajax_loader_bottom_text">Loading...</span></div></div>').appendTo(ajax_loader_container);

        $(window).scroll(function() {
            if($(window).scrollTop() + $(window).height() - $(document).height() > - 100) {
                var ajax_loader_option = ajax_loader_container.data('option');
//console.log(ajax_loader_container.data('option'));
//console.log(ajax_loader_container.data('option').page_count);
                if (!ajax_loader_container.hasClass('ajax_loader_container_complete') && !ajax_loader_container.hasClass('ajax_loader_container_loading')) {
                    ajax_loader_container.addClass('ajax_loader_container_loading');
                    var next_page_id_group = {};
                    var id_counter = 0;
                    for (var id_index in ajax_loader_container.data('option').id_group) {
                        id_counter++;
                        if (id_counter < ajax_loader_container.data('option').page_size * (ajax_loader_container.data('option').page_number+1)+1) continue;
                        if (id_counter >= ajax_loader_container.data('option').page_size * (ajax_loader_container.data('option').page_number+2)+1) break;

                        next_page_id_group[id_index] = ajax_loader_container.data('option').id_group[id_index];
                    }
                    var post_value =
                    {
                        'page_number': 0,
                        'id_group': next_page_id_group
                    };
                    if ($('.system_debug').length>0)
                    {
                        post_value['system_debug'] = true;
                    }
                    $.each(ajax_loader_container.data('option'), function (option_key, option_value) {
                        if (option_key != 'id_group' && option_key != 'page_number')
                        {
                            post_value[option_key] = option_value;
                        }
                    });
                    //console.log(post_value);
                    $.ajax({
                        'type': 'POST',
                        'url': 'listing/ajax-load',
                        'data': post_value,
                        'timeout': 10000
                    }).always(function (callback_obj, status, info_obj) {
                        ajax_loader_container.removeClass('ajax_loader_container_loading');
//console.log(status);
//console.log(callback_obj);
                        if (status == 'success') {
                            var data = callback_obj;
                            var xhr = info_obj;

                            if (typeof ajax_loader_container.data('option').data_encode_type !== 'undefined') {
                                var data_encode_type = ajax_loader_container.data('option').data_encode_type;
                                switch (data_encode_type) {
                                    case 'none':
                                        break;
                                    case 'base64':
                                    default:
                                        // unknown encode type default to base64
                                        data = atob(data);
                                }
                            }
                            data = $.parseJSON(data);
//console.log(data);

                            ajax_loader_container.append(data.html);
                            data.style.forEach(function(element, index){
                                if (element.type == 'text_content')
                                {
                                    style_tag.append(element.content);
//console.log(style_tag);
                                }
                            });
                            data.script.forEach(function(element, index){
                                if (element.type == 'text_content')
                                {
                                    $('body').append('<script type="text/javascript">'+element.content+'</script>>');
                                }
                            });
                            if ($('.system_debug').length>0)
                            {
                                if (typeof $('.system_debug').data('ajax_load_count') == 'undefined') $('.system_debug').data('ajax_load_count', 1);
                                else $('.system_debug').data('ajax_load_count', $('.system_debug').data('ajax_load_count')+1);
                                $('<div />',{
                                    'class':'system_debug_row container'
                                }).html('ajax load ('+$('.system_debug').data('ajax_load_count')+') content: '+JSON.stringify(data.system_debug)).appendTo('.system_debug');
                            }

                            ajax_loader_container.children('.clear').appendTo(ajax_loader_container);
                            ajax_loader_container.children('.ajax_loader_bottom').appendTo(ajax_loader_container);
                            ajax_loader_container.data('option').page_number++;
//console.log([ajax_loader_container.data('option').page_number,ajax_loader_container.data('option').page_count]);
                            if (ajax_loader_container.data('option').page_number >= ajax_loader_container.data('option').page_count-1)
                            {
                                ajax_loader_container.addClass('ajax_loader_container_complete');
                            }
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
                }
            }
        });


    });
};

$.fn.drop_file_uploader = function(user_option) {
    var default_option = {};
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

// Function Reference:
// http://stackoverflow.com/questions/10867506/dragleave-of-parent-element-fires-when-dragging-over-children-elements, retrieved on 27 Mar, 2014
// http://stackoverflow.com/questions/10253663/how-to-detect-the-dragleave-event-in-firefox-when-dragging-outside-the-window, retrieved on 27 Mar, 2014
    return this.each(function() {

        var self = $(this);
        var collection = $();

        self.on('dragenter', function(event) {
            if (collection.size() === 0) {
                self.trigger('drop_file_uploader_enter');
            }
            collection = collection.add(event.target);
        });

        self.on('dragleave', function(event) {
            /*
             * Firefox 3.6 fires the dragleave event on the previous element
             * before firing dragenter on the next one so we introduce a delay
             */
            setTimeout(function() {
                collection = collection.not(event.target);
                if (collection.size() === 0) {
                    self.trigger('drop_file_uploader_leave');
                }
            }, 1);
        });
    });
};

// Expandable Content
$.fn.expandable_content = function(user_option){
    var default_option = {
        'multi_expanded': 0,
        'focus_current': 0
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function(){
        var expand_parent = $(this);
        expand_parent.addClass('expand_parent');
        expand_parent.data(option);
        if (expand_parent.hasClass('expand_parent_multi'))
        {
            expand_parent.data('multi_expanded', 1);
        }
            
        expand_parent.children('.expand_trigger').click(function(event){
            event.preventDefault();
            var expand_parent = $(this).parent();
            var expand_wrapper = expand_parent.children('.expand_wrapper');
            if (expand_parent.hasClass('expand_parent_expanded'))
            {
                expand_wrapper.animate({
                    'height': 0
                },500,function(){
                    $(this).css('height','');
                    expand_parent.removeClass('expand_parent_expanded');
                });
            }
            else
            {
                if (!expand_parent.data('multi_expanded'))
                {
                    expand_parent.parent().children('.expand_parent_expanded').removeClass('expand_parent_expanded');
                }

                if (expand_parent.data('focus_current'))
                {
                    $('body').animate({
                        'scrollTop': Math.min($(window).scrollTop()+expand_wrapper.children('.expand_container').outerHeight(), expand_parent.position().top)
                    },500);
                }
                expand_wrapper.animate({
                    'height': expand_wrapper.children('.expand_container').outerHeight()
                },500,function(){
                    $(this).css('height','');
                    expand_parent.addClass('expand_parent_expanded');
                    if (typeof expand_parent.data('function_after_expand') === 'function')
                    {
                        expand_parent.data('function_after_expand')();
                    }
                    if (expand_parent.data('focus_current'))
                    {
                        $(window).scrollTop(Math.min($(window).scrollTop()+expand_wrapper.children('.expand_container').outerHeight(), expand_parent.position().top));
                    }
                });
            }
        });

        expand_parent.find('.expand_close').click(function(event){
            event.preventDefault();
            var expand_parent = $(this).parents('.expand_parent');
            $(this).closest('.expand_parent_expanded').children('.expand_trigger').click();
        });
    });
};

$.fn.form_inline_editor = function(user_option){
    var default_option = {
        'default_text': $(this).find('.form_inline_editor_input').attr('placeholder')
    }; // Need to fix here
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function() {
        var inline_editor = $(this);
        inline_editor.find('.form_inline_editor_text').click(function(){
            $(this).parent().addClass('form_inline_editor_edit_mode');
            $(this).parent().find('.form_inline_editor_input').focus().select();
        });

        inline_editor.find('.form_inline_editor_input').blur(function(){
            $(this).parent().removeClass('form_inline_editor_edit_mode');
            if ($(this).val())
            {
                $(this).parent().find('.form_inline_editor_text').html($(this).val());
            }
            else
            {
                $(this).parent().find('.form_inline_editor_text').html(option['default_text']);
            }
        });
    });
};

/*// Image Editor
$.fn.form_image_editor = function(user_option){
    var default_option = {
        'trigger': '.form_image_editor_trigger',
        'source': '.form_image_editor_source',
        'result': '.form_image_editor_result'
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function() {
        var image_editor = $(this);
        var image_source = '';
console.log('fe1');
        
        image_source = image_editor.find(option['source']).val();
        if (image_editor.data('source'))
        {
            image_source = image_editor.data('source');
        }
        if (!image_source)
        {
            image_source = '/images/no_image_found.jpg';
        }
        image_editor.find(option['trigger']).overlay_popup({
            'html_content': '<img src="'+image_source+'" />',
            'init_function':function(overlay_trigger){
console.log('fe2');
console.log(image_editor.find(option['source']));
            }
        });
    });
};*/

// Image Uploader
$.fn.form_image_uploader = function(user_option){
    var default_option = {
        'trigger': '.form_image_uploader_trigger',
        'result': '.form_image_uploader_result',
        'allow_delete': false,
        'delete_trigger': '.form_image_uploader_delete_trigger',
        'default_image': '/images/no_image_found.jpg',
        'shrink_large': false,
        'width': 200,
        'height': 200,
        'quality': 0.6
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function() {
        var image_uploader = $(this);
        var image_uploader_trigger = image_uploader.find(option['trigger']);
        var image_uploader_result = image_uploader.find(option['result']);
        var image_uploader_delete_trigger = null;
        if (option['allow_delete'])
        {
            image_uploader_delete_trigger = image_uploader.find(option['delete_trigger']);
            if(image_uploader_delete_trigger.length == 0)
            {
                image_uploader_delete_trigger = $('<a />',{
                    'href':'javascript:void(0);',
                    'class':'form_image_uploader_delete_trigger'
                });
                image_uploader_delete_trigger.appendTo(image_uploader);
            }
        }

        var source_image = $('<img />');
        var result_image = image_uploader_trigger.find('img');

        source_image[0].onload = function(){
            var source_image_width = source_image[0].width;
            var source_image_height = source_image[0].height;
            
            if (source_image_height <= 0)
            {
                console.log('source image height null error');
            }
            else
            {
                // Resize Image
                var source_image_ratio = source_image_width / source_image_height;
                var result_image_ratio = option['width'] / option['height'];

                if (source_image_ratio > result_image_ratio)
                {
                    if (source_image_height < option['height'] || option['shrink_large'])
                    {
                        source_image_height = option['height'];
                        source_image_width = source_image_ratio * source_image_height;
                    }                    
                }

                if (source_image_ratio <= result_image_ratio)
                {
                    if (source_image_width < option['width'] || option['shrink_large'])
                    {
                        source_image_width = option['width'];
                        source_image_height = source_image_width / source_image_ratio;
                    }
                }

                // Crop Image
                var temp_canvas = document.createElement('canvas');
                temp_canvas.width = option['width'];
                temp_canvas.height = option['height'];
                temp_ctx = temp_canvas.getContext('2d');

                temp_ctx.fillStyle = '#ffffff';
                temp_ctx.fillRect(0,0,option['width'],option['height']);
                temp_ctx.drawImage(source_image[0],(option['width']-source_image_width)/2,(option['height']-source_image_height)/2,source_image_width,source_image_height);

                // Apply Image
                result_image.attr('src',temp_canvas.toDataURL('image/jpeg',option['quality']));
                image_uploader_result.val(result_image.attr('src'));
                image_uploader.removeClass('form_image_uploader_container_empty');
            }
        };

        function read_file(file, call_back, option)
        {
            var error_message= '';

            if (typeof call_back === 'undefined')
            {
                call_back = function() {};
            }

            if (typeof option === 'undefined')
            {
                option = {};
            }

            // Check that there is a file uploaded
            if(file)
            {
                // Check file type
                if(file.type != 'image/jpeg' && file.type != 'image/png' && file.type != 'image/gif')
                {
                    error_message = 'Please upload a .jpg, .png or .gif file';
                }
                else
                {
                    // Check file size
                    if(file.size > 10485760)
                    {
                        error_message = 'Please upload an image smaller than 10MB';
                    }
                    else
                    {
                        var reader = new FileReader();

                        // When it's loaded, we'll assign the read section to a variable (bg_img);
                        reader.onload = function(event){
                            source_image[0].src = event.target.result;
                            if (typeof call_back === 'function')
                            {
                                if (option['call_back'])
                                {
                                    call_back(option['call_back']);
                                }
                                else
                                {
                                    call_back();
                                }
                            }                            
                        };
                
                        // Pass the reader the file to read and give us the DataURL
                        reader.readAsDataURL(file);
                    }
                }
            }
            else
            {
                error_message = 'Unable to read source file.';
            }
        };

        image_uploader_trigger.click(function(event)
        {
            event.preventDefault();
            var image_source = $('<input />',{
                'type': 'file'
            });
            image_source.hide().appendTo(image_uploader);

            // File Upload Functions
            image_source.change(function(event){
                var file = event.target.files[0];
                var data = {
                    'image_source': image_source
                };
                read_file(file, function(data){
                    data['image_source'].remove();
                }, {'call_back':data});
            });

            image_source.click();

        });

        image_uploader.on('drop', function(event){
            event.preventDefault();

            var file = event.originalEvent.dataTransfer.files[0];
            read_file(file);

        });

        if (option['allow_delete'])
        {
            image_uploader_delete_trigger.click(function(event)
            {
                result_image.attr('src',option['default_image']);
                image_uploader_result.val('_DELETE');
                image_uploader.addClass('form_image_uploader_container_empty');
            });    
        }
    });
};

// JS Selector
$.fn.form_js_selector = function(user_option){
    var default_option = {
        'max_select_allowed': 1,
        'min_select_required': 0
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function() {
        var js_select_container = $(this);
        js_select_container.data(option);

        js_select_container.find('.form_js_select_input').keydown(function(event){
            var js_select_drop_down = js_select_container.find('.form_js_select_drop_down');
            var chosen_item = js_select_drop_down.find('.form_js_select_drop_down_item_chosen');
            var active_list = js_select_drop_down.find('.form_js_select_drop_down_item:not(.form_js_select_drop_down_item_hidden):not(.form_js_select_drop_down_item_selected)');
            var active_list_length = active_list.length;
            var chosen_item_index = active_list.index(chosen_item);

            switch (event.which)
            {
            case 38:
                // Key Arrow Up Pressed
                event.preventDefault();
                if (chosen_item.length == 1)
                {
                    if (chosen_item_index <= 0)
                    {
                        chosen_item_index = 0;
                    }
                    else
                    {
                        chosen_item_index--;
                    }
                    chosen_item.removeClass('form_js_select_drop_down_item_chosen');
                    chosen_item = active_list.eq(chosen_item_index);
                    chosen_item.addClass('form_js_select_drop_down_item_chosen');
                    js_select_drop_down.scrollTop(chosen_item_index*chosen_item.outerHeight());
                }
                break;
            case 40:
                // Key Arrow Down Pressed
                event.preventDefault();
                if (chosen_item.length == 1)
                {
                    if (chosen_item_index >= active_list_length-1)
                    {
                        chosen_item_index = active_list_length-1;
                    }
                    else
                    {
                        chosen_item_index++;
                    }
                    chosen_item.removeClass('form_js_select_drop_down_item_chosen');
                    chosen_item = active_list.eq(chosen_item_index);
                    chosen_item.addClass('form_js_select_drop_down_item_chosen');
                    js_select_drop_down.scrollTop(chosen_item_index*chosen_item.outerHeight());
                }
                break;
            case 13:
                // Key Enter Pressed
                event.preventDefault();

                if (active_list_length <= 1)
                {
                    bln_hasMatch = 0;
                }
                chosen_item.addClass('form_js_select_drop_down_item_selected');
                chosen_item_index = 0;
                chosen_item.removeClass('form_js_select_drop_down_item_chosen');
                active_list = js_select_drop_down.find('.form_js_select_drop_down_item:not(.form_js_select_drop_down_item_hidden):not(.form_js_select_drop_down_item_selected)');
                chosen_item = active_list.eq(chosen_item_index);
                chosen_item.addClass('form_js_select_drop_down_item_chosen');

                $.fn.form_js_selector.add(chosen_item);
                break;    
            }
        });

        js_select_container.find('.form_js_select_input').keyup(function(event){
            var js_select_container = $(this).parents('.form_js_select_container');
            var js_select_input = $(this);
            var search_keyword = js_select_input.val().toLowerCase();
            var js_select_drop_down = js_select_container.find('.form_js_select_drop_down');
            var start_position = 0;
            var bln_hasMatch = 0;
            js_select_drop_down.find('.form_js_select_drop_down_item_chosen').each(function(){
                var start_position = $(this).text().toLowerCase().indexOf(search_keyword);
                if (start_position < 0)
                {
                    $(this).removeClass('form_js_select_drop_down_item_chosen');
                }
            });
            js_select_drop_down.find('li').each(function(){
                var start_position = $(this).text().toLowerCase().indexOf(search_keyword);
                var js_select_drop_down_item_html = $(this).text();
                $(this).html(js_select_drop_down_item_html);
                if (start_position >= 0)
                {
                    if (!$(this).hasClass('form_js_select_drop_down_item_selected'))
                    {
                        if (search_keyword.length > 0)
                        {
                            js_select_drop_down_item_html = js_select_drop_down_item_html.slice(0, start_position)+'<span class="high_light">'+js_select_drop_down_item_html.slice(start_position);
                            start_position += '<span class="high_light">'.length + search_keyword.length;
                            js_select_drop_down_item_html = js_select_drop_down_item_html.slice(0, start_position)+'</span>'+js_select_drop_down_item_html.slice(start_position);
                            $(this).html(js_select_drop_down_item_html);
                        }
                        $(this).removeClass('form_js_select_drop_down_item_hidden');
                        if (!bln_hasMatch)
                        {
                            if (js_select_drop_down.find('.form_js_select_drop_down_item_chosen').length <= 0)
                            {
                                $(this).addClass('form_js_select_drop_down_item_chosen');
                            }
                            bln_hasMatch = 1;
                        }
                    }
                }
                else
                {
                    $(this).addClass('form_js_select_drop_down_item_hidden');
                }
            });

            if (!js_select_container.hasClass('expand_parent_expanded'))
            {
                js_select_container.find('.form_js_select_drop_down_trigger').click();
            }

            if (bln_hasMatch)
            {
                js_select_drop_down.removeClass('form_js_select_drop_down_empty');
            }
            else
            {
                js_select_drop_down.addClass('form_js_select_drop_down_empty');
            }
        });

        js_select_container.find('.form_js_select_drop_down_item').hover(function(){
            if (!$(this).hasClass('form_js_select_drop_down_item_chosen'))
            {
                $(this).parent().find('.form_js_select_drop_down_item_chosen').removeClass('form_js_select_drop_down_item_chosen');
                $(this).addClass('form_js_select_drop_down_item_chosen');
            }
        });

        js_select_container.find('.form_js_select_drop_down_item').click(function(){

            $.fn.form_js_selector.add($(this));
        });

        js_select_container.find('.form_js_select_result_remove').click(function(){
            $.fn.form_js_selector.remove($(this).parent());
        });

        $.fn.form_js_selector.add = function (new_selected_item)
        {
            var active_list = new_selected_item.parent().find('.form_js_select_drop_down_item:not(.form_js_select_drop_down_item_hidden):not(.form_js_select_drop_down_item_selected)');
            var active_list_length = active_list.length;

            new_selected_item.addClass('form_js_select_drop_down_item_selected');

            if (active_list_length <= 1)
            {
                new_selected_item.parent().addClass('form_js_select_drop_down_empty');
            }

            var js_select_container = new_selected_item.parents('.form_js_select_container');
            var js_select_drop_down_item_selected = js_select_container.find('.form_js_select_drop_down_item_selected');
            var js_select_value = js_select_container.find('.form_js_select_value');
            var js_select_value_array = [];
            var js_result_container = js_select_container.find('.form_js_select_result_container');
            js_result_container.html('');

            js_select_drop_down_item_selected.each(function(){
                js_select_value_array.push($(this).attr('form_js_select_value'));
                var js_select_result = $('<div class="form_js_select_result" form_js_select_value="'+$(this).attr('form_js_select_value')+'"><div class="form_js_select_result_title">'+$(this).text()+'</div></div>').data('form_js_select_value',$(this).attr('form_js_select_value')).appendTo(js_result_container);
                var js_select_result_remove = $('<div />',{
                    'class':'form_js_select_result_remove',
                    'title':'Click to remove category'
                }).appendTo(js_select_result);
                $('<div class="clear"></div>').appendTo(js_select_result);
                js_select_result_remove.click(function(){
                    $.fn.form_js_selector.remove($(this).parent());
                });
            });

            js_select_value.val(js_select_value_array.join()).trigger('change');

            if (js_select_value_array.length >= js_select_container.data('max_select_allowed'))
            {
                js_select_container.addClass('form_js_select_container_full');
                js_select_container.removeClass('expand_parent_expanded');
            }
            else
            {
                js_select_container.removeClass('form_js_select_container_full');
            }
        };

        $.fn.form_js_selector.remove = function (item_to_remove)
        {
            var remove_value = item_to_remove.data('form_js_select_value');
            if (!remove_value)
            {
                remove_value = item_to_remove.attr('form_js_select_value');
            }
            var js_select_container = item_to_remove.parents('.form_js_select_container');
            var js_select_value = js_select_container.find('.form_js_select_value');
            var js_select_drop_down_item_selected = js_select_container.find('.form_js_select_drop_down_item_selected');

            js_select_drop_down_item_selected.each(function(){
                if ($(this).attr('form_js_select_value') == remove_value)
                {
                    $(this).removeClass('form_js_select_drop_down_item_selected');
                    $(this).parent().removeClass('form_js_select_drop_down_empty');
                }
            });

            var selected_values = js_select_value.val().split(',');
            var remove_value_index = selected_values.indexOf(remove_value);
            if (remove_value_index > -1)
            {
                selected_values.splice(remove_value_index, 1);
            }
            js_select_value.val(selected_values.join()).trigger('change');

            if (selected_values.length < js_select_container.data('max_select_allowed'))
            {
                js_select_container.removeClass('form_js_select_container_full');
            }

            item_to_remove.remove();
        };

        js_select_container.on('reset', function(event) {
            var js_select_drop_down_item = js_select_container.find('.form_js_select_drop_down_item');
            var js_select_value = js_select_container.find('.form_js_select_value');
            if (!js_select_value.length)
            {
                return false;
            }
            var selected_values = js_select_value.val().split(',');
            if (js_select_value.val() == '')
            {
                selected_values = [];
            }
            var js_result_container = js_select_container.find('.form_js_select_result_container');
            js_result_container.html('');
            js_select_drop_down_item.removeClass('form_js_select_drop_down_item_selected');

            js_select_drop_down_item.each(function(){
                if (selected_values.indexOf($(this).attr('form_js_select_value')) > -1)
                {
                    $(this).addClass('form_js_select_drop_down_item_selected');
                    var js_select_result = $('<div class="form_js_select_result" form_js_select_value="'+$(this).attr('form_js_select_value')+'"><div class="form_js_select_result_title">'+$(this).text()+'</div></div>').data('form_js_select_value',$(this).attr('form_js_select_value')).appendTo(js_result_container);
                    var js_select_result_remove = $('<div />',{
                        'class':'form_js_select_result_remove',
                        'title':'Click to remove category'
                    }).appendTo(js_select_result);
                    $('<div class="clear"></div>').appendTo(js_select_result);
                    js_select_result_remove.click(function(){
                        $.fn.form_js_selector.remove($(this).parent());
                    });
                }
            });

            if (selected_values.length >= js_select_container.data('max_select_allowed'))
            {
                js_select_container.addClass('form_js_select_container_full');
                js_select_container.removeClass('expand_parent_expanded');
            }
            else
            {
                js_select_container.removeClass('form_js_select_container_full');
            }

            js_select_container.removeClass('expand_parent_expanded');
        });

        // Init Js Selector on Load
        js_select_container.trigger('reset');
    });
};

// Textarea Counter
$.fn.form_textarea_counter = function(user_option){
    var default_option = {
        'text_counter_position':'end'
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function(){
        var textarea_container = $(this);
        var textarea = textarea_container.find('textarea');
        if ((textarea.length == 1) && (typeof textarea.attr('maxlength') !== 'undefined'))
        {
            var length_max = textarea.attr('maxlength');
            var text_counter = textarea_container.find('.form_textarea_counter');

            if( /Chrome/i.test(navigator.userAgent) )
            {
                textarea.removeAttr('maxlength');
            }

            if (text_counter.length < 1)
            {
                var current_length_max = length_max - textarea.val().length;
                if (current_length_max < 0)
                {
                    current_length_max = 0;
                    textarea.val(textarea.val().substr(0,length_max));
                }
                $('<div class="form_textarea_counter_container"><p><span class="form_textarea_counter">'+current_length_max+'</span> characters left (including spaces and line breaks)</p></div>').appendTo(textarea_container);
                text_counter = textarea_container.find('.form_textarea_counter');
            }

            textarea.keydown(function(event){
                var length_current = textarea.val().length;
                if (length_current >= length_max)
                {
                    // Allow Function Keys - delete, backspace, tab & escape
                    if ( event.keyCode != 46 && event.keyCode != 8 && event.keyCode != 9 && event.keyCode != 27)
                    {
                        // Allow Ctrl functions except Ctrl+V (paste)
                        if (event.ctrlKey !== true || event.keyCode == 86)
                        {
                            event.preventDefault();
                        }
                    }
                }
                else
                {
                    if (length_max - length_current < 0)
                    {
                        textarea.val(textarea.val().substr(0,length_max));
                        length_current = textarea.val().length;
                    }

                    text_counter.html(length_max - length_current);

                }
            });

            textarea.keyup(function(event){
                var length_current = textarea.val().length;

                if (length_max - length_current < 0)
                {
                    textarea.val(textarea.val().substring(0,length_max));
                    length_current = textarea.val().length;
                }

                text_counter.html(length_max - length_current);
            });
        }
    });
};

// Tool Tip
$.fn.form_tool_tip = function(user_option){
    var default_option = {
        'multi_display': 0,
        'auto_close_delay': 0
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function(){
        var tool_tip_wrapper = $(this);
        tool_tip_wrapper.find('.form_row_tip_mask').click(function(){
            if (!tool_tip_wrapper.hasClass('form_row_tip_display'))
            {
                if (option['multi_display'] != 1)
                {
                    $('.form_row_tip_wrapper').removeClass('form_row_tip_display');
                }
                tool_tip_wrapper.find('.form_row_tip_container').fadeIn(300,function(){
                    tool_tip_wrapper.addClass('form_row_tip_display');
                    $(this).attr('style','');
                    if (option['auto_close_delay'] > 0)
                    {
                        var tool_tip_container = $(this);
                        setTimeout(function(){
                            tool_tip_container.fadeOut(300,function(){
                                tool_tip_wrapper.removeClass('form_row_tip_display');
                                $(this).attr('style','');
                            });
                        },option['auto_close_delay']);

                    }
                });
            }
            else
            {
                tool_tip_wrapper.find('.form_row_tip_container').fadeOut(300,function(){
                    tool_tip_wrapper.removeClass('form_row_tip_display');
                    $(this).attr('style','');
                });
            }
        });

        tool_tip_wrapper.find('.form_row_close').click(function(){
            tool_tip_wrapper.find('.form_row_tip_container').fadeOut(500,function(){
                tool_tip_wrapper.removeClass('form_row_tip_display');
                $(this).attr('style','');
            });
        });
    });
};

$.fn.gallery_popup = function(user_option){
    var default_option = {
        'parent':$('body'),
        'navigation_type':'loop',
        'close_button':true,
        'close_on_click_wrapper':true
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    if (typeof option['init_function'] !== 'function')
    {
        option['init_function'] = function() {};
    }

    var gallery_group = $(this);

    return this.each(function() {
        var gallery_trigger = $(this);

        // Create Overlay Wrapper - Append to the <body> by default
        var overlay_wrapper = $('<div />',{
            'id': 'overlay_wrapper_gallery',
            'class': 'overlay_wrapper wrapper'
        });
        var overlay_container = $('<div />',{
            'id': 'overlay_container_gallery',
            'class': 'overlay_container overlay_container_loading'
        }).appendTo(overlay_wrapper);
        var gallery_image_container = $('<div />',{
            'id': 'overlay_gallery_image_conatiner'
        }).appendTo(overlay_container);
        var overlay_mask =     $('<div />', {
            'class': 'overlay_mask'
        }).appendTo(overlay_container);

        if (option['close_button'])
        {
            $('<div />', {
                'class': 'overlay_close'
            }).appendTo(overlay_container);
        }

        option['init_function'](gallery_trigger,overlay_wrapper);

        if (!gallery_trigger.data('gallery_image_src'))
        {
            if (gallery_trigger.find('.gallery_image_large').lenght>0)
            {
                gallery_trigger.data('gallery_image_src',gallery_trigger.find('.gallery_image_large:first').attr('src'));
            }
            else
            {
                gallery_trigger.data('gallery_image_src',gallery_trigger.find('img:first').attr('src'));
            }
        }

        gallery_trigger.click(function(event){
            event.preventDefault();

            var gallery_trigger = $(this);        // localise gallery_trigger

            var gallery_trigger_index = gallery_group.index(gallery_trigger);
            var gallery_group_length = gallery_group.length;

            // Remove Other Overlay in same Parent Container if exists
            if (option['parent'].find('.overlay_wrapper').length > 0)
            {
                option['parent'].find('.overlay_wrapper').remove();
            }

            gallery_image_container.html('');
            var overlay_gallery_image = $('<img />',{
                'src': '/images/bg_transparent.png'
            }).appendTo(gallery_image_container);

            overlay_wrapper.hide().appendTo(option['parent']);

            overlay_wrapper.fadeIn(500,function(){
                var current_image = new Image();
                var canvas_width = Math.max(360,$(window).width()*0.8);
                var canvas_height = Math.max(270,($(window).height()-$('#top_wrapper').height())*0.9);
                var canvas_ratio = canvas_width / canvas_height;

                if (gallery_trigger.data('gallery_image_src'))
                {
                    current_image.src = gallery_trigger.data('gallery_image_src');
                }
                else
                {
                    current_image.src = gallery_trigger.find('img:first');
                }

                current_image.onload = function()
                {
                    if (current_image.src == overlay_gallery_image.attr('src'))
                    {
                        return false;
                    }

                    var image_width = Math.max(1, current_image.width);
                    var image_height = Math.max(1, current_image.height);
                    var image_ratio = image_width / image_height;

                    if (image_ratio > canvas_ratio)
                    {
                        image_height = image_height / image_width * canvas_width;
                        image_width = canvas_width;
                    }
                    else
                    {
                        image_width = image_width / image_height * canvas_height;
                        image_height = canvas_height;
                    }

                    var image_area_limit = Math.sqrt((image_width * image_height) / 360000);

                    if (image_area_limit > 1)
                    {
                        image_width = image_width / image_area_limit;
                        image_height = image_height /image_area_limit;
                    }

                    overlay_container.addClass('overlay_container_loading');
                    overlay_container.find('img').fadeOut(200,function(){
                        overlay_container.animate({
                            'width':image_width,
                            'height':image_height,
                            'marginTop':Math.max(0,($(window).height()+$('#top_wrapper').height()-image_height)/2)
                        },500,function(){
                            overlay_gallery_image.attr('src', current_image.src).fadeIn(200);
                            overlay_container.removeClass('overlay_container_loading');
                        });
                    });


                    
                    //overlay_container.append('<img src="'+current_image.src+'" />').removeClass('overlay_container_loading');

                };


                if (gallery_group_length > 1)
                {
                    var overlay_gallery_navigation_previous = $('<div />',{
                        'id': 'overlay_gallery_navigation_previous',
                        'class': 'overlay_gallery_navigation overlay_gallery_navigation_disabled'
                    }).appendTo(overlay_container);
                    var overlay_gallery_navigation_next = $('<div />',{
                        'id': 'overlay_gallery_navigation_next',
                        'class': 'overlay_gallery_navigation overlay_gallery_navigation_disabled'
                    }).appendTo(overlay_container);

                    if (gallery_trigger_index < (gallery_group_length-1) || option['navigation_type'] == 'loop')
                    {
                        overlay_gallery_navigation_next.removeClass('overlay_gallery_navigation_disabled');
                    }
                    if (gallery_trigger_index > 0 || option['navigation_type'] == 'loop')
                    {
                        overlay_gallery_navigation_previous.removeClass('overlay_gallery_navigation_disabled');
                    }

                    overlay_gallery_navigation_previous.click(function(event){
                        event.preventDefault();
                        overlay_gallery_navigation_next.removeClass('overlay_gallery_navigation_disabled');

                        if (gallery_trigger_index <= 0)
                        {
                            gallery_trigger_index = gallery_group_length-1;
                        }
                        else
                        {
                            gallery_trigger_index = gallery_trigger_index-1;
                            if (gallery_trigger_index <= 0 && option['navigation_type'] != 'loop')
                            {
                                overlay_gallery_navigation_previous.addClass('overlay_gallery_navigation_disabled');
                            }
                        }
                        gallery_trigger = gallery_group.eq(gallery_trigger_index);
                        current_image.src = gallery_trigger.data('gallery_image_src');
                    });
                    overlay_gallery_navigation_next.click(function(event){
                        event.preventDefault();
                        overlay_gallery_navigation_previous.removeClass('overlay_gallery_navigation_disabled');

                        if (gallery_trigger_index >= gallery_group_length-1)
                        {
                            gallery_trigger_index = 0;
                        }
                        else
                        {
                            gallery_trigger_index = gallery_trigger_index+1;
                            if (gallery_trigger_index >= (gallery_group_length-1) && option['navigation_type'] != 'loop')
                            {
                                overlay_gallery_navigation_next.addClass('overlay_gallery_navigation_disabled');
                            }
                        }
                        gallery_trigger = gallery_group.eq(gallery_trigger_index);
                        current_image.src = gallery_trigger.data('gallery_image_src');
                    });
                }

                overlay_wrapper.bind('close', function(){
                    $(this).fadeOut(500,function(){
                        overlay_gallery_image.attr('src','/images/bg_transparent.png');
                        $(this).css({
                            'width':'',
                            'height':'',
                            'marginTop':''
                        }).remove();
                    });
                    
                    $(window).unbind('keydown');
                });

                overlay_wrapper.click(function(event){
                    if ($(event.target).is($(this)) && (option['close_on_click_wrapper'] == true))
                    {
                        $(this).trigger('close');
                    }
                });

                overlay_wrapper.find('.overlay_close').click(function(){
                    $(this).trigger('close');
                });

                $(window).keydown(function(event) {
                    event.preventDefault();

                    switch(event.which)
                    {
                        case 37:    // Key Left
                            overlay_gallery_navigation_previous.click();
                            break;
                        case 38:    // Key Up
                            overlay_gallery_navigation_previous.click();
                            break;
                        case 39:    // Key Right
                            overlay_gallery_navigation_next.click();
                            break;
                        case 40:    // Key Down
                            overlay_gallery_navigation_next.click();
                            break;
                        case 27:    // Key Esc
                            overlay_wrapper.trigger('close');
                            break;
                    }
                });
            });
        });
    });
};

$.fn.overlay_popup = function(user_option){
    var default_option = {
        'parent':$('body'),
        'overlay_wrapper_id':'',
        'overlay_container_id':'',
        'overlay_wrapper_extra_class':'wrapper',
        'overlay_container_extra_class':'container',
        'load_type':'iframe',
        'close_button':true,
        'close_on_click_wrapper':true
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    if (option['html_content'])
    {
        option['load_type'] = 'html_content';
    }

    if (typeof option['init_function'] !== 'function')
    {
        option['init_function'] = function() {};
    }

    return this.each(function() {
        var overlay_trigger = $(this);

        if (!option['html_content'] && !option['html_datasrc'])
        {
            option['html_datasrc'] = overlay_trigger.attr('href');
        }

        // Create Overlay Wrapper - Append to the <body> by default
        var overlay_wrapper = $('<div />',{
            'id': option['overlay_wrapper_id'],
            'class': 'overlay_wrapper '+option['overlay_wrapper_extra_class']
        });
        var overlay_container = $('<div />',{
            'id': option['overlay_container_id'],
            'class': 'overlay_container '+option['overlay_container_extra_class']
        });

        overlay_trigger.click(function(event){
            event.preventDefault();

            // Remove Other Overlay in same Parent Container if exists
            if (option['parent'].find('.overlay_wrapper').length > 0)
            {
                option['parent'].find('.overlay_wrapper').remove();
            }

            overlay_container.html('');

            overlay_wrapper.append(overlay_container).hide().appendTo(option['parent']);

            overlay_wrapper.fadeIn(500,function(){
                switch (option['load_type'])
                {
                case 'html_content':
                    overlay_container.html(option['html_content']);
                    option['init_function'](overlay_trigger,overlay_wrapper);
                    break;
                case 'html_datasrc':
                    overlay_container.load(option['html_datasrc'],function(){
                        option['init_function'](overlay_trigger,overlay_wrapper);
                    });
                    break;
                default:
                    $('<iframe />',  {
                        'src':option['html_datasrc']
                    }).load(function(){
                        option['init_function'](overlay_trigger,overlay_wrapper);
                        $(this).css('height',$(this).contents().find('body').height())
                    }).appendTo(overlay_container);
                }

                if (option['title'])
                {
                    $('<div />', {
                        'class': 'overlay_title'
                    }).html(option['title']).prependTo(overlay_container);
                }

                if (option['close_button'])
                {
                    $('<div />', {
                        'class': 'overlay_close'
                    }).appendTo(overlay_container);
                }

                overlay_wrapper.click(function(event){
                    if ($(event.target).is($(this)) && (option['close_on_click_wrapper'] == true))
                    {
                        $(this).fadeOut(500,function(){$(this).remove();});
                    }
                });

                overlay_wrapper.find('.overlay_close').click(function(){
                    overlay_wrapper.fadeOut(500,function(){$(this).remove();});
                });
            });
        });
    });
};

// Mobile Touch Slider
$.fn.touch_slider = function(user_option){
    var default_option = {
        'min_trigger_offset': 0.3,
        'display_count': true,
        'navigation_arrow': true
    };
    // Extend our default option with user provided.
    var option = $.extend(default_option, user_option);

    return this.each(function(){
        var slider_container = $(this);
        var slider_item = $(this).find('.touch_slider_item');
        if (slider_item.length > 1)
        {
            slider_container.data('option', option);
            slider_container.data('count_total', slider_item.length);
            slider_container.data('count_current', 1);
            var slider_item_first_clone = slider_item.first().clone();
            slider_item_first_clone.attr('id',slider_item_first_clone.attr('id')+'_clone').appendTo(slider_container);
            var slider_item_last_clone = slider_item.last().clone();
            slider_item_last_clone.attr('id',slider_item_last_clone.attr('id')+'_clone').insertBefore(slider_item.first());
            slider_container.css('text-indent','-100%');
            slider_container.data('touch_start_x',0);
            slider_container.data('touch_offset_x',0);
            slider_container.data('current_event','');

            slider_container.on('set_current',function(event,new_count_current) {
                var slider_container = $(this);

                if (new_count_current > slider_container.data('count_total'))
                {
                    new_count_current -= slider_container.data('count_total');
                }
                if (new_count_current < 1)
                {
                    new_count_current += slider_container.data('count_total');
                }
                slider_container.data('count_current', new_count_current);
                slider_container.css('text-indent',-100*slider_container.data('count_current')+'%');
                if (slider_container.data('option').display_count === true)
                {
                    slider_container.find('.touch_slider_image_counter_container').html(slider_container.data('count_current')+' / '+slider_container.data('count_total'));
                }
            });

            slider_container.bind('touchstart',function(event){
                if (!slider_container.data('current_event'))
                {
                    var touchobj = event.originalEvent.changedTouches[0];
                    slider_container.data('touch_start_x',parseInt(touchobj.clientX));
                    slider_container.data('touch_start_y',parseInt(touchobj.clientY));
                    slider_container.data('current_event','touch_slider');
                }
            });
            slider_container.bind('touchmove',function(event){
                var touchobj = event.originalEvent.changedTouches[0];
                var touch_offset_x = parseInt(touchobj.clientX) - slider_container.data('touch_start_x');
                var touch_offset_y = parseInt(touchobj.clientY) - slider_container.data('touch_start_y');
                if (Math.abs(touch_offset_x) > 5)
                {
                    event.preventDefault();
                    slider_container.data('current_event','move_slider');
                }
                if (slider_container.data('current_event') == 'move_slider')
                {
                    event.preventDefault();
                    if (Math.abs(touch_offset_x/slider_container.width()) > 1)
                    {
                        if (touch_offset_x > 0)
                        {
                            slider_container.data('touch_start_x', slider_container.data('touch_start_x') + slider_container.width());
                            slider_container.data('touch_offset_x', slider_container.data('touch_offset_x') - slider_container.width());
                            slider_container.find('.touch_slider_item:first').css('marginLeft',slider_container.data('touch_offset_x')+'px');
                            slider_container.trigger('set_current',[slider_container.data('count_current')-1]);
                        }
                        else
                        {
                            slider_container.data('touch_start_x', slider_container.data('touch_start_x') - slider_container.width());
                            slider_container.data('touch_offset_x', slider_container.data('touch_offset_x') + slider_container.width());
                            slider_container.find('.touch_slider_item:first').css('marginLeft',slider_container.data('touch_offset_x')+'px');
                            slider_container.trigger('set_current',[slider_container.data('count_current')+1]);
                        }
                    }
                    slider_container.data('touch_offset_x',parseInt(touchobj.clientX) - slider_container.data('touch_start_x'));
                    slider_container.find('.touch_slider_item:first').css('marginLeft',slider_container.data('touch_offset_x')+'px');

                }
            });
            slider_container.bind('touchend',function(event){
                if (slider_container.data('current_event') == 'move_slider')
                {
                    event.preventDefault();

                    if (Math.abs(slider_container.data('touch_offset_x')/slider_container.width()) > slider_container.data('option').min_trigger_offset)
                    {
                        if (slider_container.data('touch_offset_x') > 0)
                        {
                            slider_container.trigger('set_current',[slider_container.data('count_current')-1]);
                        }
                        else
                        {
                            slider_container.trigger('set_current',[slider_container.data('count_current')+1]);
                        }
                    }
                    slider_item = $(this).find('.touch_slider_item:first').attr('style','');
                    slider_container.data('touch_start_x', 0);
                    slider_container.data('touch_offset_x', 0);
                    slider_container.data('current_event','');
                }
            });

            if (slider_container.data('option').navigation_arrow === true)
            {
                var navigation_previous = $('<div />', {
                    'class': 'touch_slider_navigation_button touch_slider_navigation_button_previous'
                });
                var navigation_next = $('<div />', {
                    'class': 'touch_slider_navigation_button touch_slider_navigation_button_next'
                });

                navigation_previous.appendTo(slider_container);
                navigation_previous.bind('touchstart mousedown',function(event){
                    event.preventDefault();
                    var slider_container = $(this).closest('.touch_slider_container');
                    if (!slider_container.data('current_event'))
                    {
                        slider_container.data('current_event','navigation_previous_touch');
                        $(this).addClass('touch_slider_navigation_button_pressed');
                    }
                });
                navigation_previous.bind('touchend mouseup',function(event){
                    event.preventDefault();
                    var slider_container = $(this).closest('.touch_slider_container');
                    if (slider_container.data('current_event') == 'navigation_previous_touch')
                    {
                        $(this).removeClass('touch_slider_navigation_button_pressed');
                        slider_container.data('touch_start_x', 0);
                        slider_container.data('touch_offset_x', 0);
                        slider_container.trigger('set_current',[slider_container.data('count_current')-1]);
                        slider_container.data('current_event','');
                    }
                });

                navigation_next.appendTo(slider_container);
                navigation_next.bind('touchstart mousedown',function(event){
                    event.preventDefault();
                    var slider_container = $(this).closest('.touch_slider_container');
                    if (!slider_container.data('current_event'))
                    {
                        slider_container.data('current_event', 'navigation_next_touch');
                        $(this).addClass('touch_slider_navigation_button_pressed');
                    }
                });
                navigation_next.bind('touchend mouseup',function(event){
                    event.preventDefault();
                    var slider_container = $(this).closest('.touch_slider_container');
                    if (slider_container.data('current_event') == 'navigation_next_touch')
                    {
                        $(this).removeClass('touch_slider_navigation_button_pressed');
                        slider_container.data('touch_start_x', 0);
                        slider_container.data('touch_offset_x', 0);
                        slider_container.trigger('set_current',[slider_container.data('count_current')+1]);
                        slider_container.data('current_event','');
                    }
                });
            }

            if (slider_container.data('option').display_count === true)
            {
                var image_counter = $('<div />', {
                    'class': 'touch_slider_image_counter_container'
                }).html(slider_container.data('count_current')+' / '+slider_container.data('count_total'));
                image_counter.appendTo(slider_container);
            }

        }
    });
};

// Javascript Functions
function FrameOnload(){
    $('.off_canvas_trigger').click(function(event){
        event.preventDefault();
        
        $('#off_canvas_wrapper').toggleClass('off_canvas_expand');
    });
    $('.off_canvas_halt').click(function(event){
        event.preventDefault();
        
        $('#off_canvas_wrapper').removeClass('off_canvas_expand');
    });

    $('.expand_parent').expandable_content();
    $('.listing_detail_view_section_wrapper').expandable_content({
        'multi_expanded':1,
        'focus_current':1
    });

    $('.search_trigger').click(function(event){
        event.preventDefault();
        
        $('#search_wrapper').toggleClass('search_expand');
    });
    $('.search_halt').click(function(event){
        event.preventDefault();
        
        $('#search_wrapper').removeClass('search_expand');
    });

    $('#search_keyword').keydown(function(event){
        if (event.which == 13)
        {
            $('#search_location').focus();
        }
    });

    $('#search_location').keydown(function(event){
        if (event.which == 13)
        {
            //$('#search_submit').click();
        }
    });

    $('#search_submit').click(function(event){
        event.preventDefault();

        var search_page_url = 'listing/search/';
        search_page_url += encodeURIComponent(encodeURIComponent($('input[name="keyword"]').val()));
        if ($('input[name="location"]').val())
        {
            search_page_url += '/where/'+encodeURIComponent(encodeURIComponent($('input[name="location"]').val()));
        }
        window.location.href = search_page_url;
    });

    $('.touch_slider_container').touch_slider();

    /*var temp = 0;
    $(window).scroll(function() {
        if($(window).scrollTop() + $(window).height() - $(document).height() > - 100) {
            if (Math.abs(temp - ($(window).scrollTop() + $(window).height())) > 20)
            {
                //console.log($('.listing_block_wrapper').data('id_group'));
                console.log($('.listing_block_wrapper').data('page_number'));
                if ($('.listing_block_wrapper').data('page_number') < $('.listing_block_wrapper').data('page_count'))
                {
                    var post_value = {
                        'id_group':$('.listing_block_wrapper').data('id_group'),
                        'page_size':$('.listing_block_wrapper').data('page_size'),
                        'page_number':$('.listing_block_wrapper').data('page_number')+1
                    };
                    $.ajax({
                        'type': 'POST',
                        'url': 'listing/ajax_load',
                        'data': post_value,
                        'timeout': 10000
                        /*'success': function(result_string) {
                            $('.listing_block_wrapper').append(result_string);
                            $('.listing_block_wrapper').data('page_number',  $('.listing_block_wrapper').data('page_number')+1);
                        },
                        'error': function(request, status, error) {
                            if (status == 'timeout')
                            {
                                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Try again later</p>');
                            }
                            else
                            {
                                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Error Unknown, Try again later</p>');
                            }
                        },
                        'complete': function(url, options)
                        {

                            temp = $(window).scrollTop() + $(window).height();
                            console.log(temp);
                            $('.system_debug').html(temp);

                        }*//*
                    }).always(function(callback_obj, status, info_obj) {
console.log(status);
                        if (status == 'success')
                        {
                            var data = callback_obj;
                            var xhr = info_obj;

                            $('.listing_block_wrapper').append(callback_obj);
                            $('.listing_block_wrapper').data('page_number',  $('.listing_block_wrapper').data('page_number')+1);
                        }
                        else
                        {
                            var xhr = callback_obj;
                            var error = info_obj;

                            if (status == 'timeout')
                            {
                                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Try again later</p>');
                            }
                            else
                            {
                                overlay_info.removeClass('overlay_info_success').addClass('overlay_info_error').html('<p>Get Rating Page Failed, Error Unknown, Try again later</p>');
                            }
                        }

                        temp = $(window).scrollTop() + $(window).height();
                        console.log(temp);
                        $('.system_debug').html(temp);
                    });
                }

                temp = $(window).scrollTop() + $(window).height();
                console.log(temp);
                $('.system_debug').html(temp);
            }
        }
    });*/
}
function BodyOnload(){
}

// Window Load/Resize Functions

// Document Ready Functions
$(document).ready(function(){
    FrameOnload();
    BodyOnload();
});