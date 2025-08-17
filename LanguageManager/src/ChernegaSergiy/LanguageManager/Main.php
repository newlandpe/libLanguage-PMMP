<?php

declare(strict_types=1);

namespace ChernegaSergiy\LanguageManager;

use ChernegaSergiy\Language\Language;
use ChernegaSergiy\Language\LanguageAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase {

    private static ?self $instance = null;

    private LanguageAPI $languageAPI;
    private Config $playerLanguageConfig;

    public function onEnable(): void {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->languageAPI = new LanguageAPI();
        $this->saveDefaultConfig();

        $this->playerLanguageConfig = new Config($this->getDataFolder() . "player_languages.yml", Config::YAML, []);

        $this->loadLanguages();

        $defaultLocale = $this->getConfig()->get("default-language", "en_US");
        $defaultLang = $this->languageAPI->getLanguageByLocale($defaultLocale);
        if ($defaultLang !== null) {
            $this->languageAPI->setDefaultLanguage($defaultLang);
        } else {
            $this->getLogger()->warning("Default language '{$defaultLocale}' not found. Using first registered language as default.");
            $allLanguages = $this->languageAPI->getLanguages();
            if (!empty($allLanguages)) {
                $this->languageAPI->setDefaultLanguage(reset($allLanguages));
            }
        }

        $this->getLogger()->info($this->languageAPI->localize(null, "plugin_enabled"));
    }

    private function loadLanguages(): void {
        $languageDir = $this->getDataFolder() . 'languages/';
        if (!is_dir($languageDir)) {
            mkdir($languageDir, 0777, true);
        }

        $this->saveResource("languages/en_US.yml");
        $enTranslations = (new Config($languageDir . "en_US.yml", Config::YAML))->getAll();
        $english = new Language("en_US", $enTranslations);
        $this->languageAPI->registerLanguage($english);

        $this->saveResource("languages/uk_UA.yml");
        $ukTranslations = (new Config($languageDir . "uk_UA.yml", Config::YAML))->getAll();
        $ukrainian = new Language("uk_UA", $ukTranslations);
        $this->languageAPI->registerLanguage($ukrainian);
    }

    private function getPlayerLanguage(Player $player): string {
        return $this->playerLanguageConfig->get($player->getName(), "en_US");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch ($command->getName()) {
            case "setlang":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . $this->languageAPI->localize($sender, "command.player_only"));
                    return true;
                }
                if (count($args) < 1) {
                    $sender->sendMessage(TF::RED . $this->languageAPI->localize($sender, "command.setlang.usage"));
                    return true;
                }
                $newLocale = $args[0];
                if ($this->languageAPI->getLanguageByLocale($newLocale) === null) {
                    $sender->sendMessage(TF::RED . $this->languageAPI->localize($sender, "command.setlang.invalid_locale", ["%locale%" => $newLocale]));
                    return true;
                }
                $this->playerLanguageConfig->set($sender->getName(), $newLocale);
                $this->playerLanguageConfig->save();
                $sender->sendMessage(TF::GREEN . $this->languageAPI->localize($sender, "command.setlang.success", ["%locale%" => $newLocale]));
                return true;

            case "mylang":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . $this->languageAPI->localize($sender, "command.player_only"));
                    return true;
                }
                $locale = $this->getPlayerLanguage($sender);
                $sender->sendMessage(TF::GREEN . $this->languageAPI->localize($sender, "command.mylang.current", ["%locale%" => $locale]));
                return true;

            case "listlangs":
                $sender->sendMessage(TF::GREEN . $this->languageAPI->localize($sender, "command.listlangs.header"));
                foreach ($this->languageAPI->getLanguages() as $locale => $langObject) {
                    $sender->sendMessage(TF::GREEN . $locale . ' - ' . $langObject->getTranslation("language.name"));
                }
                return true;

            default:
                return false;
        }
    }

    public static function getLanguageAPI(): LanguageAPI {
        if (self::$instance === null) {
            throw new \RuntimeException("LanguageManager plugin is not enabled yet.");
        }
        return self::$instance->languageAPI;
    }
}
