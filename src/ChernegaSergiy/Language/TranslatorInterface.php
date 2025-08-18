<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use pocketmine\command\CommandSender;

interface TranslatorInterface {

    public function translateFor(?CommandSender $sender, string $key, array $args = []): string;

    public function translate(string $locale, string $key, array $args = [], ?CommandSender $sender = null): string;
}
