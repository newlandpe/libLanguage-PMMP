<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class LanguageAPI {

    /** @var Translator */
    private $translator;

    public function __construct() {
        $this->translator = new Translator();
    }

    public function registerLanguage(Language $language): void {
        $this->translator->registerLanguage($language);
    }

    public function setDefaultLanguage(Language $language): void {
        $this->translator->setDefaultLanguage($language);
    }

    public function getTranslator(): Translator {
        return $this->translator;
    }

    /**
     * @param CommandSender|null $sender
     * @return string
     */
    public function resolveLocale(?CommandSender $sender): string {
        if ($sender === null) {
            $defaultLang = $this->translator->getDefaultLanguage();
            if ($defaultLang !== null) {
                return $defaultLang->getLocale();
            }
            return "en_US";
        }
        if ($sender instanceof Player) {
            return $sender->getLocale();
        }
        $defaultLang = $this->translator->getDefaultLanguage();
        if ($defaultLang !== null) {
            return $defaultLang->getLocale();
        }
        return "en_US";
    }

    /**
     * @param CommandSender|null $sender
     * @param string $key
     * @param array $args
     * @return string
     */
    public function localize(?CommandSender $sender, string $key, array $args = [], ?string $locale = null): string {
        $finalLocale = $locale;
        if ($finalLocale === null) {
            $finalLocale = $this->resolveLocale($sender);
        }
        return $this->translator->translate($key, $args, $finalLocale);
    }

    public function getLanguageByLocale(string $locale): ?Language {
        return $this->translator->getLanguageByLocale($locale);
    }

    public function getLanguages(): array {
        return $this->translator->getLanguages();
    }
}
