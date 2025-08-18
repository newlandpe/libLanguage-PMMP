<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use pocketmine\player\Player;

class DefaultLocaleResolver implements LocaleResolverInterface {

    public function resolve(Player $player): string {
        return $player->getLocale();
    }
}