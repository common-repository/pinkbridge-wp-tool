jQuery(document).ready(function() {
    var ajax_url = customMedia.ajaxurl;
    var admin_page_url = customMedia.admin_page_url;

    jQuery('.pinkbridge-login-outer .pinkbridge-login-section .ptc-dam-api-endpoint').on('click', '#ptc_dam_save_api', function(e){
        e.preventDefault();
        var $this = jQuery(this);
        $this.parent('form').find('.form-loader').show();
        $this.hide();
        var api_value = jQuery(this).parent('form').find('.pinkbridge_endpoint').val();
        var pinkbridge_api_nonce_field = jQuery(this).parent('form').find('#pinkbridge_api_nonce_field').val();
        
        if(api_value == '' || api_value == null){
            jQuery(this).parent('form').find('.error').text('Please Enter API Endpoint');
            jQuery(this).parent('form').find('.error').show();
            $this.parent('form').find('.form-loader').hide();
            $this.show();   
        } else{
            jQuery(this).parent('form').find('.error').hide();
             jQuery.ajax({
                url: ajax_url,
                type: 'POST',
                data:{ 
                    action: 'ptc_dam_store_api_endpoint',
                    pinkbridge_endpoint: api_value,
                    nonce: pinkbridge_api_nonce_field,
                    nonce_action: 'pinkbridge_api_nonce_action'
                },
                success: function( result ){
                    if(result && result.success == false){
                        var err_msg = result.data;
                        $this.parent('form').find('.error').text(err_msg);
                        $this.parent('form').find('.error').show();
                    } else {
                        if(result.status == 'success'){
                            window.location = admin_page_url;
                        } else{
                            var err_msg = result.message;
                            $this.parent('form').find('.error').text(err_msg);
                            $this.parent('form').find('.error').show();
                            $this.parent('form').find('.form-loader').hide();
                            $this.show();
                        }
                    }
                }, error: function (request, status, error) {
                    $this.parent('form').find('.form-loader').hide();
                    $this.show();
                    alert(request.responseText);
                }
            });
        }
    });

    jQuery('.pinkbridge-login-outer .pinkbridge-login-section .ptc-dam-login-form').on('click', '#ptc-authentication-login', function(e){
        e.preventDefault();
        var $this = jQuery(this);
        $this.parent('form').find('.form-loader').show();
        $this.hide();
        var email = jQuery(this).parent('form').find('.ptc-user-name').val();
        var password = jQuery(this).parent('form').find('.ptc-user-password').val();
        var pinkbridge_endpoint = jQuery(this).parent('form').find('.pinkbridge_endpoint').val();
        var pinkbridge_login_nonce_field = jQuery(this).parent('form').find('#pinkbridge_login_nonce_field').val();
        
        if(email == '' || email == null || password == '' || password == null){
            jQuery(this).parent('form').find('.error').html('<strong>ERROR: </strong>Please Enter Email and Password.');
            jQuery(this).parent('form').find('.error').show();
            $this.parent('form').find('.form-loader').hide();
            $this.show();
        } else{
            jQuery(this).parent('form').find('.error').hide();
             jQuery.ajax({
                url: ajax_url,
                type: 'POST',
                data:{ 
                    action: 'ptc_dam_store_login_data',
                    email: email,
                    password: password,
                    pinkbridge_endpoint: pinkbridge_endpoint,
                    nonce: pinkbridge_login_nonce_field,
                    nonce_action: 'pinkbridge_login_nonce_action'
                },
                success: function( result ){

                    if(result && result.success == false){
                        var err_msg = result.data;
                        $this.parent('form').find('.error').text(err_msg);
                        $this.parent('form').find('.error').show();
                    } else{
                        
                        if(result.status == 'success'){
                            window.location = admin_page_url;
                        } else{
                            var err_msg = result.message;
                            
                            $this.parent('form').find('.error').text(err_msg);
                            $this.parent('form').find('.error').show();
                            $this.parent('form').find('.form-loader').hide();
                            $this.show();
                        }
                    }
                }, error: function (request, status, error) {
                    $this.parent('form').find('.form-loader').hide();
                    $this.show();
                    alert(request.responseText);
                }
            });
        }
    });
});

