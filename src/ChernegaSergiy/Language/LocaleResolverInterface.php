<?php

declare(strict_types=1);

namespace ChernegaSergiy\Language;

use pocketmine\player\Player;

interface LocaleResolverInterface {

    public function resolve(Player $player): string;
}
