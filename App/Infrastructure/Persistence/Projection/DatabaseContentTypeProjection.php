<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\ContentType\Event\ContentTypeWasCreated;
use App\Domain\ContentType\Event\ContentTypeDescriptionWasChanged;
use App\Domain\ContentType\Event\ContentTypeSlugWasChanged;
use App\Domain\ContentType\Event\ContentTypeTitleWasChanged;
use App\Domain\ContentType\Event\ContentTypeWasDeleted;
use App\Domain\ContentType\Services\ContentTypeProjection;
use App\Infrastructure\Persistence\Database;
use Codefy\Domain\EventSourcing\BaseProjection;
use Exception as NativeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Expressive\OrmException;
use ReflectionException;

use function App\Shared\Helpers\dfdb;

final class DatabaseContentTypeProjection extends BaseProjection implements ContentTypeProjection
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
     * @param ContentTypeWasCreated $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeWasCreated(ContentTypeWasCreated $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content_type')
                    ->set([
                        'content_type_id' => $event->contentTypeId()->toNative(),
                        'content_type_title' => $event->contentTypeTitle()->toNative(),
                        'content_type_slug' => $event->contentTypeSlug()->toNative(),
                        'content_type_description' => $event->contentTypeDescription()->toNative(),
                    ])
                    ->save();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTypeTitleWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeTitleWasChanged(ContentTypeTitleWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content_type')
                    ->set([
                        'content_type_title' => $event->contentTypeTitle()->toNative(),
                    ])
                    ->where('content_type_id = ?', $event->contentTypeId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTypeSlugWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeSlugWasChanged(ContentTypeSlugWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content_type')
                    ->set([
                        'content_type_slug' => $event->contentTypeSlug()->toNative(),
                    ])
                    ->where('content_type_id = ?', $event->contentTypeId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTypeDescriptionWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeDescriptionWasChanged(ContentTypeDescriptionWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content_type')
                    ->set([
                        'content_type_description' => $event->contentTypeDescription()->toNative(),
                    ])
                    ->where('content_type_id = ?', $event->contentTypeId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ContentTypeWasDeleted $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenContentTypeWasDeleted(ContentTypeWasDeleted $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {

                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'content_type')
                    ->where('content_type_id = ?', $event->contentTypeId()->toNative())
                    ->delete();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }
}
