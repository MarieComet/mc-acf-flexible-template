
jQuery(document).ready(function($){
    console.log('ready');
    // make sure acf is loaded, it should be, but just in case
    if (typeof acf == 'undefined') { return; }
    // add button for save templates
    var button = '<div class="acf-mc-ft-save-wrap">';
        button += '<div class="acf-mc-ft-save-success acf-success-message" style="display:none;"></div>';
        button += '<div class="acf-mc-ft-save-error acf-error-message" style="display:none;"></div>';
        button += '<div class="acf-mc-ft-input"><label for="mc_acf_template_name">'+mc_acf_ft.ft_label+'</label>';
        button += '<input type="text" class="acf-mc-ft-template-name" value="" name="mc_acf_template_name">';
        button += '<a href="#" class="acf-mc-ft-save acf-button button button-secondary">Save</a>';
        button += '</div></div>';

    $('.acf-field-flexible-content .values').next().append( button );

    // Import template
    $('.acf-templates-select').on('change', function(e) {
        e.preventDefault();
        console.log('clicked');
        var parentFlex = $( this ).parents('.acf-field-flexible-content');
        var parentValues = parentFlex.find('.values');
        var numberLayouts = parentValues.find('.layout').length;
        $form = $('form#post');
        
        var selectedTemplate = $(this).val();
        //console.log(selectedTemplate);
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
            console.log(json);
                if(true === json.success) {
                    
                    $('.acf-mc-ft-import-error').hide();
                    $('.acf-mc-ft-import-success').text( json.data.message ).show();
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
                    });

                    setTimeout(function(){
                        $('.acf-mc-ft-import-success').text( '' ).hide();
                        //$(parentValues).find('.layout').removeClass('bg-green');
                    }, 3000);

                } else {
                    //console.log(json.data.message);
                    $('.acf-mc-ft-import-success').hide();
                    $('.acf-mc-ft-import-error').text( json.data.message ).show();
                }

                // unlock so WP can publish form
                acf.validation.busy = 0;
                acf.validation.toggle( $form, 'unlock' );
            },
            error: function( json ) {
                console.log(json);
            }
        });
    });

    $('.acf-mc-ft-save').on('click', function(e){
        e.preventDefault();

        var parentFlex = $( this ).parents('.acf-field-flexible-content');
        var parentGroupKey = parentFlex.data( "key" );
        //var parent = $( this ).parents('.acf-mc-ft-save-wrap');
        var parentValues = parentFlex.find('.values');
        var template_name = parentFlex.find('.acf-mc-ft-template-name').val();
        
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
            console.log(data);
            // bail early if not json success
                //console.log(json);
                if(true === json.success) {
                    $('.acf-mc-ft-save-error').hide();
                    $('.acf-mc-ft-save-success').text( json.data.message ).show();
                    setTimeout(function(){
                        $('.acf-mc-ft-save-success').text( '' ).hide();
                    }, 3000);
                } else {
                    //console.log(json.data.message);
                    $('.acf-mc-ft-save-success').hide();
                    $('.acf-mc-ft-save-error').text( json.data.message ).show();
                }

                // unlock so WP can publish form
                acf.validation.busy = 0;
                acf.validation.toggle( $form, 'unlock' );
            },
            error: function( json ) {
                console.log('erreur');
            }
        });
    });
});