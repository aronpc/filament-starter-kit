# Development Commands

## 🚨 CRITICAL: Run After Every Change

```bash
composer test    # Run all tests, linting, type checking, refactor validation
composer fix     # Fix code style, apply refactoring, format code
```

**ALWAYS** run these commands before committing.

---

## Available Commands

```bash
# Development
composer run dev     # Start Laravel server, queue, logs, Vite
npm run dev          # Vite only
npm run build        # Production build

# Testing
./vendor/bin/pest                                    # All tests
./vendor/bin/pest tests/Feature/Auth/LoginTest.php  # Specific file
./vendor/bin/pest --filter "test name"               # Specific test

# Code Quality
composer fix         # Fix all: types, refactor, format
composer test        # Run all tests + quality checks
composer lint        # Lint PHP & JS/CSS
composer refactor    # Apply Rector rules
./vendor/bin/pint    # Format PHP
./vendor/bin/phpstan # Static analysis
```
