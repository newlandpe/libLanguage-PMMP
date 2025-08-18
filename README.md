# libLanguage Virion

[![Poggit CI](https://poggit.pmmp.io/ci.shield/newlandpe/libLanguage/libLanguage)](https://poggit.pmmp.io/ci/newlandpe/libLanguage/libLanguage)

`libLanguage` is a powerful and flexible language abstraction library designed for PocketMine-MP plugins. It provides a convenient way to manage multiple language translations within your plugin, allowing for easy localization of messages, commands, and other text-based content.

## Installation

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

## Architecture: Scoped Translations

The core of `libLanguage` is the `LanguageAPI` class, which functions as a centralized, server-wide service (singleton). It provides a **scoped system** to ensure that translations from different plugins do not conflict.

Each plugin registers its own languages, and the API keeps them isolated. When you request a translation, the API first looks within your plugin's registered languages. If it can't find a translation there, it will fall back to a designated "global" language provider, such as the [LanguageManager](https://poggit.pmmp.io/p/LanguageManager) plugin.

This provides the best of both worlds: **no risk of translation conflicts** between plugins, and a **centralized fallback** for common messages if a language manager is installed.

## Basic Usage

### 1. Registering Your Languages

In your plugin's `onEnable()` method, you should register all the language files you want to use. You must pass your plugin's instance (`$this`) to scope the languages correctly.

## Basic Usage

`libLanguage` provides a set of interfaces and classes to manage translations in a clean, decoupled way.

### 1. Getting a Translator Instance

To translate messages, you will typically create an instance of `PluginTranslator`. This class requires your plugin's own translations and a `LocaleResolverInterface` instance (which you can get from the `LanguageHub`).

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\LanguageHub;
use ChernegaSergiy\Language\PluginTranslator;
use ChernegaSergiy\Language\TranslatorInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MyPlugin extends PluginBase {

    private TranslatorInterface $translator;

    public function onEnable(): void {
        // 1. Load your plugin's translations
        $this->saveResource("languages/en_US.yml");
        $this->saveResource("languages/uk_UA.yml");

        $translations = [];
        $languageDir = $this->getDataFolder() . 'languages/';
        $languageFiles = glob($languageDir . "*.yml");
        foreach ($languageFiles as $file) {
            $locale = basename($file, ".yml");
            $translations[$locale] = (new Config($file, Config::YAML))->getAll();
        }

        // 2. Get the best available LocaleResolver from the LanguageHub
        //    (LanguageManager will register a better one if installed)
        $localeResolver = LanguageHub::getInstance()->getLocaleResolver();

        // 3. Create your PluginTranslator instance
        //    The last argument is your plugin's default locale if no player preference is found.
        $this->translator = new PluginTranslator($this, $translations, $localeResolver, "en_US");
    }

    // ... rest of your plugin
}
```

### 2. Translating Messages

Once you have your `PluginTranslator` instance, you can use its `translateFor()` or `translate()` methods.

```php
<?php

namespace MyPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class MyPlugin extends PluginBase {
    // ... (onEnable and $this->translator from above)

    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool {
        if ($command->getName() === "mycommand") {
            // Translate for a CommandSender (Player or Console)
            $welcomeMessage = $this->translator->translateFor(
                $sender, 
                "myplugin.welcome.message",
                ["player" => $sender->getName()]
            );
            $sender->sendMessage($welcomeMessage);

            // Translate for a specific locale (e.g., for a broadcast)
            $broadcastMessage = $this->translator->translate(
                "uk_UA", // Specific locale
                "myplugin.broadcast.message",
                ["server" => "MyServer"]
            );
            $this->getServer()->broadcastMessage($broadcastMessage);

            return true;
        }
        return false;
    }
}
```

## API Reference

### `ChernegaSergiy\Language\LanguageHub`

*   `getInstance()`: (Static) Returns the singleton instance of the hub.
*   `registerLocaleResolver(LocaleResolverInterface $resolver)`: Registers a custom locale resolver. The last registered resolver takes precedence.
*   `getLocaleResolver()`: Returns the currently active `LocaleResolverInterface` instance (either a custom one or `DefaultLocaleResolver`).

### `ChernegaSergiy\Language\LocaleResolverInterface`

*   `resolve(Player $player): string`: Resolves the locale for a given player.

### `ChernegaSergiy\Language\DefaultLocaleResolver`

*   Implements `LocaleResolverInterface`. Resolves locale by returning `$player->getLocale()`.

### `ChernegaSergiy\Language\TranslatorInterface`

*   `translateFor(CommandSender $sender, string $key, array $args = []): string`: Translates a message for a given `CommandSender` (Player or Console).
*   `translate(string $locale, string $key, array $args = []): string`: Translates a message for a specific locale.

### `ChernegaSergiy\Language\PluginTranslator`

*   Implements `TranslatorInterface`. This is the concrete class plugins will use.
*   `__construct(PluginBase $plugin, array $translations, LocaleResolverInterface $localeResolver, string $defaultLocale)`: Constructor.

### `ChernegaSergiy\Language\Language`

*   `__construct(string $locale, array $translations)`: Creates a new language instance.
*   `getLocale()`: Returns the locale string (e.g., "en_US").
*   `getTranslation(string $key): ?string`: Retrieves a raw translation string for a key, or `null` if not found.


### 2. Getting a Translated Message

To get a translated message, call the `localize()` method, making sure to pass your plugin instance (`$this`).

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\LanguageAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class MyPlugin extends PluginBase {
    // ... (onEnable and registerLanguages from above)

    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool {
        if ($command->getName() === "mycommand") {
            if (!$sender instanceof Player) return false;

            // Call localize(), passing your plugin instance ($this)
            $welcomeMessage = LanguageAPI::getInstance()->localize(
                $this, 
                $sender, 
                "myplugin.welcome.message",
                ["player" => $sender->getName()]
            );

            $sender->sendMessage($welcomeMessage);
            return true;
        }
        return false;
    }
}
```

## API Reference

### `ChernegaSergiy\Language\LanguageAPI`

* `getInstance()`: (Static) Returns the singleton instance of the API.
* `registerLanguage(PluginBase $plugin, Language $language)`: Registers a `Language` instance for a specific plugin.
* `clearLanguages(PluginBase $plugin)`: Clears all languages registered by a specific plugin.
* `localize(PluginBase $plugin, ?CommandSender $sender, string $key, array $args = [])`: Localizes a message, searching in the plugin's scope first, then falling back to the global scope.
* `setGlobalScope(string $pluginName)`: To be used by a language manager plugin to declare itself as the global fallback provider.
* `registerLanguageProvider(\Closure $provider)`: To be used by a language manager plugin to provide player-specific language logic.

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
