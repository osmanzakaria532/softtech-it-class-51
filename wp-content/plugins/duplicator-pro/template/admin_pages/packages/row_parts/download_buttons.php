<?php

/**
 * @package Duplicator
 */

use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var ?DUP_PRO_Package $package
 */

$package              = $tplData['package'];
$archive_exists       = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive) != false);
$installer_exists     = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Installer) != false);
$archiveDownloadURL   = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Archive);
$installerDownloadURL = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Installer);
$pack_format          = strtolower($package->Archive->Format);

if (!CapMng::can(CapMng::CAP_EXPORT, false)) {
    return;
}

$isRunning  = DUP_PRO_Package::isPackageRunning();
$canEnabled = ($package->haveRemoteStorage() || $package->haveLocalStorage());

if ($archive_exists) : ?>
    <nav class="dup-dnload-menu">
        <button
            class="dup-dnload-btn button no-select can-enabled"
            type="button" 
            aria-haspopup="true"
            <?php disabled(!$canEnabled || $isRunning); ?>
            >
            <i class="fa fa-download"></i>&nbsp;
            <span><?php esc_html_e("Download", 'duplicator-pro'); ?></span>
        </button>

        <nav class="dup-dnload-menu-items">
            <button
                aria-label="<?php esc_html_e("Download Installer and Archive", 'duplicator-pro') ?>"
                title="<?php echo ($installer_exists ? '' : esc_html__("Unable to locate both package files!", 'duplicator-pro')); ?>"
                onclick="DupPro.Pack.DownloadFile('<?php echo esc_attr($archiveDownloadURL); ?>',
                        '<?php echo esc_attr($package->get_archive_filename()); ?>');
                        setTimeout(function () {DupPro.Pack.DownloadFile('<?php echo esc_attr($installerDownloadURL); ?>');}, 700);
                        jQuery(this).parent().hide();
                        return false;"
                class="dup-dnload-both"
                >
                    <i class="fa fa-fw <?php echo ($installer_exists ? 'fa-download' : 'fa-exclamation-triangle') ?>"></i>
                    &nbsp;<?php esc_html_e("Both Files", 'duplicator-pro') ?>
            </button>
            <button
                aria-label="<?php esc_html_e("Download Installer", 'duplicator-pro') ?>"
                title="<?php echo ($installer_exists) ? '' : esc_html__("Unable to locate installer package file!", 'duplicator-pro'); ?>"
                onclick="DupPro.Pack.DownloadFile('<?php echo esc_attr($installerDownloadURL); ?>');
                        jQuery(this).parent().hide();
                        return false;"
                class="dup-dnload-installer">
                <i class="fa fa-fw <?php echo ($installer_exists ? 'fa-bolt' : 'fa-exclamation-triangle') ?>"></i>&nbsp;
                <?php esc_html_e("Installer", 'duplicator-pro') ?>
            </button>
            <button
                aria-label="<?php esc_html_e("Download Archive", 'duplicator-pro') ?>"
                onclick="DupPro.Pack.DownloadFile('<?php echo esc_attr($archiveDownloadURL); ?>',
                        '<?php echo esc_attr($package->get_archive_filename()); ?>');
                        jQuery(this).parent().hide();
                        return false;"
                        
                class="dup-dnload-archive">
                    <i class="fa-fw far fa-file-archive"></i>&nbsp;
                    <?php echo esc_html__('Archive', 'duplicator-pro') . ' (' . esc_html($pack_format) . ')'; ?>
            </button>
        </nav>
    </nav>
<?php else :
    ?>
    <button 
        type="button" 
        class="button button-link dup-remote-download <?php echo ($canEnabled ? 'can-enabled' : ''); ?>"
        data-package-id="<?php echo (int) $package->ID; ?>"
        data-needs-download="<?php echo $package->haveLocalStorage() ? "false" : "true"; ?>"
        aria-label="<?php esc_attr_e("Download Backup", 'duplicator-pro') ?>"
        title="<?php esc_attr_e("Download Backup.", 'duplicator-pro') ?>"
        <?php disabled(!$canEnabled || $isRunning); ?>
    >
        <i class="fas fa-download fa-fw"></i> <?php esc_html_e("Download", 'duplicator-pro'); ?>
    </button>
<?php endif; ?>
