<?php

/**
 * Duplicator messages sections
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Addons\FtpAddon\Models\SFTPStorage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

$uploadChunkSize   = $tplData['uploadChunkSize'];
$downloadChunkSize = $tplData['downloadChunkSize'];
?>
<h3 class="title"><?php echo esc_html(SFTPStorage::getStypeName()); ?></h3>
<hr size="1" />
<table class="form-table">
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Download Chunk Size", 'duplicator-pro'); ?></label></th>
        <td>
            <input class="dup-narrow-input text-right"
                   type="number"
                   min="<?php echo (int) SFTPStorage::MIN_DOWNLOAD_CHUNK_SIZE_IN_MB; ?>"
                   max="<?php echo (int) SFTPStorage::MAX_DOWNLOAD_CHUNK_SIZE_IN_MB; ?>"
                   name="sftp_download_chunksize_in_mb"
                   id="sftp_download_chunksize_in_mb"
                   data-parsley-required
                   data-parsley-type="number"
                   data-parsley-errors-container="#sftp_download_chunksize_in_mb_error_container"
                   value="<?php echo (int) $downloadChunkSize; ?>" />&nbsp;<b>MB</b>
            <div id="sftp_download_chunksize_in_mb_error_container" class="duplicator-error-container"></div>
            <p class="description">
                <?php esc_html_e('How much should be downloaded from the server per attempt.', 'duplicator-pro'); ?>
                <?php echo esc_html(sprintf(__('Min size %smb.', 'duplicator-pro'), SFTPStorage::MIN_DOWNLOAD_CHUNK_SIZE_IN_MB)); ?>
            </p>
        </td>
    </tr>
</table>
