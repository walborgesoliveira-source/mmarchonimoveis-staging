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
            $id        = get_the_ID();
            $codigo    = get_field('codigo', $id);
            $tipo      = get_field('tipo', $id);
            $area      = get_field('area', $id);
            $preco     = get_field('preco', $id);
            $destaque  = get_field('destaque', $id);
            $instagram = get_field('link_instagram', $id);
            $facebook  = get_field('link_facebook', $id);
            $youtube   = get_field('link_youtube', $id);
            $foto      = get_field('fotos', $id);
            $foto_url  = $foto ? $foto['url'] : '';
            $tipos_label = ['terreno'=>'Terreno','casa'=>'Casa','apartamento'=>'Apartamento','comercial'=>'Comercial'];
        ?>
        <article class="card-imovel">
            <div class="card-foto">
                <?php if ($foto_url): ?>
                    <img src="<?php echo esc_url($foto_url); ?>" alt="<?php the_title(); ?>" loading="lazy">
                <?php else: ?>
                    <div class="card-foto-placeholder">
                        <svg viewBox="0 0 24 24" width="32" height="32" stroke="#c5baa8" fill="none" stroke-width="1">
                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Foto em breve</span>
                    </div>
                <?php endif; ?>
                <?php if ($tipo): ?>
                    <span class="card-tipo"><?php echo esc_html($tipos_label[$tipo] ?? $tipo); ?></span>
                <?php endif; ?>
                <?php if ($destaque): ?>
                    <span class="card-destaque">Destaque</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($codigo): ?>
                    <div class="card-codigo">Cód. <?php echo esc_html($codigo); ?></div>
                <?php endif; ?>
                <h2 class="card-titulo">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="card-resumo"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></div>
                <div class="card-info">
                    <?php if ($area): ?>
                        <span class="card-area"><?php echo esc_html($area); ?></span>
                    <?php endif; ?>
                    <?php if ($preco): ?>
                        <span class="card-preco"><?php echo esc_html($preco); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($instagram || $facebook || $youtube): ?>
                <div class="card-redes">
                    <?php if ($instagram): ?>
                    <a href="<?php echo esc_url($instagram); ?>" target="_blank" class="card-rede" title="Instagram">
                        <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($facebook): ?>
                    <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="card-rede" title="Facebook">
                        <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($youtube): ?>
                    <a href="<?php echo esc_url($youtube); ?>" target="_blank" class="card-rede" title="YouTube">
                        <svg viewBox="0 0 24 24"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </article>
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
