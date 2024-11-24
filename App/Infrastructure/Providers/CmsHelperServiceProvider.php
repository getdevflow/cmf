<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Devflow;
use App\Shared\Services\Parsecode;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Exception;
use ReflectionException;

use function Qubus\Security\Helpers\esc_html__;
use function sprintf;

final class CmsHelperServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function register(): void
    {
        $this->registerHooksFiltersAndHelpers();
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    private function registerHooksFiltersAndHelpers(): void
    {
        /**
         * An action called to add the plugin's link
         * to the menu structure.
         */
        Action::getInstance()->doAction('admin_menu');
        /**
         * Registers & enqueues a stylesheet to be printed in backend head section.
         */
        Action::getInstance()->doAction('enqueue_admin_css');
        /**
         * Fires in head section of all admin screens.
         */
        Action::getInstance()->doAction('cms_admin_head');
        /**
         * Registers & enqueues a stylesheet to be printed in frontend head section.
         */
        Action::getInstance()->doAction('enqueue_css');
        /**
         * Prints scripts and/or data in the head of the front end.
         */
        Action::getInstance()->doAction('cms_head');
        /**
         * Registers & enqueues javascript to be printed in backend footer section.
         */
        Action::getInstance()->doAction('enqueue_admin_js');
        /**
         * Prints scripts and/or data before the ending body tag of the backend.
         */
        Action::getInstance()->doAction('cms_admin_footer');
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
         * Prints CMS release information.
         */
        Action::getInstance()->doAction('cms_release');
        /**
         * Prints widgets at the top portion of the admin.
         */
        Action::getInstance()->doAction('admin_top_widgets');
        /**
         * Default actions and filters.
         */
        Action::getInstance()->addAction('cms_admin_head', 'head_release_meta', 5);
        //Action::getInstance()->addAction('cms_admin_footer', 'App\Shared\Helpers\admin_dashboard_js', 5);
        //Action::getInstance()->addAction('cms_head', 'head_release_meta', 5);
        //Action::getInstance()->addAction('cms_release', 'foot_release', 5);
        Action::getInstance()->addAction('login_form_top', 'App\Shared\Helpers\cms_login_form_show_message', 5);
        Action::getInstance()->addAction('admin_notices', 'App\Shared\Helpers\cms_dev_mode', 5);
        Action::getInstance()->addAction('save_site', 'App\Shared\Helpers\new_site_schema', 5, 3);
        Action::getInstance()->addAction('save_site', 'App\Shared\Helpers\create_site_directories', 5, 3);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_usermeta', 5, 2);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_tables', 5, 2);
        Action::getInstance()->addAction('deleted_site', 'App\Shared\Helpers\delete_site_directories', 5, 2);
        Action::getInstance()->addAction('reset_password_route', 'App\Shared\Helpers\send_reset_password_email', 5, 2);
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
        Filter::getInstance()->addFilter('reassign_posts', 'App\Shared\Helpers\reassign_posts', 5, 2);
        Filter::getInstance()->addFilter('reassign_sites', 'App\Shared\Helpers\reassign_sites', 5, 2);
        Filter::getInstance()->addFilter(
            'mail.xmailer',
            fn() => sprintf(esc_html__(string: 'Devflow %s', domain: 'devflow'), Devflow::inst()->release()),
            10,
        );
    }
}
