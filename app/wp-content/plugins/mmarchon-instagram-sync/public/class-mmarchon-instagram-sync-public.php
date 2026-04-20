<?php

if (!defined('ABSPATH')) {
    exit;
}

class MMarchon_Instagram_Sync_Public
{
    public static function boot(): void
    {
        add_shortcode('instagram_imovel', [__CLASS__, 'render_shortcode']);
        add_shortcode('instagram_feed', [__CLASS__, 'render_feed_shortcode']);
    }

    public static function render_shortcode(array $atts): string
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'instagram_imovel'
        );

        $imovel_id = absint($atts['id']);
        if (!$imovel_id && is_singular(['imovel', 'imoveis'])) {
            $imovel_id = get_the_ID();
        }

        if (!$imovel_id) {
            return '';
        }

        $posts = MMarchon_Instagram_Sync::get_posts_for_imovel($imovel_id);
        if (!$posts) {
            return '<div class="mmarchon-instagram-sync-empty">Nenhum post do Instagram vinculado a este imóvel.</div>';
        }

        return self::render_posts_grid($posts, true);
    }

    public static function render_feed_shortcode(array $atts): string
    {
        $atts = shortcode_atts(
            [
                'limit'       => 6,
                'only_linked' => 1,
            ],
            $atts,
            'instagram_feed'
        );

        $posts = MMarchon_Instagram_Sync::get_recent_posts(
            absint($atts['limit']),
            (bool) absint((string) $atts['only_linked'])
        );

        if (!$posts) {
            return '<div class="mmarchon-instagram-sync-empty">Nenhum post sincronizado do Instagram ainda.</div>';
        }

        return self::render_posts_grid($posts, false);
    }

    public static function render_posts_grid(array $posts, bool $show_related_imovel): string
    {
        ob_start();
        ?>
        <div class="mmarchon-instagram-sync-grid">
            <?php foreach ($posts as $post) : ?>
                <article class="mmarchon-instagram-sync-item">
                    <?php echo self::render_media($post); ?>
                    <div class="mmarchon-instagram-sync-body">
                    <?php if ($show_related_imovel && !empty($post['imovel_id'])) : ?>
                        <p class="mmarchon-instagram-sync-meta">Imóvel relacionado: <strong><?php echo esc_html(get_the_title((int) $post['imovel_id'])); ?></strong></p>
                    <?php endif; ?>
                    <?php if (!empty($post['caption'])) : ?>
                        <p class="mmarchon-instagram-sync-caption"><?php echo esc_html(wp_trim_words((string) $post['caption'], 26)); ?></p>
                    <?php endif; ?>
                    <?php if (!$show_related_imovel && !empty($post['imovel_id'])) : ?>
                        <p class="mmarchon-instagram-sync-meta">
                            <a href="<?php echo esc_url(get_permalink((int) $post['imovel_id'])); ?>">Ver imóvel relacionado</a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($post['permalink'])) : ?>
                        <p class="mmarchon-instagram-sync-actions"><a class="mmarchon-instagram-sync-cta" href="<?php echo esc_url($post['permalink']); ?>" target="_blank" rel="noopener noreferrer">Ver no Instagram</a></p>
                    <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <style>
            .mmarchon-instagram-sync-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin:24px 0}
            .mmarchon-instagram-sync-item{border:1px solid rgba(53,95,70,.14);padding:16px;border-radius:18px;background:linear-gradient(180deg,#fff 0%,#fbf8f1 100%);box-shadow:0 20px 40px rgba(31,59,43,.08)}
            .mmarchon-instagram-sync-body{display:flex;flex-direction:column;gap:12px;padding-top:14px}
            .mmarchon-instagram-sync-item img,.mmarchon-instagram-sync-item video{width:100%;height:auto;display:block;border-radius:8px}
            .mmarchon-instagram-sync-thumb{display:block;position:relative}
            .mmarchon-instagram-sync-link-badge{position:absolute;left:14px;top:14px;display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(31,59,43,.78);color:#fff;font-size:11px;letter-spacing:.12em;text-transform:uppercase}
            .mmarchon-instagram-sync-placeholder{display:flex;min-height:220px;padding:22px;border-radius:14px;background:radial-gradient(circle at top right,rgba(196,154,42,.32),transparent 38%),linear-gradient(135deg,#1f3b2b,#355f46 54%,#6a3c5b 100%);color:#fff;flex-direction:column;justify-content:flex-end;gap:10px;text-decoration:none}
            .mmarchon-instagram-sync-placeholder strong{font-size:26px;line-height:1.05;max-width:10ch}
            .mmarchon-instagram-sync-placeholder span{font-size:14px;line-height:1.4;max-width:18ch}
            .mmarchon-instagram-sync-badge{display:inline-flex;align-self:flex-start;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.15);font-size:11px;letter-spacing:.12em;text-transform:uppercase}
            .mmarchon-instagram-sync-play{position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%);width:56px;height:56px;border-radius:50%;background:rgba(0,0,0,.68);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
            .mmarchon-instagram-sync-caption{margin:0;font-size:14px;line-height:1.65;color:#445348}
            .mmarchon-instagram-sync-meta{margin:0;font-size:12px;line-height:1.5;color:#6c746b;letter-spacing:.04em;text-transform:uppercase}
            .mmarchon-instagram-sync-actions{margin:4px 0 0}
            .mmarchon-instagram-sync-cta{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border-radius:999px;background:#1f3b2b;color:#fff;text-decoration:none;font-size:12px;letter-spacing:.12em;text-transform:uppercase}
            .mmarchon-instagram-sync-cta:hover{background:#355f46;color:#fff}
        </style>
        <?php

        return (string) ob_get_clean();
    }

    public static function render_media(array $post): string
    {
        $attachment_id = !empty($post['attachment_id']) ? absint($post['attachment_id']) : 0;
        $permalink     = !empty($post['permalink']) ? esc_url($post['permalink']) : '';
        $media_type    = (string) ($post['media_type'] ?? '');

        if ($media_type === 'link') {
            if (!$permalink) {
                return '';
            }

            $image_url = (string) ($post['media_url'] ?? '');
            if ($image_url !== '') {
                return sprintf(
                    '<a class="mmarchon-instagram-sync-thumb is-link" href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt=""><span class="mmarchon-instagram-sync-link-badge">Instagram</span></a>',
                    $permalink,
                    esc_url($image_url)
                );
            }

            return sprintf(
                '<a class="mmarchon-instagram-sync-placeholder" href="%s" target="_blank" rel="noopener noreferrer"><span class="mmarchon-instagram-sync-badge">Instagram</span><strong>Publicacao vinculada</strong><span>Abra o conteudo completo direto no Instagram.</span></a>',
                $permalink
            );
        }

        if ($media_type === 'video') {
            if ($attachment_id) {
                $video_url = wp_get_attachment_url($attachment_id);
                if (!$video_url) {
                    return '';
                }

                return sprintf(
                    '<video controls preload="metadata" playsinline src="%s"></video>',
                    esc_url($video_url)
                );
            }

            $image_url = (string) ($post['media_url'] ?? '');
            if ($image_url === '') {
                return '';
            }

            return sprintf(
                '<a class="mmarchon-instagram-sync-thumb is-video" href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt=""><span class="mmarchon-instagram-sync-play" aria-hidden="true">&#9654;</span></a>',
                $permalink ?: '#',
                esc_url($image_url)
            );
        }

        if ($attachment_id) {
            $image = wp_get_attachment_image($attachment_id, 'large');
            if ($image) {
                return $image;
            }
        }

        if (!empty($post['media_url'])) {
            $image_html = sprintf('<img src="%s" alt="">', esc_url($post['media_url']));
            if ($permalink) {
                return sprintf(
                    '<a class="mmarchon-instagram-sync-thumb" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                    $permalink,
                    $image_html
                );
            }

            return $image_html;
        }

        return '';
    }
}
