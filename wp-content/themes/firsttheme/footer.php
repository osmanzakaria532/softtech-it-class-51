
        <footer>
            <?php
                // show the menu in frontend
                wp_nav_menu(
                    // decided to which menu is visible
                    array(
                        'theme_location' => 'footer-menu',
                    )
                );
            ?>

        </footer>
        
        <?php wp_footer(); ?>
    </body>
</html>