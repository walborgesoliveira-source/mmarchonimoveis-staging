<?php
/**
 * Footer — Marchon Child
 */
?>
<footer class="marchon-site-footer" aria-label="Rodapé do site">
    <div class="marchon-site-footer-inner">
        <div class="marchon-site-footer-top">
            <div class="marchon-site-footer-nav">
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu-rodape',
                    'menu_class'     => 'marchon-footer-menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                ]);
                ?>
            </div>
        </div>
        <p class="marchon-site-footer-line">
            &copy;2026 <a href="https://www.iaguru.com.br/" target="_blank" rel="noopener noreferrer">IA GURU</a> | Waldir Tuca Borges — Rio de Janeiro, RJ | Todos os direitos reservados.
        </p>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
