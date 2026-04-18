<?php
/**
 * Template: Fallback
 * Marchon Child Theme
 */
get_header(); ?>

<main class="marchon-page-content">
    <div class="marchon-page-inner">
        <?php if (have_posts()): while (have_posts()): the_post(); ?>
            <article class="marchon-post-preview">
                <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="page-content"><?php the_excerpt(); ?></div>
            </article>
        <?php endwhile; else: ?>
            <p>Nenhum conteúdo encontrado.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
