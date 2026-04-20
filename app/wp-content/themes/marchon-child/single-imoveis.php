<?php
/**
 * Template: Imóvel Individual
 * Marchon Child Theme
 */
get_header();

$marchon_instagram_shortcode = marchon_get_instagram_feed_shortcode();

$id        = get_the_ID();
$codigo    = get_field('codigo', $id);
$tipo      = get_field('tipo', $id);
$area      = get_field('area', $id);
$preco     = get_field('preco', $id);
$cod_ref   = get_field('codigo_referencia', $id);
$foto      = get_field('fotos', $id);
$foto_url  = $foto ? $foto['url'] : '';
$instagram = get_field('link_instagram', $id);
$facebook  = get_field('link_facebook', $id);
$youtube   = get_field('link_youtube', $id);
$tipos_label = ['terreno'=>'Terreno','casa'=>'Casa','apartamento'=>'Apartamento','comercial'=>'Comercial'];

$wpp_msg = urlencode('Olá Marcos! Tenho interesse no imóvel *' . get_the_title() . '* (Cód. ' . $codigo . '). Poderia me dar mais informações?');
?>

<div class="single-imovel-header">
    <div class="single-imovel-inner">
        <div class="single-imovel-badges">
            <?php if ($tipo): ?>
                <span class="single-badge tipo"><?php echo esc_html($tipos_label[$tipo] ?? $tipo); ?></span>
            <?php endif; ?>
            <?php if ($codigo): ?>
                <span class="single-badge codigo">Cód. <?php echo esc_html($codigo); ?></span>
            <?php endif; ?>
        </div>
        <h1 class="single-titulo"><?php the_title(); ?></h1>
        <?php if ($preco): ?>
            <div class="single-preco"><?php echo esc_html($preco); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="single-imovel-grid">
    <div class="single-conteudo">
        <?php if ($foto_url): ?>
            <img src="<?php echo esc_url($foto_url); ?>" alt="<?php the_title(); ?>" class="single-foto-principal">
        <?php endif; ?>
        <div class="single-descricao">
            <?php the_content(); ?>
        </div>
    </div>

    <div class="single-sidebar">
        <div class="single-info-box">
            <?php if ($area): ?>
            <div class="single-info-item">
                <span class="single-info-label">Área</span>
                <span class="single-info-valor"><?php echo esc_html($area); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($tipo): ?>
            <div class="single-info-item">
                <span class="single-info-label">Tipo</span>
                <span class="single-info-valor"><?php echo esc_html($tipos_label[$tipo] ?? $tipo); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($cod_ref): ?>
            <div class="single-info-item">
                <span class="single-info-label">Referência</span>
                <span class="single-info-valor"><?php echo esc_html($cod_ref); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($preco): ?>
            <div class="single-info-item">
                <span class="single-info-label">Preço</span>
                <span class="single-info-valor" style="color:var(--verde);font-family:var(--font-title);font-size:1.1rem"><?php echo esc_html($preco); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <a href="https://wa.me/5522998121056?text=<?php echo $wpp_msg; ?>" target="_blank" class="single-cta-wpp">
            <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Tenho interesse
        </a>

        <?php if ($instagram || $facebook || $youtube): ?>
        <div class="single-redes">
            <?php if ($instagram): ?>
            <a href="<?php echo esc_url($instagram); ?>" target="_blank" class="single-rede">
                <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                Instagram
            </a>
            <?php endif; ?>
            <?php if ($facebook): ?>
            <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="single-rede">
                <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </a>
            <?php endif; ?>
            <?php if ($youtube): ?>
            <a href="<?php echo esc_url($youtube); ?>" target="_blank" class="single-rede">
                <svg viewBox="0 0 24 24"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
                YouTube
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($marchon_instagram_shortcode !== '') : ?>
<section class="single-instagram-relacionado">
    <div class="single-instagram-relacionado-inner">
        <div class="single-instagram-relacionado-hero">
            <div>
                <div class="secao-label">Instagram</div>
                <h2 class="secao-titulo">Veja mais detalhes, inspiração e motivos para se imaginar <em>neste imóvel</em></h2>
                <p class="marchon-instagram-home-intro">No Instagram da MM Imóveis, este imóvel ganha ainda mais vida com novos ângulos, destaques visuais e publicações que ajudam você a sentir o potencial de cada espaço.</p>
            </div>
            <div class="single-instagram-relacionado-actions">
                <a href="https://wa.me/5522998121056?text=<?php echo $wpp_msg; ?>" target="_blank" rel="noopener noreferrer" class="btn-verde">Agendar visita agora</a>
                <a href="https://www.instagram.com/mmimoveis__/" target="_blank" rel="noopener noreferrer" class="btn-outline">Ver mais no Instagram</a>
            </div>
        </div>
        <div class="marchon-instagram-feed-shell">
            <?php echo marchon_render_instagram_feed(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- COMENTÁRIOS -->
<?php if (comments_open()): ?>
<div class="comments-area">
    <?php comments_template(); ?>
</div>
<?php endif; ?>

<?php get_footer(); ?>
