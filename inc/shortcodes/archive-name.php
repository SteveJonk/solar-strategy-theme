<?php

function display_archive_name_shortcode()
{
    // For Custom Post Type Archives
    if (is_post_type_archive()) {
        return post_type_archive_title('', false);
    }
    // For Category, Tag, or Custom Taxonomy Archives
    elseif (is_category() || is_tag() || is_tax()) {
        return single_term_title('', false);
    }
    // For Date Archives
    elseif (is_date()) {
        if (is_year()) return get_the_date(_x('Y', 'yearly archives date format'));
        if (is_month()) return get_the_date(_x('F Y', 'monthly archives date format'));
        if (is_day()) return get_the_date(_x('F j, Y', 'daily archives date format'));
    }
    return 'Blog';
}
add_shortcode('archive_name', 'display_archive_name_shortcode');
