## 2.2.0 (2026-06-01)
- ➕ Security audit advisory on updates screen
- ➕ Site specific plugins
- 🔧 Paginator
- ➕ Firewall
- ➕ Referrer spam

## 2.1.0 (2026-05-25)
- ➕ Site migrations and commands
- ➕ Extension commands
- 💾 Enhanced updater command
- ➕ Plugin and Theme activate/deactivate/remove integrity

## 2.0.0 (2026-05-18)
- ➕ Http transport client
- ➕ Schedule content to be published in the future
- ➕ Schedule products to be published in the future
- 💾 Check if plugin class exists before loading
- 💾 Check if theme class exists before loading
- ➕ New page builder
- ➕ Event listener service provider
- ➕ Scheduler service provider
- ➕ Page builder service provider
- 🔧 Dependency issue with Database
- 💾 Updated namespace under `Cms`
- ➕ Http cache
- ➕ Plugin install screen
- ➕ Theme install screen
- ➕ Enable/disable plugins and themes for subsites
- 🔧 Code refactoring
- 🔧 Other minor fixes and improvements

## 1.3.0 (2025-02-14)
- 🔧 Fixed misspelling in `config/cms.php`
- 💾 Update server is filterable (`updater_api_version`, `updater_base_url`, `updater_url`)
- ➕ New `SeoFactory` for Search Engine Optimization (SEO)
- 🔧 Fixed domain mapping update/removal
- 🔧 Fixed content and product form filters for custom fields
- ➕ New theme loop and helper functions
- ➕ New version 2 REST API with bearer authorization
- 🔧 Fixed admin dashboard mobile access

## 1.2.0 (2025-01-05)
- ➖ Removed unused imports in `db` helper
- 💾 Core is now a composer component
- 🔧 Fixed issue with loading all the .mo files for translation
- ➕ Added theme features
- ➕ Added extension contract

## 1.1.1 (2024-12-19)
- 🔧 fix: bug in `ContentMetaWasChanged`
- 💾 enhancement: added alternative location for custom views, under `Cms` namespace
- 🔧 fix: added missing `temp` folder for upgrades

## 1.1.0 (2024-12-16)
- 💾 Added ability to install plugins via composer
- 💾 Added upgrade procedure via the terminal
- ➕ `cms:update:check` command to check for new updates
- ➕ `cms:update` command to update your system to the latest release
- 💾 When an update is available, a message will appear in the backend
- ➕ `update_message` filter hook added
