<?php

// Create shortcode to display the name of the first category of the current post
function display_category_name_shortcode()
{
    $categories = get_the_category();
    if ($categories) {
        return $categories[0]->name;
    }
    return '';
}
add_shortcode('category_name', 'display_category_name_shortcode');
