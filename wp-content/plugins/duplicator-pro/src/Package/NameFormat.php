<?php

namespace Duplicator\Package;

use DUP_PRO_Package_Template_Entity;
use DUP_PRO_Schedule_Entity;

class NameFormat
{
    const FORMATS = [
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
        'domain',
        'sitetitle',
        'templatename',
        'schedulename',
    ];

    const FILTER_CHARS = [
        '.',
        '%',
        ':',
        '-',
        ' ',
    ];

    const DEFAULT_FORMAT = '%year%%month%%day%_%sitetitle%';

    /**
     * Get the name from format
     *
     * @param string $format      The format
     * @param int    $scheduleId  The schedule id or 0 if not exists
     * @param int    $temaplateId The template id or 0 if not exists
     *
     * @return string
     */
    public static function getNameFromFormat($format, $scheduleId = 0, $temaplateId = 0)
    {
        $parsed = strlen($format) == 0 ? self::DEFAULT_FORMAT : $format;

        if (strpos($parsed, '%year%') !== false) {
            $parsed = str_replace('%year%', date('Y'), $parsed);
        }

        if (strpos($parsed, '%month%') !== false) {
            $parsed = str_replace('%month%', date('m'), $parsed);
        }

        if (strpos($parsed, '%day%') !== false) {
            $parsed = str_replace('%day%', date('d'), $parsed);
        }

        if (strpos($parsed, '%hour%') !== false) {
            $parsed = str_replace('%hour%', date('H'), $parsed);
        }

        if (strpos($parsed, '%minute%') !== false) {
            $parsed = str_replace('%minute%', date('i'), $parsed);
        }

        if (strpos($parsed, '%second%') !== false) {
            $parsed = str_replace('%second%', date('s'), $parsed);
        }

        if (strpos($parsed, '%domain%') !== false) {
            $siteUrl = get_site_url();
            $parsed  = str_replace('%domain%', parse_url($siteUrl, PHP_URL_HOST), $parsed);
        }

        if (strpos($parsed, '%sitetitle%') !== false) {
            $title  = sanitize_title(get_bloginfo('name'));
            $title  = substr(sanitize_file_name($title), 0, 40);
            $parsed = str_replace('%sitetitle%', $title, $parsed);
        }

        if (strpos($parsed, '%templatename%') !== false) {
            $templateName = '';
            if ($temaplateId > 0) {
                $template = DUP_PRO_Package_Template_Entity::getById($temaplateId);
                if ($template !== false) {
                    $templateName = $template->name;
                }
            }
            $parsed = str_replace('%templatename%', $templateName, $parsed);
        }

        if (strpos($parsed, '%schedulename%') !== false) {
            $scheduleName = '';
            if ($scheduleId > 0) {
                $schedule = DUP_PRO_Schedule_Entity::getById($scheduleId);
                if ($schedule !== false) {
                    $scheduleName = $schedule->name;
                }
            }
            $parsed = str_replace('%schedulename%', $scheduleName, $parsed);
        }

        $parsed = str_replace(self::FILTER_CHARS, '', $parsed);

        return sanitize_file_name($parsed);
    }

    /**
     * Get tags description
     *
     * @return array<string, string>
     */
    public static function getTagsDescriptions()
    {
        return [
            'year'         => __('Current Year', 'duplicator-pro'),
            'month'        => __('Current Month', 'duplicator-pro'),
            'day'          => __('Current Day', 'duplicator-pro'),
            'hour'         => __('Current Hour', 'duplicator-pro'),
            'minute'       => __('Current Minute', 'duplicator-pro'),
            'second'       => __('Current Second', 'duplicator-pro'),
            'domain'       => __('Current Domain', 'duplicator-pro'),
            'sitetitle'    => __('Current Site Title', 'duplicator-pro'),
            'templatename' => __('Current Template Name', 'duplicator-pro'),
            'schedulename' => __('Current Schedule Name', 'duplicator-pro'),
        ];
    }
}
