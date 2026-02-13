# AGENTS.md

This document provides guidelines for AI coding agents working in the RAMSAY codebase.

## Project Overview

RAMSAY is a vanilla PHP web application for managing a Yu-Gi-Oh DIY game server. It provides card search, voting, banlist management, and author statistics features.

- **Language**: PHP 7.0+
- **Database**: SQLite 3
- **Architecture**: Lightweight MVC pattern (no framework)
- **Frontend**: Vanilla JavaScript, CSS

## Build/Lint/Test Commands

This project has no build system, package manager, or automated tests.

### Running the Application

1. Deploy to a PHP-enabled web server (IIS, Apache, Nginx)
2. Configure `config.php` or create `config.user.php` to override settings
3. Access via web browser

### Debugging

- Set `DEBUG_MODE` to `true` in `config.php` for detailed error messages
- Debug logs are written to `logs/debug.log`

### Manual Testing

1. Visit `?controller=card` - Card search functionality
2. Visit `?controller=vote` - Voting overview
3. Visit `?controller=admin&action=login` - Admin login (default: admin/admin123)

## Code Style Guidelines

### PHP Style

#### Naming Conventions

- **Classes**: PascalCase (e.g., `CardController`, `VoteModel`, `Database`)
- **Methods**: camelCase (e.g., `getCardById()`, `getAllVotes()`, `searchCards()`)
- **Properties**: camelCase with private visibility (e.g., `$cardParser`, `$voteModel`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DB_PATH`, `DEBUG_MODE`, `CARDS_PER_PAGE`)
- **Variables**: snake_case or camelCase (be consistent within function scope)

#### Class Structure

```php
<?php
/**
 * Class description (in Chinese or English)
 *
 * Detailed explanation of class purpose
 */
class ClassName {
    /**
     * Property description
     * @var Type
     */
    private $propertyName;

    /**
     * Constructor description
     */
    public function __construct() {
        // Initialization
    }

    /**
     * Method description
     *
     * @param Type $paramName Parameter description
     * @return Type Return value description
     */
    public function methodName($paramName) {
        // Implementation
    }
}
```

#### Documentation

- Use PHPDoc blocks for all classes, methods, and properties
- Document parameters with `@param` and return values with `@return`
- Use `@var` for property type declarations
- Comments can be in Chinese or English (Chinese preferred for user-facing descriptions)

#### Singleton Pattern

Several core classes use singleton pattern:

```php
public static function getInstance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### Imports and File Loading

- No `use` statements - this is vanilla PHP without namespaces
- Class autoloading via `spl_autoload_register` in `index.php`
- Views are loaded via `include` statements

```php
include __DIR__ . '/../Views/layout.php';
include __DIR__ . '/../Views/cards/detail.php';
```

### Database Operations

- Use the `Database` singleton class for all database operations
- Always use prepared statements (the `Database` class handles this)

```php
$db = Database::getInstance();

// Get single row
$row = $db->getRow('SELECT * FROM cards WHERE id = ?', [$cardId]);

// Get multiple rows
$rows = $db->getRows('SELECT * FROM votes WHERE status = ?', [$status]);

// Insert
$id = $db->insert('votes', ['card_id' => $cardId, 'reason' => $reason]);

// Update
$db->update('votes', ['status' => 1], 'id = ?', [$id]);
```

### Error Handling

- Use try-catch for database operations
- Check `DEBUG_MODE` before exposing error details

```php
try {
    // Database operation
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die('Error: ' . $e->getMessage());
    } else {
        error_log('Error: ' . $e->getMessage());
        die('Database operation failed');
    }
}
```

### Views and HTML Output

- Views are in `includes/Views/` directory
- Always escape output with `htmlspecialchars()` or `Utils::escapeHtml()`

```php
<?php echo htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8'); ?>
<?php echo Utils::escapeHtml($text); ?>
```

- Use `isset()` and `!empty()` for checking variable existence
- Flash messages use `$_SESSION['success_message']` and `$_SESSION['error_message']`

### Configuration

- Define constants in `config.php` with `defined()` check for override capability

```php
if (!defined('SETTING_NAME')) {
    define('SETTING_NAME', 'default_value');
}
```

- User overrides go in `config.user.php`

### JavaScript Style

- Use `document.addEventListener('DOMContentLoaded', ...)` for initialization
- Use `const` and `let` (not `var`)
- Event handlers use `function(e) {}` syntax (not arrow functions for `this` binding)
- Feature detection before using APIs

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const element = document.getElementById('my-element');
    if (element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            // Handle click
        });
    }
});
```

### CSS Style

- Use kebab-case for class names (e.g., `.card-list`, `.vote-button`)
- Organize styles by component/section
- Use CSS custom properties for theming where appropriate

## Directory Structure

```
/
├── index.php                 # Entry point and routing
├── config.php                # Configuration constants
├── config.user.php           # User configuration overrides
├── includes/
│   ├── Core/                 # Core classes (Database, Auth, Utils)
│   ├── Models/               # Data models (Card, Vote, User)
│   ├── Controllers/          # Request handlers
│   └── Views/                # PHP templates
├── assets/
│   ├── css/style.css         # Main stylesheet
│   └── js/script.js          # Main JavaScript
├── data/
│   ├── ramsay.db             # SQLite database
│   └── cache/                # Cache directory
└── logs/                     # Debug logs
```

## Routing

URL format: `?controller=controllerName&action=methodName`

- Controller names map to classes via `$controllerMap` in `index.php`
- Default controller: `card` (or configured via `HOME_PAGE`)
- Default action: `index`

## Important Patterns

1. **Always validate user input** - Use `(int)` casting, `trim()`, and validation arrays
2. **Check feature flags** - Use `defined('FEATURE_ENABLED') && FEATURE_ENABLED`
3. **Use helper methods** - `Utils::escapeHtml()`, `Utils::redirect()`, `Utils::getEnvironments()`
4. **Session handling** - Auth class manages sessions; check `$auth->isLoggedIn()` and `$auth->hasPermission()`
5. **Memory management** - For large operations, call `Utils::checkMemoryUsage()` and `Utils::forceGarbageCollection()`

## Security Considerations

- Passwords stored with `password_hash()` and verified with `password_verify()`
- Session cookies use `httponly` and `samesite=Strict`
- SQL injection prevented via prepared statements
- XSS prevented via output escaping
- CSRF protection not implemented - be cautious with form submissions
