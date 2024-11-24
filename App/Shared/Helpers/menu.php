<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use ReflectionException;

use function Qubus\Support\Helpers\add_trailing_slash;

/**
 * Add an admin submenu page link.
 *
 * Uses admin_submenu_$location_{$menuRoute} filter hook.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $location Submenu location.
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return mixed
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_admin_submenu(
    string $location,
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): mixed {
    if ($permission !== null) {
        if (!current_user_can($permission)) {
            return false;
        }
    }
    $menuRoute = add_trailing_slash($menuRoute);

    if ('' !== current_screen('screen_child', $screen)) {
        $menu = '<li' . current_screen('screen_child', $screen) . '>
        <a href="' . admin_url($menuRoute) . '">
        <i class="fa-regular fa-circle" style="color:#3498db;font-weight:bold;"></i> 
        <strong>' . $menuTitle . '</strong></a></li>' . "\n";
    } else {
        $menu = '<li' . current_screen('screen_child', $screen) . '>
        <a href="' . admin_url($menuRoute) . '"><i class="fa-regular fa-circle"></i> ' .
        $menuTitle . '</a></li>' . "\n";
    }
    /**
     * Filter's the admin menu.
     *
     * The dynamic parts of this filter are `location` (where menu will appear), and
     * $_menu_route with the removed slash if present.
     *
     * @param string $menu The menu to return.
     */
    return Filter::getInstance()->applyFilter("admin_submenu_{$location}_{$menuRoute}", $menu);
}

/**
 * Adds an admin dashboard submenu page link.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return false|string         Return the new menu or false if permission is not met.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_dashboard_submenu(
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): false|string {
    return add_admin_submenu('dashboard', $menuTitle, $menuRoute, $screen, $permission);
}

/**
 * Adds a sites submenu page link.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return false|string         Return the new menu or false if permission is not met.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_sites_submenu(
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): false|string {
    return add_admin_submenu('sites', $menuTitle, $menuRoute, $screen, $permission);
}

/**
 * Adds a plugin submenu page link.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return false|string         Return the new menu or false if permission is not met.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_plugins_submenu(
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): false|string {
    return add_admin_submenu('plugins', $menuTitle, $menuRoute, $screen, $permission);
}

/**
 * Adds a users submenu page link.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return false|string         Return the new menu or false if permission is not met.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_users_submenu(
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): false|string {
    return add_admin_submenu('users', $menuTitle, $menuRoute, $screen, $permission);
}

/**
 * Adds an options submenu page link.
 *
 * @file App/Shared/Helpers/menu.php
 * @param string $menuTitle The text to be used for the menu.
 * @param string $menuRoute The route part of the url.
 * @param string $screen Unique name of menu's screen.
 * @param string|null $permission The permission required for this menu to be displayed to the user.
 * @return false|string         Return the new menu or false if permission is not met.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_options_submenu(
    string $menuTitle,
    string $menuRoute,
    string $screen,
    string $permission = null
): false|string {
    return add_admin_submenu('options', $menuTitle, $menuRoute, $screen, $permission);
}
