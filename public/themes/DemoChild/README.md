# Demo Child

Activate **Demo Child** in the CMS Themes screen to try Vihzhuo child themes. Keep
Demo installed: `DemoChildTheme` extends `DemoTheme` and inherits its `master`
layout, `main` block, CSS, and JavaScript. Only the `header` block is replaced.
Activation is optional; adding this example does not select it for an existing site.

Use this folder as a starting point. Give a new child its own namespace, class,
folder, metadata ID, and slug. Its parent must be non-final and autoloadable.
Keep `parent::handle()` when adding child initialization.

An overriding block or layout directory replaces the entire parent resource.
Include its config, view, and any PHP models/controllers you need. For dynamic
blocks, declare the matching PHP namespace in `config.php`.

If you install Header Footer Builder, opt in from your theme's `handle()` using its
`header.footer.slots` filter with `['header' => ['header']]`. Add a footer slot only
if your theme supplies a footer block. Its `header.footer.canvas.assets` filter
should return Bootstrap 5.3.3 CSS, `css/style.css`, and Bootstrap 5.3.3 bundle JS to
match Demo's layout. The optional plugin's README contains a complete hook example.
See [the child-theme guide](../../../docs/vihzhuo-child-themes.md) for preview,
asset, security, and cache behavior.
