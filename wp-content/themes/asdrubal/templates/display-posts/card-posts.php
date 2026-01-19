<div class="posts-card"> 
    <a href="<?php the_permalink(); ?>" title="<?php the_permalink(); ?>" id="<?php the_ID(); ?>"> 
        <div class="post-name"><?php the_title(); ?></div>
        <div class="post-desc"><?php the_excerpt('more information...'); ?></div>
    </a>
</div>