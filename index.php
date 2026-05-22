<?php get_header(); ?>

<?php get_template_part('template-parts/navigation') ?>

<?php
// Display the content of the assigned "Posts page"
$posts_page_id = get_option('page_for_posts');

if ($posts_page_id) {
    $posts_page = get_post($posts_page_id);

    if ($posts_page && ! empty($posts_page->post_content)) {
        $content = apply_filters('the_content', $posts_page->post_content);
        echo $content;
    }
}
?>
<div class="container">
    <?php get_template_part('template-parts/tag-list'); ?>

    <div class="post-list">
        <?php while (have_posts()) {
            the_post();
            get_template_part('template-parts/post-card');
        } ?>
    </div>
    <?php the_posts_pagination(); ?>
</div>

<?php get_template_part('template-parts/footer'); ?>

<?php get_footer() ?>