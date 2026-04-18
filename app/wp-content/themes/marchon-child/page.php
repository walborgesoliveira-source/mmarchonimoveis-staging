<?php
/**
 * Template: Página WordPress
 * Marchon Child Theme
 */
get_header(); ?>

<main class="marchon-page-content">
    <div class="marchon-page-inner">
        <?php while (have_posts()): the_post(); ?>
            <article class="marchon-page-entry">
            <h1 class="entry-title secao-titulo"><?php the_title(); ?></h1>
            <div class="page-content"><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
