<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Models\Storages\AbstractStorageEntity;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var AbstractStorageEntity[] $storages
 */
$storages = $tplData['storages'];
/** @var int */
$packageId = $tplData['packageId'];
/** @var string */
$packageName = $tplData['packageName'];
/** @var bool */
$isStorageFull = $tplData['isStorageFull'];

?>
<div id="dup-remote-package-download">
    <form method="POST" action="">
        <input type="hidden" name="package_id" value="<?php echo (int) $packageId; ?>">
        <input type="hidden" name="download" value="1">
        <input type="hidden" name="action" value="<?php echo esc_attr(PackagesPageController::ACTION_START_DOWNLOAD); ?>">
        <?php PackagesPageController::getInstance()->getActionByKey(PackagesPageController::ACTION_START_DOWNLOAD)->getActionNonceFileds(); ?>
        <input type="hidden" name="afterDownloadAction" value="">
        <p>
            <?php
            printf(
                wp_kses(
                    __('The <b>%s</b> backup isn\'t in a local storage, it\'s possible to download it on local server.', 'duplicator-pro'),
                    [
                        'b' => [],
                    ]
                ),
                esc_html($packageName)
            );
            ?>
        </p>
        <?php
        $tplMng->render('admin_pages/packages/remote_download/remote_storages_list');
        if ($isStorageFull) {
            ?>
            <p>
                <small>
                    <i class="maroon">
                        <?php
                        esc_html_e(
                            'The maximum number of backups for the default local storage has been reached. 
                            The oldest backup will be deleted automatically, after the download finishes. 
                            Alternatively you can delete a backup to make room for the download.',
                            'duplicator-pro'
                        );
                        ?>
                    </i>
                </small>
            </p>
        <?php } ?>
        <hr class="margin-top-1 margin-bottom-1" >
        <div class="dup-buttons-container">
            <button id="dup-remote-package-cancel-btn" class="button button-secondary">
                <?php esc_html_e('Cancel', 'duplicator-pro'); ?>
            </button>
            <button 
                id="dup-remote-package-download-btn" 
                class="button button-primary"
                type="submit"
            >
                <?php esc_html_e('Download', 'duplicator-pro'); ?>
            </button>
        </div>
    </form>
</div>
<script>
jQuery(document).ready(function ($) {
    $('input[name="storage_ids[]"]').click(function(event) {
        $('#dup-remote-package-download-btn').prop('disabled', false);
    });

    $('#dup-remote-package-cancel-btn').click(function(event) {
        event.preventDefault();
        tb_remove();
    });
});
</script>
