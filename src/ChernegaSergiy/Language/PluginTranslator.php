<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PluginTranslator implements TranslatorInterface {

    private array $translations;
    private LocaleResolverInterface $localeResolver;
    private string $defaultLocale;

    public function __construct(PluginBase $plugin, array $languages, LocaleResolverInterface $localeResolver, string $defaultLocale = "en_US") {
        $this->localeResolver = $localeResolver;
        $this->defaultLocale = $defaultLocale;

        $this->translations = [];
        foreach ($languages as $language) {
            if (!$language instanceof Language) {
                throw new InvalidArgumentException("Expected an array of Language objects.");
            }
            $this->translations[$language->getLocale()] = $language->getTranslations();
            LanguageHub::getInstance()->registerLocale($plugin->getName(), $language);
        }
    }

    public function translateFor(?CommandSender $sender, string $key, array $args = []): string {
        $locale = $sender instanceof Player ? $this->localeResolver->resolve($sender) : $this->defaultLocale;
        return $this->translate($locale, $key, $args, $sender);
    }

    public function translate(string $locale, string $key, array $args = [], ?CommandSender $sender = null): string {
        $translation = $this->translations[$locale][$key] ?? $this->translations[$this->defaultLocale][$key] ?? $key;

        // Process internal placeholders
        foreach ($args as $placeholder => $value) {
            $translation = str_replace('%' . $placeholder . '%', (string)$value, $translation);
        }

        // Process PlaceholderAPI placeholders if possible
        if ($sender instanceof Player) {
            /** @var \MohamadRZ4\Placeholder\PlaceholderAPI|null $placeholderApi */
            $placeholderApi = \pocketmine\Server::getInstance()->getPluginManager()->getPlugin("PlaceholderAPI");
            if ($placeholderApi !== null && $placeholderApi->isEnabled()) {
                $translation = $placeholderApi->parsePlaceholders($translation, $sender);
            }
        }

        return $translation;
    }
}
