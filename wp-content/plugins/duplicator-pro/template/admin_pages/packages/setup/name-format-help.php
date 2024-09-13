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

?>
<p>
    <?php esc_html_e(
        'It is possible to customize the name of 
        the backups using a fixed part and dynamic parts through tags. The available tags are as follows:',
        'duplicator-pro'
    ); ?>

    <ul>
        <?php foreach (NameFormat::getTagsDescriptions() as $tag => $description) : ?>
            <li>
                <strong>%<?php echo esc_html($tag); ?>%</strong> - <?php echo esc_html($description); ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <p>
        <?php esc_html_e(
            'Here are some examples of name formats:',
            'duplicator-pro'
        ); ?>
    </p>

    <ul>
        <li>
            <strong>%year%%month%%day%_%sitetitle%</strong> - 
            <?php esc_html_e('Backup name with date and site title (\'It\'s the default)', 'duplicator-pro'); ?>            
        </li>
        <li>
            <strong>%year%%month%%day%_%hour%%minute%%second%_mytext_</strong> - 
            <?php esc_html_e('Backup name with date and time and fixed text', 'duplicator-pro'); ?>
        </li>
        <li>
            <strong>%year%%month%%day%_%schedulename%</strong> - 
            <?php esc_html_e('Backup name with date and schedule name', 'duplicator-pro'); ?>
        </li>
    </ul>
</p>