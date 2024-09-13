<?php

/**
 * @package Duplicator
 */

use Duplicator\Package\NameFormat;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

$nameFormat = $tplData['nameFormat'];
$notes      = $tplData['notes'];

$helpContent = $tplMng->render('admin_pages/packages/setup/name-format-help', [], false);
?>

<div>
    <label for="package-name-format" class="lbl-larger">
        <?php esc_html_e('Backup Name Format', 'duplicator-pro') ?>:
    </label>&nbsp;
    <i 
        class="fas fa-question-circle fa-sm"
        data-tooltip-title="<?php esc_attr_e("Backup name format", 'duplicator-pro'); ?>"
        data-tooltip="<?php echo esc_attr($helpContent); ?>" 
        data-tooltip-width="400"
    ></i>

    <div class="dup-notes-add">
        <button 
            type="button" 
            onClick="jQuery('#dup-notes-area').toggle()" 
            class="dup-btn-borderless"  
            title="<?php esc_attr_e('Add Notes', 'duplicator-pro') ?>"
        >
            <i class="far fa-edit"></i>
        </button>
    </div>
</div>

<div>
    <div style="display: flex;" >
        <input 
            type="text" 
            id="package-name-format" 
            name="package_name_format" 
            data-parsley-errors-container="#template_package_name_error_container"
            data-parsley-required="true" 
            value="<?php echo esc_attr($nameFormat); ?>" 
            autocomplete="off"
        >
        <select class="dup-format-name-tags width-medium margin-left-1 secondary-color secondary-border-color" >
            <option value="" selected >
                <?php esc_html_e('Dynamic Tags', 'duplicator-pro') ?>
            </option>
            <?php foreach (NameFormat::FORMATS as $format) { ?>
                <option value="%<?php echo esc_attr($format); ?>%">
                    %<?php echo esc_html($format); ?>%
                </option>
            <?php } ?>
        </select>
    </div>
    <div id="template_package_name_error_container" class="duplicator-error-container"></div>

    <div id="dup-notes-area">
        <label class="lbl-larger"><?php esc_html_e('Notes', 'duplicator-pro') ?>:</label><br/>
        <textarea id="package-notes" name="package-notes" maxlength="300" ><?php echo esc_html($notes); ?></textarea>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('.dup-format-name-tags').change(function(e) {
            e.stopPropagation();

            if ($(this).val() === '') {
                return;
            }

            let input = $('#package-name-format');
            let currentValue = input.val();
            let newValue = currentValue + $(this).val();
            input.val(newValue);

            $(this).val('');
        });        
    })
</script>