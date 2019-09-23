<?php

namespace rcb\helpers;

class ArrayHelper
{

    /**
     * @param string $pgArray
     * @return array
     */
    public static function decodePgArray(string $pgArray): array
    {
        if ($pgArray === '{}') {
            return [];
        }
        $cols = str_getcsv(trim($pgArray, '{}'));
        foreach ($cols as $index => $col) {
            if (strpos($col, '(') === false) {
                continue;
            }
            $cols[$index] = str_getcsv(str_replace('\\', '', trim($col, '()')));
        }
        return $cols;
    }

    /**
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function filterKeys(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * @param array $array
     * @return array
     */
    public static function filterEmpty(array $array): array
    {
        return array_filter($array, function ($value) {
            return $value !== null && $value !== '';
        });
    }

}
