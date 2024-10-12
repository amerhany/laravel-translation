<?php

namespace Amir\TranslationService\Helpers;

class TranslationHelper
{
    /**
     * Translates a string using Google Translate.
     *
     * @param string $q - The string to translate.
     * @param string $sl - Source language.
     * @param string $tl - Target language.
     * @return string
     */
    public static function translateAPI($q, $sl, $tl)
    {
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=t&sl=" . $sl . "&tl=" . $tl . "&q=" . urlencode($q);
        $res = file_get_contents($url);
        $decodedResponse = json_decode($res);

        if (isset($decodedResponse[0])) {
            $translatedTexts = [];
            foreach ($decodedResponse[0] as $translation) {
                if (isset($translation[0])) {
                    $translatedTexts[] = $translation[0];
                }
            }
            return implode(' ', $translatedTexts);
        }

        return '';
    }

    /**
     * Removes invalid characters from a string.
     *
     * @param string $string
     * @return string
     */
    public static function removeInvalidCharacters($string)
    {
        // Replace or filter invalid characters as needed
        return str_ireplace(['\'', '"', ';', '<', '>'], ' ', $string);
    }

}
