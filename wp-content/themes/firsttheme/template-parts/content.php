<?php 


    while (have_posts()) {

        the_post();
        
        // echo "post show korao <br />";
        the_title();
        echo "<br />";

        // the_post_thumbnail();
        echo the_post_thumbnail();

        // echo get_the_content();
        the_content();
    }

?>