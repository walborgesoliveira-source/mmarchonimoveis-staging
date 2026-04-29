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

    wp_enqueue_script(
        'marchon-search-popup',
        get_stylesheet_directory_uri() . '/assets/js/search-popup.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script('marchon-search-popup', 'marchonSearchPopup', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('marchon_search_popup'),
        'i18n'    => [
            'initialHint'  => 'Busque primeiro pelo código do imóvel ou digite bairro, tipo e conteúdo.',
            'minimumChars' => 'Digite pelo menos 2 caracteres para buscar.',
            'loading'      => 'Buscando resultados...',
            'noResults'    => 'Nenhum resultado encontrado.',
            'error'        => 'Não foi possível concluir a busca agora.',
        ],
    ]);
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

function marchon_whatsapp_number(): string {
    return '5522998121056';
}

function marchon_get_contact_email(): string {
    return 'suportemarcosmarchonimoveis@gmail.com';
}

function marchon_get_portal_url(): string {
    return home_url('/marchon-crm/');
}

function marchon_get_first_field(int $post_id, array $keys): string {
    foreach ($keys as $key) {
        $value = get_field($key, $post_id);
        if ($value !== null && $value !== '' && $value !== false) {
            return is_scalar($value) ? trim((string) $value) : '';
        }
    }

    return '';
}

function marchon_get_whatsapp_url(array $args = []): string {
    $action = trim((string) ($args['action'] ?? 'receber mais informações'));
    $source = trim((string) ($args['source'] ?? 'site'));
    $title  = trim((string) ($args['title'] ?? ''));
    $code   = trim((string) ($args['code'] ?? ''));
    $text   = trim((string) ($args['text'] ?? ''));

    if ($text === '') {
        if ($title !== '' || $code !== '') {
            $imovel = $title !== '' ? $title : 'este imóvel';
            $codigo = $code !== '' ? ' Cód. ' . $code : '';
            $text = sprintf(
                'Olá, vi o imóvel %s%s no %s e gostaria de %s.',
                $imovel,
                $codigo,
                $source,
                $action
            );
        } else {
            $text = sprintf('Olá, vim do %s e gostaria de %s.', $source, $action);
        }
    }

    return sprintf(
        'https://wa.me/%s?text=%s',
        marchon_whatsapp_number(),
        rawurlencode($text)
    );
}

function marchon_get_imovel_card_metrics(int $post_id): array {
    $metrics = [];

    $area_total = marchon_get_first_field($post_id, ['area_total', 'area_terreno', 'area_lote']);
    $area_construida = marchon_get_first_field($post_id, ['area_construida', 'area_privativa']);
    $vagas = marchon_get_first_field($post_id, ['vagas', 'garagem']);
    $area_padrao = marchon_get_first_field($post_id, ['area']);

    if ($area_total !== '') {
        $metrics[] = [
            'icon'  => 'area',
            'label' => 'Área total',
            'value' => $area_total,
        ];
    }

    if ($area_construida !== '') {
        $metrics[] = [
            'icon'  => 'built',
            'label' => 'Área construída',
            'value' => $area_construida,
        ];
    }

    if ($vagas !== '') {
        $metrics[] = [
            'icon'  => 'garage',
            'label' => 'Vagas',
            'value' => $vagas,
        ];
    }

    if (empty($metrics) && $area_padrao !== '') {
        $metrics[] = [
            'icon'  => 'area',
            'label' => 'Área',
            'value' => $area_padrao,
        ];
    }

    return $metrics;
}

function marchon_render_metric_icon(string $icon): string {
    return match ($icon) {
        'built' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/></svg>',
        'garage' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-7 9 7M5 10v9h14v-9M8 19v-5h8v5M8.5 11h.01M15.5 11h.01"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM9 6v12M15 6v12M4 12h16"/></svg>',
    };
}

function marchon_render_imovel_card(int $post_id, string $heading_tag = 'h3'): string {
    $codigo = get_field('codigo', $post_id);
    $tipo = get_field('tipo', $post_id);
    $preco = get_field('preco', $post_id);
    $destaque = get_field('destaque', $post_id);
    $foto = get_field('fotos', $post_id);
    $foto_url = is_array($foto) ? (string) ($foto['url'] ?? '') : '';
    $tipos_label = ['terreno' => 'Terreno', 'casa' => 'Casa', 'apartamento' => 'Apartamento', 'comercial' => 'Comercial'];
    $heading_tag = in_array($heading_tag, ['h2', 'h3', 'h4'], true) ? $heading_tag : 'h3';
    $metrics = marchon_get_imovel_card_metrics($post_id);
    $title = get_the_title($post_id);
    $whatsapp_url = marchon_get_whatsapp_url([
        'source' => 'site',
        'action' => 'consultar disponibilidade',
        'title'  => $title,
        'code'   => (string) $codigo,
    ]);

    ob_start();
    ?>
    <article class="card-imovel">
        <div class="card-foto">
            <?php if ($foto_url): ?>
                <img src="<?php echo esc_url($foto_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
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
            <<?php echo $heading_tag; ?> class="card-titulo">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html($title); ?></a>
            </<?php echo $heading_tag; ?>>
            <?php if ($preco): ?>
                <div class="card-preco-destaque"><?php echo esc_html($preco); ?></div>
            <?php endif; ?>
            <div class="card-resumo"><?php echo esc_html(wp_trim_words(get_the_excerpt($post_id), 20)); ?></div>
            <?php if (!empty($metrics)): ?>
                <div class="card-info">
                    <?php foreach ($metrics as $metric): ?>
                        <span class="card-meta-pill" aria-label="<?php echo esc_attr($metric['label']); ?>">
                            <span class="card-meta-icon"><?php echo marchon_render_metric_icon($metric['icon']); ?></span>
                            <span class="card-meta-copy">
                                <strong><?php echo esc_html($metric['value']); ?></strong>
                                <small><?php echo esc_html($metric['label']); ?></small>
                            </span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="card-actions">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="btn-outline card-btn">Ver detalhes</a>
                <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="btn-verde card-btn">Consultar disponibilidade</a>
            </div>
        </div>
    </article>
    <?php

    return (string) ob_get_clean();
}

add_filter('wp_nav_menu_items', function(string $items, object $args): string {
    if (($args->theme_location ?? '') !== 'menu-principal') {
        return $items;
    }

    $url   = marchon_get_portal_url();
    $label = 'Portal do Corretor';
    $class = 'menu-item menu-item-portal-corretor';

    if (is_page('marchon-crm')) {
        $class .= ' current-menu-item';
    }

    $items .= '<li class="' . esc_attr($class) . '">'
            . '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>'
            . '</li>';

    return $items;
}, 10, 2);

// ── PAPÉIS E CAPACIDADES: IMÓVEIS ──────────────────────────────────────────
function marchon_get_imoveis_capabilities(): array {
    return [
        'edit_post'              => 'edit_imovel',
        'read_post'              => 'read_imovel',
        'delete_post'            => 'delete_imovel',
        'edit_posts'             => 'edit_imoveis',
        'edit_others_posts'      => 'edit_others_imoveis',
        'publish_posts'          => 'publish_imoveis',
        'read_private_posts'     => 'read_private_imoveis',
        'delete_posts'           => 'delete_imoveis',
        'delete_private_posts'   => 'delete_private_imoveis',
        'delete_published_posts' => 'delete_published_imoveis',
        'delete_others_posts'    => 'delete_others_imoveis',
        'edit_private_posts'     => 'edit_private_imoveis',
        'edit_published_posts'   => 'edit_published_imoveis',
        'create_posts'           => 'create_imoveis',
    ];
}

function marchon_is_imoveis_manager(): bool {
    return current_user_can('edit_imoveis') && !current_user_can('administrator');
}

add_filter('register_post_type_args', function(array $args, string $post_type): array {
    if ($post_type !== 'imoveis') {
        return $args;
    }

    $args['capability_type'] = ['imovel', 'imoveis'];
    $args['map_meta_cap']    = true;
    $args['capabilities']    = marchon_get_imoveis_capabilities();
    $args['supports']        = ['title', 'editor', 'thumbnail'];

    return $args;
}, 20, 2);

add_action('init', function() {
    $imoveis_caps = array_values(marchon_get_imoveis_capabilities());

    $role = get_role('gestor_imoveis');
    if (!$role) {
        $role = add_role(
            'gestor_imoveis',
            'Gestor de Imoveis',
            [
                'read'         => true,
                'upload_files' => true,
            ]
        );
    }

    if ($role instanceof WP_Role) {
        $role->add_cap('read');
        $role->add_cap('upload_files');
        foreach ($imoveis_caps as $cap) {
            $role->add_cap($cap);
        }
    }

    $admin_role = get_role('administrator');
    if ($admin_role instanceof WP_Role) {
        foreach ($imoveis_caps as $cap) {
            $admin_role->add_cap($cap);
        }
    }
});

add_action('init', function() {
    $user = get_user_by('login', 'Marcos');
    if (!$user instanceof WP_User) {
        return;
    }

    if ($user->roles !== ['gestor_imoveis']) {
        $user->set_role('gestor_imoveis');
    }
}, 30);

add_action('admin_menu', function() {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    add_menu_page(
        'Imoveis',
        'Imoveis',
        'edit_imoveis',
        'edit.php?post_type=imoveis',
        '',
        'dashicons-admin-home',
        5
    );

    add_submenu_page(
        'edit.php?post_type=imoveis',
        'Todos os Imoveis',
        'Todos os Imoveis',
        'edit_imoveis',
        'edit.php?post_type=imoveis'
    );

    add_submenu_page(
        'edit.php?post_type=imoveis',
        'Adicionar Imovel',
        'Adicionar Imovel',
        'create_imoveis',
        'post-new.php?post_type=imoveis'
    );

    remove_menu_page('index.php');
    remove_menu_page('edit.php');
    remove_menu_page('upload.php');
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php');
    remove_menu_page('plugins.php');
    remove_menu_page('users.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
    remove_menu_page('contact-form-7');
    remove_menu_page('edit.php?post_type=acf-post-type');
    remove_menu_page('edit.php?post_type=acf-taxonomy');
    remove_menu_page('cptui_main_menu');
    remove_menu_page('sb-instagram-feed');
    remove_menu_page('sbi-feed-builder');
    remove_menu_page('mmarchon-instagram-sync');

}, 999);

add_action('admin_bar_menu', function(WP_Admin_Bar $wp_admin_bar) {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->add_node([
        'id' => 'wp-logo',
        'title' => '<span class="marchon-adminbar-logo" aria-hidden="true"></span><span class="screen-reader-text">IA Guru</span>',
        'href' => 'https://www.iaguru.com.br/',
        'meta' => [
            'title' => 'IA Guru',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ],
    ]);

    foreach ([
        'comments',
        'new-content',
        'customize',
        'edit-profile',
        'updates',
        'themes',
        'plugins',
        'site-name',
    ] as $node) {
        $wp_admin_bar->remove_node($node);
    }
}, 999);

function marchon_render_iaguru_admin_bar_logo(): void {
    $logo_url = get_stylesheet_directory_uri() . '/assets/images/logo-iaguru2026.png';
    ?>
    <style>
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item {
            padding-inline: 10px;
        }

        #wpadminbar #wp-admin-bar-wp-logo .marchon-adminbar-logo {
            display: block;
            width: 84px;
            height: 32px;
            background-image: url('<?php echo esc_url($logo_url); ?>');
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
        }
    </style>
    <?php
}

add_action('wp_head', 'marchon_render_iaguru_admin_bar_logo', 99);
add_action('admin_head', 'marchon_render_iaguru_admin_bar_logo', 99);

add_action('wp_dashboard_setup', function() {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
});

add_filter('use_block_editor_for_post_type', function(bool $use_block_editor, string $post_type): bool {
    if ($post_type === 'imoveis' && marchon_is_imoveis_manager()) {
        return false;
    }

    return $use_block_editor;
}, 10, 2);

add_action('add_meta_boxes_imoveis', function() {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    foreach ([
        'postexcerpt',
        'commentstatusdiv',
        'commentsdiv',
        'slugdiv',
        'trackbacksdiv',
        'revisionsdiv',
        'authordiv',
        'pageparentdiv',
        'formatdiv',
        'postcustom',
    ] as $meta_box_id) {
        remove_meta_box($meta_box_id, 'imoveis', 'normal');
        remove_meta_box($meta_box_id, 'imoveis', 'side');
        remove_meta_box($meta_box_id, 'imoveis', 'advanced');
    }
}, 99);

add_action('admin_head', function() {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'imoveis') {
        return;
    }
    ?>
    <style>
        #contextual-help-link-wrap,
        #screen-options-link-wrap,
        .editor-post-featured-image__toggle,
        .editor-post-excerpt,
        .editor-post-discussion,
        .editor-post-last-revision {
            display: none !important;
        }
    </style>
    <?php
});

add_action('admin_init', function() {
    if (!marchon_is_imoveis_manager()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) {
        return;
    }

    $allowed_screens = [
        'edit-imoveis',
        'imoveis',
        'upload',
    ];

    if (in_array($screen->id, $allowed_screens, true)) {
        return;
    }

    wp_safe_redirect(admin_url('post-new.php?post_type=imoveis'));
    exit;
});

add_filter('login_redirect', function(string $redirect_to, string $requested_redirect_to, WP_User|WP_Error $user): string {
    if (!$user instanceof WP_User) {
        return $redirect_to;
    }

    if (in_array('administrator', $user->roles, true)) {
        return $redirect_to;
    }

    if (in_array('gestor_imoveis', $user->roles, true)) {
        return admin_url('post-new.php?post_type=imoveis');
    }

    return $redirect_to;
}, 10, 3);

add_action('login_enqueue_scripts', function() {
    $logo_url = get_stylesheet_directory_uri() . '/assets/images/logo-iaguru2026.png';
    ?>
    <style>
        body.login {
            background: linear-gradient(180deg, #fdfcf8 0%, #f0e7d7 100%);
        }

        body.login div#login h1 a {
            background-image: url('<?php echo esc_url($logo_url); ?>');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            width: min(300px, 82vw);
            height: 110px;
            margin-bottom: 18px;
        }
    </style>
    <?php
});

add_filter('login_headerurl', function(): string {
    return 'https://www.iaguru.com.br/';
});

add_filter('login_headertext', function(): string {
    return 'IA Guru';
});

function marchon_render_iaguru_favicon(): void {
    $favicon_url = get_stylesheet_directory_uri() . '/assets/images/iaguru.png';
    ?>
    <link rel="icon" href="<?php echo esc_url($favicon_url); ?>" type="image/png" sizes="any">
    <link rel="shortcut icon" href="<?php echo esc_url($favicon_url); ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo esc_url($favicon_url); ?>">
    <?php
}

add_action('wp_head', 'marchon_render_iaguru_favicon', 1);
add_action('admin_head', 'marchon_render_iaguru_favicon', 1);
add_action('login_head', 'marchon_render_iaguru_favicon', 1);

function marchon_search_popup_post_type_label(string $post_type): string {
    return match ($post_type) {
        'imoveis' => 'Imóvel',
        'page'    => 'Página',
        'post'    => 'Post',
        default   => 'Conteúdo',
    };
}

function marchon_search_popup_result_item(WP_Post $post): array {
    $post_type = get_post_type($post);
    $meta_bits = [];

    if ($post_type === 'imoveis') {
        $codigo = get_field('codigo', $post->ID);
        $preco  = get_field('preco', $post->ID);

        if ($codigo) {
            $meta_bits[] = 'Cód. ' . wp_strip_all_tags((string) $codigo);
        }

        if ($preco) {
            $meta_bits[] = wp_strip_all_tags((string) $preco);
        }
    }

    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post)), 18);

    return [
        'id'          => $post->ID,
        'title'       => get_the_title($post),
        'url'         => get_permalink($post),
        'typeLabel'   => marchon_search_popup_post_type_label($post_type),
        'meta'        => implode(' • ', array_filter($meta_bits)),
        'description' => wp_strip_all_tags($description),
    ];
}

function marchon_handle_search_popup_ajax(): void {
    check_ajax_referer('marchon_search_popup', 'nonce');

    $term = sanitize_text_field(wp_unslash($_POST['term'] ?? ''));
    if (mb_strlen($term) < 2) {
        wp_send_json_success([
            'results' => [],
        ]);
    }

    $post_types = ['imoveis', 'page', 'post'];
    $ids = [];
    $normalized_term = preg_replace('/\s+/', '', $term);
    $is_code_like = (bool) preg_match('/^[0-9A-Za-z_-]+$/', $normalized_term);

    $codigo_query = new WP_Query([
        'post_type'           => 'imoveis',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'fields'              => 'ids',
        'meta_query'          => [
            'relation' => 'OR',
            [
                'key'     => 'codigo',
                'value'   => $term,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'codigo_referencia',
                'value'   => $term,
                'compare' => 'LIKE',
            ],
        ],
    ]);

    if (!empty($codigo_query->posts)) {
        $ids = array_map('intval', $codigo_query->posts);
    }

    if (!$is_code_like || empty($ids)) {
        $search_query = new WP_Query([
            'post_type'              => $post_types,
            'post_status'            => 'publish',
            's'                      => $term,
            'posts_per_page'         => 6,
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'fields'                 => 'ids',
            'orderby'                => ['post_type' => 'ASC', 'date' => 'DESC'],
            'suppress_filters'       => false,
        ]);

        if (!empty($search_query->posts)) {
            $ids = array_values(array_unique(array_merge($ids, array_map('intval', $search_query->posts))));
        }
    }

    $ids = array_slice($ids, 0, 6);

    if (empty($ids)) {
        wp_send_json_success([
            'results' => [],
        ]);
    }

    $ordered_posts = get_posts([
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => count($ids),
        'post__in'       => $ids,
        'orderby'        => 'post__in',
    ]);

    $results = array_map('marchon_search_popup_result_item', $ordered_posts);

    wp_send_json_success([
        'results' => $results,
    ]);
}

add_action('wp_ajax_marchon_search_popup', 'marchon_handle_search_popup_ajax');
add_action('wp_ajax_nopriv_marchon_search_popup', 'marchon_handle_search_popup_ajax');

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

add_action('init', function() {
    register_post_type('vip_lead', [
        'labels' => [
            'name'          => 'Alertas VIP',
            'singular_name' => 'Alerta VIP',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-email-alt',
        'supports'            => ['title'],
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'menu_position'       => 26,
        'exclude_from_search' => true,
    ]);
});

function marchon_render_vip_alert_form(): string {
    $status = sanitize_key((string) ($_GET['alerta_vip'] ?? ''));
    $message = '';
    $message_class = '';

    if ($status === 'sucesso') {
        $message = 'Cadastro recebido. O lead foi salvo no staging para acompanhamento interno.';
        $message_class = 'is-success';
    } elseif ($status === 'erro') {
        $message = 'Não foi possível registrar o cadastro agora. Revise os campos e tente novamente.';
        $message_class = 'is-error';
    }

    ob_start();
    ?>
    <div class="vip-alert-card">
        <div class="secao-label">Alerta VIP</div>
        <h3 class="vip-alert-title">Não encontrou o imóvel ideal?</h3>
        <p class="vip-alert-text">Cadastre-se no nosso Alerta VIP e receba as novidades de Lumiar e Nova Friburgo antes de irem para o Instagram.</p>
        <?php if ($message !== '') : ?>
            <div class="vip-alert-message <?php echo esc_attr($message_class); ?>"><?php echo esc_html($message); ?></div>
        <?php endif; ?>
        <form class="vip-alert-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="marchon_vip_alert_signup">
            <input type="hidden" name="source" value="home">
            <?php wp_nonce_field('marchon_vip_alert_signup', 'marchon_vip_nonce'); ?>
            <div class="vip-alert-grid">
                <label>
                    <span>Nome</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" required>
                </label>
                <label>
                    <span>WhatsApp</span>
                    <input type="tel" name="whatsapp" required>
                </label>
                <label>
                    <span>Interesse</span>
                    <input type="text" name="interest" placeholder="Ex.: terreno em Lumiar, casa com vista, sítio">
                </label>
            </div>
            <button type="submit" class="btn-verde vip-alert-submit">Quero receber antes</button>
        </form>
    </div>
    <?php

    return (string) ob_get_clean();
}

function marchon_handle_vip_alert_signup(): void {
    $redirect = home_url('/?alerta_vip=erro');

    if (!wp_verify_nonce($_POST['marchon_vip_nonce'] ?? '', 'marchon_vip_alert_signup')) {
        wp_safe_redirect($redirect);
        exit;
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $whatsapp = sanitize_text_field(wp_unslash($_POST['whatsapp'] ?? ''));
    $interest = sanitize_text_field(wp_unslash($_POST['interest'] ?? ''));
    $source = sanitize_text_field(wp_unslash($_POST['source'] ?? 'home'));

    if ($name === '' || $email === '' || $whatsapp === '' || !is_email($email)) {
        wp_safe_redirect($redirect);
        exit;
    }

    $lead_id = wp_insert_post([
        'post_type'   => 'vip_lead',
        'post_status' => 'publish',
        'post_title'  => sprintf('%s - %s', $name, $email),
    ], true);

    if (is_wp_error($lead_id)) {
        wp_safe_redirect($redirect);
        exit;
    }

    update_post_meta($lead_id, 'vip_name', $name);
    update_post_meta($lead_id, 'vip_email', $email);
    update_post_meta($lead_id, 'vip_whatsapp', $whatsapp);
    update_post_meta($lead_id, 'vip_interest', $interest);
    update_post_meta($lead_id, 'vip_source', $source);

    wp_safe_redirect(home_url('/?alerta_vip=sucesso#alerta-vip'));
    exit;
}

add_action('admin_post_nopriv_marchon_vip_alert_signup', 'marchon_handle_vip_alert_signup');
add_action('admin_post_marchon_vip_alert_signup', 'marchon_handle_vip_alert_signup');

function marchon_get_instagram_feed_shortcode(): string {
    if (shortcode_exists('instagram-feed')) {
        return '[instagram-feed]';
    }

    if (shortcode_exists('instagram_feed')) {
        return '[instagram_feed limit="6" only_linked="1"]';
    }

    return '';
}

function marchon_get_instagram_local_fallback_images(): array {
    $images = [];

    $query = new WP_Query([
        'post_type'      => 'imoveis',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ]);

    while ($query->have_posts()) {
        $query->the_post();
        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large');
        if ($thumbnail) {
            $images[] = $thumbnail;
        }
    }
    wp_reset_postdata();

    $images = array_merge($images, [
        home_url('/wp-content/uploads/2026/04/mmarchon-codigo-001.jpg'),
        home_url('/wp-content/uploads/2026/04/002-1.jpg'),
        home_url('/wp-content/uploads/2026/04/002-6.jpg'),
        home_url('/wp-content/uploads/2026/04/0001-2.jpg'),
    ]);

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $images = array_map(function (string $url) use ($site_host): string {
        return preg_replace('#https?://(dev|staging|www)?\.?mmarchonimoveis\.com\.br#', 'https://' . $site_host, $url);
    }, $images);

    return array_values(array_unique(array_filter($images)));
}

function marchon_prepare_instagram_feed_html(string $html): string {
    $fallback_images = marchon_get_instagram_local_fallback_images();
    $index = 0;

    return (string) preg_replace_callback(
        '/(<a\b[^>]*\bclass="[^"]*\bsbi_photo\b[^"]*"[^>]*\bdata-full-res="([^"]+)"[^>]*>)(.*?)(<\/a>)/is',
        function (array $matches) use ($fallback_images, &$index): string {
            $fallback_url = $fallback_images[$index % max(1, count($fallback_images))] ?? $matches[2];
            $image_url = esc_url($fallback_url);
            $link = $matches[1];
            $style = 'background-image: url(\'' . $image_url . '\');';
            $index++;

            if (str_contains($link, ' style="')) {
                $link = preg_replace('/ style="([^"]*)"/i', ' style="$1 ' . esc_attr($style) . '"', $link, 1);
            } else {
                $link = preg_replace('/>$/', ' style="' . esc_attr($style) . '">', $link, 1);
            }
            $link = preg_replace('/>$/', ' data-local-image="' . esc_url($image_url) . '">', $link, 1);

            $inner = (string) preg_replace(
                '/<img\b[^>]*\bsrc="[^"]*placeholder\.png"[^>]*>/i',
                '<img src="' . $image_url . '" alt="" aria-hidden="true">',
                $matches[3],
                1
            );

            return $link . $inner . $matches[4];
        },
        $html
    );
}

function marchon_render_instagram_feed(): string {
    $shortcode = marchon_get_instagram_feed_shortcode();

    if ($shortcode !== '') {
        $output = (string) do_shortcode($shortcode);
        if ($output !== '' && !str_contains($output, 'sbi_mod_error') && !str_contains($output, 'No feed found')) {
            return marchon_prepare_instagram_feed_html($output);
        }
    }

    return sprintf(
        '<div class="marchon-instagram-fallback"><p>O feed do Instagram sera exibido aqui assim que a conta for conectada no painel.</p><a class="btn-verde" href="%s" target="_blank" rel="noopener noreferrer">Abrir Instagram</a></div>',
        esc_url('https://www.instagram.com/mmimoveis__/')
    );
}

function marchon_render_editorial_posts(int $limit = 3): string {
    $query = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => max(1, $limit),
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    ]);

    if (!$query->have_posts()) {
        return '';
    }

    ob_start();
    ?>
    <div class="marchon-editorial-grid">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article class="marchon-editorial-card">
                <div class="marchon-editorial-meta"><?php echo esc_html(get_the_date('d.m.Y')); ?></div>
                <h3 class="marchon-editorial-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p class="marchon-editorial-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 30)); ?></p>
                <a class="marchon-editorial-link" href="<?php the_permalink(); ?>">Ler conteúdo completo</a>
            </article>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    return (string) ob_get_clean();
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
        echo marchon_render_imovel_card(get_the_ID(), 'h3');
    endwhile; wp_reset_postdata();
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
            'foto'      => $foto ? $foto['url'] : '',
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
            <h1 class="banner-titulo" id="banner-titulo"><?php echo esc_html($slides[0]['titulo']); ?></h1>
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

// ── FALLBACK DO FEED DO INSTAGRAM ───────────────────────────────────────────
add_action('wp_footer', function() { ?>
    <script>
    (function() {
        function getInstagramImageUrl(link) {
            if (!link) return '';

            var localUrl = link.getAttribute('data-local-image');
            if (localUrl) return localUrl;

            var directUrl = link.getAttribute('data-full-res');
            if (directUrl) return directUrl;

            var srcSet = link.getAttribute('data-img-src-set');
            if (!srcSet) return '';

            try {
                var parsed = JSON.parse(srcSet.replace(/&quot;/g, '"'));
                return parsed['640'] || parsed['320'] || parsed['150'] || parsed.d || '';
            } catch (error) {
                return '';
            }
        }

        function restoreInstagramImages() {
            document.querySelectorAll('.marchon-instagram-feed-shell .sbi_photo img').forEach(function(img) {
                if (img.src.indexOf('/placeholder.png') === -1) return;

                var url = getInstagramImageUrl(img.closest('.sbi_photo'));
                if (!url) return;

                img.src = url;
                img.removeAttribute('srcset');
                img.alt = '';
                img.setAttribute('aria-hidden', 'true');
            });
        }

        document.addEventListener('DOMContentLoaded', restoreInstagramImages);
        window.addEventListener('load', restoreInstagramImages);
        setTimeout(restoreInstagramImages, 1200);
    })();
    </script>
<?php });

// ── BOTÃO WHATSAPP FLUTUANTE ────────────────────────────────────────────────
add_action('wp_footer', function() { ?>
    <?php if (is_singular('imoveis')) {
        return;
    } ?>
    <a href="<?php echo esc_url(marchon_get_whatsapp_url([
        'source' => 'botão flutuante do site',
        'action' => 'falar com a equipe',
    ])); ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-float" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
<?php });
