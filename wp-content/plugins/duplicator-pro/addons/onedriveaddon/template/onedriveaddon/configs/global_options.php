<?php

/**
 * Duplicator messages sections
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Addons\OneDriveAddon\Models\OneDriveStorage;
use Duplicator\Addons\OneDriveAddon\OnedriveAdapter;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */
?>
<h3 class="title"><?php echo esc_html(OneDriveStorage::getStypeName()) ?></h3>
<hr size="1" />
<table class="form-table">
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Upload Chunk Size", 'duplicator-pro'); ?></label></th>
        <td>
            <input 
                type="number"
                name="onedrive_upload_chunksize_in_kb"
                id="onedrive_upload_chunksize_in_kb"
                class="dup-narrow-input text-right"
                step="320"
                min="<?php echo intval(DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB); ?>"
                data-parsley-required
                data-parsley-type="number"
                data-parsley-errors-container="#onedrive_upload_chunksize_in_kb_error_container"
                value="<?php echo (int) $tplData['uploadChunkSize']; ?>" 
            >&nbsp;<b>KB</b>
            <div id="onedrive_upload_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
            <p class="description">
            <?php
                printf(
                    esc_html__(
                        'How much should be uploaded to OneDrive per attempt. It should be multiple of %1$dkb. 
                        Higher=faster but less reliable. Default size %2$dkb. Min size %3$dkb.',
                        'duplicator-pro'
                    ),
                    (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB,
                    (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_DEFAULT_SIZE_IN_KB,
                    (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB
                );
                ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Download Chunk Size", 'duplicator-pro'); ?></label></th>
        <td>
            <input 
                type="number"
                name="onedrive_download_chunksize_in_kb"
                id="onedrive_download_chunksize_in_kb"
                class="dup-narrow-input text-right"
                min="<?php echo (int) OneDriveStorage::MIN_DOWNLOAD_CHUNK_SIZE_IN_KB; ?>"
                data-parsley-required
                data-parsley-type="number"
                data-parsley-errors-container="#onedrive_download_chunksize_in_kb_error_container"
                value="<?php echo (int) $tplData['downloadChunkSize']; ?>" 
            >&nbsp;<b>KB</b>
            <div id="onedrive_download_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
            <p class="description">
                <?php esc_html_e('How much should be downloaded from OneDrive per attempt.', 'duplicator-pro');
                printf(
                    esc_html__('Default size %1$dkb. Min size %2$dkb.', 'duplicator-pro'),
                    (int) OneDriveStorage::DEFAULT_DOWNLOAD_CHUNK_SIZE_IN_KB,
                    (int) OneDriveStorage::MIN_DOWNLOAD_CHUNK_SIZE_IN_KB
                ); ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("HTTP Version", 'duplicator-pro'); ?></label></th>
        <td>
            <input
                type="radio"
                value="<?php echo (int) OnedriveAdapter::HTTP_VERSION_AUTO ?>"
                name="onedrive_http_version" id="onedrive_http_version_auto"
                <?php checked($tplData['httpVersion'], OnedriveAdapter::HTTP_VERSION_AUTO); ?>
            >
            <label for="onedrive_http_version_auto"><?php esc_html_e("Auto", 'duplicator-pro'); ?></label> &nbsp;

            <input
                type="radio"
                value="<?php echo (int) OnedriveAdapter::HTTP_VERSION_11 ?>"
                name="onedrive_http_version"
                id="onedrive_http_version_11"
                <?php checked($tplData['httpVersion'], OnedriveAdapter::HTTP_VERSION_11); ?>
            >
            <label for="onedrive_http_version_11"><?php esc_html_e("HTTP 1.1", 'duplicator-pro'); ?></label> &nbsp;

            <input
                type="radio"
                value="<?php echo (int) OnedriveAdapter::HTTP_VERSION_20 ?>"
                name="onedrive_http_version"
                id="onedrive_http_version_20"
                <?php checked($tplData['httpVersion'], OnedriveAdapter::HTTP_VERSION_20); ?>
            >
            <label for="onedrive_http_version_20"><?php esc_html_e("HTTP 2.0", 'duplicator-pro'); ?></label>&nbsp;
        </td>
    </tr>
</table>
