<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\Site\Event\SiteDomainWasChanged;
use App\Domain\Site\Event\SiteMappingWasChanged;
use App\Domain\Site\Event\SiteNameWasChanged;
use App\Domain\Site\Event\SiteOwnerWasChanged;
use App\Domain\Site\Event\SitePathWasChanged;
use App\Domain\Site\Event\SiteSlugWasChanged;
use App\Domain\Site\Event\SiteStatusWasChanged;
use App\Domain\Site\Event\SiteWasCreated;
use App\Domain\Site\Event\SiteWasDeleted;
use App\Domain\Site\Event\SiteWasModified;
use App\Domain\Site\Services\SiteProjection;
use App\Infrastructure\Persistence\Database;
use Codefy\Domain\EventSourcing\BaseProjection;
use Exception as NativeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Expressive\OrmException;
use ReflectionException;

use function App\Shared\Helpers\dfdb;

final class DatabaseSiteProjection extends BaseProjection implements SiteProjection
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
     * @param SiteWasCreated $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteWasCreated(SiteWasCreated $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_id' => $event->siteId()->toNative(),
                        'site_key' => $event->siteKey()->toNative(),
                        'site_name' => $event->siteName()->toNative(),
                        'site_slug' => $event->siteSlug()->toNative(),
                        'site_domain' => $event->siteDomain()->toNative(),
                        'site_mapping' => $event->siteMapping()->toNative(),
                        'site_path' => $event->sitePath()->toNative(),
                        'site_owner' => $event->siteOwner()->toNative(),
                        'site_status' => $event->siteStatus()->toNative(),
                        'site_registered' => $event->siteRegistered()
                    ])
                    ->save();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteNameWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteNameWasChanged(SiteNameWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_name' => $event->siteName()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteSlugWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteSlugWasChanged(SiteSlugWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_slug' => $event->siteSlug()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteDomainWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteDomainWasChanged(SiteDomainWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_domain' => $event->siteDomain()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteMappingWasChanged(SiteMappingWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_mapping' => $event->siteMapping()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SitePathWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSitePathWasChanged(SitePathWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_path' => $event->sitePath()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteOwnerWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteOwnerWasChanged(SiteOwnerWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_owner' => $event->siteOwner()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteStatusWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteStatusWasChanged(SiteStatusWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_status' => $event->siteStatus()->toNative(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteWasModified $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteWasModified(SiteWasModified $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->set([
                        'site_modified' => $event->siteModified(),
                    ])
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param SiteWasDeleted $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenSiteWasDeleted(SiteWasDeleted $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {

                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'site')
                    ->where('site_id = ?', $event->siteId()->toNative())
                    ->delete();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }
}
