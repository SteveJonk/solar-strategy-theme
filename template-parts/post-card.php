<div class="post-card">
    <a class="post-card__link" href=" <?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>">
        <img class="post-card__image" alt="image for <?php the_title(); ?>"
            src="<?php echo get_the_post_thumbnail_url(null, 'large') ?>" />
        <!-- show only first category -->
        <div class="post-card__category">
            <?php $categories = get_the_category(); ?>
            <?php if ($categories) : ?>
                <?php echo $categories[0]->name; ?>
            <?php endif; ?>
        </div>
        <h5 class="post-card__title"><?php the_title(); ?></h5>
        <?php the_excerpt(); ?>
    </a>
</div>