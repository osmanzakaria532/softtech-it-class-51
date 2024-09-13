<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$tooltipContent = $tplMng->render('admin_pages/packages/packages_table_head_status_icons', [], false);
?>
<h2 class="screen-reader-text"><?php esc_html_e('Packages list', 'duplicator-pro') ?></h2>
<thead>
    <tr>
        <th class="dup-check-column" style="width:10px;">
            <input 
                type="checkbox" 
                id="dup-chk-all" 
                title="<?php esc_attr_e("Select all packages", 'duplicator-pro') ?>" 
                style="margin-left:15px" onclick="DupPro.Pack.SetDeleteAll()" />
        </th>
        <th class="dup-name-column" >
            <?php esc_html_e("Backup Name", 'duplicator-pro') ?>
        </th>
        <th class="dup-note-column">
            <?php esc_html_e("Note", 'duplicator-pro') ?>
        </th>
        <th class="dup-storages-column">
            <?php esc_html_e("Storages", 'duplicator-pro') ?>
        </th>
        <th class="dup-flags-column">
            <?php esc_html_e("Status", 'duplicator-pro') ?>&nbsp;
            <i 
                class="fa-solid fa-circle-info"
                data-tooltip-title="<?php esc_attr_e("Status Icons", 'duplicator-pro'); ?>"
                data-tooltip="<?php echo esc_attr($tooltipContent); ?>"
            ></i>
        </th>
        <th class="dup-created-column">
            <?php esc_html_e("Created", 'duplicator-pro') ?>
        </th>
        <th class="dup-age-column">
            <?php esc_html_e("Age", 'duplicator-pro') ?>
        </th>
        <th class="dup-size-column">
            <?php esc_html_e("Size", 'duplicator-pro') ?>
        </th>
        <th class="dup-download-column" style="width:75px;"></th>
        <th class="dup-restore-column" style="width:25px;"></th>
        <th id="dup-header-chkall" class="dup-details-column" >
        <?php if ($tplData['totalElements'] > 0) { ?>
                <a href="javascript:void(0)" class="button button-link"><i class="fas fa-chevron-left"></i></a>
        <?php } ?>
        </th>
    </tr>
</thead>
