<?php
/**
 * Template: Página Inicial
 * Marchon Child Theme
 */
get_header(); ?>

<?php $marchon_instagram_shortcode = marchon_get_instagram_feed_shortcode(); ?>

<?php echo do_shortcode('[banner_imoveis]'); ?>

<?php echo do_shortcode('[marchon_stats]'); ?>

<?php $marchon_editorial_posts = marchon_render_editorial_posts(3); ?>

<!-- ÚLTIMOS IMÓVEIS -->
<section class="marchon-imoveis">
    <div class="marchon-imoveis-inner">
        <div class="secao-label">Portfólio</div>
        <h2 class="secao-titulo">Imóveis <em>disponíveis</em></h2>
        <?php echo do_shortcode('[ultimos_imoveis quantidade="6"]'); ?>
        <div class="ver-todos">
            <a href="<?php echo get_post_type_archive_link('imoveis'); ?>" class="btn-verde">
                Ver todos os imóveis
            </a>
        </div>
    </div>
</section>

<?php if ($marchon_editorial_posts !== '') : ?>
<section class="marchon-editorial-home">
    <div class="marchon-editorial-home-inner">
        <div class="secao-label">Conteúdo</div>
        <h2 class="secao-titulo">Atualizações, oportunidades e bastidores do <em>mercado local</em></h2>
        <p class="marchon-editorial-intro">Os posts publicados no WordPress agora aparecem na primeira página. Isso resolve o conteúdo órfão e cria um fluxo simples para você manter novidades, campanhas e destaques.</p>
        <?php echo $marchon_editorial_posts; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($marchon_instagram_shortcode !== '') : ?>
<section class="marchon-instagram-home">
    <div class="marchon-instagram-home-inner">
        <div class="marchon-instagram-home-hero">
            <div class="marchon-instagram-home-copy">
                <div class="secao-label">Instagram</div>
                <h2 class="secao-titulo">Descubra imóveis que encantam à primeira vista e despertam <em>vontade de visitar</em></h2>
                <p class="marchon-instagram-home-intro">No Instagram da MM Imóveis, cada publicação aproxima você de oportunidades especiais, mostrando detalhes, atmosfera e o estilo de vida que cada imóvel pode oferecer.</p>
                <div class="marchon-instagram-home-points">
                    <span>Oportunidades em destaque</span>
                    <span>Ambientes que inspiram</span>
                    <span>Contato rápido pelo WhatsApp</span>
                </div>
                <div class="marchon-instagram-home-actions">
                    <a href="https://wa.me/5522998121056" target="_blank" rel="noopener noreferrer" class="btn-verde">Quero atendimento prioritário</a>
                    <a href="https://www.instagram.com/mmimoveis__/" target="_blank" rel="noopener noreferrer" class="btn-outline">Ver vitrine no Instagram</a>
                </div>
            </div>
        </div>
        <div class="marchon-instagram-feed-shell">
            <?php echo marchon_render_instagram_feed(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SOBRE O CORRETOR -->
<section class="marchon-sobre" id="sobre">
    <div class="marchon-sobre-inner">
        <div class="sobre-foto">
            <?php
            $foto_corretor = get_stylesheet_directory_uri() . '/assets/images/marcosmarchon2026.png';
            ?>
            <img src="<?php echo esc_url($foto_corretor); ?>" alt="Corretor Marcos Marchon">
            <div class="sobre-creci">
                <strong>CRECI</strong>
                <span>95681</span>
            </div>
        </div>
        <div class="sobre-texto">
            <div class="secao-label">Quem sou eu</div>
            <h2 class="secao-titulo">Marcos<br><em>Marchon</em></h2>
            <p>Corretor de imóveis altamente qualificado e comprometido, com registro ativo no CRECI, garantindo segurança e profissionalismo em cada negociação.</p>
            <p>Nascido em Nova Friburgo e residente do 5º Distrito de Lumiar, possuo uma conexão especial com a região — compreendendo suas nuances culturais e as necessidades específicas dos moradores e compradores locais.</p>
            <p>Com vasta experiência em vendas e locação na área, ofereço orientação personalizada em cada etapa do processo.</p>
            <a href="https://wa.me/5522998121056" target="_blank" class="btn-wpp">
                <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Falar com Marcos
            </a>
        </div>
    </div>
</section>

<!-- DEPOIMENTOS -->
<section class="marchon-depoimentos">
    <div class="marchon-depoimentos-inner">
        <div class="secao-label">Clientes</div>
        <h2 class="secao-titulo">O que dizem sobre<br><em>nosso trabalho</em></h2>
        <div class="depoimentos-grid">
            <?php
            $args = [
                'post_type'      => 'comments',
                'status'         => 'approve',
                'number'         => 6,
                'post_type__in'  => ['imoveis'],
            ];
            $comments = get_comments([
                'status'  => 'approve',
                'number'  => 6,
                'post_type' => 'imoveis',
            ]);
            if ($comments):
                foreach ($comments as $comment): ?>
                <div class="depoimento-card">
                    <div class="depoimento-estrelas">★★★★★</div>
                    <p class="depoimento-texto"><?php echo esc_html($comment->comment_content); ?></p>
                    <div class="depoimento-autor"><?php echo esc_html($comment->comment_author); ?></div>
                </div>
            <?php endforeach;
            else: ?>
                <p style="color:var(--cinza-suave);grid-column:1/-1">
                    Os depoimentos aparecerão aqui conforme os clientes forem comentando nos imóveis.
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CONTATO -->
<section class="marchon-contato" id="contato">
    <div class="marchon-contato-inner">
        <div class="contato-info">
            <div class="secao-label">Fale Conosco</div>
            <h2 class="secao-titulo">Pronto para<br><em>encontrar</em><br>seu imóvel?</h2>
            <div style="margin-top:2rem">
                <div class="contato-item">
                    <div class="contato-icone">
                        <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <span class="contato-label">WhatsApp / Telefone</span>
                        <div class="contato-valor"><a href="https://wa.me/5522998121056">(22) 99812-1056</a></div>
                    </div>
                </div>
                <div class="contato-item">
                    <div class="contato-icone">
                        <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <span class="contato-label">E-mail</span>
                        <div class="contato-valor"><a href="mailto:suportemarcosmarchonimoveis@gmail.com">suportemarcosmarchonimoveis@gmail.com</a></div>
                    </div>
                </div>
                <div class="contato-item">
                    <div class="contato-icone">
                        <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <span class="contato-label">Localização</span>
                        <div class="contato-valor">Lumiar, Nova Friburgo — RJ</div>
                    </div>
                </div>
            </div>
            <div class="redes-sociais">
                <a href="https://www.instagram.com/mmimoveis__/" target="_blank" class="rede-btn" aria-label="Instagram">
                    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="https://www.facebook.com/mmarchonimoveis" target="_blank" class="rede-btn" aria-label="Facebook">
                    <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
            </div>
        </div>
        <div>
            <?php $contact_form_shortcode = marchon_get_contact_form_shortcode(); ?>
            <?php if ($contact_form_shortcode): ?>
                <?php echo do_shortcode($contact_form_shortcode); ?>
            <?php else: ?>
                <div class="contact-fallback-card">
                    <div class="secao-label">Contato direto</div>
                    <h3 class="contact-fallback-title">O formulário ainda não está ativo neste ambiente.</h3>
                    <p>Você pode falar com Marcos agora mesmo por WhatsApp ou por e-mail.</p>
                    <div class="contact-fallback-actions">
                        <a href="https://wa.me/5522998121056" target="_blank" class="btn-verde">Chamar no WhatsApp</a>
                        <a href="mailto:suportemarcosmarchonimoveis@gmail.com" class="btn-outline">Enviar e-mail</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
