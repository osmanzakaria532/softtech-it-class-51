<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Addons\DropboxAddon\Models\DropboxStorage;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;

/**
 * Variables
 *
 * @var ?DUP_PRO_Package $Package
 * @var bool $archive_export_onlydb
 */

$global = DUP_PRO_Global_Entity::getInstance();

global $wp_version;

$diagnosticUrl = ToolsPageController::getInstance()->getMenuLink(ToolsPageController::L2_SLUG_DISAGNOSTIC);
?>
<!-- ================================================================
SETUP
================================================================ -->
<div class="details-title">
    <i class="fas fa-tasks fa-sm fa-fw"></i> <?php esc_html_e("Setup", 'duplicator-pro'); ?>
    <div class="dup-more-details">
        <a href="<?php echo esc_url($diagnosticUrl); ?>" target="_blank" title="<?php esc_attr_e('Show Diagnostics', 'duplicator-pro'); ?>">
            <i class="fa fa-microchip"></i>
        </a>&nbsp;
        <a href="site-health.php" target="_blank" title="<?php esc_attr_e('Site Health', 'duplicator-pro'); ?>"><i class="fas fa-file-medical-alt"></i></a>
    </div>
</div>

<!-- ======================
SYSTEM SETTINGS -->
<?php TplMng::getInstance()->render(
    'admin_pages/packages/scan/scan_items/system_settings',
    ['hasDropbox' => $Package->contains_storage_type(DropboxStorage::getSType())]
); ?>

<!-- ======================
WP SETTINGS -->
<div class="scan-item">
    <?php
    if (!$archive_export_onlydb && isset($_POST['filter-on'])) {
        $file_filter_data        = array(
            'filter-dir'   => DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths'])),
            'filter-files' => DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths'])),
        );
        $_SESSION['filter_data'] = $file_filter_data;
    } else {
        if (isset($_SESSION['filter_data'])) {
            unset($_SESSION['filter_data']);
        }
    }
    //TODO Login Need to go here

    $core_dir_included   = array();
    $core_files_included = array();
    //by default fault
    $core_dir_notice  = false;
    $core_file_notice = false;

    if (!$archive_export_onlydb && isset($_POST['filter-on']) && isset($_POST['filter-paths'])) {
        //findout matched core directories
        $filter_dirs =  DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths']), true);

        // clean possible blank spaces before and after the paths
        for ($i = 0; $i < count($filter_dirs); $i++) {
            $filter_dirs[$i] = trim($filter_dirs[$i]);
            $filter_dirs[$i] = (substr($filter_dirs[$i], -1) == "/") ? substr($filter_dirs[$i], 0, strlen($filter_dirs[$i]) - 1) : $filter_dirs[$i];
        }
        $core_dir_included = array_intersect($filter_dirs, DUP_PRO_U::getWPCoreDirs());
        $core_dir_notice   = !empty($core_dir_included);


        //find out core files
        $filter_files = DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths']), true);

        // clean possible blank spaces before and after the paths
        for ($i = 0; $i < count($filter_files); $i++) {
            $filter_files[$i] = trim($filter_files[$i]);
        }
        $core_files_included = array_intersect($filter_files, DUP_PRO_U::getWPCoreFiles());
        $core_file_notice    = !empty($core_files_included);
    }
    ?>
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php esc_html_e('WordPress', 'duplicator-pro'); ?></div>
        <div id="data-srv-wp-all"></div>
    </div>
    <div class="info">
    <?php
    //VERSION CHECK
    echo '<span id="data-srv-wp-version"></span>&nbsp;<b>' . __('WordPress Version', 'duplicator-pro') . ":</b>&nbsp; {$wp_version} <br/>";
    echo '<div class="scan-system-subnote">';
    printf(
        __(
            'It is recommended to have a version of WordPress that is greater than %1$s. 
            Older version of WordPress can lead to migration issues and are a security risk.  
            If possible please update your WordPress site to the latest version.',
            'duplicator-pro'
        ),
        DUPLICATOR_PRO_SCAN_MIN_WP
    );
    echo '</div>';

    //CORE FILES
    echo '<hr size="1" /><span id="data-srv-wp-core"></span>&nbsp;<b>' . __('Core Files', 'duplicator-pro') . "</b> <br/>";

    $filter_text = "";
    if ($core_dir_notice) {
        echo '<div id="data-srv-wp-core-missing-dirs">';
        echo wp_kses(__("The core WordPress paths below will <u>not</u> be included in the archive. These paths are required for WordPress to function!", 'duplicator-pro'), array('u' => array()));
        echo "<br/>";
        foreach ($core_dir_included as $core_dir) {
            echo '&nbsp; &nbsp; <b><i class="fa fa-exclamation-circle scan-warn"></i>&nbsp;' . $core_dir . '</b><br/>';
        }
        echo '</small><br/>';
        echo '</div>';
        $filter_text = esc_html__('directories', 'duplicator-pro');
    }

    if ($core_file_notice) {
        echo '<div id="data-srv-wp-core-missing-dirs">';
        echo wp_kses(__("The core WordPress file below will <u>not</u> be included in the archive. This file is required for WordPress to function!", 'duplicator-pro'), array('u' => array()));
        echo "<br/>";
        foreach ($core_files_included as $core_file) {
            echo '&nbsp; &nbsp; <b><i class="fa fa-exclamation-circle scan-warn"></i>&nbsp;' . $core_file . '</b><br/>';
        }
        echo '</div><br/>';
        $filter_text .= (strlen($filter_text) > 0) ? esc_html__(" and file", "duplicator-pro") : esc_html__("files", "duplicator-pro");
    }

    if (strlen($filter_text) > 0) {
        echo '<div class="scan-system-subnote">';
        printf(
            __(
                'Note: Please change the %1$s filters if you wish to include the WordPress core files 
                otherwise the data will have to be manually copied to the new location for the site to function properly.',
                'duplicator-pro'
            ),
            $filter_text
        );
        echo '</div>';
    }


    if (!$core_dir_notice && !$core_file_notice) {
        echo '<div class="scan-system-subnote">';
        esc_html_e(
            "If the scanner is unable to locate the wp-config.php file in the root directory, then you will need to manually copy it to its new location. 
            This check will also look for core WordPress paths that should be included in the archive for WordPress to work correctly.",
            'duplicator-pro'
        );
        echo '</div>';
    }

    if (!is_multisite()) {
        //Normal Site
        echo '<hr size="1" /><span><div class="dup-scan-good"><i class="fa fa-check"></i></div></span>&nbsp;<b>' . __('Multisite: N/A', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('Multisite was not detected on this site. It is currently configured as a standard WordPress site.', 'duplicator-pro');
        echo "&nbsp;<i><a href='https://codex.wordpress.org/Create_A_Network' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
        echo '</div>';
    } elseif (License::can(License::CAPABILITY_MULTISITE_PLUS)) {
        //MU Gold
        echo '<hr size="1" /><span><div class="dup-scan-good"><i class="fa fa-check"></i></div></span>&nbsp;<b>' . __('Multisite: Detected', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('This license level has full access to all Multisite Plus+ features.', 'duplicator-pro');
        echo '</div>';
    } else {
        //MU Personal, Freelancer
        echo '<hr size="1" /><span><div class="dup-scan-warn"><i class="fa fa-exclamation-triangle fa-sm"></i></div></span>&nbsp;';
        echo '<b>' . __('Multisite: Detected', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            esc_html__(
                'Duplicator Pro is at the %1$s license level which allows for backups and migrations of an entire Multisite network.&nbsp;',
                'duplicator-pro'
            ),
            License::getLicenseToString()
        );
        echo '<br>';
        _e("To unlock all <b>Multisite Plus</b> features please upgrade the license before building a package.", 'duplicator-pro');
        echo '<br/>';
        echo "<a href='" . esc_url(License::getUpsellURL()) . "' target='_blank'>" . __('Upgrade Here', 'duplicator-pro') . "</a>&nbsp;|&nbsp;";
        echo "&nbsp;<a href='" . DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . "how-does-duplicator-handle-multisite-support' target='_blank'>"
               . __('Multisite Plus Feature Overview', 'duplicator-pro') . "</a>";
        echo '</div>';
    }
    ?>
    </div>
</div>

<!-- ======================
Restore only package -->
<div id="migration-status-scan-item" class="scan-item">
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php esc_html_e('Import Status', 'duplicator-pro');?></div>
        <div id="data-arc-status-migratepackage"></div>
    </div>
    <div class="info">
        <script id="hb-migrate-package-result" type="text/x-handlebars-template">
            <div class="container">
                <div class="data">
                    {{#if ARC.Status.PackageIsNotImportable}}
                        <hr>
                        <p>
                            <span class="maroon">
                            <?php esc_html_e("This package is not compatible with", 'duplicator-pro'); ?>
                                <i data-tooltip-title="<?php esc_attr_e("Drag and Drop Import", 'duplicator-pro'); ?>"
                                   data-tooltip="<?php esc_html_e('The Drag and Drop import method is a way to migrate packages. You can find it under Duplicator Pro > Import.', 'duplicator-pro'); ?>">
                                   <u><?php esc_html_e("Drag and Drop import", 'duplicator-pro'); ?></u>.&nbsp;
                                </i>
                                <?php esc_html_e("However it can still be used to perform a database migration.", 'duplicator-pro'); ?>
                            </span>

                            {{#if ARC.Status.IsDBOnly}}
                                <?php
                                esc_attr_e(
                                    "Database only packages can only be installed via the installer.php file. 
                                    The Drag and Drop interface only processes packages that have all WordPress core directories and all database tables.",
                                    'duplicator-pro'
                                );
                                ?>
                            {{else}}
                                <?php esc_attr_e("To make the package compatible with Drag and Drop import don't filter any tables or core directories.", 'duplicator-pro'); ?>
                            {{/if}}
                        </p>
                        {{#if ARC.Status.HasFilteredCoreFolders}}
                        <p>
                            <b><?php esc_attr_e("FILTERED CORE DIRS:", 'duplicator-pro'); ?></b>
                        </p>
                        <ol>
                            {{#each ARC.FilteredCoreDirs as |dir|}}
                            <li>{{dir}} </li>
                            {{/each}}
                        </ol>
                        {{/if}}
                        {{#if ARC.Status.HasFilteredSiteTables}}
                            <b><?php esc_attr_e("FILTERED SITE TABLES:", 'duplicator-pro'); ?></b>
                            <div class="dup-scan-files-migrae-status">
                                <ol>
                                    {{#each DB.FilteredTables as |table|}}
                                    <li>{{table}} </li>
                                    {{/each}}
                                </ol>
                            </div>
                        {{/if}}
                    {{else}}
                        <?php esc_html_e("The package you are about to create is compatible with Drag and Drop import.", 'duplicator-pro'); ?>
                    {{/if}}
                </div>
            </div>
        </script>
        <div id="migrate-package-result"></div>
    </div>
</div>

<script>
(function ($)
{
    //Ints the various server data responses from the scan results
    DupPro.Pack.intServerData = function(data)
    {
        $('#data-srv-php-websrv').html(DupPro.Pack.setScanStatus(data.SRV.PHP.websrv));
        $('#data-srv-php-openbase').html(DupPro.Pack.setScanStatus(data.SRV.PHP.openbase));
        $('#data-srv-php-maxtime').html(DupPro.Pack.setScanStatus(data.SRV.PHP.maxtime));
        $('#data-srv-php-minmemory').html(DupPro.Pack.setScanStatus(data.SRV.PHP.minMemory));
        $('#data-srv-php-arch64bit').html(DupPro.Pack.setScanStatus(data.SRV.PHP.arch64bit));
        $('#data-srv-php-mysqli').html(DupPro.Pack.setScanStatus(data.SRV.PHP.mysqli));
        $('#data-srv-php-openssl').html(DupPro.Pack.setScanStatus(data.SRV.PHP.openssl));
        $('#data-srv-php-allowurlfopen').html(DupPro.Pack.setScanStatus(data.SRV.PHP.allowurlfopen));
        $('#data-srv-php-curlavailable').html(DupPro.Pack.setScanStatus(data.SRV.PHP.curlavailable));
        $('#data-srv-php-version').html(DupPro.Pack.setScanStatus(data.SRV.PHP.version));
        $('#data-srv-php-all').html(DupPro.Pack.setScanStatus(data.SRV.PHP.ALL));
        //Wordpress
        $('#data-srv-wp-version').html(DupPro.Pack.setScanStatus(data.SRV.WP.version));
        $('#data-srv-wp-core').html(DupPro.Pack.setScanStatus(data.SRV.WP.core));
        $('#data-srv-wp-all').html(DupPro.Pack.setScanStatus(data.SRV.WP.ALL));
    }
})(jQuery);
</script>
