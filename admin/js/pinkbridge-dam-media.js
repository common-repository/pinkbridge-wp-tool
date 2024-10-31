jQuery(document).ready(function ($) {
    
    var tabName = 'DAM';
    var multi_img_selection = false;
    var form_nonce = customMedia.nonce;
    var img_selected_ids = {};

    if (wp.media) {
         wp.media.view.MediaFrame.Select.prototype.browseRouter = function (routerView) {
             routerView.set({
                upload: {
                    text: wp.media.view.l10n.uploadFilesTitle,
                    priority: 20
                },
                browse: {
                    text: wp.media.view.l10n.mediaLibraryTitle,
                    priority: 40
                },
                ptc_dam: {
                    text: tabName,
                    priority: 60
                }
            });
        };

        wp.media.view.Modal.prototype.on( "ready", function() {
            wp.media.view.Modal.prototype.on( "open", function(el) {
                
                multi_img_selection = false;
                dam_reset_image_selection();
                img_selected_ids = {};
                
                jQuery('.dam-media-wrapper').removeClass('ptc_dam_selected');
                jQuery( ".media-modal", this.el ).find('.media-menu a[href="#tab-upload"]').trigger('click');
                
                var active_frame = wp.media.frame || null;

                if(active_frame){
                    active_frame.content.mode('upload');
                    
                    var get_media_option = active_frame.content.view.options;
                    var get_state = get_media_option.state;

                    if(get_state == 'featured-image'){
                        multi_img_selection = false;
                    } else{
                        multi_img_selection = get_media_option.states && get_media_option.states[0] && get_media_option.states[0].attributes ?
                            get_media_option.states[0].attributes.multiple : get_media_option.multiple;
                    }
                }

                if (jQuery(document).find('.media-modal-content:visible .media-router .media-menu-item.active').text() === tabName) {
                    openDamTab(multi_img_selection);
                }
            });
        
            // Execute this code when a Modal is closed.
            wp.media.view.Modal.prototype.on( "close", function(el) {
                wp.media.frame.close();
            });
        });
    
        // Handle click event on media router
        jQuery(wp.media).on('click', '.media-router .media-menu-item', function (e) {
            jQuery('.dam-media-wrapper').removeClass('ptc_dam_selected');

            if (jQuery(e.target).text() === tabName) {
                img_selected_ids = {};
                openDamTab(multi_img_selection);
            }
        });
    }

     // Handle clicks on media frame content
    jQuery(document).on('click', '.media-frame-content .pinkbridge-dam-media-wrapper .pinkbridge-sidebar-wrapper li', function(e){
        e.stopPropagation(); 

        // code to toggle folders
        jQuery(this).toggleClass("active");
        jQuery(this).find('.active.has-child').children('.open-tree').slideUp();
        jQuery(this).find('.has-child').removeClass('active');
        jQuery(this).children(".open-tree").slideToggle(300);

        jQuery(document).find('.media-frame-content .pinkbridge-dam-media-wrapper .pinkbridge-right-wrapper').html('');
        var get_parent_id = jQuery(this).attr('data-id');
        jQuery(document).find('.pinkbridge-dam-content-outer').append('<div id="dam_overlay"></div>');

        jQuery.ajax({
            url: customMedia.ajaxurl,
            type: 'POST',
            data:{ 
                action: 'ptc_dam_media_content',
                parent_id: get_parent_id,
                nonce: form_nonce
            },
            success: function( result ){
                if(result && result.success === false){
                    var errhtml = result.data;
                    jQuery(document).find('.media-frame-content .pinkbridge-dam-media-wrapper .pinkbridge-right-wrapper').html(errhtml);
                    jQuery(document).find('.pinkbridge-dam-content-outer #dam_overlay').remove();
                } else{
                    jQuery(document).find('.media-frame-content .pinkbridge-dam-media-wrapper .pinkbridge-right-wrapper').html(result);
                    jQuery(document).find('.pinkbridge-dam-content-outer #dam_overlay').remove();
                }
            }
        });
    });

    // Handle clicks on media frame content
    jQuery(document).on('click', '.media-frame-content .pinkbridge-dam-media-wrapper .pinkbridge-dam-content-outer .pinkbridge-dam-content-wrapper .pinkbridge-right-content-wrapper .dam-media-right-bar img', function(e){
        jQuery('.media-modal-content').append('<div id="dam_overlay"></div>');

        var img_remove_add = true;
        var img_selection = jQuery(this).parents('.pinkbridge-dam-media-wrapper').attr('dam-selection');
        var active_frame = wp.media.frame || null;
        var selected_src = '';
        var img_id = '';
        var img_name = '';

        if(jQuery(this).parent().hasClass('ptc_dam_selected')){
            rem_img_id = jQuery(this).parent().attr('data-id');
            delete img_selected_ids[rem_img_id];
            jQuery(this).parent().removeClass('ptc_dam_selected');
            img_remove_add = false;
            image_Selection_function(img_selected_ids);
        } else{
            selected_src = jQuery(this).attr('data-src');
            img_id = jQuery(this).parent().attr('data-id');
            img_name = jQuery(this).parent().attr('data-name');
            img_type = jQuery(this).parent().attr('data-type');
            img_remove_add = true;
            if(img_selection == "true"){
            } else{
                jQuery('.dam-media-wrapper').removeClass('ptc_dam_selected');
                img_selected_ids = {};
            }
            jQuery(this).parent().addClass('ptc_dam_selected');
        }

        if (active_frame) {
            var _state = active_frame.content.view._state;
            var selection = active_frame.state() ? active_frame.state().get('selection') : null;

            if (selection && img_selected_ids.length === 0) {
                selection.reset();
            }

            if(img_id){
                jQuery.ajax({
                    url: customMedia.ajaxurl,
                    type: 'POST',
                    data:{ 
                        action: 'ptc_dam_save_img_url',
                        selected_src: selected_src,
                        img_id: img_id,
                        img_name: img_name,
                        img_type:img_type,
                        nonce: customMedia.nonce
                    },
                    success: function( result ){
                        if(result && result.success == false){
                            var err_msg = result.data;
                            remove_dam_overlay(true, jQuery(this));
                            alert(err_msg);
                        } else{
                            if(result.details){
                                if(result.status == 'success'){
                                    var selected_id = result.details.id;
                                    if (!(img_id in img_selected_ids)) {
                                        img_selected_ids[img_id] = {};
                                    }
                                    img_selected_ids[img_id]['id'] = selected_id;
                                    img_selected_ids[img_id]['src'] = selected_src;
                                    image_Selection_function(img_selected_ids);
                                    remove_dam_overlay(false);
                                } else {
                                    remove_dam_overlay(true, jQuery(this));
                                    alert(result.details.message);
                                }
                            } else{
                                remove_dam_overlay(true, jQuery(this));
                                var er_msg = result.message ? result.message : 'Not able to select current Image.'
                                alert(er_msg);
                            }
                        }
                    }, error: function (request, status, error) {
                        remove_dam_overlay(true, jQuery(this));
                        alert(request.responseText);
                    }
                }); 
            } else{
                remove_dam_overlay(true, jQuery(this));
            }
        } else{
            remove_dam_overlay(true, jQuery(this));
            alert('Something went wrong!! Please Contact to Administrator.');
        }
    });

    function image_Selection_function(img_selected_ids){

        var newArray = $.map(img_selected_ids, function (el) {
            return el.id !== '' ? el : null;
        });
        let active_frame = wp.media.frame || null;
        let details = img_selected_ids;
        
        if (active_frame) {
            let _state = active_frame.content.view._state;
            let selection = active_frame.state() ? active_frame.state().get('selection') : null;

            if (selection || img_selected_ids.length === 0) {
                selection.reset();
            }
        
            if(details){
                for (let i in details) {
                    if (active_frame) {
                        let attachment = wp.media.attachment(details[i]['id']);
                        attachment.set('url', details[i]['src']);

                        if (selection) {
                            selection.add(wp.media.attachment(details[i]['id']));
                        }

                        if (_state === 'library' || _state === 'edit-attachment') {
                            if (active_frame.content.get() && active_frame.content.get().collection) {
                                active_frame.content.get().collection._requery(true);
                            }
                            active_frame.trigger('library:selection:add');
                        } else {
                            wp.media.attachment(details[i]['id']).fetch();
                        }
                    }
                }
            }
        }
        remove_dam_overlay(false);
    }

    function remove_dam_overlay($class=false, $this=''){
        jQuery(document).find('.media-modal-content').children('#dam_overlay').remove();

        if($class == true){
            $this.parent().removeClass('ptc_dam_selected');
        }
    }

    // reset selection
    function dam_reset_image_selection(){

        if(wp.media){
            var active_frame = wp.media.frame || null;

            if (active_frame) {
                if(active_frame.state() !== undefined){
                    var selection = active_frame.state().get('selection') ? active_frame.state().get('selection') : '';
                    if (selection) {
                        selection.reset();
                    }
                }
            }
        }
    }

    // Function to open DAM tab
    function openDamTab() {
        dam_reset_image_selection();
        jQuery('.media-modal-content').append('<div id="dam_overlay"></div>');
        
        jQuery.ajax({
            url: customMedia.ajaxurl,
            type: 'POST',
            data:{ 
                action: 'ptc_dam_media',
                nonce:form_nonce,
                multi_img_selection: multi_img_selection
            },
            success: function( result ){

                if(result && result.success == false){
                    var err_msg = result.data;
                    jQuery(document).find('.media-modal-content:visible .media-frame-content').html(err_msg);
                    remove_dam_overlay(false);
                } else {
                    jQuery(document).find('.media-modal-content:visible .media-frame-content').html(result.data);
                    remove_dam_overlay(false);
                }

            }, error: function (request, status, error) {
                remove_dam_overlay(false);
                alert(request.responseText);
            }
        });
    }
    
});