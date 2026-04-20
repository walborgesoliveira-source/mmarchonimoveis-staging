<?php

if (!defined('ABSPATH')) {
    exit;
}

class MMarchon_Instagram_Sync_Admin
{
    public static function boot(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            __('Instagram Sync', 'mmarchon-instagram-sync'),
            __('Instagram Sync', 'mmarchon-instagram-sync'),
            'manage_options',
            'mmarchon-instagram-sync',
            [__CLASS__, 'render_page'],
            'dashicons-instagram',
            58
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            'mmarchon_instagram_sync',
            MMarchon_Instagram_Sync::OPTION_SETTINGS,
            [
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                'default'           => MMarchon_Instagram_Sync::get_settings(),
            ]
        );
    }

    public static function sanitize_settings($value): array
    {
        $settings = [
            'instagram_username' => MMarchon_Instagram_Sync::sanitize_instagram_username($value['instagram_username'] ?? MMarchon_Instagram_Sync::DEFAULT_INSTAGRAM_USERNAME),
            'access_token'       => sanitize_text_field($value['access_token'] ?? ''),
            'user_id'            => sanitize_text_field($value['user_id'] ?? ''),
            'mock_enabled'       => empty($value['mock_enabled']) ? '0' : '1',
        ];

        MMarchon_Instagram_Sync::save_settings($settings);

        return $settings;
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings    = MMarchon_Instagram_Sync::get_settings();
        $last_result = get_option(MMarchon_Instagram_Sync::OPTION_LAST_RESULT, []);
        $posts       = MMarchon_Instagram_Sync::get_imported_posts();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Instagram Sync', 'mmarchon-instagram-sync'); ?></h1>
            <p><?php esc_html_e('Cadastra links de posts do Instagram no site e associa automaticamente aos imóveis por hashtag #ID123, #REFCODIGO ou #COD123.', 'mmarchon-instagram-sync'); ?></p>

            <?php if (!empty($_GET['synced'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                'Sincronização concluída. Importados: %d | Duplicados: %d | Erros: %d',
                                absint($_GET['imported'] ?? 0),
                                absint($_GET['duplicates'] ?? 0),
                                absint($_GET['errors'] ?? 0)
                            )
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['url_import'])) : ?>
                <div class="notice <?php echo !empty($_GET['errors']) ? 'notice-error' : 'notice-success'; ?> is-dismissible">
                    <p>
                        <?php
                        if (!empty($_GET['errors'])) {
                            echo esc_html(rawurldecode((string) ($_GET['message'] ?? 'Falha ao importar pela URL do Instagram.')));
                        } else {
                            echo esc_html(
                                sprintf(
                                    'Importação por URL concluída. Importados: %d | Duplicados: %d | Erros: %d',
                                    absint($_GET['imported'] ?? 0),
                                    absint($_GET['duplicates'] ?? 0),
                                    absint($_GET['errors'] ?? 0)
                                )
                            );
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('mmarchon_instagram_sync'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mmarchon_instagram_username"><?php esc_html_e('Instagram Username', 'mmarchon-instagram-sync'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(MMarchon_Instagram_Sync::OPTION_SETTINGS); ?>[instagram_username]" id="mmarchon_instagram_username" type="text" class="regular-text" value="<?php echo esc_attr($settings['instagram_username']); ?>">
                            <p class="description"><?php esc_html_e('Conta usada neste ambiente de staging. Ex.: mmimoveis__', 'mmarchon-instagram-sync'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mmarchon_access_token"><?php esc_html_e('Access Token', 'mmarchon-instagram-sync'); ?></label></th>
                        <td><input name="<?php echo esc_attr(MMarchon_Instagram_Sync::OPTION_SETTINGS); ?>[access_token]" id="mmarchon_access_token" type="password" class="regular-text" value="<?php echo esc_attr($settings['access_token']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mmarchon_user_id"><?php esc_html_e('Instagram User ID', 'mmarchon-instagram-sync'); ?></label></th>
                        <td><input name="<?php echo esc_attr(MMarchon_Instagram_Sync::OPTION_SETTINGS); ?>[user_id]" id="mmarchon_user_id" type="text" class="regular-text" value="<?php echo esc_attr($settings['user_id']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Modo mock', 'mmarchon-instagram-sync'); ?></th>
                        <td>
                            <label>
                                <input name="<?php echo esc_attr(MMarchon_Instagram_Sync::OPTION_SETTINGS); ?>[mock_enabled]" type="checkbox" value="1" <?php checked($settings['mock_enabled'], '1'); ?>>
                                <?php esc_html_e('Usar dados simulados quando token/user_id não estiverem configurados.', 'mmarchon-instagram-sync'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Salvar configurações', 'mmarchon-instagram-sync')); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 20px;">
                <?php wp_nonce_field('mmarchon_instagram_sync_now'); ?>
                <input type="hidden" name="action" value="mmarchon_instagram_sync_now">
                <?php submit_button(__('Sincronizar agora', 'mmarchon-instagram-sync'), 'secondary', 'submit', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="margin-top: 20px; max-width: 760px;">
                <?php wp_nonce_field('mmarchon_instagram_import_url'); ?>
                <input type="hidden" name="action" value="mmarchon_instagram_import_url">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mmarchon_instagram_url"><?php esc_html_e('Importar por URL', 'mmarchon-instagram-sync'); ?></label></th>
                        <td>
                            <input name="instagram_url" id="mmarchon_instagram_url" type="url" class="regular-text code" placeholder="https://www.instagram.com/p/ABC123/">
                            <p class="description"><?php esc_html_e('Cole a URL pública de um post, reel ou perfil do Instagram. Se enviar um arquivo manualmente abaixo, a URL vira opcional e serve apenas como link da publicação.', 'mmarchon-instagram-sync'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mmarchon_manual_media"><?php esc_html_e('Arquivo manual', 'mmarchon-instagram-sync'); ?></label></th>
                        <td>
                            <input name="manual_media" id="mmarchon_manual_media" type="file" accept="image/*,video/*">
                            <p class="description"><?php esc_html_e('Opcional. Envie uma imagem ou vídeo para cadastrar o card no WordPress mesmo quando o Instagram não expuser a mídia publicamente.', 'mmarchon-instagram-sync'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mmarchon_instagram_imovel_id"><?php esc_html_e('ID do imóvel', 'mmarchon-instagram-sync'); ?></label></th>
                        <td>
                            <input name="imovel_id" id="mmarchon_instagram_imovel_id" type="number" min="1" class="small-text" placeholder="60">
                            <p class="description"><?php esc_html_e('Opcional. Se deixar em branco, o plugin tenta identificar pelo texto da publicação usando #ID123, #REFCODIGO ou #COD123.', 'mmarchon-instagram-sync'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mmarchon_caption_override"><?php esc_html_e('Legenda manual', 'mmarchon-instagram-sync'); ?></label></th>
                        <td>
                            <textarea name="caption_override" id="mmarchon_caption_override" rows="4" class="large-text" placeholder="Opcional: cole aqui a legenda se o Instagram não entregar o texto corretamente."></textarea>
                            <p class="description"><?php esc_html_e('Opcional. Se preencher, esta legenda substitui o texto extraído da publicação.', 'mmarchon-instagram-sync'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Cadastrar link do Instagram', 'mmarchon-instagram-sync'), 'secondary', 'submit', false); ?>
            </form>

            <h2 style="margin-top: 30px;"><?php esc_html_e('Status', 'mmarchon-instagram-sync'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><td><?php esc_html_e('Fonte atual', 'mmarchon-instagram-sync'); ?></td><td><?php echo esc_html($last_result['source'] ?? ($settings['mock_enabled'] === '1' ? 'mock' : 'graph_api')); ?></td></tr>
                    <tr><td><?php esc_html_e('Conta configurada', 'mmarchon-instagram-sync'); ?></td><td><a href="<?php echo esc_url(MMarchon_Instagram_Sync::get_instagram_profile_url()); ?>" target="_blank" rel="noopener noreferrer">@<?php echo esc_html($settings['instagram_username']); ?></a></td></tr>
                    <tr><td><?php esc_html_e('Últimos importados', 'mmarchon-instagram-sync'); ?></td><td><?php echo esc_html((string) ($last_result['imported'] ?? 0)); ?></td></tr>
                    <tr><td><?php esc_html_e('Duplicações evitadas', 'mmarchon-instagram-sync'); ?></td><td><?php echo esc_html((string) ($last_result['duplicates'] ?? 0)); ?></td></tr>
                    <tr><td><?php esc_html_e('Erros', 'mmarchon-instagram-sync'); ?></td><td><?php echo esc_html((string) ($last_result['errors'] ?? 0)); ?></td></tr>
                    <tr><td><?php esc_html_e('Log file', 'mmarchon-instagram-sync'); ?></td><td><code><?php echo esc_html(MMarchon_Instagram_Sync::get_log_file()); ?></code></td></tr>
                </tbody>
            </table>

            <h2 style="margin-top: 30px;"><?php esc_html_e('Posts importados', 'mmarchon-instagram-sync'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Instagram ID', 'mmarchon-instagram-sync'); ?></th>
                        <th><?php esc_html_e('Tipo', 'mmarchon-instagram-sync'); ?></th>
                        <th><?php esc_html_e('Imóvel', 'mmarchon-instagram-sync'); ?></th>
                        <th><?php esc_html_e('Data', 'mmarchon-instagram-sync'); ?></th>
                        <th><?php esc_html_e('Link', 'mmarchon-instagram-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$posts) : ?>
                    <tr><td colspan="5"><?php esc_html_e('Nenhum post importado ainda.', 'mmarchon-instagram-sync'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($posts as $post) : ?>
                        <tr>
                            <td><code><?php echo esc_html($post['instagram_id']); ?></code></td>
                            <td><?php echo esc_html($post['media_type']); ?></td>
                            <td><?php echo $post['imovel_id'] ? esc_html('#' . $post['imovel_id']) : '&mdash;'; ?></td>
                            <td><?php echo esc_html($post['timestamp']); ?></td>
                            <td>
                                <?php if (!empty($post['permalink'])) : ?>
                                    <a href="<?php echo esc_url($post['permalink']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Abrir', 'mmarchon-instagram-sync'); ?></a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
