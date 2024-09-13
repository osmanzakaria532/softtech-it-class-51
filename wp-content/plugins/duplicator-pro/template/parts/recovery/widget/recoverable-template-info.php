<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Package\Recovery\RecoveryStatus;
use Duplicator\Views\ViewHelper;

/**
 * Variables
 *
 * @var array<string, mixed> $tplData
 * @var DUP_PRO_Package_Template_Entity $template
 * @var DUP_PRO_Schedule_Entity|null $schedule
 * @var bool $isList
 */

$isList   = isset($tplData['isList']) ? $tplData['isList'] : false;
$template = isset($tplData['template']) ? $tplData['template'] : null;
$schedule = isset($tplData['schedule']) ? $tplData['schedule'] : null;

if (isset($schedule)) {
    $recoveryStatus = new RecoveryStatus($schedule);
} else {
    $recoveryStatus = new RecoveryStatus($template);
}

$isRecoveable          = $recoveryStatus->isRecoveable();
$templareRecoveryAlter = new DUP_PRO_UI_Dialog();

if (!$isRecoveable) {
    $templareRecoveryAlter->title        = (
        isset($schedule) ?
            __('Schedule: Recovery Point', 'duplicator-pro') :
            __('Template: Recovery Point', 'duplicator-pro')
        );
    $templareRecoveryAlter->width        = 600;
    $templareRecoveryAlter->height       = 600;
    $templareRecoveryAlter->showButtons  = false;
    $templareRecoveryAlter->templatePath = 'parts/recovery/widget/template-filters-info';
    $templareRecoveryAlter->templateArgs = ['recoveryStatus' => $recoveryStatus];
    $templareRecoveryAlter->initAlert();
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('#dup-template-recoveable-info-<?php echo (int) $templareRecoveryAlter->getUniqueIdCounter(); ?>').click(function () {
    <?php $templareRecoveryAlter->showAlert(); ?>
            });
        });
    </script>
    <?php
}
?>
<span class="dup-template-recoveable-info-wrapper" >
    <?php
    if ($isRecoveable) {
        ?>
            <?php esc_html_e('Available', 'duplicator-pro'); ?>
            <sup><?php ViewHelper::disasterIcon(); ?></sup>
        <?php
    } else {
        ?>
        <a href="javascript:void(0)"
            id="dup-template-recoveable-info-<?php echo (int) $templareRecoveryAlter->getUniqueIdCounter(); ?>" 
            class="dup-template-recoveable-info"><u><?php esc_html_e('Disabled', 'duplicator-pro'); ?></u></a>
        <?php
    }

    if (!$isList) {
        ?>
        <sup>
        <i class="fas fa-question-circle fa-sm"
            data-tooltip-title="<?php esc_attr_e("Recovery Status", 'duplicator-pro'); ?>" 
            data-tooltip="<?php
            if (!isset($schedule)) {
                esc_html_e(
                    "The Recovery Status can be either 'Available' or 'Disabled'. 
                    An 'Available' status allows the templates archive to be restored through the recovery point wizard. 
                    A 'Disabled' status means the archive can still be used but just not ran as a rapid recovery point.",
                    'duplicator-pro'
                );
            } else {
                esc_html_e(
                    "The Recovery Status can be either 'Available' or 'Disabled'. 
                    An 'Available' status allows the schedules archive to be restored through the recovery point wizard. 
                    A 'Disabled' status means the archive can still be used but just not ran as a rapid recovery point.",
                    'duplicator-pro'
                );
            }
            ?>"
        ></i>
        </sup>
    <?php } ?>
</span>
