<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- <title><?php bloginfo()?></title> -->

        <!-- dynamic title add process  -->
        <?php wp_head() ?>
        <link rel="stylesheet" href="style.css">
        <style>
            .banner-area img {
                width: 600px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body <?php body_class(); ?>>
        
        <nav>
            <?php
                // show the menu in frontend
                wp_nav_menu(
                    // decided to which menu is visible
                    array(
                        'theme_location' => 'main-menu',
                    )
                );
            ?>
        </nav>

        <h1>welcome to our website</h1>

        <header class="banner-area">
            <!-- <img src="<?php echo get_template_directory_urI() ?>/images/banner.jpg" alt=""> -->
            <img src="<?php header_image() ?>" alt="">
        </header>