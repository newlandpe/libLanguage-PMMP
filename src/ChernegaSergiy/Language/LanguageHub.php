<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use InvalidArgumentException;
use pocketmine\plugin\PluginBase;

class LanguageHub {

    private static ?self $instance = null;

    private static array $VALID_LOCALES = [
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

    private ?LocaleResolverInterface $localeResolver = null;
    private array $knownLocales = [];
    private array $defaultLocales = [];

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function registerLocaleResolver(LocaleResolverInterface $resolver): void {
        $this->localeResolver = $resolver;
    }

    public function getLocaleResolver(): LocaleResolverInterface {
        if ($this->localeResolver === null) {
            // Provide a default resolver if no custom one has been registered.
            $this->localeResolver = new DefaultLocaleResolver();
        }
        return $this->localeResolver;
    }

    public function registerLocale(string $pluginName, Language $language): void {
        $locale = $language->getLocale();

        if (!isset(self::$VALID_LOCALES[$locale])) {
            throw new InvalidArgumentException("Invalid locale '" . $locale . "' registered. Please refer to https://github.com/ZtechNetwork/MCBVanillaResourcePack/blob/7a9c12d0e8680f3a2415bc87577cea99a73c254d/texts/languages.json for valid locales.");
        }

        if (isset($this->knownLocales[$pluginName][$locale])) {
            throw new LanguageAlreadyRegisteredException("Locale '" . $locale . "' is already registered for plugin '" . $pluginName . "'.");
        }

        $this->knownLocales[$pluginName][$locale] = $language;
    }

    public function getKnownLocales(): array {
        $allLocales = [];
        foreach ($this->knownLocales as $pluginLocales) {
            foreach ($pluginLocales as $language) {
                $allLocales[] = $language->getLocale();
            }
        }
        return array_unique($allLocales);
    }

    public function setDefaultLocale(PluginBase $plugin, string $locale) : void {
        $this->defaultLocales[$plugin->getName()] = $locale;
    }

    public function getDefaultLocale(PluginBase $plugin) : ?string {
        return $this->defaultLocales[$plugin->getName()] ?? null;
    }
}
