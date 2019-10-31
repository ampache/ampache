        </div>
    <!-- /container -->
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/components/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/javascript/base.js"></script>
    <?php
        if (isset($jsEnd) && !empty($jsEnd) && is_array($jsEnd)) {
            foreach ($jsEnd as $js) {
                echo $js;
            }
        } ?>
    </body>
</html>
