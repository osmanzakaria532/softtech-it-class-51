<?php

/**
 * @package Duplicator
 */

use Duplicator\Core\CapMng;
use Duplicator\Views\ViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var ?DUP_PRO_Package $package
 */

$package = $tplData['package'];

if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
    return;
}

$isRunning  = DUP_PRO_Package::isPackageRunning();
$canEnabled = ($package->haveRemoteStorage() || $package->haveLocalStorage());
?>
<button 
    type="button" 
    class="button button-link dup-restore-backup <?php echo ($canEnabled ? 'can-enabled' : ''); ?>"
    data-package-id="<?php echo (int) $package->ID; ?>"
    data-needs-download="<?php echo $package->haveLocalStorage() ? "false" : "true"; ?>"
    aria-label="<?php esc_attr_e("Restore backup", 'duplicator-pro') ?>"
    title="<?php esc_attr_e("Restore backup.", 'duplicator-pro') ?>"
    <?php disabled(!$canEnabled || $isRunning); ?>
>
    <?php ViewHelper::restoreIcon(); ?> <?php esc_html_e("Restore", 'duplicator-pro'); ?>
</button>
