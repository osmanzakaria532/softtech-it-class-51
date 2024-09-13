<?php 

    // This is for the show main menu abng akta id dewa chara menu save kora jabe na.
    register_nav_menu('main-menu', 'Main Menu');
    register_nav_menu('footer-menu', 'Footer Menu');


    add_action('after_setup_theme', 'first_theme_function');

    function first_theme_function(){
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');

        add_theme_support('custom-header');
        add_theme_support('custom-background');
    }

    add_action('wp_footer', function(){
        echo "show the last line";
    });


?>