<?php

if (!defined('ABSPATH')) {
    exit;
}

class MMarchon_Instagram_Sync
{
    public const OPTION_SETTINGS = 'mmarchon_instagram_sync_settings';
    public const OPTION_LAST_RESULT = 'mmarchon_instagram_sync_last_result';
    public const DEFAULT_INSTAGRAM_USERNAME = 'mmimoveis__';

    public static function boot(): void
    {
        add_filter('cron_schedules', [__CLASS__, 'register_cron_schedule']);
        add_action(MMARCHON_INSTAGRAM_SYNC_CRON_HOOK, [__CLASS__, 'run_sync']);
        add_action('admin_post_mmarchon_instagram_sync_now', [__CLASS__, 'handle_manual_sync']);
        add_action('admin_post_mmarchon_instagram_import_url', [__CLASS__, 'handle_manual_import_from_url']);
        add_action('init', [__CLASS__, 'ensure_log_directory']);

        MMarchon_Instagram_Sync_Admin::boot();
        MMarchon_Instagram_Sync_Public::boot();
    }

    public static function activate(): void
    {
        self::create_table();
        self::ensure_log_directory();

        if (!wp_next_scheduled(MMARCHON_INSTAGRAM_SYNC_CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'mmarchon_every_ten_minutes', MMARCHON_INSTAGRAM_SYNC_CRON_HOOK);
        }

        self::log('Plugin activated.');
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(MMARCHON_INSTAGRAM_SYNC_CRON_HOOK);
        self::log('Plugin deactivated.');
    }

    public static function register_cron_schedule(array $schedules): array
    {
        $schedules['mmarchon_every_ten_minutes'] = [
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display'  => __('Every 10 Minutes', 'mmarchon-instagram-sync'),
        ];

        return $schedules;
    }

    public static function get_table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'mmarchon_instagram_posts';
    }

    public static function create_table(): void
    {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            instagram_id VARCHAR(191) NOT NULL,
            caption LONGTEXT NULL,
            media_type VARCHAR(32) NOT NULL,
            media_url TEXT NULL,
            permalink TEXT NULL,
            timestamp DATETIME NOT NULL,
            imovel_id BIGINT UNSIGNED NULL,
            attachment_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY instagram_id (instagram_id),
            KEY imovel_id (imovel_id),
            KEY attachment_id (attachment_id),
            KEY timestamp (timestamp)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function ensure_log_directory(): void
    {
        $upload_dir = wp_get_upload_dir();
        $log_dir    = trailingslashit($upload_dir['basedir']) . 'mmarchon_logs';

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $index_file = trailingslashit($log_dir) . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n");
        }
    }

    public static function get_log_file(): string
    {
        $upload_dir = wp_get_upload_dir();

        return trailingslashit($upload_dir['basedir']) . 'mmarchon_logs/instagram-sync.log';
    }

    public static function log(string $message, array $context = []): void
    {
        self::ensure_log_directory();

        $line = sprintf(
            "[%s] %s%s\n",
            current_time('mysql'),
            $message,
            $context ? ' ' . wp_json_encode($context) : ''
        );

        file_put_contents(self::get_log_file(), $line, FILE_APPEND);
    }

    public static function get_settings(): array
    {
        $defaults = [
            'instagram_username' => self::DEFAULT_INSTAGRAM_USERNAME,
            'access_token' => '',
            'user_id'      => '',
            'mock_enabled' => '1',
        ];

        $settings = get_option(self::OPTION_SETTINGS, []);

        return wp_parse_args(is_array($settings) ? $settings : [], $defaults);
    }

    public static function save_settings(array $data): void
    {
        $settings = [
            'instagram_username' => self::sanitize_instagram_username($data['instagram_username'] ?? self::DEFAULT_INSTAGRAM_USERNAME),
            'access_token'       => sanitize_text_field($data['access_token'] ?? ''),
            'user_id'            => sanitize_text_field($data['user_id'] ?? ''),
            'mock_enabled'       => empty($data['mock_enabled']) ? '0' : '1',
        ];

        update_option(self::OPTION_SETTINGS, $settings, false);
    }

    public static function sanitize_instagram_username(string $username): string
    {
        $username = ltrim(trim($username), '@');
        $username = preg_replace('/[^A-Za-z0-9._]/', '', $username) ?: '';

        return $username !== '' ? $username : self::DEFAULT_INSTAGRAM_USERNAME;
    }

    public static function get_instagram_profile_url(): string
    {
        $settings = self::get_settings();

        return sprintf(
            'https://www.instagram.com/%s/',
            rawurlencode($settings['instagram_username'])
        );
    }

    public static function handle_manual_sync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'mmarchon-instagram-sync'));
        }

        check_admin_referer('mmarchon_instagram_sync_now');

        $result = self::run_sync();
        $args   = [
            'page'       => 'mmarchon-instagram-sync',
            'synced'     => 1,
            'imported'   => (int) ($result['imported'] ?? 0),
            'duplicates' => (int) ($result['duplicates'] ?? 0),
            'errors'     => (int) ($result['errors'] ?? 0),
        ];

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function handle_manual_import_from_url(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'mmarchon-instagram-sync'));
        }

        check_admin_referer('mmarchon_instagram_import_url');

        $url              = isset($_POST['instagram_url']) ? wp_unslash((string) $_POST['instagram_url']) : '';
        $imovel_id        = isset($_POST['imovel_id']) ? absint($_POST['imovel_id']) : 0;
        $caption_override = isset($_POST['caption_override']) ? wp_unslash((string) $_POST['caption_override']) : '';
        $uploaded_media   = isset($_FILES['manual_media']) && is_array($_FILES['manual_media']) ? $_FILES['manual_media'] : [];
        $result           = [
            'imported'   => 0,
            'duplicates' => 0,
            'errors'     => 0,
            'source'     => 'instagram_link',
        ];

        try {
            if (!self::has_uploaded_media($uploaded_media) && trim($url) === '') {
                throw new RuntimeException('Informe uma URL do Instagram ou envie um arquivo manual.');
            }

            if (self::has_uploaded_media($uploaded_media)) {
                $post   = self::build_manual_post_from_input(
                    $url,
                    $imovel_id ?: null,
                    [
                        'caption'        => $caption_override,
                        'uploaded_media' => $uploaded_media,
                    ]
                );
                $status = self::store_post($post);

                $result = [
                    'imported'   => $status === 'imported' ? 1 : 0,
                    'duplicates' => $status === 'duplicate' ? 1 : 0,
                    'errors'     => 0,
                    'source'     => 'manual_upload',
                ];
            } else {
                $result = self::import_from_url(
                    $url,
                    $imovel_id ?: null,
                    [
                        'caption' => $caption_override,
                    ]
                );
            }
        } catch (Throwable $throwable) {
            $result['errors']  = 1;
            $result['message'] = $throwable->getMessage();
            self::log('Manual URL import failed.', ['error' => $throwable->getMessage(), 'url' => $url]);
        }

        update_option(self::OPTION_LAST_RESULT, $result, false);

        $args = [
            'page'       => 'mmarchon-instagram-sync',
            'url_import' => 1,
            'imported'   => (int) $result['imported'],
            'duplicates' => (int) $result['duplicates'],
            'errors'     => (int) $result['errors'],
        ];

        if (!empty($result['message'])) {
            $args['message'] = rawurlencode((string) $result['message']);
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function run_sync(): array
    {
        self::create_table();

        $stats = [
            'imported'   => 0,
            'duplicates' => 0,
            'errors'     => 0,
            'source'     => 'mock',
        ];

        try {
            $posts           = self::fetch_instagram_posts();
            $stats['source'] = self::is_graph_api_enabled() ? 'graph_api' : 'mock';

            foreach ($posts as $post) {
                $result = self::store_post($post);

                if ($result === 'duplicate') {
                    $stats['duplicates']++;
                } elseif ($result === 'imported') {
                    $stats['imported']++;
                }
            }

            self::log('Sync completed.', $stats);
        } catch (Throwable $throwable) {
            $stats['errors']++;
            $stats['message'] = $throwable->getMessage();
            self::log('Sync failed.', ['error' => $throwable->getMessage()]);
        }

        update_option(self::OPTION_LAST_RESULT, $stats, false);

        return $stats;
    }

    public static function is_graph_api_enabled(): bool
    {
        $settings = self::get_settings();

        return !empty($settings['access_token']) && !empty($settings['user_id']) && empty($settings['mock_enabled']);
    }

    public static function fetch_instagram_posts(): array
    {
        if (self::is_graph_api_enabled()) {
            return self::fetch_from_graph_api();
        }

        return self::get_mock_posts();
    }

    public static function fetch_from_graph_api(): array
    {
        $settings = self::get_settings();
        $fields   = [
            'id',
            'caption',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
        ];
        $url      = add_query_arg(
            [
                'fields'       => implode(',', $fields),
                'access_token' => $settings['access_token'],
            ],
            sprintf('https://graph.instagram.com/%s/media', rawurlencode($settings['user_id']))
        );

        $response = wp_safe_remote_get(
            $url,
            [
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 300 || !is_array($body)) {
            throw new RuntimeException('Instagram Graph API returned an invalid response.');
        }

        $items = [];
        foreach (($body['data'] ?? []) as $item) {
            $items[] = self::normalize_post($item);
        }

        return $items;
    }

    public static function import_from_url(string $url, ?int $forced_imovel_id = null, array $overrides = []): array
    {
        self::create_table();

        $post = null;
        $parsed = self::parse_instagram_url($url);

        if ($parsed['type'] === 'profile') {
            return self::import_profile_from_url($parsed['url'], $forced_imovel_id);
        }

        if ($parsed['type'] === 'post') {
            try {
                $post = self::fetch_from_public_url($parsed['url']);
            } catch (Throwable $throwable) {
                $post = self::build_link_only_post_from_input($parsed['url'], $forced_imovel_id, $overrides);
            }
        }

        if ($post === null) {
            $post = self::build_link_only_post_from_input($url, $forced_imovel_id, $overrides);
        } else {
            if ($forced_imovel_id) {
                $post['imovel_id'] = self::validate_imovel_id($forced_imovel_id);
            }

            if (!empty($overrides['caption'])) {
                $post['caption'] = wp_kses_post((string) $overrides['caption']);
            }

            $post['external_only'] = true;
        }

        $status = self::store_post($post);

        return [
            'imported'   => $status === 'imported' ? 1 : 0,
            'duplicates' => $status === 'duplicate' ? 1 : 0,
            'errors'     => 0,
            'source'     => 'instagram_link',
        ];
    }

    public static function import_profile_from_url(string $url, ?int $forced_imovel_id = null): array
    {
        $links = self::fetch_profile_post_links($url);
        if (!$links) {
            throw new RuntimeException('No public posts were found on this Instagram profile page.');
        }

        $result = [
            'imported'   => 0,
            'duplicates' => 0,
            'errors'     => 0,
            'source'     => 'manual_profile_url',
        ];

        foreach ($links as $post_url) {
            try {
                $post = self::fetch_from_public_url($post_url);
                if ($forced_imovel_id) {
                    $post['imovel_id'] = self::validate_imovel_id($forced_imovel_id);
                }

                $status = self::store_post($post);
                if ($status === 'duplicate') {
                    $result['duplicates']++;
                } else {
                    $result['imported']++;
                }
            } catch (Throwable $throwable) {
                $result['errors']++;
                self::log('Profile URL item import failed.', ['url' => $post_url, 'error' => $throwable->getMessage()]);
            }
        }

        if ($result['imported'] === 0 && $result['duplicates'] === 0 && $result['errors'] > 0) {
            throw new RuntimeException('The Instagram profile was reached, but no post could be imported.');
        }

        return $result;
    }

    public static function fetch_from_public_url(string $url): array
    {
        $parsed         = self::parse_instagram_url($url);
        $normalized_url = $parsed['url'];

        if ($parsed['type'] !== 'post') {
            throw new RuntimeException('Only Instagram post or reel URLs can be fetched as a single item.');
        }

        if ($normalized_url === '') {
            throw new RuntimeException('Instagram URL invalid or unsupported.');
        }

        $html = self::fetch_instagram_public_html($normalized_url);

        if ($html === '') {
            throw new RuntimeException('Instagram page could not be fetched.');
        }

        $meta = self::extract_meta_tags($html);
        $path = (string) wp_parse_url($normalized_url, PHP_URL_PATH);
        preg_match('#/(?:p|reel|reels|tv)/([^/]+)/?#i', $path, $matches);

        $instagram_id = sanitize_title($matches[1] ?? md5($normalized_url));
        $media_url    = $meta['og:image'] ?? $meta['twitter:image'] ?? $meta['twitter:image:src'] ?? $meta['og:video'] ?? $meta['og:video:secure_url'] ?? '';
        $caption      = self::extract_caption_from_meta($meta);
        $timestamp    = $meta['article:published_time'] ?? $meta['og:updated_time'] ?? gmdate('c');
        $title        = strtolower((string) ($meta['twitter:title'] ?? $meta['og:title'] ?? ''));
        $media_type   = (!empty($meta['og:video']) || !empty($meta['og:video:secure_url']) || str_contains($title, 'reel')) ? 'VIDEO' : 'IMAGE';

        if ($media_url === '') {
            throw new RuntimeException('Instagram media URL not found on the public page.');
        }

        return self::normalize_post(
            [
                'id'         => $instagram_id,
                'caption'    => $caption,
                'media_type' => $media_type,
                'media_url'  => $media_url,
                'permalink'  => $normalized_url,
                'timestamp'  => $timestamp,
            ]
        );
    }

    public static function fetch_instagram_public_html(string $url): string
    {
        $response = wp_safe_remote_get(
            $url,
            [
                'timeout' => 20,
                'headers' => [
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0 Safari/537.36',
                ],
            ]
        );

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
            $html = (string) wp_remote_retrieve_body($response);
            if (self::instagram_html_has_public_meta($html)) {
                return $html;
            }
        }

        return self::fetch_instagram_public_html_via_curl($url);
    }

    public static function fetch_instagram_public_html_via_curl(string $url): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $command = sprintf(
            '/usr/bin/curl -sL -A %s -H %s %s',
            escapeshellarg('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0 Safari/537.36'),
            escapeshellarg('Accept-Language: en-US,en;q=0.9'),
            escapeshellarg($url)
        );

        $html = (string) shell_exec($command);

        return self::instagram_html_has_public_meta($html) ? $html : '';
    }

    public static function instagram_html_has_public_meta(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        return str_contains($html, 'twitter:image')
            || str_contains($html, 'og:image')
            || str_contains($html, 'property="og:title"')
            || str_contains($html, 'name="description"');
    }

    public static function get_mock_posts(): array
    {
        $now = gmdate('c');

        $items = [
            [
                'id'         => 'mock-ig-image-001',
                'caption'    => 'Apartamento em destaque com varanda gourmet. #ID60',
                'media_type' => 'IMAGE',
                'media_url'  => 'https://picsum.photos/seed/mmarchon-60/1200/800',
                'permalink'  => self::get_instagram_profile_url(),
                'timestamp'  => $now,
            ],
            [
                'id'            => 'mock-ig-video-002',
                'caption'       => 'Tour rápido do imóvel com vista livre. #ID59',
                'media_type'    => 'VIDEO',
                'media_url'     => 'https://samplelib.com/lib/preview/mp4/sample-5s.mp4',
                'thumbnail_url' => 'https://picsum.photos/seed/mmarchon-59/1200/800',
                'permalink'     => self::get_instagram_profile_url(),
                'timestamp'     => $now,
            ],
        ];

        return array_map([__CLASS__, 'normalize_post'], $items);
    }

    public static function normalize_post(array $post): array
    {
        $media_type = strtoupper((string) ($post['media_type'] ?? 'IMAGE'));
        $map        = [
            'IMAGE'          => 'image',
            'VIDEO'          => 'video',
            'CAROUSEL_ALBUM' => 'carousel',
        ];

        return [
            'instagram_id' => sanitize_text_field((string) ($post['id'] ?? '')),
            'caption'      => wp_kses_post((string) ($post['caption'] ?? '')),
            'media_type'   => $map[$media_type] ?? 'image',
            'media_url'    => esc_url_raw((string) ($post['media_url'] ?? $post['thumbnail_url'] ?? '')),
            'permalink'    => esc_url_raw((string) ($post['permalink'] ?? '')),
            'timestamp'    => gmdate('Y-m-d H:i:s', strtotime((string) ($post['timestamp'] ?? 'now'))),
        ];
    }

    public static function store_post(array $post): string
    {
        global $wpdb;

        $table_name   = self::get_table_name();
        $instagram_id = $post['instagram_id'] ?? '';

        if ($instagram_id === '') {
            throw new RuntimeException('Instagram post without ID cannot be stored.');
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table_name} WHERE instagram_id = %s", $instagram_id)
        );

        if ($existing) {
            self::update_existing_post((int) $existing, $post);
            self::log('Duplicate avoided.', ['instagram_id' => $instagram_id]);
            return 'duplicate';
        }

        $imovel_id     = !empty($post['imovel_id'])
            ? self::validate_imovel_id((int) $post['imovel_id'])
            : self::extract_imovel_id((string) ($post['caption'] ?? ''));
        $attachment_id = !empty($post['attachment_id'])
            ? absint((int) $post['attachment_id'])
            : (empty($post['external_only']) ? self::download_media($post) : 0);

        $wpdb->insert(
            $table_name,
            [
                'instagram_id'  => $instagram_id,
                'caption'       => $post['caption'] ?? '',
                'media_type'    => $post['media_type'] ?? 'image',
                'media_url'     => $post['media_url'] ?? '',
                'permalink'     => $post['permalink'] ?? '',
                'timestamp'     => $post['timestamp'] ?? current_time('mysql', true),
                'imovel_id'     => $imovel_id ?: null,
                'attachment_id' => $attachment_id ?: null,
                'created_at'    => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                $imovel_id ? '%d' : '%s',
                $attachment_id ? '%d' : '%s',
                '%s',
            ]
        );

        if ($wpdb->last_error) {
            throw new RuntimeException($wpdb->last_error);
        }

        self::log(
            'Post imported.',
            [
                'instagram_id'  => $instagram_id,
                'imovel_id'     => $imovel_id,
                'attachment_id' => $attachment_id,
            ]
        );

        return 'imported';
    }

    public static function update_existing_post(int $row_id, array $post): void
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $update     = [];
        $formats    = [];

        if (!empty($post['caption'])) {
            $update['caption'] = $post['caption'];
            $formats[]         = '%s';
        }

        if (!empty($post['media_url'])) {
            $update['media_url'] = $post['media_url'];
            $formats[]           = '%s';
        }

        if (!empty($post['permalink'])) {
            $update['permalink'] = $post['permalink'];
            $formats[]           = '%s';
        }

        if (!empty($post['imovel_id'])) {
            $update['imovel_id'] = (int) $post['imovel_id'];
            $formats[]           = '%d';
        }

        if (isset($post['media_type']) && $post['media_type'] !== '') {
            $update['media_type'] = $post['media_type'];
            $formats[]            = '%s';
        }

        if (!$update) {
            return;
        }

        $wpdb->update(
            $table_name,
            $update,
            ['id' => $row_id],
            $formats,
            ['%d']
        );
    }

    public static function extract_imovel_id(string $caption): ?int
    {
        if (!preg_match('/#ID(\d+)/i', $caption, $matches)) {
            if (preg_match('/#(?:REF|COD|IMOVEL|IMO)-?([A-Z0-9._-]+)/i', $caption, $reference_matches)) {
                return self::find_imovel_id_by_reference((string) $reference_matches[1]);
            }

            return null;
        }

        $post_id = absint($matches[1]);

        return self::validate_imovel_id($post_id);
    }

    public static function has_uploaded_media($uploaded_media): bool
    {
        return is_array($uploaded_media)
            && !empty($uploaded_media['tmp_name'])
            && isset($uploaded_media['error'])
            && (int) $uploaded_media['error'] === UPLOAD_ERR_OK;
    }

    public static function build_manual_post_from_input(string $url, ?int $forced_imovel_id, array $overrides): array
    {
        $caption       = wp_kses_post((string) ($overrides['caption'] ?? ''));
        $attachment_id = self::create_attachment_from_upload($overrides['uploaded_media'], $caption !== '' ? $caption : 'Instagram media');
        $attachment_url = wp_get_attachment_url($attachment_id) ?: '';
        $permalink     = '';
        $instagram_id  = 'manual-' . wp_generate_password(12, false, false);
        $parsed        = self::parse_instagram_url($url);

        if ($parsed['type'] === 'post') {
            $permalink    = $parsed['url'];
            $instagram_id = self::extract_instagram_shortcode_from_url($parsed['url']) ?: $instagram_id;
        }

        $mime_type = (string) get_post_mime_type($attachment_id);
        $media_type = str_starts_with($mime_type, 'video/') ? 'video' : 'image';

        return [
            'instagram_id'  => $instagram_id,
            'caption'       => $caption,
            'media_type'    => $media_type,
            'media_url'     => $attachment_url,
            'permalink'     => $permalink,
            'timestamp'     => current_time('mysql', true),
            'imovel_id'     => $forced_imovel_id ? self::validate_imovel_id($forced_imovel_id) : null,
            'attachment_id' => $attachment_id,
        ];
    }

    public static function build_link_only_post_from_input(string $url, ?int $forced_imovel_id, array $overrides): array
    {
        $parsed       = self::parse_instagram_url($url);
        $preview      = self::fetch_public_link_preview($parsed['type'] === 'post' ? $parsed['url'] : $url);
        $caption      = wp_kses_post((string) ($overrides['caption'] ?? ''));
        $permalink    = $parsed['type'] === 'post' ? $parsed['url'] : '';
        $instagram_id = self::extract_instagram_shortcode_from_url($permalink);

        if ($instagram_id === '') {
            $instagram_id = 'manual-link-' . wp_generate_password(12, false, false);
        }

        if ($caption === '' && !empty($preview['description'])) {
            $caption = wp_kses_post((string) $preview['description']);
        }

        return [
            'instagram_id'  => $instagram_id,
            'caption'       => $caption,
            'media_type'    => 'link',
            'media_url'     => (string) ($preview['image_url'] ?? ''),
            'permalink'     => $permalink,
            'timestamp'     => current_time('mysql', true),
            'imovel_id'     => $forced_imovel_id ? self::validate_imovel_id($forced_imovel_id) : self::extract_imovel_id($caption),
            'attachment_id' => 0,
            'external_only' => true,
        ];
    }

    public static function fetch_public_link_preview(string $url): array
    {
        if (!function_exists('exec')) {
            return [];
        }

        $image_line = self::run_instagram_shell_pipeline(
            $url,
            "grep -oE 'twitter:image\\\" content=\\\"[^\\\"]+|og:image\\\" content=\\\"[^\\\"]+' | head -n 1"
        );
        $desc_line = self::run_instagram_shell_pipeline(
            $url,
            "grep -oE 'description\\\" content=\\\"[^\\\"]+' | head -n 1"
        );

        $image_url = '';
        $description = '';

        if ($image_line !== '' && preg_match('/content="([^"]+)/', $image_line, $matches)) {
            $image_url = html_entity_decode((string) $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($desc_line !== '' && preg_match('/content="([^"]+)/', $desc_line, $matches)) {
            $description = html_entity_decode((string) $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return [
            'image_url'    => esc_url_raw($image_url),
            'description'  => $description,
        ];
    }

    public static function run_instagram_shell_pipeline(string $url, string $pipeline): string
    {
        $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0 Safari/537.36';
        $curl_cmd   = sprintf(
            '/usr/bin/curl -sL -A %s -H %s %s',
            escapeshellarg($user_agent),
            escapeshellarg('Accept-Language: en-US,en;q=0.9'),
            escapeshellarg($url)
        );

        $command = sprintf('/bin/sh -lc %s', escapeshellarg($curl_cmd . ' | ' . $pipeline));
        $output  = [];
        $status  = 0;

        exec($command, $output, $status);

        if ($status !== 0 && !$output) {
            return '';
        }

        return trim(implode("\n", $output));
    }

    public static function create_attachment_from_upload(array $uploaded_media, string $description = ''): int
    {
        if (!self::has_uploaded_media($uploaded_media)) {
            throw new RuntimeException('Manual media upload failed.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $overrides = [
            'test_form' => false,
        ];

        $attachment_id = media_handle_upload('manual_media', 0, [], $overrides);

        if (is_wp_error($attachment_id)) {
            throw new RuntimeException($attachment_id->get_error_message());
        }

        if ($description !== '') {
            wp_update_post([
                'ID'           => $attachment_id,
                'post_content' => $description,
            ]);
        }

        return (int) $attachment_id;
    }

    public static function extract_instagram_shortcode_from_url(string $url): string
    {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);

        if (preg_match('#/(?:p|reel|reels|tv)/([^/]+)/?#i', $path, $matches)) {
            return sanitize_title($matches[1]);
        }

        return '';
    }

    public static function validate_imovel_id(int $post_id): ?int
    {
        if (!$post_id) {
            return null;
        }

        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        if (!in_array($post->post_type, ['imovel', 'imoveis'], true)) {
            return null;
        }

        return $post_id;
    }

    public static function find_imovel_id_by_reference(string $reference): ?int
    {
        global $wpdb;

        $normalized_reference = self::normalize_reference_token($reference);
        if ($normalized_reference === '') {
            return null;
        }

        $meta_keys = ['codigo', 'codigo_referencia'];

        foreach ($meta_keys as $meta_key) {
            $results = $wpdb->get_col(
                $wpdb->prepare(
                    "
                    SELECT post_id
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = %s
                      AND UPPER(REPLACE(REPLACE(REPLACE(meta_value, '-', ''), '_', ''), ' ', '')) = %s
                    LIMIT 1
                    ",
                    $meta_key,
                    $normalized_reference
                )
            );

            if (!$results) {
                continue;
            }

            $post_id = self::validate_imovel_id((int) $results[0]);
            if ($post_id) {
                return $post_id;
            }
        }

        return null;
    }

    public static function normalize_reference_token(string $reference): string
    {
        $reference = strtoupper(trim($reference));
        $reference = preg_replace('/[^A-Z0-9]/', '', $reference) ?: '';

        return $reference;
    }

    public static function normalize_instagram_post_url(string $url): string
    {
        $parsed = self::parse_instagram_url($url);

        return $parsed['type'] === 'post' ? $parsed['url'] : '';
    }

    public static function parse_instagram_url(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['type' => '', 'url' => ''];
        }

        $sanitized = esc_url_raw($url);
        $parts     = wp_parse_url($sanitized);

        if (!is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            return ['type' => '', 'url' => ''];
        }

        $host = strtolower((string) $parts['host']);
        if (!in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
            return ['type' => '', 'url' => ''];
        }

        $path = (string) $parts['path'];

        if (preg_match('#^/(?:p|reel|reels|tv)/[^/]+/?#i', $path, $matches)) {
            return [
                'type' => 'post',
                'url'  => 'https://www.instagram.com' . rtrim($matches[0], '/') . '/',
            ];
        }

        if (preg_match('#^/([A-Za-z0-9._]+)/?$#', $path, $matches)) {
            return [
                'type' => 'profile',
                'url'  => 'https://www.instagram.com/' . rawurlencode($matches[1]) . '/',
            ];
        }

        return ['type' => '', 'url' => ''];
    }

    public static function fetch_profile_post_links(string $url, int $limit = 6): array
    {
        $api_links = self::fetch_profile_post_links_from_web_api($url, $limit);
        if ($api_links) {
            return $api_links;
        }

        $response = wp_safe_remote_get(
            $url,
            [
                'timeout' => 20,
                'headers' => [
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0 Safari/537.36',
                ],
            ]
        );

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $html = (string) wp_remote_retrieve_body($response);

        if ($code >= 300 || $html === '') {
            throw new RuntimeException('Instagram profile page could not be fetched.');
        }

        preg_match_all('#https://www\.instagram\.com/(?:p|reel|reels|tv)/[^"\'\\\\<\s]+/#i', $html, $absolute_matches);
        preg_match_all('#/(?:p|reel|reels|tv)/[^"\'\\\\<\s]+/#i', $html, $relative_matches);

        $links = [];

        foreach ($absolute_matches[0] as $match) {
            $links[] = self::normalize_instagram_post_url($match);
        }

        foreach ($relative_matches[0] as $match) {
            $links[] = self::normalize_instagram_post_url('https://www.instagram.com' . $match);
        }

        $links = array_values(array_unique(array_filter($links)));

        return array_slice($links, 0, max(1, $limit));
    }

    public static function fetch_profile_post_links_from_web_api(string $url, int $limit = 6): array
    {
        $parsed   = self::parse_instagram_url($url);
        $path     = (string) wp_parse_url($parsed['url'], PHP_URL_PATH);
        $username = trim($path, '/');

        if ($username === '') {
            return [];
        }

        $api_url = add_query_arg(
            [
                'username' => $username,
            ],
            'https://www.instagram.com/api/v1/users/web_profile_info/'
        );

        $response = wp_safe_remote_get(
            $api_url,
            [
                'timeout' => 20,
                'headers' => [
                    'Accept'            => '*/*',
                    'Referer'           => 'https://www.instagram.com/',
                    'User-Agent'        => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0 Safari/537.36',
                    'X-IG-App-ID'       => '936619743392459',
                    'X-Requested-With'  => 'XMLHttpRequest',
                ],
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($code >= 300 || !is_array($body)) {
            return [];
        }

        $edges = $body['data']['user']['edge_owner_to_timeline_media']['edges'] ?? [];
        if (!is_array($edges) || !$edges) {
            return [];
        }

        $links = [];

        foreach ($edges as $edge) {
            $shortcode = sanitize_text_field((string) ($edge['node']['shortcode'] ?? ''));
            if ($shortcode === '') {
                continue;
            }

            $links[] = 'https://www.instagram.com/p/' . rawurlencode($shortcode) . '/';
        }

        $links = array_values(array_unique(array_filter($links)));

        return array_slice($links, 0, max(1, $limit));
    }

    public static function extract_meta_tags(string $html): array
    {
        $meta = [];

        if ($html === '') {
            return $meta;
        }

        if (class_exists('DOMDocument')) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML($html);
            libxml_clear_errors();

            if ($loaded) {
                foreach ($dom->getElementsByTagName('meta') as $node) {
                    if (!$node instanceof DOMElement) {
                        continue;
                    }

                    $key = $node->getAttribute('property');
                    if ($key === '') {
                        $key = $node->getAttribute('name');
                    }

                    $content = $node->getAttribute('content');
                    if ($key === '' || $content === '') {
                        continue;
                    }

                    $meta[strtolower(html_entity_decode($key, ENT_QUOTES | ENT_HTML5, 'UTF-8'))] = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        if ($meta) {
            return $meta;
        }

        if (preg_match_all('/<meta\s+[^>]*(?:property|name)=["\']([^"\']+)["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $meta[strtolower(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (preg_match_all('/<meta\s+[^>]*content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']([^"\']+)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $meta[strtolower(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $meta;
    }

    public static function extract_caption_from_meta(array $meta): string
    {
        $candidates = [
            $meta['og:description'] ?? '',
            $meta['description'] ?? '',
            $meta['twitter:description'] ?? '',
            $meta['og:title'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim(wp_strip_all_tags((string) $candidate));
            if ($candidate === '') {
                continue;
            }

            $candidate = preg_replace('/\s+on Instagram:.*$/i', '', $candidate) ?: $candidate;
            $candidate = preg_replace('/^Photo by .*? on Instagram[:\s-]*/i', '', $candidate) ?: $candidate;

            return trim($candidate);
        }

        return '';
    }

    public static function download_media(array $post): int
    {
        $url = $post['media_url'] ?? '';
        if (!$url) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url, 30);

        if (is_wp_error($tmp)) {
            self::log('Media download failed.', ['url' => $url, 'error' => $tmp->get_error_message()]);
            return 0;
        }

        $path     = wp_parse_url($url, PHP_URL_PATH);
        $filename = $path ? wp_basename($path) : 'instagram-media';

        if (!str_contains($filename, '.')) {
            $filename .= ($post['media_type'] ?? '') === 'video' ? '.mp4' : '.jpg';
        }

        $file_array = [
            'name'     => sanitize_file_name($filename),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload(
            $file_array,
            0,
            sanitize_text_field(wp_trim_words((string) ($post['caption'] ?? 'Instagram media'), 12))
        );

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            self::log('Media sideload failed.', ['url' => $url, 'error' => $attachment_id->get_error_message()]);
            return 0;
        }

        return (int) $attachment_id;
    }

    public static function get_imported_posts(int $limit = 50): array
    {
        global $wpdb;

        self::create_table();

        $table_name = self::get_table_name();
        $limit      = max(1, absint($limit));

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY timestamp DESC LIMIT %d", $limit),
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }

    public static function get_posts_for_imovel(int $imovel_id): array
    {
        global $wpdb;

        self::create_table();

        $table_name = self::get_table_name();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE imovel_id = %d ORDER BY timestamp DESC",
                $imovel_id
            ),
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }

    public static function get_recent_posts(int $limit = 6, bool $only_linked = false): array
    {
        global $wpdb;

        self::create_table();

        $table_name = self::get_table_name();
        $limit      = max(1, absint($limit));
        $where_sql  = $only_linked ? 'WHERE imovel_id IS NOT NULL' : '';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }
}
