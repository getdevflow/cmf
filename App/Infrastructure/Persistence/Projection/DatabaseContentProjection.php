<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\Content\Event\ContentAuthorWasChanged;
use App\Domain\Content\Event\ContentBodyWasChanged;
use App\Domain\Content\Event\ContentFeaturedImageWasChanged;
use App\Domain\Content\Event\ContentMetaWasChanged;
use App\Domain\Content\Event\ContentModifiedGmtWasChanged;
use App\Domain\Content\Event\ContentModifiedWasChanged;
use App\Domain\Content\Event\ContentParentWasChanged;
use App\Domain\Content\Event\ContentParentWasRemoved;
use App\Domain\Content\Event\ContentPublishedGmtWasChanged;
use App\Domain\Content\Event\ContentPublishedWasChanged;
use App\Domain\Content\Event\ContentShowInMenuWasChanged;
use App\Domain\Content\Event\ContentShowInSearchWasChanged;
use App\Domain\Content\Event\ContentSidebarWasChanged;
use App\Domain\Content\Event\ContentSlugWasChanged;
use App\Domain\Content\Event\ContentStatusWasChanged;
use App\Domain\Content\Event\ContentTitleWasChanged;
use App\Domain\Content\Event\ContentTypeWasChanged;
use App\Domain\Content\Event\ContentWasCreated;
use App\Domain\Content\Event\ContentWasDeleted;
use App\Domain\Content\Services\ContentProjection;
use App\Infrastructure\Persistence\Database;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\Domain\EventSourcing\BaseProjection;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Exception as NativeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmException;
use ReflectionException;

use function App\Shared\Helpers\add_contentmeta;
use function App\Shared\Helpers\dfdb;
use function App\Shared\Helpers\update_contentmeta;

final class DatabaseContentProjection extends BaseProjection implements ContentProjection
{
    protected ?Database $dfdb = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @param ContentWasCreated $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws CommandPropertyNotFoundException
     * @throws UnresolvableQueryHandlerException
     * @throws Exception
     * @throws NativeException
     */
    public function projectWhenContentWasCreated(ContentWasCreated $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_id' => $event->contentId()->toNative(),
                            'content_title' => $event->contentTitle()->toNative(),
                            'content_slug' => $event->contentSlug()->toNative(),
                            'content_body' => $event->contentBody() === null ? null : $event->contentBody()->toNative(),
                            'content_author' => $event->contentAuthor()->toNative(),
                            'content_type' => $event->contentTypeSlug()->toNative(),
                            'content_parent' => $event->contentParent() === null ?
                                    null : $event->contentParent()->toNative(),
                            'content_sidebar' => $event->contentSidebar()->toNative(),
                            'content_show_in_menu' => $event->contentShowInMenu()->toNative(),
                            'content_show_in_search' => $event->contentShowInSearch()->toNative(),
                            'content_featured_image' => $event->contentFeaturedImage()->toNative(),
                            'content_status' => $event->contentStatus()->toNative(),
                            'content_created' => $event->contentCreated()->format('Y-m-d H:i:s'),
                            'content_created_gmt' => $event->contentCreatedGmt()->format('Y-m-d H:i:s'),
                            'content_published' => $event->contentPublished()->format('Y-m-d H:i:s'),
                            'content_published_gmt' => $event->contentPublishedGmt()->format('Y-m-d H:i:s'),
                        ])
                        ->save();
            });

            if (!$event->contentmeta()->isEmpty()) {
                foreach ($event->contentmeta()->toNative() as $meta => $value) {
                    add_contentmeta($event->aggregateId()->__toString(), $meta, $value);
                }
            }
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTitleWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTitleWasChanged(ContentTitleWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_title' => $event->contentTitle()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentSlugWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentSlugWasChanged(ContentSlugWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_slug' => $event->contentSlug()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentBodyWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentBodyWasChanged(ContentBodyWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_body' => $event->contentBody() === null ? null : $event->contentBody()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentAuthorWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentAuthorWasChanged(ContentAuthorWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_author' => $event->contentAuthor()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTypeWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeWasChanged(ContentTypeWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_type' => $event->contentTypeSlug()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentParentWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentParentWasChanged(ContentParentWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_parent' => $event->contentParent() === null ?
                                    null : $event->contentParent()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentParentWasRemoved $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentParentWasRemoved(ContentParentWasRemoved $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_parent' => null,
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentSidebarWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentSidebarWasChanged(ContentSidebarWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_sidebar' => $event->contentSidebar()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentShowInMenuWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentShowInMenuWasChanged(ContentShowInMenuWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_show_in_menu' => $event->contentShowInMenu()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentShowInSearchWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentShowInSearchWasChanged(ContentShowInSearchWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_show_in_search' => $event->contentShowInSearch()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentFeaturedImageWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentFeaturedImageWasChanged(ContentFeaturedImageWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                            'content_featured_image' => $event->contentFeaturedImage()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentStatusWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentStatusWasChanged(ContentStatusWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                                'content_status' => $event->contentStatus()->toNative(),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentPublishedWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentPublishedWasChanged(ContentPublishedWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                                'content_published' => $event->contentPublished()->format('Y-m-d H:i:s'),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentPublishedGmtWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentPublishedGmtWasChanged(ContentPublishedGmtWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                                'content_published_gmt' => $event->contentPublishedGmt()->format('Y-m-d H:i:s'),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentModifiedWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentModifiedWasChanged(ContentModifiedWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->set([
                                'content_modified' => $event->contentModified()->format('Y-m-d H:i:s'),
                        ])
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentModifiedGmtWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentModifiedGmtWasChanged(ContentModifiedGmtWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content')
                    ->set([
                        'content_modified_gmt' => $event->contentModifiedGmt()->format('Y-m-d H:i:s'),
                    ])
                    ->where('content_id = ?', $event->contentId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentMetaWasChanged $event
     * @return void
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function projectWhenContentMetaWasChanged(ContentMetaWasChanged $event): void
    {
        if (!$event->contentmeta()->isEmpty()) {
            foreach ($event->contentmeta()->toNative() as $meta => $value) {
                update_contentmeta($event->aggregateId()->__toString(), $meta, $value);
            }
        }
    }

    /**
     * @param ContentWasDeleted $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentWasDeleted(ContentWasDeleted $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {

                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'content')
                        ->where('content_id = ?', $event->contentId()->toNative())
                        ->delete();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }
}
