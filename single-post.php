<?php get_header(); ?>

<?php get_template_part('template-parts/navigation') ?>

<div class="container">
    <?php while (have_posts()) {
        the_post();
        
        the_content();

        $categories = wp_get_post_categories($post->ID);

        $related = get_posts(array(
            'category__in' => $categories,
            'numberposts' => 3,
            'post__not_in' => array($post->ID)
        )); ?>

        <?php if ($related) : ?>

            <section class="related-posts">
                <h2>Related posts</h2>
                <div class="post-list">
                    <?php
                    if ($related) foreach ($related as $post) {
                        setup_postdata($post);
                        get_template_part('template-parts/post-card');
                    }
                    ?>
                </div>
                <?php
            if (! empty($categories)) {
                $first_category      = $categories[0];
                $first_category_link = get_category_link($first_category);
                // Echo this, with category link and see more <div class="wp-block-button is-style-primary-animation"><a class="wp-block-button__link has-brand-sunny-gradient-gradient-background has-background has-border-color has-orange-border-color wp-element-button" href="http://localhost:10164/contact-us/" style="border-width:4px;border-top-left-radius:8px;border-top-right-radius:8px;border-bottom-left-radius:8px;border-bottom-right-radius:8px">Request Advice</a></div>

                echo '<div class="wp-block-buttons"><div class="wp-block-button is-style-primary-animation"><a class="wp-block-button__link has-brand-sunny-gradient-gradient-background has-background has-border-color has-orange-border-color wp-element-button" style="border-width:4px;border-top-left-radius:8px;border-top-right-radius:8px;border-bottom-left-radius:8px;border-bottom-right-radius:8px" href="' . esc_url($first_category_link) . '">' . __('See more', 'theme') . '</a></div>';
            } ?>
            </section>
        <?php endif; ?>
    <?php } ?>
</div>

<?php wp_reset_postdata(); ?>

<?php get_template_part('template-parts/footer'); ?>

<?php get_footer() ?>