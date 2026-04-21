<?php
/**
 * Template: Arquivo — Todos os Imóveis
 * Marchon Child Theme
 */
get_header(); ?>

<div style="background:var(--verde);padding:4rem 2.5rem 3rem;text-align:center">
    <div style="font-size:0.7rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--terra-claro);margin-bottom:0.5rem">Portfólio</div>
    <h1 style="font-family:var(--font-title);font-size:clamp(2rem,5vw,4rem);font-weight:300;color:#fff">
        Todos os <em>Imóveis</em>
    </h1>
    <p style="color:rgba(255,255,255,0.75);margin-top:0.5rem">
        Terrenos e casas disponíveis em Lumiar e Nova Friburgo
    </p>
</div>

<section class="marchon-imoveis" style="background:var(--bege)">
    <div class="marchon-imoveis-inner">
        <div class="imoveis-grid">
        <?php if (have_posts()): while (have_posts()): the_post();
            echo marchon_render_imovel_card(get_the_ID(), 'h2');
        ?>
        <?php endwhile;
        else: ?>
            <p style="color:var(--cinza-suave);text-align:center;grid-column:1/-1;padding:3rem">
                Nenhum imóvel cadastrado ainda.
            </p>
        <?php endif; ?>
        </div>

        <?php the_posts_pagination(['mid_size' => 2]); ?>
    </div>
</section>

<?php get_footer(); ?>
