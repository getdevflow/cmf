<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Devflow;
use App\Shared\Services\Parsecode;
use Codefy\Framework\Support\CodefyServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\get_user_timezone;
use function App\Shared\Helpers\load_active_plugins;
use function date_default_timezone_set;
use function Qubus\Security\Helpers\esc_html__;
use function sprintf;

final class CmsHelperServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     */
    public function register(): void
    {
        $this->registerHooksFiltersAndHelpers();
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws TypeException
     */
    private function registerHooksFiltersAndHelpers(): void
    {
        if (!$this->codefy->isRunningInConsole()) {
            load_active_plugins();
            /**
             * Set the timezone for the application.
             */
            date_default_timezone_set(get_user_timezone());
        }
        /**
         * An action called to add the plugin's link
         * to the menu structure.
         */
        Action::getInstance()->doAction('admin_menu');
        /**
         * Registers & enqueues javascript to be printed in frontend footer section.
         */
        Action::getInstance()->doAction('enqueue_js');
        /**
         * Prints scripts and/or data before the ending body tag
         * of the front end.
         */
        Action::getInstance()->doAction('cms_footer');
        /**
         * Default actions and filters.
         */
        Action::getInstance()->addAction('login_form_top', 'App\Shared\Helpers\cms_login_form_show_message', 5);
        Action::getInstance()->addAction('admin_notices', 'App\Shared\Helpers\cms_dev_mode', 5);
        Action::getInstance()->addAction('save_site', 'App\Shared\Helpers\new_site_schema', 5, 3);
        Action::getInstance()->addAction('save_site', 'App\Shared\Helpers\create_site_directories', 5, 3);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_usermeta', 5, 2);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_tables', 5, 2);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_directories', 5, 2);
        Action::getInstance()->addAction('reset_password_route', 'App\Shared\Helpers\send_reset_password_email', 5, 2);
        Action::getInstance()->addAction('reassign_content', 'App\Shared\Helpers\reassign_content', 5, 2);
        Action::getInstance()->addAction('reassign_sites', 'App\Shared\Helpers\reassign_sites', 5, 2);
        Action::getInstance()->addAction(
            'password_change_email',
            'App\Shared\Helpers\send_password_change_email',
            5,
            3
        );
        Action::getInstance()->addAction('email_change_email', 'App\Shared\Helpers\send_email_change_email', 5, 2);
        Action::getInstance()->addAction('before_router_login', 'App\Shared\Helpers\does_site_exist', 6);
        Action::getInstance()->addAction('enqueue_cms_editor', 'App\Shared\Helpers\cms_editor', 5);
        Action::getInstance()->addAction('flush_cache', 'App\Shared\Helpers\populate_usermeta_cache', 5);
        Action::getInstance()->addAction('login_init', 'App\Shared\Helpers\populate_usermeta_cache', 5);
        Action::getInstance()->addAction('update_user_init', 'App\Shared\Helpers\populate_usermeta_cache', 5);
        Action::getInstance()->addAction('flush_cache', 'App\Shared\Helpers\populate_contentmeta_cache', 5);
        Action::getInstance()->addAction('update_post_init', 'App\Shared\Helpers\populate_contentmeta_cache', 5);
        Action::getInstance()->addAction('flush_cache', 'App\Shared\Helpers\populate_options_cache', 5);
        Action::getInstance()->addAction('maintenance_mode', 'App\Shared\Helpers\cms_maintenance_mode', 1);
        Action::getInstance()->addAction('cms_logout', 'App\Shared\Helpers\renew_csrf_session', 5, 2);
        Filter::getInstance()->addFilter('the_content', [Parsecode::getInstance(), 'autop']);
        Filter::getInstance()->addFilter('the_content', [Parsecode::getInstance(), 'unAutop']);
        Filter::getInstance()->addFilter('the_content', [Parsecode::getInstance(), 'doParsecode'], 5);
        Filter::getInstance()->addFilter(
            'the_content',
            'App\Shared\Helpers\cms_encode_email',
            $this->codefy->configContainer->getConfigKey(key: 'cms.eae_filter_priority')
        );
        Filter::getInstance()->addFilter('cms_authenticate_user', '\App\Shared\Helpers\cms_authenticate', 5, 3);
        Filter::getInstance()->addFilter('cms_auth_cookie', '\App\Shared\Helpers\cms_set_auth_cookie', 5, 2);

        Filter::getInstance()->addFilter(
            'mail.xmailer',
            fn() => sprintf(esc_html__(string: 'Devflow %s', domain: 'devflow'), Devflow::inst()->release()),
            10,
        );
        /**
         * Fires once activated plugins have loaded.
         */
        Action::getInstance()->doAction('plugins_loaded');
    }
}
