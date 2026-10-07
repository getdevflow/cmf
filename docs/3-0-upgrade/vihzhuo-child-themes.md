# Vihzhuo child themes

Devflow Core uses Vihzhuo 2.1 or later. The Website Manager, page editor, public
renderer, and Header Footer Builder use the configured Core theme adapter.

## Create a child

The bundled `public/themes/DemoChild` example extends Demo and replaces only its
header. Its layouts, other blocks, CSS, and JavaScript remain in Demo. Select
**Demo Child** in the CMS Themes screen to try it; it is not activated automatically.

Copy that example to start your own theme. Give it its own folder, namespace,
class, metadata ID, slug, and Registry key. Its declaration takes this form:

```php
namespace Theme\MyChild;

use Theme\MyParent\MyParentTheme;

final class MyChildTheme extends MyParentTheme
{
    // Supply the child's own meta() declaration, following DemoChildTheme.
    // Keep parent::handle() when adding child initialization.
}
```

The project autoloads `Theme\` from `public/themes/`. Keep the parent installed
and autoloadable. A parent intended for extension must not be `final`;
intermediate children must also be non-final if they will have children.
Calling `parent::handle()` retains the parent's pagebuilder support and hooks.

Core's `get_parent_theme()` helper reads PHP inheritance for every ancestor.
There is no separate `parents` configuration map. Keep the theme folder and URL
configuration unchanged. The current site's activated theme takes precedence
over `theme.active_theme`, which is the fallback when no theme is activated.
Explicit custom theme adapters remain supported and manage their own inheritance.

## Override blocks and layouts

Resources are identified by slug. A matching child directory replaces the entire
parent resource: configuration, view, model, controller, and scripts. Resources
absent from the child fall back to the nearest ancestor. Existing saved page
references continue to use the same slugs. Core and plugin resources registered
with `Vihzhuo\Extensions` keep their existing precedence over theme resources.

To override `header`, supply `blocks/header/config.php` and `view.html` or
`view.php`. To override `master`, supply `layouts/master/config.php` and
`view.php`. A directory containing only configuration does not inherit its
parent's view. Copy the complete resource first, then customize it.

Dynamic blocks should declare their PHP namespace in their configuration:

```php
return [
    'title' => 'Featured content',
    'namespace' => 'Theme\\MyChild\\Blocks\\Featured',
];
```

Match that namespace in `model.php` and `controller.php`. Inherited resources
retain their parent namespace.

## Assets and translations

Use `phpb_theme_asset('css/style.css')` in PHP views, and
`[theme-url]/css/style.css` in HTML blocks. Assets resolve through the child's
and ancestors' `public/` directories, with existing root asset paths supported.
A matching child file replaces the parent asset. If both stylesheets are needed,
give them different filenames and load both. Relative image/font URLs inside an
inherited stylesheet resolve relative to that stylesheet's owning theme.

Translations in `translations/en.php` and the selected locale fall back through
the ancestor chain. Child top-level translation keys replace ancestor keys.
When overriding a nested translation group, supply that complete group.

When installed and activated, Header Footer Builder uses the same adapter. Themes
can opt in through its `header.footer.slots` and `header.footer.canvas.assets`
filters in their `handle()` method; no CMS configuration is needed. Keep inherited
block slugs in the slots mapping. Relative assets returned by the assets filter use
the same fallback. Match the JavaScript version used by the parent layout. Call
`parent::handle()` in a child and register later-priority filters to override its
integration. See the optional plugin's README for callback signatures and examples.

## Preview without activation

For a developer preview of an installed theme:

```php
$theme = new \App\Infrastructure\Services\Vihzhuo\VihzhuoTheme(
    $themeConfig,
    $default,
    previewTheme: 'Theme\\MyChild\\MyChildTheme',
);
$builder->setTheme($theme);
```

For [Header Footer Builder](https://getdevflow.com/extensions/getdevflow/header-footer-builder) fragments, pass that adapter to both operations:

```php
$body = \Plugin\HeaderFooterBuilder\Support\Runtime::render($template, $theme, true);
$assets = \Plugin\HeaderFooterBuilder\Support\Runtime::canvasAssets($theme);
```

Register the preview candidate's integration filters before rendering, and scope
them to the adapter passed to their callbacks. Creating a preview adapter does not
run the CMS theme's `handle()` method.

Explicit preview selection is a trusted developer API. An HTTP integration must
restrict it to installed theme classes and retain authorization and CSRF checks.
Do not accept arbitrary classes or filesystem paths from public requests. The
CMS and Header Footer Builder do not add a public theme-selection parameter.

## Security and cache

Themes execute PHP and should be reviewed and deployed like plugins. Keep
resources within their configured directories: traversal paths and symlinks
escaping those directories are rejected by the resolver. Keep every ancestor
installed while a child uses it.

Activation and deactivation clear the existing Vihzhuo page cache. After editing
inherited templates, use the CMS cache-flush action. No new cache configuration
is required.

