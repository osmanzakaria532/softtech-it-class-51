<div class="contentBox">
    <div class="innerBox">
        <?php
            while (have_posts()) : the_post(); ?>

            <div class="contentTitle">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <h4>sub-title : <?php echo get_post_meta(get_the_id(), 'osman', true); ?></h4>
            </div>

            <div class="contentText">
                
                <?php 
                    // the_content(); 
                    // echo wp_trim_words('function', 'howManyWords', 'lastKiHobe')


                    // $readMore = '<p><a href="'.get_permalink().'">read more</a></p>';
                    // echo wp_trim_words( get_the_content(), 3, $readMore );
                    
                    // Check if it's a single post or not
                    if ( is_single() ) : 
                        the_content();
                    else :
                        $readMore = '<p><a href="' . get_permalink() . '">read more</a></p>';
                        echo wp_trim_words( get_the_content(), 3, $readMore ); 
                    endif; 
                ?>
            </div>

        <?php endwhile; ?>
    </div>
</div>
