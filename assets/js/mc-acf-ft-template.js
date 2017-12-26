jQuery(document).ready(function($){
    console.log('ready');
    // make sure acf is loaded, it should be, but just in case
    if (typeof acf == 'undefined') { return; }
    // add button for save templates
    /*var button = '<div class="acf-mc-ft-save-wrap">';
        button += '<div class="acf-mc-ft-save-success acf-success-message" style="display:none;"></div>';
        button += '<div class="acf-mc-ft-save-error acf-error-message" style="display:none;"></div>';
        button += '<div class="acf-mc-ft-input"><label for="mc_acf_template_name">'+mc_acf_ft.ft_label+'</label>';
        button += '<input type="text" class="acf-mc-ft-template-name" value="" name="mc_acf_template_name">';
        button += '<a href="#" class="acf-mc-ft-save acf-button button button-secondary">Save</a>';
        button += '</div></div>';

    $('.acf-field-flexible-content .values').each(function(index) {
        $(this).next().append( button );
    });*/

    var MC_ACF_Flexible_Template = acf.ajax.extend({

        events: {
            'click button.mc-acf-ft-open-import' : '_open_popup',
            'click button.acf-mc-ft-close' : '_close_popup',
            'change .acf-templates-select' : '_import_template',
            'click .acf-mc-ft-save' : '_save_template',
        },

        _open_popup : function(e) {

            e.preventDefault();
            e.stopImmediatePropagation();
            // vars
            var parentFlex = e.$el.closest('.acf-field-flexible-content');

            var popup = $( parentFlex.find('.acf-mc-import-content.popup') );

            if(popup.length) {
                $(popup).show();
                $(popup).addClass('-open');
            }
        },

        _close_popup : function(e) {

            e.preventDefault();
            e.stopImmediatePropagation();
            // vars
            var parentFlex = e.$el.closest('.acf-field-flexible-content');

            var popup = $( parentFlex.find('.acf-mc-import-content.popup') );

            if(popup.length) {
                $(popup).hide();
                $(popup).addClass('-close');
            }
        },
        // Import template
        _import_template : function(e) {
            e.preventDefault();

            var parentFlex = e.$el.closest('.acf-field-flexible-content');

            var parentValues = parentFlex.find('.values');

            var numberLayouts = parentValues.find('.layout').length;

            var error_div = parentFlex.find('.acf-mc-ft-import-error');
            var succes_div = parentFlex.find('.acf-mc-ft-import-success');

            $form = $('form#post');
            
            var selectedTemplate = e.$el.val();

            var data = {
                action      : 'mc_acf_import_template',
                acf_templates   : selectedTemplate,
                number_layout : numberLayouts
            };

            data = acf.prepare_for_ajax(data);

            // set busy
            acf.validation.busy = 1;

            // lock form
            acf.validation.toggle( $form, 'lock' );

            $.post({
                url: acf.get('ajaxurl'),
                type: 'post',
                data: data,
                dataType: 'json',
                action: 'mc_acf_import_template',
                success: function( json ) {

                    if(true === json.success) {
                        
                        $(error_div).hide();
                        $(succes_div).text( json.data.message ).show();
                        var layoutsHtml =  $(json.data.layouts);
                        // loop on layouts
                        $.each(layoutsHtml, function(key, value) {
                            // create object for use it later
                            var newItem = $(value);
                            // append to parent
                            $( parentValues ).append( newItem );
                            // this action set the field and render correctly tabs, etc.
                            acf.do_action('append', newItem);
                            // add -collapsed class, if not all new layouts will be opened
                            $(newItem).addClass('-collapsed just-added bg-green');
                            // remove the empty div
                            $(parentFlex).find('.no-value-message').hide();
                        });

                        setTimeout(function(){
                            $(succes_div).text( '' ).hide();
                            //$(parentValues).find('.layout').removeClass('bg-green');
                        }, 5000);

                    } else {
                        //console.log(json.data.message);
                        $(succes_div).hide();
                        $(error_div).text( json.data.message ).show();
                    }

                    // unlock so WP can publish form
                    acf.validation.busy = 0;
                    acf.validation.toggle( $form, 'unlock' );
                },
                error: function( json ) {
                    console.log(json);
                }
            });
        },

        // Save template
        _save_template : function(e) {
            e.preventDefault();

            var parentFlex = e.$el.closest('.acf-field-flexible-content');

            var parentGroupKey = parentFlex.attr( 'data-key' );

            var parentValues = parentFlex.find('.values');

            var template_name = parentFlex.find('.acf-mc-ft-template-name').val();

            var error_div = parentFlex.find('.acf-mc-ft-save-error');
            var succes_div = parentFlex.find('.acf-mc-ft-save-success');
            
            $form = $('form#post');

            acf.do_action('validation_begin');

            var data = acf.serialize(parentValues);
            
            // append AJAX action       
            data.action = 'mc_acf_ft_save_template';
            data.mc_acf_template_name = template_name;

            
            if(parentGroupKey.length) {
                data.mc_acf_parent_key = parentGroupKey;
            }

            data = acf.prepare_for_ajax(data);

            // set busy
            acf.validation.busy = 1;

            // lock form
            acf.validation.toggle( $form, 'lock' );
            //console.log(data);

            $.post({
                url: acf.get('ajaxurl'),
                type: 'post',
                data: data,
                dataType: 'json',
                action: 'mc_acf_ft_save_template',
                success: function( json ) {

                    if(true === json.success) {
                        $(error_div).hide();
                        $(succes_div).text( json.data.message ).show();
                        setTimeout(function(){
                            $(succes_div).text( '' ).hide();
                        }, 5000);
                    } else {
                        //console.log(json.data.message);
                        $(succes_div).hide();
                        $(error_div).text( json.data.message ).show();
                    }

                    // unlock so WP can publish form
                    acf.validation.busy = 0;
                    acf.validation.toggle( $form, 'unlock' );
                },
                error: function( json ) {
                    console.log('erreur');
                }
            });
        }
    });
});