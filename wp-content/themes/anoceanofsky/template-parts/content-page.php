<div class="contentBox">
    <div class="innerBox">
        <?php
            while (have_posts()) : the_post(); ?>

            <div class="contentTitle">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </div>

            <div class="contentText">
                
                <?php 
                    the_content(); 
                    // echo wp_trim_words('function', 'howManyWords', 'lastKiHobe')


                    // $readMore = '<p><a href="'.get_permalink().'">read more</a></p>';
                    // echo wp_trim_words( get_the_content(), 3, $readMore );
                    
                    // Check if it's a single post or not
                    // if ( is_single() ) : 
                    //     the_content();
                    // else :
                    //     $readMore = '<p><a href="' . get_permalink() . '">read more</a></p>';
                    //     echo wp_trim_words( get_the_content(), 3, $readMore ); 
                    // endif; 
                ?>
            </div>

        <?php endwhile; ?>

        <?php

        $alada = new WP_Query(array(
            'post_type' => 'basic-testimonials'
        ));
        
        
        while($alada->have_posts()) : $alada->the_post(); ?>

            <p><?php the_title(); ?></p>
            <p><?php the_post_thumbnail(); ?></p>
            <p><?php echo get_post_meta(get_the_id(), 'sub-title', true); ?></p>
            <p><?php echo get_post_meta(get_the_id(), 'wiki_test_wysiwyg', true); ?></p>

        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div>
