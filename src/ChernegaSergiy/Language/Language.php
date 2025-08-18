<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

class Language {

    private string $locale;
    private array $translations;

    public function __construct(string $locale, array $translations) {
        $this->locale = $locale;
        $this->translations = $translations;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getTranslations(): array {
        return $this->translations;
    }
}
