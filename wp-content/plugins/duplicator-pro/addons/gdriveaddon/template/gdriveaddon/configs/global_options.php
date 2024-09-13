<?php

/**
 * Duplicator messages sections
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Addons\GDriveAddon\Models\GDriveStorage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */
?>
<h3 class="title"><?php echo esc_html(GDriveStorage::getStypeName()); ?></h3>
<hr size="1" />
<table class="form-table">
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Upload Chunk Size", 'duplicator-pro'); ?></label></th>
        <td>
            <input
                class="dup-narrow-input text-right"
                name="gdrive_upload_chunksize_in_kb"
                id="gdrive_upload_chunksize_in_kb"
                type="number"
                min="256"
                step="256"
                data-parsley-required
                data-parsley-type="number"
                data-parsley-errors-container="#gdrive_upload_chunksize_in_kb_error_container"
                value="<?php echo (int) $tplData['uploadChunkSize']; ?>"
            >&nbsp;<b>KB</b>
            <div id="gdrive_upload_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
            <p class="description">
                <?php esc_html_e(
                    'How much should be uploaded to Google Drive per attempt. Higher=faster but less reliable. It should be multiple of 256.',
                    'duplicator-pro'
                ); ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Download Chunk Size", 'duplicator-pro'); ?></label></th>
        <td>
            <input
                class="dup-narrow-input text-right"
                name="gdrive_download_chunksize_in_kb"
                id="gdrive_download_chunksize_in_kb"
                type="number"
                min="256"
                step="256"
                data-parsley-required
                data-parsley-type="number"
                data-parsley-errors-container="#gdrive_download_chunksize_in_kb_error_container"
                value="<?php echo (int) $tplData['downloadChunkSize']; ?>"
            >&nbsp;<b>KB</b>
            <div id="gdrive_download_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
            <p class="description">
                <?php esc_html_e(
                    'How much should be downloaded from Google Drive per attempt.',
                    'duplicator-pro'
                ); ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Transfer Mode", 'duplicator-pro'); ?></label></th>
        <td>
            <input
                type="radio"
                value="<?php echo (int) DUP_PRO_Google_Drive_Transfer_Mode::Auto ?>"
                name="gdrive_transfer_mode" id="gdrive_transfer_mode_auto"
                <?php checked($tplData['transferMode'], DUP_PRO_Google_Drive_Transfer_Mode::Auto); ?>
            >
            <label for="gdrive_transfer_mode_auto"><?php esc_html_e("Auto", 'duplicator-pro'); ?></label> &nbsp;

            <input
                type="radio" <?php disabled(!DUP_PRO_Server::isURLFopenEnabled()) ?>
                value="<?php echo (int) DUP_PRO_Google_Drive_Transfer_Mode::FOpen_URL ?>"
                name="gdrive_transfer_mode"
                id="gdrive_transfer_mode_stream"
                <?php checked($tplData['transferMode'], DUP_PRO_Google_Drive_Transfer_Mode::FOpen_URL); ?>
            >
            <label for="gdrive_transfer_mode_stream"><?php esc_html_e("FOpen URL", 'duplicator-pro'); ?></label> &nbsp;
            <?php if (!DUP_PRO_Server::isURLFopenEnabled()) : ?>
                <i
                    class="fas fa-question-circle fa-sm"
                    data-tooltip-title="<?php esc_attr_e("FOpen URL", 'duplicator-pro'); ?>"
                    data-tooltip="<?php esc_attr_e('Not available because "allow_url_fopen" is turned off in the php.ini', 'duplicator-pro'); ?>">
                </i>
            <?php endif; ?>
        </td>
    </tr>
</table>
