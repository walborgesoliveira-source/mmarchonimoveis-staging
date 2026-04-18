<?php
/**
 * Marchon Child — functions.php
 * Tema filho do Twenty Twenty-Five para Marcos Marchon Imóveis
 */

// ── CARREGAR ESTILOS ────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    // Estilo do tema pai
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css'
    );
    // Estilo do tema filho
    wp_enqueue_style(
        'marchon-child-style',
        get_stylesheet_uri(),
        ['parent-style'],
        wp_get_theme()->get('Version')
    );
    // Google Fonts
    wp_enqueue_style(
        'marchon-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap',
        [],
        null
    );
});

// ── SUPORTE DO TEMA ─────────────────────────────────────────────────────────
add_action('after_setup_theme', function() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption']);
    add_theme_support('custom-logo', [
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
});

// ── MENUS ───────────────────────────────────────────────────────────────────
add_action('after_setup_theme', function() {
    register_nav_menus([
        'menu-principal' => 'Menu Principal',
        'menu-rodape'    => 'Menu Rodapé',
    ]);
});

// ── HELPERS DO TEMA ─────────────────────────────────────────────────────────
function marchon_env(string $key, string $default = ''): string {
    $value = getenv($key);

    return $value === false ? $default : (string) $value;
}

function marchon_env_bool(string $key, bool $default = false): bool {
    $value = strtolower(marchon_env($key, $default ? 'true' : 'false'));

    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function marchon_get_contact_form_shortcode() {
    if (!shortcode_exists('contact-form-7')) {
        return '';
    }

    $forms = get_posts([
        'post_type'      => 'wpcf7_contact_form',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'title'          => 'Contato',
    ]);

    if (!$forms) {
        $forms = get_posts([
            'post_type'      => 'wpcf7_contact_form',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ]);
    }

    if (!$forms) {
        return '';
    }

    return sprintf('[contact-form-7 id="%d" title="%s"]', $forms[0]->ID, esc_attr($forms[0]->post_title));
}

// ── SMTP / EMAIL ────────────────────────────────────────────────────────────
add_action('phpmailer_init', function(PHPMailer\PHPMailer\PHPMailer $phpmailer) {
    $host = marchon_env('MARCHON_SMTP_HOST');

    if ($host === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->Port       = (int) marchon_env('MARCHON_SMTP_PORT', '587');
    $phpmailer->SMTPAuth   = marchon_env_bool('MARCHON_SMTP_AUTH', true);
    $phpmailer->Username   = marchon_env('MARCHON_SMTP_USER');
    $phpmailer->Password   = marchon_env('MARCHON_SMTP_PASS');
    $phpmailer->SMTPSecure = marchon_env('MARCHON_SMTP_SECURE', 'tls');
    $phpmailer->CharSet    = 'UTF-8';
    $phpmailer->Timeout    = 20;

    $from_email = marchon_env('MARCHON_FROM_EMAIL', $phpmailer->Username ?: get_option('admin_email'));
    $from_name  = marchon_env('MARCHON_FROM_NAME', get_bloginfo('name'));

    if ($from_email !== '') {
        $phpmailer->setFrom($from_email, $from_name, false);
    }
}, 20);

// ── WIDGETS ─────────────────────────────────────────────────────────────────
add_action('widgets_init', function() {
    register_sidebar([
        'name'          => 'Sidebar Imóvel',
        'id'            => 'sidebar-imovel',
        'description'   => 'Widgets exibidos na página de imóvel individual',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
});

// ── SHORTCODE: 6 ÚLTIMOS IMÓVEIS ────────────────────────────────────────────
add_shortcode('ultimos_imoveis', function($atts) {
    $atts = shortcode_atts(['quantidade' => 6], $atts);

    $query = new WP_Query([
        'post_type'      => 'imoveis',
        'posts_per_page' => intval($atts['quantidade']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if (!$query->have_posts()) return '<p>Nenhum imóvel cadastrado ainda.</p>';

    ob_start(); ?>
    <div class="imoveis-grid">
    <?php while ($query->have_posts()): $query->the_post();
        $id       = get_the_ID();
        $codigo   = get_field('codigo', $id);
        $tipo     = get_field('tipo', $id);
        $area     = get_field('area', $id);
        $preco    = get_field('preco', $id);
        $destaque = get_field('destaque', $id);
        $instagram = get_field('link_instagram', $id);
        $facebook  = get_field('link_facebook', $id);
        $youtube   = get_field('link_youtube', $id);
        $foto     = get_field('fotos', $id);
        $foto_url = $foto ? $foto['url'] : '';
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
            <h3 class="card-titulo"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
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
    <?php endwhile; wp_reset_postdata();
    return ob_get_clean();
});

// ── SHORTCODE: BANNER ROTATIVO ──────────────────────────────────────────────
add_shortcode('banner_imoveis', function() {
    $query = new WP_Query([
        'post_type'      => 'imoveis',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'meta_query'     => [[
            'key'     => 'destaque',
            'value'   => '1',
            'compare' => '='
        ]],
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    // Se não tiver destaques, pega os 5 mais recentes
    if (!$query->have_posts()) {
        $query = new WP_Query([
            'post_type'      => 'imoveis',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
        ]);
    }

    if (!$query->have_posts()) return '';

    $slides = [];
    while ($query->have_posts()): $query->the_post();
        $foto = get_field('fotos');
        $slides[] = [
            'titulo'    => get_the_title(),
            'resumo'    => wp_trim_words(get_the_excerpt(), 15),
            'link'      => get_permalink(),
            'foto'      => $foto ? $foto['url'] : '',
            'area'      => get_field('area'),
            'preco'     => get_field('preco'),
        ];
    endwhile;
    wp_reset_postdata();

    ob_start(); ?>
    <div class="marchon-banner" id="banner-rotativo">
        <?php foreach ($slides as $i => $slide): ?>
        <div class="banner-slide <?php echo $i === 0 ? 'ativo' : ''; ?>"
             style="background-image: url('<?php echo $slide['foto'] ? esc_url($slide['foto']) : 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1600&q=80'; ?>')">
            <div class="banner-overlay"></div>
        </div>
        <?php endforeach; ?>

        <div class="banner-conteudo">
            <div class="banner-badge" id="banner-badge">Lumiar · Nova Friburgo · RJ</div>
            <h1 class="banner-titulo" id="banner-titulo"><?php echo esc_html($slides[0]['titulo']); ?></h1>
            <p class="banner-subtitulo" id="banner-subtitulo"><?php echo esc_html($slides[0]['resumo']); ?></p>
            <a href="<?php echo esc_url($slides[0]['link']); ?>" class="banner-btn" id="banner-link">Ver imóvel</a>
            <a href="https://wa.me/5522998121056" target="_blank" class="banner-btn-outline">Falar com Corretor</a>
        </div>

        <button class="banner-nav banner-prev" onclick="bannerNav(-1)" aria-label="Anterior">&#8249;</button>
        <button class="banner-nav banner-next" onclick="bannerNav(1)" aria-label="Próximo">&#8250;</button>

        <div class="banner-dots">
            <?php foreach ($slides as $i => $slide): ?>
            <button class="banner-dot <?php echo $i === 0 ? 'ativo' : ''; ?>"
                    onclick="bannerIr(<?php echo $i; ?>)" aria-label="Slide <?php echo $i+1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    var bannerSlides = <?php echo json_encode($slides); ?>;
    var bannerAtual = 0;
    var bannerTimer;

    function bannerAtualizar(n) {
        var slides = document.querySelectorAll('.banner-slide');
        var dots   = document.querySelectorAll('.banner-dot');
        slides[bannerAtual].classList.remove('ativo');
        dots[bannerAtual].classList.remove('ativo');
        bannerAtual = (n + slides.length) % slides.length;
        slides[bannerAtual].classList.add('ativo');
        dots[bannerAtual].classList.add('ativo');
        document.getElementById('banner-titulo').textContent    = bannerSlides[bannerAtual].titulo;
        document.getElementById('banner-subtitulo').textContent = bannerSlides[bannerAtual].resumo;
        document.getElementById('banner-link').href             = bannerSlides[bannerAtual].link;
    }
    function bannerNav(dir) { clearInterval(bannerTimer); bannerAtualizar(bannerAtual + dir); bannerIniciar(); }
    function bannerIr(n)    { clearInterval(bannerTimer); bannerAtualizar(n); bannerIniciar(); }
    function bannerIniciar() { bannerTimer = setInterval(function(){ bannerAtualizar(bannerAtual + 1); }, 5000); }
    bannerIniciar();
    </script>
    <?php
    return ob_get_clean();
});

// ── SHORTCODE: STATS ────────────────────────────────────────────────────────
add_shortcode('marchon_stats', function() {
    $total = wp_count_posts('imoveis')->publish;
    ob_start(); ?>
    <div class="marchon-stats">
        <div class="stat-item">
            <div class="stat-num"><?php echo $total; ?>+</div>
            <div class="stat-label">Imóveis disponíveis</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">CRECI</div>
            <div class="stat-label">95681 — Registro ativo</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">Lumiar</div>
            <div class="stat-label">Especialista na região</div>
        </div>
    </div>
    <?php return ob_get_clean();
});

// ── LIGHTBOX PARA GALERIA DE FOTOS ─────────────────────────────────────────
add_action('wp_footer', function() {
    if (!is_singular('imoveis')) return; ?>
    <div class="marchon-lightbox" id="lightbox" onclick="this.classList.remove('aberto')">
        <button class="marchon-lightbox-fechar" onclick="document.getElementById('lightbox').classList.remove('aberto')">✕</button>
        <img id="lightbox-img" src="" alt="">
    </div>
    <script>
    document.querySelectorAll('.single-descricao .wp-block-gallery figure img').forEach(function(img) {
        img.addEventListener('click', function() {
            document.getElementById('lightbox-img').src = this.src.replace(/-\d+x\d+(?=\.)/, '');
            document.getElementById('lightbox').classList.add('aberto');
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('lightbox').classList.remove('aberto');
    });
    </script>
<?php });

// ── NAV TOGGLE MOBILE ───────────────────────────────────────────────────────
add_action('wp_footer', function() { ?>
    <script>
    (function() {
        var btn = document.getElementById('nav-toggle');
        var nav = document.getElementById('nav-primary');
        if (!btn || !nav) return;
        btn.addEventListener('click', function() {
            var aberto = nav.classList.toggle('aberto');
            btn.classList.toggle('aberto', aberto);
            btn.setAttribute('aria-expanded', aberto);
        });
    })();
    </script>
<?php });

// ── BOTÃO WHATSAPP FLUTUANTE ────────────────────────────────────────────────
add_action('wp_footer', function() { ?>
    <a href="https://wa.me/5522998121056" target="_blank" class="whatsapp-float" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
<?php });
