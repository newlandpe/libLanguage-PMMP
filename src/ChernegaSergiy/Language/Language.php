<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

class Language {

    /** @var string */
    private $locale;

    /** @var array */
    private $translations;

    public function __construct(string $locale, array $translations) {
        $this->locale = $locale;
        $this->translations = $translations;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getTranslation(string $key, array $args = []): string {
        $translation = $this->translations[$key] ?? $key; // Return key if translation not found

        foreach ($args as $placeholder => $value) {
            $translation = str_replace($placeholder, (string)$value, $translation);
        }

        return $translation;
    }
}
