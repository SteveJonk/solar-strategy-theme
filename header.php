<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset='<?php bloginfo('charset') ?>'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="shortcut icon" type="image/png" href="<?php echo get_site_icon_url(); ?>">
        <?php wp_head() ?>

    </head>

    <body <?php body_class() ?>>
        <?php wp_body_open(); ?>

        <header class="header">
            <div class="header__body container">
                <div class="header__body__logo">
                    <a href="<?php echo home_url() ?>"><img src="<?php echo get_theme_mod('navbar_logo'); ?>"
                            type="image/x-icon" alt="header logo" height="40" loading="eager"></a>
                </div>
                <a id="js__open-nav" class="header__body__hamburger">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5.33333 17.3333C6.06971 17.3333 6.66667 16.7364 6.66667 16C6.66667 15.2636 6.06971 14.6667 5.33333 14.6667C4.59695 14.6667 4 15.2636 4 16C4 16.7364 4.59695 17.3333 5.33333 17.3333Z"
                            fill="#E19730" />
                        <path
                            d="M26.7463 14.6667H10.5863C9.89414 14.6667 9.33301 15.2278 9.33301 15.92V16.08C9.33301 16.7722 9.89414 17.3333 10.5863 17.3333H26.7463C27.4385 17.3333 27.9997 16.7722 27.9997 16.08V15.92C27.9997 15.2278 27.4385 14.6667 26.7463 14.6667Z"
                            fill="#E19730" />
                        <path
                            d="M26.7467 21.3333H5.25333C4.56114 21.3333 4 21.8945 4 22.5867V22.7467C4 23.4389 4.56114 24 5.25333 24H26.7467C27.4389 24 28 23.4389 28 22.7467V22.5867C28 21.8945 27.4389 21.3333 26.7467 21.3333Z"
                            fill="#E19730" />
                        <path
                            d="M26.7467 8H5.25333C4.56114 8 4 8.56114 4 9.25333V9.41333C4 10.1055 4.56114 10.6667 5.25333 10.6667H26.7467C27.4389 10.6667 28 10.1055 28 9.41333V9.25333C28 8.56114 27.4389 8 26.7467 8Z"
                            fill="#E19730" />
                    </svg>
                </a>
                <div id="js__nav" class="header__body__navbar">
                    <a class="header__body__navbar__mobile-logo" href="<?php echo home_url() ?>"><img
                            src="<?php echo get_theme_mod('navbar_logo'); ?>" type="image/x-icon" alt="header logo"
                            height="40" loading="eager"></a>
                    <div class="header__body__navbar__left">
                        <?php wp_nav_menu(
                        array(
                            'theme_location' => 'headerMenuLeft',
                            'depth' => 2,
                            'container' => true,
                            'menu_id' => "navbar-left",
                        )

                    ); ?>
                    </div>
                    <div class="header__body__navbar__right">
                        <?php wp_nav_menu(
                        array(
                            'theme_location' => 'headerMenuRight',
                            'depth' => 2,
                            'container' => true,
                            'menu_id' => "navbar-right",
                        )

                    ); ?>
                    </div>
                    <a id="js__close-nav" class="header__body__navbar__close" href="#">
                        <svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M18.3978 16.1791L3.19972 0.980998C2.71026 0.49154 1.91669 0.491541 1.42724 0.980998L1.3141 1.09413C0.824642 1.58359 0.824642 2.37716 1.3141 2.86662L16.5122 18.0647C17.0016 18.5542 17.7952 18.5542 18.2847 18.0647L18.3978 17.9516C18.8873 17.4621 18.8873 16.6685 18.3978 16.1791Z"
                                fill="#E19730" />
                            <path
                                d="M16.0843 0.886244L0.886241 16.0843C0.396783 16.5738 0.396783 17.3673 0.886241 17.8568L0.999378 17.9699C1.48883 18.4594 2.2824 18.4594 2.77186 17.9699L17.9699 2.77186C18.4594 2.2824 18.4594 1.48884 17.9699 0.999381L17.8568 0.886244C17.3673 0.396787 16.5738 0.396787 16.0843 0.886244Z"
                                fill="#E19730" />
                        </svg>

                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">