# MC ACF Flexible Template

## Description 

* [Article de présentation](https://mariecomet.fr/2018/01/04/gerez-flexibles-acf-librairie-modeles/) 🇫🇷
* [Blog post description](https://mariecomet.fr/2018/01/04/gerez-flexibles-acf-librairie-modeles/#english) 🇬🇧

This WordPress plugin makes it possible to save the ACF flexible content fields as templates and to use them again.

### Requirements

Advanced Custom Fields Pro 5.7 or greater has to be installed and activated. 


## Installation 

* Download the zip archive and upload it to your WordPress site.
* Install and activate Advanced Custom Fields Pro 5.7 or greater. 
* Activate the plugin.
* Export and import will be turned on by default for new flexible fields, if you want disable them, go in your flexible field settings.
* If your flexible field is registered via PHP, you must add `'mc_acf_ft_true_false'=> 1` in the options of this last one (after min, max and button_label options)
* Export and Import will show on your flexible fields.
* You can edit your templates in admin > ACF Templates.
* See screenshots.

## FAQ

### Export and import buttons don't show on my flexible field

* Sometime it's necessary to turn on / off option twice.

### How to define which type of user has access to these features?

* There is two filters you can use for that :
`mc_ft_import_cap` and `mc_ft_save_cap` which you must pass one or more WordPress capabilities.

### How to customize labels used in admin templates?

* Because Flexible fields can be very different things depending on the projects, you can use 4 filters to customize the labels used in admin, and also textdomain :
`mc_ft_template_plural`, `mc_ft_template_singular` and `mc_ft_template_all_menu_label`.
Examples of how to use them :
```
// singular label
function my_custom_template_sing_label( $singular_name ) {
    $singular_name = __( 'Custom singular', 'my-text-domain' );
    return $singular_name;
}
add_filter( 'mc_ft_template_singular', 'my_custom_template_sing_label', 10, 1 );

// plural label
function my_custom_template_plur_label( $plural_name ) {
    $plural_name = __( 'Custom plural','my-text-domain' );
    return $plural_name;
}
add_filter( 'mc_ft_template_plural', 'my_custom_template_plur_label', 10, 1 );

// the "all" menu label
function my_custom_template_menu_label( $all_menu_label ) {
    $all_menu_label = __( 'Custom "all" menu label', 'my-text-domain' );
    return $all_menu_label;
}
add_filter( 'mc_ft_template_all_menu_label', 'my_custom_template_menu_label', 10, 1 );
```

## Screenshots

* Flexible field setting
![Field setting](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-1.png "Field setting")

* Save flexible layouts as template
![Save template](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-2.png "Save template")

* Import flexible template
![Import template](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-3-1.png "Import template")

* Flexible template imported
![Template imported](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-3-2.png "Template imported")

* Flexibles templates list
![Flexibles templates list](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-4.png "Flexibles templates list")

* Edit existing template
![Edit existing template](https://github.com/MarieComet/MC-ACF-Flexible-Template/blob/master/screenshots/screenshot-5.png "Edit existing template")

### History

### 2017-12-23 1.0.1
* Initial Commit

### 2018-01-04 1.0.2
* Add edit templates functionnality
* Add filters to edit admin labels

### 2018-07-14 1.1.0
* Update for ACF 5.7.0 JS API changes
* Important : require at LEAST ACF 5.7.0

### 2018-11-01
* Add new feature "replace existing content" before import. Thanks @virgo79 !