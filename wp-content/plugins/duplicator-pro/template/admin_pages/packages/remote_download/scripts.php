<?php

/**
 * Duplicator Pro remote download scripts
 *
 * @package   Duplicator
 * @copyright (c) 2024, Snap Creek LLC
 */

use Duplicator\Controllers\PackagesPageController;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

$afterDownloadAction      = isset($tplData['afterDownloadAction']) ? $tplData['afterDownloadAction'] : '';
$remoteDownloadPackageId  = isset($tplData['remoteDownloadPackageId']) ? (int) $tplData['remoteDownloadPackageId'] : -1;
$downloadStarted          = $remoteDownloadPackageId > 0;
$afterDownloadActionNonce = strlen($afterDownloadAction) > 0 ? PackagesPageController::getInstance()->getActionByKey($afterDownloadAction)->getNonce() : '';

$alreadyDownloadDlg              = new DUP_PRO_UI_Dialog();
$alreadyDownloadDlg->width       = 550;
$alreadyDownloadDlg->height      = 200;
$alreadyDownloadDlg->showButtons = true;
$alreadyDownloadDlg->title       = __('Backup is already being downloaded', 'duplicator-pro');
$alreadyDownloadDlg->message     = __('The backup is currently being downloaded. Please wait for the download to finish.', 'duplicator-pro');
$alreadyDownloadDlg->boxClass    = 'duplication-remote-download-options-dlg';
$alreadyDownloadDlg->initAlert();

$remoteDownloadOptionsDlg              = new DUP_PRO_UI_Dialog();
$remoteDownloadOptionsDlg->width       = 750;
$remoteDownloadOptionsDlg->height      = 395;
$remoteDownloadOptionsDlg->showButtons = false;
$remoteDownloadOptionsDlg->title       = __('Download From Remote Storage', 'duplicator-pro');
$remoteDownloadOptionsDlg->message     = __('Loading Please Wait...', 'duplicator-pro');
$remoteDownloadOptionsDlg->boxClass    = 'duplicatior-remote-download-options-dlg';
$remoteDownloadOptionsDlg->initAlert();

$downloadProgressDlg               = new DUP_PRO_UI_Dialog();
$downloadProgressDlg->height       = 475;
$downloadProgressDlg->width        = 750;
$downloadProgressDlg->showButtons  = false;
$downloadProgressDlg->title        = __('Downloading Backup...', 'duplicator-pro');
$downloadProgressDlg->templatePath = 'admin_pages/packages/remote_download/download_progress';
$downloadProgressDlg->boxClass     = 'duplicator-pro-download-progress-dlg';
$downloadProgressDlg->initAlert();
?>

<script>
jQuery(document).ready(function ($) {
    let remoteDownloadModal       = $('.<?php echo esc_html($remoteDownloadOptionsDlg->boxClass); ?>');
    let remoteDownloadInProgress  = <?php echo wp_json_encode($downloadStarted); ?>;
    let remoteDownloadModalOpen   = false;

    if (remoteDownloadInProgress) {
        setTimeout(function() {
            <?php $downloadProgressDlg->showAlert(); ?>
            remoteDownloadModalOpen = true;
        }, 500);
    }

    $(document).on('thickbox:removed', function() {
        if (!remoteDownloadInProgress && !remoteDownloadModalOpen) {
            return;
        }

        remoteDownloadModalOpen = false;
    });

    DupPro.Pack.IsRemoteDownloadModalOpen = function() {
        return remoteDownloadModalOpen;
    }

    DupPro.Pack.afterRemoteDownloadAction = function() {
        let packageId = <?php echo wp_json_encode($remoteDownloadPackageId); ?>;
        let action    = <?php echo wp_json_encode($afterDownloadAction); ?>;
        let nonceVal  = <?php echo wp_json_encode($afterDownloadActionNonce); ?>;

        if (action.length === 0 || nonceVal.length === 0 || packageId <= 0) {
            location.reload();
        } else {
            Duplicator.Util.dynamicFormSubmit('', 'post', {
                packageId: packageId,
                action: action,
                _wpnonce: nonceVal
            });
        }
    }

    /**
     * Show remote download options in modal window
     * 
     * @param {number} packageId
     * @param {string} remoteAction
     * 
     * @return {boolean}
     */
    DupPro.Pack.ShowRemoteDownloadOptions = function(
        packageId,
        remoteAction,
    ) {
        Duplicator.Util.ajaxWrapper(
            {
                action: 'duplicator_get_remote_restore_download_options',
                packageId: packageId,
                remoteAction: remoteAction,
                nonce: "<?php echo esc_js(wp_create_nonce('duplicator_get_remote_restore_download_options')); ?>"
            },
            function (result, data, funcData, textStatus, jqXHR) {
                if (funcData.alreadyInUse) {
                    <?php $alreadyDownloadDlg->showAlert(); ?>
                } else if (!funcData.packageExists) {
                    DupPro.addAdminMessage(funcData.message, 'error');
                    $("tr[data-package-id='" + packageId + "'] button[data-needs-download]").prop('disabled', true);
                    $("#dup-row-pack-id-" + packageId + " .remote-storage-flag").remove();
                } else {
                    <?php $remoteDownloadOptionsDlg->showAlert(); ?>
                    remoteDownloadModal.html(data.funcData.content);
                }
                return '';        
            },
            function(result, data, funcData, textStatus, jqXHR) {
                DupPro.addAdminMessage(data.message, 'error');
                console.log(data);
                return '';
            },
            { timeout: 300000 } //Fetching validity of multiple storages can take a while
        );
        
        return false;
    }
});
</script>
