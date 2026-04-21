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
            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-brand-mark" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <img
                    src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/logommarchon3d.png'); ?>"
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                    class="site-brand-mark-image"
                >
            </a>

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

            <button
                type="button"
                class="header-search-trigger"
                data-search-popup-open
                aria-controls="marchon-search-popup"
                aria-expanded="false"
            >
                Buscar
            </button>
        </nav>
    </div>
</header>

<section class="search-inline-fallback" data-search-inline hidden aria-label="Busca rápida no site">
    <div class="search-inline-fallback-inner">
        <div class="search-inline-fallback-header">
            <span class="search-popup-kicker">Busca rápida</span>
            <h2>Resultado da busca abaixo</h2>
            <p>Se o pop-up não abrir, os resultados aparecem aqui. Códigos de imóveis têm prioridade.</p>
        </div>

        <form class="search-popup-form" data-search-inline-form novalidate>
            <label class="screen-reader-text" for="marchon-search-inline-input">Buscar no site</label>
            <input
                type="search"
                id="marchon-search-inline-input"
                class="search-popup-input"
                name="s"
                placeholder="Busque por código, bairro ou tipo de imóvel"
                autocomplete="off"
                data-search-inline-input
            >
            <button type="submit" class="search-popup-submit">Buscar</button>
        </form>

        <div class="search-popup-status" data-search-inline-status aria-live="polite"></div>
        <div class="search-popup-results" data-search-inline-results></div>
    </div>
</section>

<div
    class="search-popup"
    id="marchon-search-popup"
    data-search-popup
    hidden
    aria-hidden="true"
>
    <div class="search-popup-backdrop" data-search-popup-close></div>
    <div class="search-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="marchon-search-popup-title">
        <button type="button" class="search-popup-close" data-search-popup-close aria-label="Fechar busca">×</button>
        <div class="search-popup-header">
            <span class="search-popup-kicker">Busca rápida</span>
            <h2 id="marchon-search-popup-title">Encontre imóveis e páginas em segundos</h2>
            <p>Digite o código do imóvel primeiro para uma busca mais rápida, ou pesquise por bairro, tipo e conteúdo.</p>
        </div>

        <form class="search-popup-form" data-search-popup-form novalidate>
            <label class="screen-reader-text" for="marchon-search-input">Buscar no site</label>
            <input
                type="search"
                id="marchon-search-input"
                class="search-popup-input"
                name="s"
                placeholder="Ex.: 001, Lumiar, terreno, casa"
                autocomplete="off"
                data-search-popup-input
            >
            <button type="submit" class="search-popup-submit">Buscar</button>
        </form>

        <div class="search-popup-status" data-search-popup-status aria-live="polite"></div>
        <div class="search-popup-results" data-search-popup-results></div>
    </div>
</div>
