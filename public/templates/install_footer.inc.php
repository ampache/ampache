<?php
/** @var string $web_path */
?>
        </div>
    <!-- /container -->
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/components/bootstrap/js/bootstrap.min.js"></script>
    <?php
        if (!empty($jsEnd) && is_array($jsEnd)) {
            foreach ($jsEnd as $js) {
                echo $js;
            }
        } ?>
    </body>
</html>
