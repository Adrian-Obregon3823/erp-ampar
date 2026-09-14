<?php
if (!function_exists('inv_display_title')) {
    function inv_display_title($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($value));
    }
}
