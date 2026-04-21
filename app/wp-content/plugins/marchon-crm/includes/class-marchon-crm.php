<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Marchon_CRM
{
    private static string $plugin_file = '';

    private const INTEREST_TYPES = [
        'casa' => 'Casa',
        'apartamento' => 'Apartamento',
        'comercial' => 'Comercial',
        'terreno' => 'Terreno',
        'sitio' => 'Sitio',
        'fazenda' => 'Fazenda',
        'chacara' => 'Chacara',
        'outros' => 'Outros',
    ];

    private const TOPOGRAPHIES = [
        'plano' => 'Plano',
        'aclive' => 'Aclive',
        'declive' => 'Declive',
        'irregular' => 'Irregular',
    ];

    private const PURPOSES = [
        'residencial' => 'Residencial',
        'comercial' => 'Comercial',
        'investimento' => 'Investimento',
    ];

    private const CLIENT_STATUSES = [
        'novo' => 'Novo',
        'em_atendimento' => 'Em atendimento',
        'proposta' => 'Proposta',
        'convertido' => 'Convertido',
        'arquivado' => 'Arquivado',
    ];

    private const META_FIELDS = [
        '_mcrm_phone' => 'string',
        '_mcrm_email' => 'string',
        '_mcrm_cpf' => 'string',
        '_mcrm_interest_type' => 'string',
        '_mcrm_region' => 'string',
        '_mcrm_price_range' => 'string',
        '_mcrm_client_status' => 'string',
        '_mcrm_assigned_broker' => 'integer',
        '_mcrm_notes' => 'string',
        '_mcrm_terrain_area_min' => 'number',
        '_mcrm_terrain_area_max' => 'number',
        '_mcrm_terrain_topography' => 'string',
        '_mcrm_terrain_purpose' => 'string',
        '_mcrm_terrain_gated_community' => 'string',
    ];

    public static function boot(string $plugin_file): void
    {
        self::$plugin_file = $plugin_file;

        add_action('init', [self::class, 'register_post_type']);
        add_action('init', [self::class, 'register_meta']);
        add_action('init', [self::class, 'ensure_front_page'], 20);
        add_action('add_meta_boxes', [self::class, 'register_meta_boxes']);
        add_action('save_post_mcrm_client', [self::class, 'save_client_meta']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_front_assets']);
        add_action('admin_menu', [self::class, 'register_admin_menu']);
        add_action('admin_init', [self::class, 'redirect_admin_users_to_frontend'], 1);
        add_action('admin_post_mcrm_save_front_client', [self::class, 'handle_front_client_save']);
        add_action('restrict_manage_posts', [self::class, 'render_admin_filters']);
        add_action('pre_get_posts', [self::class, 'apply_admin_filters']);
        add_filter('manage_mcrm_client_posts_columns', [self::class, 'register_columns']);
        add_action('manage_mcrm_client_posts_custom_column', [self::class, 'render_columns'], 10, 2);
        add_filter('login_redirect', [self::class, 'frontend_login_redirect'], 20, 3);
        add_shortcode('marchon_crm_app', [self::class, 'render_frontend_app']);
        add_action('wp_ajax_mcrm_quick_search', [self::class, 'handle_ajax_quick_search']);
    }

    public static function register_post_type(): void
    {
        register_post_type('mcrm_client', [
            'labels' => [
                'name' => 'Clientes CRM',
                'singular_name' => 'Cliente CRM',
                'menu_name' => 'Clientes',
                'add_new_item' => 'Adicionar cliente',
                'edit_item' => 'Editar cliente',
                'new_item' => 'Novo cliente',
                'view_item' => 'Ver cliente',
                'search_items' => 'Buscar clientes',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public static function register_meta(): void
    {
        foreach (self::META_FIELDS as $key => $type) {
            register_post_meta('mcrm_client', $key, [
                'show_in_rest' => true,
                'single' => true,
                'type' => $type,
                'sanitize_callback' => [self::class, 'sanitize_meta_value'],
                'auth_callback' => static fn () => current_user_can('edit_posts'),
            ]);
        }
    }

    public static function register_meta_boxes(): void
    {
        add_meta_box(
            'mcrm_client_details',
            'Dados do Cliente e Interesse',
            [self::class, 'render_client_metabox'],
            'mcrm_client',
            'normal',
            'high'
        );
    }

    public static function render_client_metabox(\WP_Post $post): void
    {
        wp_nonce_field('mcrm_save_client', 'mcrm_nonce');
        $values = self::get_meta_values($post->ID);
        $brokers = self::get_brokers();
        ?>
        <style>
            .mcrm-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
            .mcrm-grid .full { grid-column:1 / -1; }
            .mcrm-box { margin-top:20px; padding:16px; background:#f6f7f7; border:1px solid #dcdcde; }
            .mcrm-field label { display:block; font-weight:600; margin-bottom:6px; }
            .mcrm-field input, .mcrm-field select, .mcrm-field textarea { width:100%; }
            .mcrm-help { color:#50575e; margin-top:6px; }
        </style>

        <div class="mcrm-grid">
            <?php self::render_input('Nome do cliente', 'post_title', $post->post_title); ?>
            <?php self::render_input('CPF', '_mcrm_cpf', $values['_mcrm_cpf'], 'text', '', 'Apenas numeros ou CPF formatado.', ['data-mask' => 'cpf', 'maxlength' => '14']); ?>
            <?php self::render_input('Telefone', '_mcrm_phone', $values['_mcrm_phone'], 'text', '', 'DDD + numero do cliente.', ['data-mask' => 'phone', 'maxlength' => '15']); ?>
            <?php self::render_input('Email', '_mcrm_email', $values['_mcrm_email'], 'email'); ?>
        </div>

        <div class="mcrm-box">
            <h3>Pipeline Comercial</h3>
            <div class="mcrm-grid">
                <?php self::render_select('Tipo de interesse', '_mcrm_interest_type', $values['_mcrm_interest_type'], self::INTEREST_TYPES); ?>
                <?php self::render_select('Status do cliente', '_mcrm_client_status', $values['_mcrm_client_status'], self::CLIENT_STATUSES); ?>
                <?php self::render_input('Regiao desejada', '_mcrm_region', $values['_mcrm_region']); ?>
                <?php self::render_input('Faixa de valor', '_mcrm_price_range', $values['_mcrm_price_range']); ?>
                <?php self::render_select('Corretor responsavel', '_mcrm_assigned_broker', $values['_mcrm_assigned_broker'], $brokers); ?>
                <div class="mcrm-field full">
                    <label for="_mcrm_notes">Observacoes</label>
                    <textarea id="_mcrm_notes" name="_mcrm_notes" rows="5"><?php echo esc_textarea($values['_mcrm_notes']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="mcrm-box" data-mcrm-terrain-fields>
            <h3>Informacoes do Terreno</h3>
            <div class="mcrm-grid">
                <?php self::render_input('Metragem minima desejada (m²)', '_mcrm_terrain_area_min', $values['_mcrm_terrain_area_min'], 'number', '1'); ?>
                <?php self::render_input('Metragem maxima desejada (m²)', '_mcrm_terrain_area_max', $values['_mcrm_terrain_area_max'], 'number', '1'); ?>
                <?php self::render_select('Topografia', '_mcrm_terrain_topography', $values['_mcrm_terrain_topography'], self::TOPOGRAPHIES); ?>
                <?php self::render_select('Finalidade', '_mcrm_terrain_purpose', $values['_mcrm_terrain_purpose'], self::PURPOSES); ?>
                <?php self::render_select('Interesse em condominio', '_mcrm_terrain_gated_community', $values['_mcrm_terrain_gated_community'], [
                    'sim' => 'Sim',
                    'nao' => 'Nao',
                ]); ?>
            </div>
        </div>
        <?php
    }

    public static function save_client_meta(int $post_id): void
    {
        if (!isset($_POST['mcrm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mcrm_nonce'])), 'mcrm_save_client')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['post_title'])) {
            remove_action('save_post_mcrm_client', [self::class, 'save_client_meta']);
            wp_update_post([
                'ID' => $post_id,
                'post_title' => sanitize_text_field(wp_unslash($_POST['post_title'])),
                'post_author' => (int) get_current_user_id(),
            ]);
            add_action('save_post_mcrm_client', [self::class, 'save_client_meta']);
        }

        foreach (array_keys(self::META_FIELDS) as $field) {
            $raw_value = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';

            if ($field === '_mcrm_assigned_broker' && $raw_value === '') {
                $raw_value = (string) get_current_user_id();
            }

            update_post_meta($post_id, $field, self::sanitize_meta_value($raw_value, $field));
        }
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'mcrm_client') {
            return;
        }

        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_script(
            'marchon-crm-admin',
            plugins_url('assets/admin.js', self::$plugin_file),
            [],
            '0.2.0',
            true
        );
    }

    public static function enqueue_front_assets(): void
    {
        if (!is_page()) {
            return;
        }

        global $post;
        if (!$post instanceof \WP_Post || !has_shortcode($post->post_content, 'marchon_crm_app')) {
            return;
        }

        wp_enqueue_style(
            'marchon-crm-app',
            plugins_url('assets/crm-app.css', self::$plugin_file),
            [],
            '0.4.0'
        );

        wp_enqueue_script(
            'marchon-crm-front',
            plugins_url('assets/crm-app.js', self::$plugin_file),
            [],
            '0.4.0',
            true
        );

        wp_localize_script('marchon-crm-front', 'mcrmData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mcrm_quick_search'),
        ]);
    }

    public static function ensure_front_page(): void
    {
        $page_id = (int) get_option('mcrm_front_page_id', 0);
        if ($page_id > 0 && get_post_status($page_id)) {
            return;
        }

        $existing = get_page_by_path('marchon-crm');
        if ($existing instanceof \WP_Post) {
            update_option('mcrm_front_page_id', (int) $existing->ID);
            return;
        }

        $page_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Marchon CRM',
            'post_name' => 'marchon-crm',
            'post_content' => '[marchon_crm_app]',
        ]);

        if (!is_wp_error($page_id) && $page_id > 0) {
            update_option('mcrm_front_page_id', (int) $page_id);
        }
    }

    public static function redirect_admin_users_to_frontend(): void
    {
        if (!is_admin() || !self::current_user_can_access_crm() || current_user_can('manage_options')) {
            return;
        }

        if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        global $pagenow;
        if (in_array($pagenow, ['admin-post.php', 'admin-ajax.php', 'async-upload.php'], true)) {
            return;
        }

        wp_safe_redirect(self::get_frontend_page_url());
        exit;
    }

    public static function frontend_login_redirect(string $redirect_to, string $requested_redirect_to, $user): string
    {
        if (!$user instanceof \WP_User) {
            return $redirect_to;
        }

        if (in_array('administrator', $user->roles, true)) {
            return $redirect_to;
        }

        if (self::user_has_crm_access($user)) {
            return self::get_frontend_page_url();
        }

        return $redirect_to;
    }

    public static function handle_front_client_save(): void
    {
        if (!is_user_logged_in() || !self::current_user_can_access_crm()) {
            wp_die('Acesso negado.');
        }

        check_admin_referer('mcrm_front_save_client', 'mcrm_front_nonce');

        $redirect = isset($_POST['_mcrm_redirect']) ? esc_url_raw(wp_unslash($_POST['_mcrm_redirect'])) : self::get_frontend_page_url();
        $client_id = isset($_POST['_mcrm_client_id']) ? absint($_POST['_mcrm_client_id']) : 0;

        if ($client_id > 0 && !self::user_can_access_client($client_id)) {
            wp_safe_redirect(add_query_arg('mcrm_notice', 'forbidden', $redirect));
            exit;
        }

        $post_data = [
            'post_type' => 'mcrm_client',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field((string) wp_unslash($_POST['post_title'] ?? '')),
            'post_author' => $client_id > 0 ? (int) get_post_field('post_author', $client_id) : get_current_user_id(),
        ];

        if ($client_id > 0) {
            $post_data['ID'] = $client_id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
            $client_id = is_wp_error($result) ? 0 : (int) $result;
        }

        if (is_wp_error($result) || $client_id <= 0) {
            wp_safe_redirect(add_query_arg('mcrm_notice', 'error', $redirect));
            exit;
        }

        foreach (array_keys(self::META_FIELDS) as $field) {
            $raw_value = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';

            if ($field === '_mcrm_assigned_broker' && !current_user_can('manage_options')) {
                $raw_value = (string) get_current_user_id();
            }

            update_post_meta($client_id, $field, self::sanitize_meta_value($raw_value, $field));
        }

        wp_safe_redirect(add_query_arg([
            'mcrm_notice' => 'saved',
            'client_id' => $client_id,
        ], $redirect));
        exit;
    }

    public static function register_admin_menu(): void
    {
        add_menu_page(
            'Marchon CRM',
            'Marchon CRM',
            'edit_posts',
            'marchon-crm',
            [self::class, 'render_dashboard_page'],
            'dashicons-id-alt',
            25
        );

        add_submenu_page(
            'marchon-crm',
            'Painel CRM',
            'Painel',
            'edit_posts',
            'marchon-crm',
            [self::class, 'render_dashboard_page']
        );

        add_submenu_page(
            'marchon-crm',
            'Clientes CRM',
            'Clientes',
            'edit_posts',
            'edit.php?post_type=mcrm_client'
        );

        add_submenu_page(
            'marchon-crm',
            'Novo Cliente CRM',
            'Adicionar cliente',
            'edit_posts',
            'post-new.php?post_type=mcrm_client'
        );

        add_submenu_page(
            'marchon-crm',
            'Relatorios CRM',
            'Relatorios',
            'edit_posts',
            'mcrm-reports',
            [self::class, 'render_reports_page']
        );
    }

    public static function render_frontend_app(): string
    {
        if (!is_user_logged_in() || !self::current_user_can_access_crm()) {
            return self::render_front_login();
        }

        $page_url = self::get_frontend_page_url();
        $view = isset($_GET['mcrm_view']) ? sanitize_key(wp_unslash($_GET['mcrm_view'])) : '';
        $editing_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
        $editing_post = $editing_id > 0 && self::user_can_access_client($editing_id) ? get_post($editing_id) : null;
        $values = $editing_post instanceof \WP_Post ? self::get_meta_values($editing_post->ID) : self::get_empty_front_values();
        $brokers = self::get_brokers();
        $clients = self::get_front_clients();
        $current_user = wp_get_current_user();
        $notice = isset($_GET['mcrm_notice']) ? sanitize_text_field(wp_unslash($_GET['mcrm_notice'])) : '';
        $status_breakdown = self::aggregate_by_map('_mcrm_client_status', self::CLIENT_STATUSES);
        $interest_breakdown = self::aggregate_by_map('_mcrm_interest_type', self::INTEREST_TYPES);

        ob_start();
        ?>
        <section class="mcrm-app-shell">
            <div class="mcrm-app-layout">
                <aside class="mcrm-sidebar">
                    <div class="mcrm-sidebar-brand">
                        <p class="mcrm-eyebrow">Marchon CRM</p>
                        <h2>Plataforma comercial</h2>
                        <p>Operacao de leads, clientes e interesses imobiliarios em uma interface dedicada.</p>
                    </div>

                    <nav class="mcrm-sidebar-nav" aria-label="Navegacao do CRM">
                        <a href="#mcrm-overview" class="mcrm-nav-item is-active">Visao Geral</a>
                        <a href="#mcrm-clients" class="mcrm-nav-item">Clientes</a>
                        <a href="#mcrm-form" class="mcrm-nav-item">Cadastro</a>
                    </nav>

                    <div class="mcrm-sidebar-metrics">
                        <div class="mcrm-preview-card mcrm-preview-primary">
                            <span class="mcrm-preview-label">Radar comercial</span>
                            <strong><?php echo esc_html((string) self::count_by_meta('_mcrm_client_status', 'novo')); ?> leads novos</strong>
                            <p>Leads prontos para atendimento no seu painel.</p>
                        </div>
                        <div class="mcrm-preview-card">
                            <span class="mcrm-preview-label">Corretor logado</span>
                            <strong><?php echo esc_html($current_user->display_name ?: 'Usuario'); ?></strong>
                        </div>
                    </div>

                    <div class="mcrm-hero-actions">
                        <a class="mcrm-btn mcrm-btn-primary" href="<?php echo esc_url(add_query_arg('client_id', 0, $page_url)); ?>">Novo cliente</a>
                        <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url(wp_logout_url($page_url)); ?>">Sair</a>
                    </div>
                </aside>

                <div class="mcrm-main">
                    <header class="mcrm-topbar">
                        <div class="mcrm-topbar-brand">
                            <span class="mcrm-topbar-kicker">Marchon Imoveis</span>
                            <strong>CRM Comercial</strong>
                        </div>
                        <form class="mcrm-topbar-search" method="get" action="<?php echo esc_url($page_url); ?>">
                            <input type="text" name="mcrm_name" data-mcrm-quick-search placeholder="Buscar por nome, CPF ou telefone" value="<?php echo esc_attr(isset($_GET['mcrm_name']) ? sanitize_text_field(wp_unslash($_GET['mcrm_name'])) : ''); ?>" autocomplete="off" autofocus>
                            <?php if (isset($_GET['mcrm_interest_type'])) : ?><input type="hidden" name="mcrm_interest_type" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['mcrm_interest_type']))); ?>"><?php endif; ?>
                            <?php if (isset($_GET['mcrm_client_status'])) : ?><input type="hidden" name="mcrm_client_status" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['mcrm_client_status']))); ?>"><?php endif; ?>
                            <?php if (isset($_GET['mcrm_cpf'])) : ?><input type="hidden" name="mcrm_cpf" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['mcrm_cpf']))); ?>"><?php endif; ?>
                            <?php if (isset($_GET['mcrm_phone'])) : ?><input type="hidden" name="mcrm_phone" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['mcrm_phone']))); ?>"><?php endif; ?>
                            <button type="submit">Buscar</button>
                        </form>
                        <div class="mcrm-topbar-user">
                            <div class="mcrm-user-chip">
                                <span class="mcrm-user-role"><?php echo current_user_can('manage_options') ? 'Administrador' : 'Corretor'; ?></span>
                                <strong><?php echo esc_html($current_user->display_name ?: 'Usuario'); ?></strong>
                            </div>
                            <div class="mcrm-topbar-actions">
                                <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url(add_query_arg('client_id', 0, $page_url)); ?>">Novo</a>
                                <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url(wp_logout_url($page_url)); ?>">Sair</a>
                            </div>
                        </div>
                    </header>

                    <div id="mcrm-overview"></div>

                    <?php self::render_front_notice($notice); ?>

                    <div class="mcrm-stat-grid">
                        <?php self::render_front_stat('Clientes visiveis', (string) self::count_clients(), add_query_arg('mcrm_view', 'clientes', $page_url)); ?>
                        <?php self::render_front_stat('Novos leads', (string) self::count_by_meta('_mcrm_client_status', 'novo'), add_query_arg(['mcrm_view' => 'clientes', 'mcrm_client_status' => 'novo'], $page_url)); ?>
                        <?php self::render_front_stat('Interesse em terreno', (string) self::count_by_meta('_mcrm_interest_type', 'terreno'), add_query_arg(['mcrm_view' => 'clientes', 'mcrm_interest_type' => 'terreno'], $page_url)); ?>
                        <?php self::render_front_stat('Corretor logado', $current_user->display_name ?: 'Usuario'); ?>
                    </div>

                    <div class="mcrm-chart-grid">
                        <section class="mcrm-panel mcrm-chart-panel">
                            <div class="mcrm-panel-head">
                                <h2>Funil por status</h2>
                                <p>Leitura imediata do pipeline comercial atual.</p>
                            </div>
                            <div class="mcrm-funnel-list">
                                <?php self::render_chart_rows($status_breakdown, 'status'); ?>
                            </div>
                        </section>
                        <section class="mcrm-panel mcrm-chart-panel">
                            <div class="mcrm-panel-head">
                                <h2>Distribuicao por interesse</h2>
                                <p>Onde a demanda esta concentrada hoje.</p>
                            </div>
                            <div class="mcrm-funnel-list">
                                <?php self::render_chart_rows($interest_breakdown, 'interest'); ?>
                            </div>
                        </section>
                    </div>

                    <?php if ($view === 'clientes') : ?>
                    <?php self::render_clients_table($clients, $page_url); ?>
                    <?php else : ?>
                    <div class="mcrm-workspace">
                <div class="mcrm-panel mcrm-panel-list" id="mcrm-clients">
                    <div class="mcrm-panel-head">
                        <h2>Clientes</h2>
                        <p>Busca por nome, CPF, telefone, status e tipo com leitura imediata de prioridade.</p>
                    </div>
                    <?php self::render_front_filters($page_url); ?>
                    <div class="mcrm-card-list">
                        <?php if ($clients->have_posts()) : ?>
                            <?php foreach ($clients->posts as $client) : ?>
                                <?php $client_values = self::get_meta_values($client->ID); ?>
                                <article class="mcrm-client-card">
                                    <div class="mcrm-client-top">
                                        <div>
                                            <div class="mcrm-client-topline">
                                                <span class="mcrm-status-badge mcrm-status-<?php echo esc_attr(self::status_class($client_values['_mcrm_client_status'])); ?>">
                                                    <?php echo esc_html(self::label_for(self::CLIENT_STATUSES, $client_values['_mcrm_client_status'])); ?>
                                                </span>
                                                <span class="mcrm-type-pill"><?php echo esc_html(self::label_for(self::INTEREST_TYPES, $client_values['_mcrm_interest_type'])); ?></span>
                                            </div>
                                            <h3><?php echo esc_html(get_the_title($client->ID)); ?></h3>
                                            <p><?php echo esc_html(self::format_cpf($client_values['_mcrm_cpf'])); ?><?php echo $client_values['_mcrm_cpf'] !== '' ? ' · ' : ''; ?><?php echo esc_html(self::format_phone($client_values['_mcrm_phone'])); ?></p>
                                        </div>
                                        <a class="mcrm-inline-link" href="<?php echo esc_url(add_query_arg('client_id', $client->ID, $page_url)); ?>">Editar</a>
                                    </div>
                                    <div class="mcrm-client-priority">
                                        <span class="mcrm-priority-label">Radar</span>
                                        <strong><?php echo esc_html(self::priority_label($client_values['_mcrm_client_status'], $client_values['_mcrm_interest_type'])); ?></strong>
                                    </div>
                                    <div class="mcrm-meta-grid">
                                        <span><strong>Regiao</strong><?php echo esc_html($client_values['_mcrm_region'] !== '' ? $client_values['_mcrm_region'] : 'Nao informada'); ?></span>
                                        <span><strong>Faixa</strong><?php echo esc_html($client_values['_mcrm_price_range'] !== '' ? $client_values['_mcrm_price_range'] : 'Nao informada'); ?></span>
                                        <span><strong>Corretor</strong><?php echo esc_html(self::get_broker_name((int) $client_values['_mcrm_assigned_broker'])); ?></span>
                                        <span><strong>Atualizacao</strong><?php echo esc_html(get_the_date('d/m/Y', $client->ID)); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="mcrm-empty-state">
                                <h3>Nenhum cliente encontrado.</h3>
                                <p>Ajuste os filtros ou cadastre um novo lead no formulario ao lado.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mcrm-panel mcrm-panel-form" id="mcrm-form">
                    <div class="mcrm-panel-head">
                        <h2><?php echo $editing_post instanceof \WP_Post ? 'Editar cliente' : 'Novo cliente'; ?></h2>
                        <p><?php echo $editing_post instanceof \WP_Post ? 'Atualize os dados comerciais e o interesse do lead.' : 'Cadastro rapido, pensado para atendimento comercial em mobile e desktop.'; ?></p>
                    </div>
                    <div class="mcrm-stepper">
                        <span class="mcrm-step is-active">1. Cliente</span>
                        <span class="mcrm-step">2. Pipeline</span>
                        <span class="mcrm-step">3. Interesse</span>
                    </div>
                    <form class="mcrm-front-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="mcrm_save_front_client">
                        <input type="hidden" name="_mcrm_client_id" value="<?php echo esc_attr($editing_post instanceof \WP_Post ? (string) $editing_post->ID : '0'); ?>">
                        <input type="hidden" name="_mcrm_redirect" value="<?php echo esc_url($page_url); ?>">
                        <?php wp_nonce_field('mcrm_front_save_client', 'mcrm_front_nonce'); ?>

                        <div class="mcrm-form-section">
                            <div class="mcrm-section-head">
                                <span>Etapa 1</span>
                                <h3>Identificacao do cliente</h3>
                            </div>
                            <div class="mcrm-form-grid">
                            <?php self::render_front_input('Nome do cliente', 'post_title', $editing_post instanceof \WP_Post ? $editing_post->post_title : ''); ?>
                            <?php self::render_front_input('CPF', '_mcrm_cpf', $values['_mcrm_cpf'], 'text', ['data-mask' => 'cpf', 'maxlength' => '14']); ?>
                            <?php self::render_front_input('Telefone', '_mcrm_phone', $values['_mcrm_phone'], 'text', ['data-mask' => 'phone', 'maxlength' => '15']); ?>
                            <?php self::render_front_input('Email', '_mcrm_email', $values['_mcrm_email'], 'email'); ?>
                            </div>
                        </div>

                        <div class="mcrm-form-section">
                            <div class="mcrm-section-head">
                                <span>Etapa 2</span>
                                <h3>Pipeline comercial</h3>
                            </div>
                            <div class="mcrm-form-grid">
                            <?php self::render_front_select('Tipo de interesse', '_mcrm_interest_type', $values['_mcrm_interest_type'], self::INTEREST_TYPES, ['data-mcrm-interest' => '1']); ?>
                            <?php self::render_front_select('Status', '_mcrm_client_status', $values['_mcrm_client_status'], self::CLIENT_STATUSES); ?>
                            <?php self::render_front_input('Regiao desejada', '_mcrm_region', $values['_mcrm_region']); ?>
                            <?php self::render_front_input('Faixa de valor', '_mcrm_price_range', $values['_mcrm_price_range']); ?>

                            <?php if (current_user_can('manage_options')) : ?>
                                <?php self::render_front_select('Corretor responsavel', '_mcrm_assigned_broker', $values['_mcrm_assigned_broker'], $brokers); ?>
                            <?php else : ?>
                                <input type="hidden" name="_mcrm_assigned_broker" value="<?php echo esc_attr((string) get_current_user_id()); ?>">
                                <div class="mcrm-front-field">
                                    <label>Corretor responsavel</label>
                                    <div class="mcrm-static-field"><?php echo esc_html($current_user->display_name ?: 'Usuario'); ?></div>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>

                        <div class="mcrm-form-section">
                            <div class="mcrm-section-head">
                                <span>Etapa 3</span>
                                <h3>Interesse e observacoes</h3>
                            </div>
                            <div class="mcrm-form-grid">
                            <div class="mcrm-front-box" data-mcrm-terrain>
                                <h3>Dados do terreno</h3>
                                <div class="mcrm-form-grid">
                                    <?php self::render_front_input('Metragem minima (m²)', '_mcrm_terrain_area_min', $values['_mcrm_terrain_area_min'], 'number'); ?>
                                    <?php self::render_front_input('Metragem maxima (m²)', '_mcrm_terrain_area_max', $values['_mcrm_terrain_area_max'], 'number'); ?>
                                    <?php self::render_front_select('Topografia', '_mcrm_terrain_topography', $values['_mcrm_terrain_topography'], self::TOPOGRAPHIES); ?>
                                    <?php self::render_front_select('Finalidade', '_mcrm_terrain_purpose', $values['_mcrm_terrain_purpose'], self::PURPOSES); ?>
                                    <?php self::render_front_select('Interesse em condominio', '_mcrm_terrain_gated_community', $values['_mcrm_terrain_gated_community'], ['sim' => 'Sim', 'nao' => 'Nao']); ?>
                                </div>
                            </div>

                            <div class="mcrm-front-field mcrm-front-field-full">
                                <label for="_mcrm_notes">Observacoes</label>
                                <textarea id="_mcrm_notes" name="_mcrm_notes" rows="5"><?php echo esc_textarea($values['_mcrm_notes']); ?></textarea>
                            </div>
                        </div>
                        </div>

                        <div class="mcrm-form-actions">
                            <button class="mcrm-btn mcrm-btn-primary" type="submit">Salvar cliente</button>
                            <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url(remove_query_arg(['client_id', 'mcrm_notice'], $page_url)); ?>">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    public static function register_columns(array $columns): array
    {
        return [
            'cb' => $columns['cb'] ?? '',
            'title' => 'Cliente',
            'mcrm_cpf' => 'CPF',
            'mcrm_phone' => 'Telefone',
            'mcrm_interest_type' => 'Tipo',
            'mcrm_region' => 'Regiao',
            'mcrm_client_status' => 'Status',
            'mcrm_assigned_broker' => 'Corretor',
            'date' => $columns['date'] ?? 'Data',
        ];
    }

    public static function render_columns(string $column, int $post_id): void
    {
        if ($column === 'mcrm_assigned_broker') {
            $broker_id = (int) get_post_meta($post_id, '_mcrm_assigned_broker', true);
            $user = $broker_id > 0 ? get_user_by('id', $broker_id) : false;
            echo esc_html($user ? $user->display_name : '—');
            return;
        }

        $map = [
            'mcrm_cpf' => '_mcrm_cpf',
            'mcrm_phone' => '_mcrm_phone',
            'mcrm_interest_type' => '_mcrm_interest_type',
            'mcrm_region' => '_mcrm_region',
            'mcrm_client_status' => '_mcrm_client_status',
        ];

        if (!isset($map[$column])) {
            return;
        }

        $value = (string) get_post_meta($post_id, $map[$column], true);
        if ($column === 'mcrm_cpf') {
            $value = self::format_cpf($value);
        }

        echo esc_html($value !== '' ? $value : '—');
    }

    public static function render_admin_filters(): void
    {
        global $typenow;

        if ($typenow !== 'mcrm_client') {
            return;
        }

        self::render_filter_select('mcrm_interest_type', 'Tipo de interesse', self::INTEREST_TYPES);
        self::render_filter_input('mcrm_cpf', 'CPF');
        self::render_filter_input('mcrm_phone', 'Telefone');
        self::render_filter_input('mcrm_region', 'Regiao');
        self::render_filter_input('mcrm_price_range', 'Faixa de valor');
        self::render_filter_select('mcrm_terrain_purpose', 'Finalidade', self::PURPOSES);
        self::render_filter_select('mcrm_client_status', 'Status', self::CLIENT_STATUSES);

        if (current_user_can('manage_options')) {
            self::render_filter_select('mcrm_assigned_broker', 'Corretor', self::get_brokers());
        }
    }

    public static function apply_admin_filters(\WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'mcrm_client') {
            return;
        }

        $meta_query = ['relation' => 'AND'];
        $map = [
            'mcrm_interest_type' => '_mcrm_interest_type',
            'mcrm_region' => '_mcrm_region',
            'mcrm_price_range' => '_mcrm_price_range',
            'mcrm_terrain_purpose' => '_mcrm_terrain_purpose',
            'mcrm_client_status' => '_mcrm_client_status',
            'mcrm_assigned_broker' => '_mcrm_assigned_broker',
        ];

        foreach ($map as $request_key => $meta_key) {
            $value = isset($_GET[$request_key]) ? sanitize_text_field(wp_unslash($_GET[$request_key])) : '';
            if ($value === '') {
                continue;
            }

            $meta_query[] = [
                'key' => $meta_key,
                'value' => $value,
                'compare' => '=',
            ];
        }

        $cpf = isset($_GET['mcrm_cpf']) ? self::sanitize_cpf((string) wp_unslash($_GET['mcrm_cpf'])) : '';
        if ($cpf !== '') {
            $meta_query[] = [
                'key' => '_mcrm_cpf',
                'value' => $cpf,
                'compare' => 'LIKE',
            ];
        }

        $phone = isset($_GET['mcrm_phone']) ? self::sanitize_phone((string) wp_unslash($_GET['mcrm_phone'])) : '';
        if ($phone !== '') {
            $meta_query[] = [
                'key' => '_mcrm_phone',
                'value' => $phone,
                'compare' => 'LIKE',
            ];
        }

        if (!current_user_can('manage_options')) {
            $meta_query[] = [
                'key' => '_mcrm_assigned_broker',
                'value' => (string) get_current_user_id(),
                'compare' => '=',
            ];
        }

        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
    }

    public static function render_dashboard_page(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $current_user = wp_get_current_user();
        $total_clients = self::count_clients();
        $new_clients = self::count_by_meta('_mcrm_client_status', 'novo');
        $terrain_clients = self::count_by_meta('_mcrm_interest_type', 'terreno');
        $recent_clients = self::get_recent_clients();
        ?>
        <div class="wrap">
            <h1>Marchon CRM</h1>
            <p>Painel rapido para o corretor acompanhar clientes, interesses e demanda por terrenos.</p>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(220px,1fr));gap:16px;max-width:1200px;">
                <?php self::render_stat_card('Clientes visiveis', (string) $total_clients, 'Leads acessiveis no seu painel.'); ?>
                <?php self::render_stat_card('Novos leads', (string) $new_clients, 'Clientes em status novo.'); ?>
                <?php self::render_stat_card('Interesse em terreno', (string) $terrain_clients, 'Clientes com foco em terreno.'); ?>
                <?php self::render_stat_card('Corretor logado', $current_user->display_name ?: 'Usuario', 'Responsavel pelo acesso atual.'); ?>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;max-width:1200px;margin-top:16px;">
                <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
                    <h2>Clientes recentes</h2>
                    <?php if ($recent_clients === []) : ?>
                        <p>Nenhum cliente cadastrado ainda.</p>
                    <?php else : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>CPF</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Corretor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clients as $client) : ?>
                                    <tr>
                                        <td><a href="<?php echo esc_url(get_edit_post_link($client->ID)); ?>"><?php echo esc_html(get_the_title($client->ID)); ?></a></td>
                                        <td><?php echo esc_html(self::format_cpf((string) get_post_meta($client->ID, '_mcrm_cpf', true))); ?></td>
                                        <td><?php echo esc_html((string) get_post_meta($client->ID, '_mcrm_interest_type', true)); ?></td>
                                        <td><?php echo esc_html((string) get_post_meta($client->ID, '_mcrm_client_status', true)); ?></td>
                                        <td><?php echo esc_html(self::get_broker_name((int) get_post_meta($client->ID, '_mcrm_assigned_broker', true))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
                    <h2>Acoes rapidas</h2>
                    <p><a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=mcrm_client')); ?>">Cadastrar cliente</a></p>
                    <p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=mcrm_client')); ?>">Ver clientes</a></p>
                    <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=mcrm-reports')); ?>">Abrir relatorios</a></p>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_reports_page(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $terrain_count = self::count_by_meta('_mcrm_interest_type', 'terreno');
        $regions = self::aggregate_values('_mcrm_region', '_mcrm_interest_type', 'terreno');
        $price_ranges = self::aggregate_values('_mcrm_price_range', '_mcrm_interest_type', 'terreno');
        $purposes = self::aggregate_values('_mcrm_terrain_purpose', '_mcrm_interest_type', 'terreno');
        ?>
        <div class="wrap">
            <h1>Relatorios do Marchon CRM</h1>
            <p>Leitura inicial da demanda por terrenos no seu painel.</p>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(280px,1fr));gap:16px;max-width:1200px;">
                <?php self::render_stat_block('Clientes interessados em terreno', (string) $terrain_count); ?>
                <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
                    <h2>Perfil de uso</h2>
                    <?php self::render_aggregate_list($purposes); ?>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
                    <h2>Regioes mais procuradas</h2>
                    <?php self::render_aggregate_list($regions); ?>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
                    <h2>Faixas de valor mais buscadas</h2>
                    <?php self::render_aggregate_list($price_ranges); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_front_login(): string
    {
        ob_start();
        ?>
        <section class="mcrm-login-shell">
            <div class="mcrm-login-card">
                <p class="mcrm-eyebrow">Marchon CRM</p>
                <h1>Entrada para corretores cadastrados no Marchon CRM.</h1>
                <div class="mcrm-login-form">
                    <?php
                    wp_login_form([
                        'redirect' => self::get_frontend_page_url(),
                        'label_username' => 'Usuario',
                        'label_password' => 'Senha',
                        'label_log_in' => 'Entrar no CRM',
                        'remember' => true,
                    ]);
                    ?>
                </div>
            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    public static function sanitize_meta_value($value, string $meta_key = '', ...$rest)
    {
        if ($meta_key === '_mcrm_notes') {
            return sanitize_textarea_field((string) $value);
        }

        if (in_array($meta_key, ['_mcrm_terrain_area_min', '_mcrm_terrain_area_max'], true)) {
            return self::sanitize_number($value);
        }

        if ($meta_key === '_mcrm_assigned_broker') {
            return (int) $value;
        }

        if ($meta_key === '_mcrm_cpf') {
            return self::sanitize_cpf((string) $value);
        }

        if ($meta_key === '_mcrm_phone') {
            return self::sanitize_phone((string) $value);
        }

        return sanitize_text_field((string) $value);
    }

    public static function sanitize_number($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }

        return (string) floatval($value);
    }

    private static function get_meta_values(int $post_id): array
    {
        $values = [];
        foreach (array_keys(self::META_FIELDS) as $key) {
            $stored = get_post_meta($post_id, $key, true);
            $values[$key] = is_scalar($stored) ? (string) $stored : '';
        }

        $values['_mcrm_cpf'] = self::format_cpf($values['_mcrm_cpf']);
        $values['_mcrm_phone'] = self::format_phone($values['_mcrm_phone']);

        if ($values['_mcrm_assigned_broker'] === '') {
            $values['_mcrm_assigned_broker'] = (string) get_current_user_id();
        }

        return $values;
    }

    private static function sanitize_cpf(string $cpf): string
    {
        return substr(preg_replace('/\D+/', '', $cpf) ?? '', 0, 11);
    }

    private static function sanitize_phone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private static function format_phone(string $phone): string
    {
        $digits = self::sanitize_phone($phone);

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits) ?: $digits;
        }

        if (strlen($digits) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits) ?: $digits;
        }

        return $digits;
    }

    private static function format_cpf(string $cpf): string
    {
        $cpf = self::sanitize_cpf($cpf);
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf) ?: $cpf;
    }

    private static function get_brokers(): array
    {
        $users = get_users([
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name'],
            'capability' => 'edit_posts',
        ]);

        $options = [];
        foreach ($users as $user) {
            $options[(string) $user->ID] = $user->display_name;
        }

        return $options;
    }

    private static function get_broker_name(int $user_id): string
    {
        if ($user_id <= 0) {
            return '—';
        }

        $user = get_user_by('id', $user_id);
        return $user ? $user->display_name : '—';
    }

    private static function current_user_can_access_crm(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        return self::user_has_crm_access(wp_get_current_user());
    }

    private static function user_has_crm_access(\WP_User $user): bool
    {
        if (in_array('administrator', $user->roles, true)) {
            return true;
        }

        return user_can($user, 'edit_posts') || user_can($user, 'edit_imoveis');
    }

    private static function user_can_access_client(int $post_id): bool
    {
        $post = get_post($post_id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'mcrm_client') {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        return (int) get_post_meta($post_id, '_mcrm_assigned_broker', true) === get_current_user_id();
    }

    private static function get_frontend_page_url(): string
    {
        $page_id = (int) get_option('mcrm_front_page_id', 0);
        if ($page_id > 0) {
            $permalink = get_permalink($page_id);
            if (is_string($permalink) && $permalink !== '') {
                return $permalink;
            }
        }

        return home_url('/marchon-crm/');
    }

    private static function get_front_clients(): \WP_Query
    {
        $meta_query = self::get_scope_meta_query();
        $search_term = isset($_GET['mcrm_name']) ? sanitize_text_field(wp_unslash($_GET['mcrm_name'])) : '';
        $exact_map = [
            'mcrm_interest_type' => '_mcrm_interest_type',
            'mcrm_client_status' => '_mcrm_client_status',
        ];

        foreach ($exact_map as $request_key => $meta_key) {
            $value = isset($_GET[$request_key]) ? sanitize_text_field(wp_unslash($_GET[$request_key])) : '';
            if ($value === '') {
                continue;
            }

            $meta_query[] = [
                'key' => $meta_key,
                'value' => $value,
                'compare' => '=',
            ];
        }

        $cpf = isset($_GET['mcrm_cpf']) ? self::sanitize_cpf((string) wp_unslash($_GET['mcrm_cpf'])) : '';
        if ($cpf !== '') {
            $meta_query[] = [
                'key' => '_mcrm_cpf',
                'value' => $cpf,
                'compare' => 'LIKE',
            ];
        }

        $phone = isset($_GET['mcrm_phone']) ? self::sanitize_phone((string) wp_unslash($_GET['mcrm_phone'])) : '';
        if ($phone !== '') {
            $meta_query[] = [
                'key' => '_mcrm_phone',
                'value' => $phone,
                'compare' => 'LIKE',
            ];
        }

        $post__in = [];
        if ($search_term !== '') {
            $search_ids = get_posts([
                'post_type' => 'mcrm_client',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                's' => $search_term,
                'meta_query' => self::get_scope_meta_query(),
            ]);

            $cpf_search = self::sanitize_cpf($search_term);
            if ($cpf_search !== '') {
                $post__in = array_merge($post__in, get_posts([
                    'post_type' => 'mcrm_client',
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => array_merge(self::get_scope_meta_query(), [[
                        'key' => '_mcrm_cpf',
                        'value' => $cpf_search,
                        'compare' => 'LIKE',
                    ]]),
                ]));
            }

            $phone_search = self::sanitize_phone($search_term);
            if ($phone_search !== '') {
                $post__in = array_merge($post__in, get_posts([
                    'post_type' => 'mcrm_client',
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => array_merge(self::get_scope_meta_query(), [[
                        'key' => '_mcrm_phone',
                        'value' => $phone_search,
                        'compare' => 'LIKE',
                    ]]),
                ]));
            }

            $post__in = array_values(array_unique(array_merge($search_ids, $post__in)));
        }

        $query_args = [
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => 24,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ];

        if ($search_term !== '') {
            $query_args['post__in'] = $post__in === [] ? [0] : $post__in;
        }

        return new \WP_Query($query_args);
    }

    private static function get_empty_front_values(): array
    {
        $values = array_fill_keys(array_keys(self::META_FIELDS), '');
        $values['_mcrm_assigned_broker'] = (string) get_current_user_id();

        return $values;
    }

    private static function render_front_filters(string $page_url): void
    {
        ?>
        <form class="mcrm-filter-bar" method="get" action="<?php echo esc_url($page_url); ?>">
            <input type="text" name="mcrm_name" placeholder="Nome do cliente" value="<?php echo esc_attr(isset($_GET['mcrm_name']) ? sanitize_text_field(wp_unslash($_GET['mcrm_name'])) : ''); ?>">
            <input type="text" name="mcrm_cpf" placeholder="CPF" value="<?php echo esc_attr(isset($_GET['mcrm_cpf']) ? sanitize_text_field(wp_unslash($_GET['mcrm_cpf'])) : ''); ?>">
            <input type="text" name="mcrm_phone" placeholder="Telefone" value="<?php echo esc_attr(isset($_GET['mcrm_phone']) ? sanitize_text_field(wp_unslash($_GET['mcrm_phone'])) : ''); ?>">
            <select name="mcrm_interest_type">
                <option value="">Tipo</option>
                <?php foreach (self::INTEREST_TYPES as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($_GET['mcrm_interest_type']) ? sanitize_text_field(wp_unslash($_GET['mcrm_interest_type'])) : '', $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="mcrm_client_status">
                <option value="">Status</option>
                <?php foreach (self::CLIENT_STATUSES as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($_GET['mcrm_client_status']) ? sanitize_text_field(wp_unslash($_GET['mcrm_client_status'])) : '', $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="mcrm-btn mcrm-btn-primary" type="submit">Filtrar</button>
            <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url(remove_query_arg(['mcrm_name', 'mcrm_cpf', 'mcrm_phone', 'mcrm_interest_type', 'mcrm_client_status', 'mcrm_notice'], $page_url)); ?>">Limpar</a>
        </form>
        <?php
    }

    private static function render_front_notice(string $notice): void
    {
        if ($notice === '') {
            return;
        }

        $messages = [
            'saved' => ['success', 'Cliente salvo com sucesso.'],
            'error' => ['error', 'Nao foi possivel salvar o cliente.'],
            'forbidden' => ['error', 'Voce nao pode editar esse cliente.'],
        ];

        if (!isset($messages[$notice])) {
            return;
        }

        [$class, $text] = $messages[$notice];
        echo '<div class="mcrm-notice mcrm-notice-' . esc_attr($class) . '">' . esc_html($text) . '</div>';
    }

    private static function render_front_input(string $label, string $name, string $value, string $type = 'text', array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $attr_name => $attr_value) {
            $attr_html .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
        }
        ?>
        <div class="mcrm-front-field">
            <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>"<?php echo $attr_html; ?>>
        </div>
        <?php
    }

    private static function render_front_select(string $label, string $name, string $value, array $options, array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $attr_name => $attr_value) {
            $attr_html .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
        }
        ?>
        <div class="mcrm-front-field">
            <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            <select id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"<?php echo $attr_html; ?>>
                <option value="">Selecione</option>
                <?php foreach ($options as $option_value => $option_label) : ?>
                    <option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected($value, (string) $option_value); ?>><?php echo esc_html($option_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private static function render_front_stat(string $title, string $value, string $link = ''): void
    {
        if ($link !== '') {
            echo '<a href="' . esc_url($link) . '" class="mcrm-stat-card mcrm-stat-card-link"><span>' . esc_html($title) . '</span><strong>' . esc_html($value) . '</strong></a>';
        } else {
            echo '<article class="mcrm-stat-card"><span>' . esc_html($title) . '</span><strong>' . esc_html($value) . '</strong></article>';
        }
    }

    private static function render_clients_table(\WP_Query $clients, string $page_url): void
    {
        $back_url = remove_query_arg(['mcrm_view', 'mcrm_client_status', 'mcrm_interest_type'], $page_url);
        ?>
        <div class="mcrm-panel mcrm-table-panel" id="mcrm-clients">
            <div class="mcrm-panel-head mcrm-table-head">
                <div>
                    <h2>Clientes cadastrados</h2>
                    <p><?php echo esc_html((string) $clients->found_posts); ?> registro(s) encontrado(s)</p>
                </div>
                <a class="mcrm-btn mcrm-btn-secondary" href="<?php echo esc_url($back_url); ?>">&larr; Voltar</a>
            </div>
            <?php if ($clients->have_posts()) : ?>
            <div class="mcrm-table-wrap">
                <table class="mcrm-client-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Interesse</th>
                            <th>Regiao</th>
                            <th>Faixa de valor</th>
                            <th>Corretor</th>
                            <th>Cadastro</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients->posts as $client) : ?>
                            <?php $cv = self::get_meta_values($client->ID); ?>
                            <tr>
                                <td class="mcrm-td-name"><?php echo esc_html(get_the_title($client->ID)); ?></td>
                                <td><?php echo esc_html(self::format_cpf($cv['_mcrm_cpf'])); ?></td>
                                <td><?php echo esc_html(self::format_phone($cv['_mcrm_phone'])); ?></td>
                                <td><span class="mcrm-status-badge mcrm-status-<?php echo esc_attr(self::status_class($cv['_mcrm_client_status'])); ?>"><?php echo esc_html(self::label_for(self::CLIENT_STATUSES, $cv['_mcrm_client_status'])); ?></span></td>
                                <td><span class="mcrm-type-pill"><?php echo esc_html(self::label_for(self::INTEREST_TYPES, $cv['_mcrm_interest_type'])); ?></span></td>
                                <td><?php echo esc_html($cv['_mcrm_region'] ?: '—'); ?></td>
                                <td><?php echo esc_html($cv['_mcrm_price_range'] ?: '—'); ?></td>
                                <td><?php echo esc_html(self::get_broker_name((int) $cv['_mcrm_assigned_broker'])); ?></td>
                                <td><?php echo esc_html(get_the_date('d/m/Y', $client->ID)); ?></td>
                                <td><a class="mcrm-inline-link" href="<?php echo esc_url(add_query_arg('client_id', $client->ID, $back_url)); ?>">Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else : ?>
            <div class="mcrm-empty-state">
                <h3>Nenhum cliente encontrado.</h3>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_chart_rows(array $items, string $context): void
    {
        $max = 1;
        foreach ($items as $item) {
            $max = max($max, (int) $item['count']);
        }

        foreach ($items as $key => $item) {
            $count = (int) $item['count'];
            $width = $max > 0 ? max(8, (int) round(($count / $max) * 100)) : 8;
            echo '<div class="mcrm-chart-row">';
            echo '<div class="mcrm-chart-row-head"><span>' . esc_html($item['label']) . '</span><strong>' . esc_html((string) $count) . '</strong></div>';
            echo '<div class="mcrm-chart-track"><span class="mcrm-chart-bar mcrm-chart-bar-' . esc_attr(self::chart_class($key, $context)) . '" style="width:' . esc_attr((string) $width) . '%"></span></div>';
            echo '</div>';
        }
    }

    private static function label_for(array $map, string $value): string
    {
        return $map[$value] ?? ($value !== '' ? $value : '—');
    }

    private static function aggregate_by_map(string $meta_key, array $label_map): array
    {
        $query = new \WP_Query([
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => self::get_scope_meta_query(),
        ]);

        $counts = [];
        foreach ($label_map as $key => $label) {
            $counts[$key] = [
                'label' => $label,
                'count' => 0,
            ];
        }

        foreach ($query->posts as $post_id) {
            $value = (string) get_post_meta($post_id, $meta_key, true);
            if ($value === '' || !isset($counts[$value])) {
                continue;
            }

            $counts[$value]['count']++;
        }

        return $counts;
    }

    private static function chart_class(string $key, string $context): string
    {
        if ($context === 'status') {
            return self::status_class($key);
        }

        return match ($key) {
            'terreno' => 'interest-terreno',
            'casa' => 'interest-casa',
            'apartamento' => 'interest-apartamento',
            'comercial' => 'interest-comercial',
            default => 'interest-default',
        };
    }

    private static function status_class(string $status): string
    {
        return match ($status) {
            'novo' => 'new',
            'em_atendimento' => 'progress',
            'proposta' => 'proposal',
            'convertido' => 'won',
            'arquivado' => 'archived',
            default => 'neutral',
        };
    }

    private static function priority_label(string $status, string $interest_type): string
    {
        if ($status === 'novo' && $interest_type === 'terreno') {
            return 'Alta prioridade';
        }

        if ($status === 'novo') {
            return 'Contato imediato';
        }

        if ($status === 'em_atendimento') {
            return 'Em aquecimento';
        }

        if ($status === 'proposta') {
            return 'Negociacao ativa';
        }

        if ($status === 'convertido') {
            return 'Cliente convertido';
        }

        if ($status === 'arquivado') {
            return 'Base historica';
        }

        return 'Acompanhar';
    }

    private static function render_input(string $label, string $name, string $value, string $type = 'text', string $step = '', string $help = '', array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $attr_name => $attr_value) {
            $attr_html .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
        }
        ?>
        <div class="mcrm-field">
            <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            <input
                type="<?php echo esc_attr($type); ?>"
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                <?php echo $step !== '' ? 'step="' . esc_attr($step) . '"' : ''; ?>
                <?php echo $attr_html; ?>
            >
            <?php if ($help !== '') : ?>
                <p class="mcrm-help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_select(string $label, string $name, string $value, array $options): void
    {
        ?>
        <div class="mcrm-field">
            <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            <select id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>">
                <option value="">Selecione</option>
                <?php foreach ($options as $option_value => $option_label) : ?>
                    <option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected($value, (string) $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private static function render_filter_select(string $name, string $label, array $options): void
    {
        $selected_value = isset($_GET[$name]) ? sanitize_text_field(wp_unslash($_GET[$name])) : '';
        ?>
        <select name="<?php echo esc_attr($name); ?>">
            <option value=""><?php echo esc_html($label); ?></option>
            <?php foreach ($options as $value => $text) : ?>
                <option value="<?php echo esc_attr((string) $value); ?>" <?php selected($selected_value, (string) $value); ?>>
                    <?php echo esc_html($text); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    private static function render_filter_input(string $name, string $label): void
    {
        $value = isset($_GET[$name]) ? sanitize_text_field(wp_unslash($_GET[$name])) : '';
        ?>
        <input type="text" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($label); ?>" value="<?php echo esc_attr($value); ?>">
        <?php
    }

    private static function count_clients(): int
    {
        $query = new \WP_Query([
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => self::get_scope_meta_query(),
        ]);

        return (int) $query->found_posts;
    }

    private static function count_by_meta(string $meta_key, string $value): int
    {
        $meta_query = self::get_scope_meta_query();
        $meta_query[] = [
            'key' => $meta_key,
            'value' => $value,
            'compare' => '=',
        ];

        $query = new \WP_Query([
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
        ]);

        return (int) $query->found_posts;
    }

    private static function aggregate_values(string $target_meta_key, string $filter_meta_key, string $filter_value): array
    {
        $meta_query = self::get_scope_meta_query();
        $meta_query[] = [
            'key' => $filter_meta_key,
            'value' => $filter_value,
            'compare' => '=',
        ];

        $query = new \WP_Query([
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
        ]);

        $counts = [];
        foreach ($query->posts as $post_id) {
            $value = trim((string) get_post_meta($post_id, $target_meta_key, true));
            if ($value === '') {
                $value = 'Nao informado';
            }

            if (!isset($counts[$value])) {
                $counts[$value] = 0;
            }

            $counts[$value]++;
        }

        arsort($counts);
        return $counts;
    }

    private static function get_recent_clients(): array
    {
        return get_posts([
            'post_type' => 'mcrm_client',
            'post_status' => 'any',
            'posts_per_page' => 8,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => self::get_scope_meta_query(),
        ]);
    }

    private static function get_scope_meta_query(): array
    {
        $meta_query = ['relation' => 'AND'];

        if (!current_user_can('manage_options')) {
            $meta_query[] = [
                'key' => '_mcrm_assigned_broker',
                'value' => (string) get_current_user_id(),
                'compare' => '=',
            ];
        }

        return $meta_query;
    }

    private static function render_aggregate_list(array $items): void
    {
        if ($items === []) {
            echo '<p>Nenhum dado disponivel ainda.</p>';
            return;
        }

        echo '<ul>';
        foreach ($items as $label => $count) {
            echo '<li>' . esc_html($label . ': ' . $count) . '</li>';
        }
        echo '</ul>';
    }

    private static function render_stat_card(string $title, string $value, string $description): void
    {
        ?>
        <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
            <h2 style="margin-top:0;"><?php echo esc_html($title); ?></h2>
            <p style="font-size:32px;margin:0 0 8px;"><strong><?php echo esc_html($value); ?></strong></p>
            <p style="margin:0;color:#50575e;"><?php echo esc_html($description); ?></p>
        </div>
        <?php
    }

    private static function render_stat_block(string $title, string $value): void
    {
        ?>
        <div style="background:#fff;border:1px solid #dcdcde;padding:16px;">
            <h2><?php echo esc_html($title); ?></h2>
            <p style="font-size:32px;margin:0;"><strong><?php echo esc_html($value); ?></strong></p>
        </div>
        <?php
    }

    public static function handle_ajax_quick_search(): void
    {
        if (!check_ajax_referer('mcrm_quick_search', 'nonce', false)) {
            wp_send_json_error(null, 403);
        }

        if (!self::current_user_can_access_crm()) {
            wp_send_json_error(null, 403);
        }

        $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';

        if (strlen($term) < 2) {
            wp_send_json_success(['results' => []]);
        }

        $scope = self::get_scope_meta_query();
        $ids   = [];

        $ids = array_merge($ids, (array) get_posts([
            'post_type'      => 'mcrm_client',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            's'              => $term,
            'meta_query'     => $scope,
        ]));

        $cpf = self::sanitize_cpf($term);
        if ($cpf !== '') {
            $ids = array_merge($ids, (array) get_posts([
                'post_type'      => 'mcrm_client',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array_merge($scope, [[
                    'key'     => '_mcrm_cpf',
                    'value'   => $cpf,
                    'compare' => 'LIKE',
                ]]),
            ]));
        }

        $phone = self::sanitize_phone($term);
        if ($phone !== '') {
            $ids = array_merge($ids, (array) get_posts([
                'post_type'      => 'mcrm_client',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array_merge($scope, [[
                    'key'     => '_mcrm_phone',
                    'value'   => $phone,
                    'compare' => 'LIKE',
                ]]),
            ]));
        }

        $ids      = array_values(array_unique($ids));
        $page_url = self::get_frontend_page_url();
        $results  = [];

        foreach (array_slice($ids, 0, 8) as $post_id) {
            $status  = (string) get_post_meta($post_id, '_mcrm_client_status', true);
            $results[] = [
                'name'   => get_the_title($post_id),
                'phone'  => (string) get_post_meta($post_id, '_mcrm_phone', true),
                'status' => self::CLIENT_STATUSES[$status] ?? $status,
                'link'   => add_query_arg('client_id', $post_id, $page_url),
            ];
        }

        wp_send_json_success(['results' => $results]);
    }
}
