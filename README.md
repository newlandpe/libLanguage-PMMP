# libLanguage Virion

[![Poggit CI](https://poggit.pmmp.io/ci.shield/newlandpe/libLanguage/libLanguage)](https://poggit.pmmp.io/ci/newlandpe/libLanguage/libLanguage)

`libLanguage` is a powerful and flexible language abstraction library designed for PocketMine-MP plugins. It provides a convenient way to manage multiple language translations within your plugin, allowing for easy localization of messages, commands, and other text-based content.

## Installation

### Using Poggit-CI (Recommended)

To include `libLanguage` in your plugin, add it as a virion dependency in your `poggit.yml` file:

```yaml
# .poggit.yml
--- # Poggit-CI Manifest.yml
build-by-default: true
branches:
- main
projects:
  MyPlugin:
    path: ""
    type: "plugin"
    libs:
      - src: newlandpe/libLanguage/libLanguage
        version: ^0.0.1 # Use the latest version or a specific one
```

Replace `MyPlugin` with your plugin's project name.

### Manual Inclusion

You can also manually include the virion by downloading the `.phar` file from Poggit and placing it in your plugin's `libs` folder, then adding it to your `plugin.yml`'s `depend` or `softdepend` section. However, using Poggit-CI is highly recommended for easier updates and dependency management.

## Basic Usage

The core of `libLanguage` is the `LanguageAPI` class, which manages `Translator` and `Language` instances.

### 1. Initialize LanguageAPI

You should initialize `LanguageAPI` in your plugin's `onEnable()` method. Each instance of `LanguageAPI` manages its own set of languages, providing per-plugin isolation for translations.

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\Language;
use ChernegaSergiy\Language\LanguageAPI;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MyPlugin extends PluginBase {

    private LanguageAPI $languageAPI;

    public function onEnable(): void {
        $this->languageAPI = new LanguageAPI(); // This creates an isolated instance for your plugin
        $this->saveDefaultConfig(); // Ensure config.yml exists

        // Load languages
        $this->loadLanguages();

        // Set default language
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
    }

    private function loadLanguages(): void {
        $languageDir = $this->getDataFolder() . 'languages/';
        if (!is_dir($languageDir)) {
            mkdir($languageDir, 0777, true);
        }

        // Example: Load English language
        $this->saveResource("languages/en_US.yml");
        $enTranslations = (new Config($languageDir . "en_US.yml", Config::YAML))->getAll();
        $english = new Language("en_US", $enTranslations);
        $this->languageAPI->registerLanguage($english);

        // Example: Load Ukrainian language
        $this->saveResource("languages/uk_UA.yml");
        $ukTranslations = (new Config($languageDir . "uk_UA.yml", Config::YAML))->getAll();
        $ukrainian = new Language("uk_UA", $ukTranslations);
        $this->languageAPI->registerLanguage($ukrainian);

        // You can add more languages here
    }

    public function getLanguageAPI(): LanguageAPI {
        return $this->languageAPI;
    }
}
```

### 2. Translate Messages

To translate messages, you'll use the `localize` method from `LanguageAPI`.

```php
<?php

namespace MyPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class MyPlugin extends PluginBase {
    // ... (onEnable and other methods from above)

    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool {
        if ($command->getName() === "mycommand") {
            // Translate a simple message for the sender
            $sender->sendMessage($this->languageAPI->localize($sender, "welcome.message"));

            // Translate a message with placeholders for the sender
            $playerName = $sender->getName();
            $sender->sendMessage($this->languageAPI->localize($sender, "greeting.player", ["%player%" => $playerName]));

            // List available languages
            $sender->sendMessage($this->languageAPI->localize($sender, "command.lang.list.header"));
            foreach ($this->languageAPI->getLanguages() as $locale => $langObject) {
                $sender->sendMessage(TF::GREEN . $locale . ' - ' . $langObject->getTranslation("language.name"));
            }
            return true;
        }
        return false;
    }
}
```

## Advanced Usage / Scenarios

### Managing Player Languages

You'll need to implement logic within your plugin to store and retrieve each player's preferred language. A common approach is to use a `Config` file per player or a single `Config` file mapping player names to locales.

**Example `player_languages.yml` (managed by your plugin):**

```yaml
Player1: en_US
Player2: uk_UA
```

**Loading Player Language (Helper Method Example):**

```php
<?php

namespace MyPlugin;

use pocketmine\player\Player;
use pocketmine\utils\Config;

// ... other use statements

class MyPlugin extends PluginBase {
    // ... existing properties and methods

    /**
     * Retrieves a player's stored language locale.
     * If no language is stored, it defaults to "en_US".
     * @param Player $player
     * @return string The player's locale.
     */
    private function getPlayerLanguage(Player $player): string {
        $playerDataPath = $this->getDataFolder() . "player_languages.yml";
        $config = new Config($playerDataPath, Config::YAML);
        return $config->get($player->getName(), "en_US"); // Default to en_US if not found
    }
}
```

**Saving Player Language (Command Example):**

```php
<?php

namespace MyPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

// ... other use statements

class MyPlugin extends PluginBase {
    // ... existing properties and methods

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "setlang") {
            if (!$sender instanceof Player) {
                $sender->sendMessage(TF::RED . "This command can only be used by players.");
                return true;
            }

            if (count($args) < 1) {
                $sender->sendMessage(TF::RED . "Usage: /setlang <locale>");
                return true;
            }

            $newLocale = $args[0];

            // Validate the locale using LanguageAPI's getLanguageByLocale
            // Note: This will return null if the language is not registered.
            // You might want to register all valid languages in onEnable for this check to work.
            if ($this->languageAPI->getLanguageByLocale($newLocale) === null) {
                $sender->sendMessage(TF::RED . "Invalid language locale: " . $newLocale . ". See valid locales at https://github.com/ZtechNetwork/MCBVanillaResourcePack/blob/7a9c12d0e8680f3a2415bc87577cea99a73c254d/texts/languages.json.");
                return true;
            }

            // Save the player's new language
            $playerDataPath = $this->getDataFolder() . "player_languages.yml";
            $config = new Config($playerDataPath, Config::YAML);
            $config->set($sender->getName(), $newLocale);
            $config->save();

            $sender->sendMessage($this->languageAPI->localize($sender, "language_set_success", ["%locale%" => $newLocale]));
            return true;
        }
        return false;
    }
}
```

> [!NOTE]
> For a complete, working example of how to manage player languages and utilize the `libLanguage` virion, you can download the `LanguageManager` plugin directly from its Poggit-CI page: [LanguageManager on Poggit-CI](https://poggit.pmmp.io/ci/newlandpe/LanguageManager/LanguageManager)

### Adding New Languages

To add a new language, simply create a new YAML file in your plugin's `languages/` folder (e.g., `es_ES.yml`) and register it using `LanguageAPI::registerLanguage()` with a `Language` instance.

**Example `languages/en_US.yml`:**

```yaml
welcome.message: "Welcome to the server!"
greeting.player: "Hello, %player%!"
command.lang.usage: "Usage: /mycommand lang <list|set <locale>>"
command.lang.list.header: "Available Languages:"
command.lang.set.success: "Your language has been set to %locale%."
command.lang.set.not_exists: "Language %locale% does not exist."
language.name: "English" # Self-descriptive name for the language
```

**Example `languages/uk_UA.yml`:**

```yaml
welcome.message: "Ласкаво просимо на сервер!"
greeting.player: "Привіт, %player%!"
command.lang.usage: "Використання: /mycommand lang <list|set <локаль>>"
command.lang.list.header: "Доступні мови:"
command.lang.set.success: "Вашу мову встановлено на %locale%."
command.lang.set.not_exists: "Мова %locale% не існує."
language.name: "Українська"
```

### Per-Plugin Language Isolation

The `libLanguage` virion is designed to support per-plugin language isolation. This means each plugin can manage its own set of translations without conflicting with other plugins using the same virion.

To achieve this, simply instantiate `LanguageAPI` within your plugin's `onEnable()` method (as shown in the "Initialize LanguageAPI" section). Each `LanguageAPI` instance will have its own `Translator` and manage its own language data independently.

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\Language;
use ChernegaSergiy\Language\LanguageAPI;
use pocketmine\plugin\PluginBase;

class MyPlugin extends PluginBase {

    private LanguageAPI $myPluginLanguageAPI; // Unique instance for this plugin

    public function onEnable(): void {
        $this->myPluginLanguageAPI = new LanguageAPI(); // Creates an isolated language manager
        // ... register languages and set default for this plugin
    }

    // ... other plugin methods using $this->myPluginLanguageAPI
}
```

## API Reference (Key Methods)

### `ChernegaSergiy\Language\LanguageAPI`

* `__construct()`: Initializes the API (no parameters).
* `registerLanguage(Language $language)`: Registers a `Language` instance.
* `setDefaultLanguage(Language $language)`: Sets the default language.
* `getTranslator()`: Returns the `Translator` instance.
* `localize(CommandSender $sender, string $key, array $args = [])`: Localizes a message for a given `CommandSender`.
* `resolveLocale(CommandSender $sender)`: Resolves the language locale for a given `CommandSender`.
* `getLanguageByLocale(string $locale)`: Retrieves a `Language` by its locale string.
* `getLanguages()`: Returns an array of all registered `Language` instances.

### `ChernegaSergiy\Language\Translator`

* `translate(string $key, array $args = [], ?string $locale = null)`: Translates a message key.
  * `$key`: The translation key (e.g., "welcome.message").
  * `$args`: An associative array of placeholders and their values (e.g., `["%player%" => "Steve"]`).
  * `$locale`: Optional. The locale to translate to. If `null`, uses the default language.
* `getLanguageByLocale(string $locale)`: Retrieves a `Language` by its locale string.
* `getLanguages()`: Returns an array of all registered `Language` instances.
* `isLanguageRegistered(string $locale)`: Checks if a language with the given locale is already registered.
* `getDefaultLanguage()`: Returns the default `Language` instance, or `null` if no default language is set.

### `ChernegaSergiy\Language\Language`

* `__construct(string $locale, array $translations)`: Creates a new language instance.
* `getLocale()`: Returns the locale string (e.g., "en_US").
* `getTranslation(string $key, array $args = [])`: Retrieves a translation for a key, with optional placeholders.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
