
## 1.1.1 (2024-12-19)
- fix: bug in `ContentMetaWasChanged`
- enhancement: added alternative location for custom views, under `Cms` namespace
- fix: added missing `temp` folder for upgrades

## 1.1.0 (2024-12-16)
- Added ability to install plugins via composer
- Added upgrade procedure via the terminal
- `cms:update:check` command to check for new updates
- `cms:update` command to update your system to the latest release
- When an update is available, a message will appear in the backend
- `update_message` filter hook added