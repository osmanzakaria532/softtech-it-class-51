<!DOCTYPE html>
<html <?php language_attributes(); ?> > <!-- dynamically add language -->
  <head>
    <meta charset="utf-8" />
    <!-- get_template_directory_uri(); -->
    <!-- <link rel="stylesheet" type="text/css" href="style.css" />  -->
    <!-- added stylesheet dynamically in functions.php -->

    <!-- <title>An Ocean of Sky</title> -->
    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <!-- <ul id="page">
      <li class="topNaviagationLink">
        <a href="#">Home</a>
      </li>
      <li class="topNaviagationLink">
        <a href="#">About</a>
      </li>
      <li class="topNaviagationLink">
        <a href="#">Portfolio</a>
      </li>
      <li class="topNaviagationLink">
        <a href="#">Services</a>
      </li>
      <li class="topNaviagationLink">
        <a href="#">Contact</a>
      </li>
    </ul> -->

    <?php

      wp_nav_menu(array(
        'theme-location' => 'main-menu',
        'menu_id' => 'page',
        'walker' => new Custom_Walker_Nav_Menu(),
      ));


    ?>