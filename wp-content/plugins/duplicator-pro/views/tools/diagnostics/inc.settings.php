<?php

/**
 * Standard: PSR-2 (almost)
 *
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package    DUP_PRO
 * @subpackage classes/package
 * @copyright  (c) 2019, Snapcreek LLC
 * @license    https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since      1.0.0
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Core\Views\TplMng;

$view_state       = DUP_PRO_UI_ViewState::getArray();
$ui_css_srv_panel = (isset($view_state['dup-settings-diag-srv-panel']) && $view_state['dup-settings-diag-srv-panel']) ? 'display:block' : 'display:none';
?>
<!-- ==============================
SERVER SETTINGS -->
<div class="dup-box">
    <div class="dup-box-title">
        <i class="fas fa-tachometer-alt"></i>
        <?php esc_html_e("Server Settings", 'duplicator-pro') ?>
        <button class="dup-box-arrow">
            <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Server Settings', 'duplicator-pro') ?></span>
        </button>
    </div>
    <div class="dup-box-panel" id="dup-settings-diag-srv-panel" style="<?php echo esc_attr($ui_css_srv_panel); ?>">
        <?php TplMng::getInstance()->render(
            'parts/tools/server_settings_table',
            [
                'serverSettings' => DUP_PRO_Server::getServerSettingsData(),
            ]
        ); ?>
    </div>
</div>
<br/>
