<?php

/**
 * @package Duplicator
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */
?>
<div id="dup-remote-package-download-wrapper">
    <div class="dup-download-progress">
        <p><?php esc_html_e('No download is currently in progress.', 'duplicator-pro'); ?></p>
    </div>
</div>
<script>
    jQuery(document).ready(function ($) {
        let content = $('tr.dup-row-progress').find('td').html();
        let transferProgress = $('.dpro-progress-status-message-transfer');
        if (transferProgress.length && content && content.length > 0) {
            $('.dup-download-progress').html(content);
        }
    });
</script>
