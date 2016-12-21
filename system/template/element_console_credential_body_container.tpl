            <div class="container body_container [[*class_extra]]">
                <div class="content_title"><h2>[[*page_title]]</h2></div>
                <div class="content_body">
                    <h3><strong>API Keys</strong></h3>
                    <div class="api_key_controller api_key_button_add_container"><a href="javascript:void(0)" class="api_key_button_add general_style_input_button general_style_input_button_orange">Create Credential</a></div>
                    <div class="api_key_hidden_container"><input name="remote_ip" type="hidden" value="[[*remote_ip]]" ></div>
                    <div class="api_key_message_container ajax_info">[[*ajax_info]]</div>
                    <div class="api_key_wrapper [[*api_key_wrapper_class_extra]]">
                        <div class="api_key_container api_key_name_container"><!--
                            --><div class="api_key_name">Key</div><!--
                            --><div class="api_key_alternate_name">Name</div><!--
                            --><div class="api_key_ip_restriction">IP Restriction</div><!--
                            --><div class="api_key_controller"></div><!--
                        --></div>
                        [[*api_key:template=`element_api_key_container`]]
                    </div>
                </div>
            </div>