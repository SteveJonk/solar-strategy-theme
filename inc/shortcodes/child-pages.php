<?php


function display_child_pages_shortcode()
{
    $child_pages = get_pages(array(
        'child_of' => get_the_ID(),
        'sort_column' => 'menu_order',
        'sort_order' => 'ASC',
    ));
    if ($child_pages) {
        ob_start(); ?>
        <div class="child-pages">
            <?php foreach ($child_pages as $page) { ?>
                <div class="child-pages__item">
                    <div class="child-pages__item__image">
                        <img src="<?php echo get_the_post_thumbnail_url($page->ID, 'large'); ?>"
                            alt="<?php echo $page->post_title; ?>">
                    </div>
                    <h3 class="child-pages__item__title"><?php echo $page->post_title; ?></h3>
                    <div class="child-pages__item__features">
                        <?php the_field('features', $page->ID); ?>
                    </div>
                    <div class="child-pages__item__viewbox">
                        <h5 class="child-pages__item__viewbox__price"><?php the_field('price', $page->ID); ?></h5>
                        <a href="<?php echo get_permalink($page->ID); ?>"
                            class="child-pages__item__viewbox__button"><?php _e('Bekijk product', 'theme'); ?>
                        </a>
                    </div>
                    <a class="child-pages__item__request-advise" href="<?php echo home_url('/contact'); ?>">
                        <?php _e('Vraag advies aan', 'theme'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="29" viewBox="0 0 22 29" fill="none">
                            <g opacity="0.8">
                                <circle cx="5.77745" cy="5.7777" r="1.44444" fill="currentColor" />
                                <circle cx="10.1105" cy="5.7777" r="1.44444" fill="currentColor" />
                                <circle cx="8.66612" cy="10.1109" r="1.44444" fill="currentColor" />
                                <circle cx="13.0001" cy="10.1109" r="1.44444" fill="currentColor" />
                                <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 14.4443 17.3333)"
                                    fill="currentColor" />
                                <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 10.1113 17.3333)"
                                    fill="currentColor" />
                                <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 11.5557 21.6665)"
                                    fill="currentColor" />
                                <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 7.22168 21.6665)"
                                    fill="currentColor" />
                                <circle cx="11.5548" cy="14.4444" r="1.44444" fill="currentColor" />
                                <circle cx="15.8878" cy="14.4444" r="1.44444" fill="currentColor" />
                            </g>
                            <circle cx="5.77745" cy="5.7777" r="1.44444" fill="currentColor" />
                            <circle cx="10.1105" cy="5.7777" r="1.44444" fill="currentColor" />
                            <circle cx="8.66612" cy="10.1109" r="1.44444" fill="currentColor" />
                            <circle cx="13.0001" cy="10.1109" r="1.44444" fill="currentColor" />
                            <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 14.4443 17.3333)"
                                fill="currentColor" />
                            <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 10.1113 17.3333)"
                                fill="currentColor" />
                            <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 11.5557 21.6665)"
                                fill="currentColor" />
                            <circle cx="1.44444" cy="1.44444" r="1.44444" transform="matrix(-1 0 0 1 7.22168 21.6665)"
                                fill="currentColor" />
                            <circle cx="11.5548" cy="14.4444" r="1.44444" fill="currentColor" />
                            <circle cx="15.8878" cy="14.4444" r="1.44444" fill="currentColor" />
                            <defs>
                                <filter id="filter0_f_1110_5551" x="-0.000325203" y="-8.10623e-05" width="21.6657" height="28.8888"
                                    filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix" />
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                                    <feGaussianBlur stdDeviation="2.16667" result="effect1_foregroundBlur_1110_5551" />
                                </filter>
                            </defs>
                        </svg>
                    </a>
                </div>
            <?php } ?>
        </div>

<?php return  ob_get_clean();
    }
    return '';
}

add_shortcode('child_pages', 'display_child_pages_shortcode');
