<?php
/**
 * Header — Marchon Child
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="masthead" class="site-header">
    <div class="site-header-inner">
        <div class="site-branding">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php endif; ?>

            <div class="site-brand-copy">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-title">
                    <?php bloginfo('name'); ?>
                </a>
                <p class="site-tagline">Imóveis em Lumiar e Nova Friburgo</p>
            </div>
        </div>

        <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menu principal" aria-controls="nav-primary" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav-primary" id="nav-primary" aria-label="Menu principal">
            <?php wp_nav_menu([
                'theme_location' => 'menu-principal',
                'menu_class'     => 'menu',
                'container'      => false,
                'fallback_cb'    => false,
            ]); ?>
        </nav>
    </div>
</header>
