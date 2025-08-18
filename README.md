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

## Architecture

The `libLanguage` virion is built around a modular and extensible architecture, ensuring robust and conflict-free translation management. Its core components are:

- **`LanguageHub` (Singleton):** This is the central point for all language-related operations. It manages the registration of languages from different plugins, handles locale resolution, and ensures that translations from various sources do not conflict. It also manages default locales for individual plugins.
- **`LocaleResolverInterface` and `DefaultLocaleResolver`:** The `LocaleResolverInterface` defines how a player's preferred language (locale) is determined. The `DefaultLocaleResolver` is the default implementation, which simply uses the player's client-side locale (`Player::getLocale()`). Plugins can register their own custom locale resolvers with `LanguageHub` to implement more sophisticated logic (e.g., fetching from a database).
- **`PluginTranslator`:** This is the primary class that your plugin will use to perform translations. It takes your plugin's registered languages, a `LocaleResolverInterface` (obtained from `LanguageHub`), and a default fallback locale. It handles placeholder replacement and integrates seamlessly with PlaceholderAPI.
- **`Language`:** A simple data class that encapsulates a specific language's locale (e.g., "en_US") and its associated array of translation keys and values.

This architecture ensures per-plugin language isolation, preventing conflicts between translations from different plugins, while providing a centralized mechanism for locale resolution and fallback.

## Basic Usage

### 1. Loading Your Plugin's Languages

In your plugin's `onEnable()` method, you should load your language files and create `Language` objects. These `Language` objects are then used to initialize your `PluginTranslator`.

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\Language;
use ChernegaSergiy\Language\LanguageHub;
use ChernegaSergiy\Language\PluginTranslator;
use ChernegaSergiy\Language\TranslatorInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MyPlugin extends PluginBase {

    private TranslatorInterface $translator;

    public function onEnable(): void {
        // Ensure your language files are saved to the plugin's data folder
        $this->saveResource("languages/en_US.yml");
        $this->saveResource("languages/uk_UA.yml");

        $languages = [];
        $languageDir = $this->getDataFolder() . 'languages/';
        $languageFiles = glob($languageDir . "*.yml");

        foreach ($languageFiles as $file) {
            $locale = basename($file, ".yml");
            $translations = (new Config($file, Config::YAML))->getAll();
            $languages[] = new Language($locale, $translations);
        }

        // Get the best available LocaleResolver from the LanguageHub
        // (A language manager plugin like LanguageManager might register a custom one)
        $localeResolver = LanguageHub::getInstance()->getLocaleResolver();

        // Initialize your PluginTranslator instance
        // The last argument is your plugin's default locale if no player preference is found.
        $this->translator = new PluginTranslator($this, $languages, $localeResolver, "en_US");
    }

    public function getTranslator(): TranslatorInterface {
        return $this->translator;
    }

    // ... rest of your plugin
}
```

### 2. Translating Messages

Once you have your `PluginTranslator` instance, you can use its `translateFor()` or `translate()` methods to get localized messages.

```php
<?php

namespace MyPlugin;

use ChernegaSergiy\Language\TranslatorInterface;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class MyPlugin extends PluginBase {
    // ... (onEnable and $this->translator from above)

    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool {
        if ($command->getName() === "mycommand") {
            // Translate for a CommandSender (Player or Console)
            // The locale will be resolved automatically based on the sender.
            $welcomeMessage = $this->translator->translateFor(
                $sender,
                "myplugin.welcome.message",
                ["player" => $sender->getName()] // Placeholders are automatically replaced
            );
            $sender->sendMessage($welcomeMessage);

            // Translate for a specific locale (e.g., for a broadcast or logging)
            $broadcastMessage = $this->translator->translate(
                "uk_UA", // Specific locale
                "myplugin.broadcast.message",
                ["server" => "MyServer"] // Placeholders are automatically replaced
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

The central singleton for language management. Access it via `LanguageHub::getInstance()`.

- `getInstance(): self`: Returns the singleton instance of the hub.
- `registerLocaleResolver(LocaleResolverInterface $resolver)`: Registers a custom locale resolver. The last registered resolver takes precedence.
- `getLocaleResolver(): LocaleResolverInterface`: Returns the currently active `LocaleResolverInterface` instance (either a custom one or `DefaultLocaleResolver`).
- `registerLocale(string $pluginName, Language $language)`: Registers a `Language` instance for a specific plugin. Used internally by `PluginTranslator`.
- `getKnownLocales(): array`: Returns an array of all unique locales registered across all plugins.
- `setDefaultLocale(PluginBase $plugin, string $locale)`: Sets the default locale for a specific plugin.
- `getDefaultLocale(PluginBase $plugin): ?string`: Retrieves the default locale for a specific plugin.

### `ChernegaSergiy\Language\LocaleResolverInterface`

An interface for resolving a player's locale.

- `resolve(Player $player): string`: Resolves and returns the locale string for a given player.

### `ChernegaSergiy\Language\DefaultLocaleResolver`

The default implementation of `LocaleResolverInterface`.

- Implements `LocaleResolverInterface`. Resolves locale by returning `$player->getLocale()`.

### `ChernegaSergiy\Language\TranslatorInterface`

An interface defining the contract for translation services.

- `translateFor(?CommandSender $sender, string $key, array $args = []): string`: Translates a message for a given `CommandSender` (Player or Console), resolving the locale automatically.
- `translate(string $locale, string $key, array $args = [], ?CommandSender $sender = null): string`: Translates a message for a specific locale.

### `ChernegaSergiy\Language\PluginTranslator`

The concrete implementation of `TranslatorInterface` used by plugins.

- `__construct(PluginBase $plugin, array $languages, LocaleResolverInterface $localeResolver, string $defaultLocale = "en_US")`: Constructor. Initializes the translator with plugin-specific languages, a locale resolver, and a default locale.
- `translateFor(?CommandSender $sender, string $key, array $args = []): string`: Translates a message for a `CommandSender`.
- `translate(string $locale, string $key, array $args = [], ?CommandSender $sender = null): string`: Translates a message for a specific locale. Supports internal and PlaceholderAPI placeholders.

### `ChernegaSergiy\Language\Language`

A data class representing a single language's translations.

- `__construct(string $locale, array $translations)`: Creates a new language instance.
- `getLocale(): string`: Returns the locale string (e.g., "en_US").
- `getTranslations(): array`: Returns the raw array of translations for this language.

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
