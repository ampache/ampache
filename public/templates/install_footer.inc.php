<?php
/** @var string $web_path */

// install_footer.inc.php

?>
        </div>
    <!-- /container -->
    <!-- Bootstrap core JavaScript -->
    <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/components/bootstrap/js/bootstrap.min.js"></script>
    <?php
        /** @var array $jsEnd */
        if (!empty($jsEnd) && is_array($jsEnd)) {
            foreach ($jsEnd as $js) {
                echo $js;
            }
        } ?>
    <?php echo \Ampache\Module\Util\Ui::material_symbol_sprite(); ?>
    </body>
</html>
