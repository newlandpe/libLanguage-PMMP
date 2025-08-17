<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use ChernegaSergiy\Language\exception\LanguageAlreadyRegisteredException;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Translator {

    private const VALID_LOCALES = [
        "en_US" => "English (United States)",
        "en_GB" => "English (United Kingdom)",
        "de_DE" => "Deutsch (Deutschland)",
        "es_ES" => "Español (España)",
        "es_MX" => "Español (México)",
        "fr_FR" => "Français (France)",
        "fr_CA" => "Français (Canada)",
        "it_IT" => "Italiano (Italia)",
        "ja_JP" => "日本語 (日本)",
        "ko_KR" => "한국어 (대한민국)",
        "pt_BR" => "Português (Brasil)",
        "pt_PT" => "Português (Portugal)",
        "ru_RU" => "Русский (Россия)",
        "zh_CN" => "中文(简体)",
        "zh_TW" => "中文(繁體)",
        "nl_NL" => "Nederlands (Nederland)",
        "bg_BG" => "Български (България)",
        "cs_CZ" => "Čeština (Česko)",
        "da_DK" => "Dansk (Danmark)",
        "el_GR" => "Ελληνικά (Ελλάδα)",
        "fi_FI" => "Suomi (Suomi)",
        "hu_HU" => "Magyar (Magyarország)",
        "id_ID" => "Indonesia (Indonesia)",
        "nb_NO" => "Norsk bokmål (Norge)",
        "pl_PL" => "Polski (Polska)",
        "sk_SK" => "Slovenčina (Slovensko)",
        "sv_SE" => "Svenska (Sverige)",
        "tr_TR" => "Türkçe (Türkiye)",
        "uk_UA" => "Українська (Україна)"
    ];

    /** @var Language[] */
    private $languages = [];

    /** @var Language|null */
    private $defaultLanguage = null;

    public function registerLanguage(Language $language): void {
        $locale = $language->getLocale();
        if (!isset(self::VALID_LOCALES[$locale])) {
            throw new InvalidArgumentException("Invalid locale '{$locale}' provided. See valid locales at https://github.com/ZtechNetwork/MCBVanillaResourcePack/blob/7a9c12d0e8680f3a2415bc87577cea99a73c254d/texts/languages.json.");
        }
        if (isset($this->languages[$locale])) {
            throw new LanguageAlreadyRegisteredException("Language with locale '{$locale}' is already registered.");
        }
        $this->languages[$locale] = $language;
    }

    public function setDefaultLanguage(Language $language): void {
        $this->defaultLanguage = $language;
    }

    /**
     * @param string $key
     * @param array $args
     * @param string|null $locale
     * @return string
     */
    public function translate(string $key, array $args = [], ?string $locale = null): string {
        $language = $locale ? $this->getLanguageByLocale($locale) : $this->defaultLanguage;

        if ($language === null) {
            return TextFormat::colorize($key);
        }

        $translation = $language->getTranslation($key, $args);
        return TextFormat::colorize($translation);
    }

    public function getLanguageByLocale(string $locale): ?Language {
        return $this->languages[$locale] ?? null;
    }

    public function getLanguages(): array {
        return $this->languages;
    }

    public function isLanguageRegistered(string $locale): bool {
        return isset($this->languages[$locale]);
    }

    public function getDefaultLanguage(): ?Language {
        return $this->defaultLanguage;
    }
}
