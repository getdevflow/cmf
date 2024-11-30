<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Qubus\Exception\Data\TypeException;

use function ceil;
use function Qubus\Security\Helpers\esc_html;

class Paginator
{
    public const string NUM_PLACEHOLDER = '(:num)';

    protected int $totalItems = 0;

    protected int $numPages = 0;

    protected int $itemsPerPage = 0;

    protected int $currentPage = 0;

    protected ?string $urlPattern = null;

    protected int $maxPagesToShow = 10;

    protected string $previousText = 'Previous';

    protected string $nextText = 'Next';

    /**
     * @param int $totalItems The total number of items.
     * @param int $itemsPerPage The number of items per page.
     * @param int $currentPage The current page number.
     * @param string $urlPattern A URL for each page, with (:num) as a placeholder
     *                           for the page number. Ex. '/foo/page/(:num)'
     */
    public function __construct(int $totalItems, int $itemsPerPage, int $currentPage, string $urlPattern = '')
    {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = $currentPage;
        $this->urlPattern = $urlPattern;

        $this->updateNumPages();
    }

    protected function updateNumPages(): void
    {
        $this->numPages = ($this->itemsPerPage === 0 ? 0 : (int) ceil($this->totalItems / $this->itemsPerPage));
    }

    /**
     * @param int $maxPagesToShow
     * @throws TypeException if $maxPagesToShow is less than 3.
     */
    public function setMaxPagesToShow(int $maxPagesToShow): void
    {
        if ($maxPagesToShow < 3) {
            throw new TypeException('maxPagesToShow cannot be less than 3.');
        }
        $this->maxPagesToShow = $maxPagesToShow;
    }

    /**
     * @return int
     */
    public function getMaxPagesToShow(): int
    {
        return $this->maxPagesToShow;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @param int $itemsPerPage
     */
    public function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
        $this->updateNumPages();
    }

    /**
     * @return int
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @param int $totalItems
     */
    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
        $this->updateNumPages();
    }

    /**
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * @return int
     */
    public function getNumPages(): int
    {
        return $this->numPages;
    }

    /**
     * @param string $urlPattern
     */
    public function setUrlPattern(string $urlPattern): void
    {
        $this->urlPattern = $urlPattern;
    }

    /**
     * @return string|null
     */
    public function getUrlPattern(): ?string
    {
        return $this->urlPattern;
    }

    /**
     * @param int $pageNum
     * @return string
     */
    public function getPageUrl(int $pageNum): string
    {
        return str_replace((string) self::NUM_PLACEHOLDER, (string) $pageNum, $this->urlPattern);
    }

    public function getNextPage(): ?int
    {
        if ($this->currentPage < $this->numPages) {
            return $this->currentPage + 1;
        }

        return null;
    }

    public function getPrevPage(): ?int
    {
        if ($this->currentPage > 1) {
            return $this->currentPage - 1;
        }

        return null;
    }

    public function getNextUrl(): ?string
    {
        if (!$this->getNextPage()) {
            return null;
        }

        return $this->getPageUrl($this->getNextPage());
    }

    /**
     * @return string|null
     */
    public function getPrevUrl(): ?string
    {
        if (!$this->getPrevPage()) {
            return null;
        }

        return $this->getPageUrl($this->getPrevPage());
    }

    /**
     * Get an array of paginated page data.
     *
     * Example:
     * [
     *     ['num' => 1,     'url' => '/example/page/1',  'isCurrent' => false],
     *     ['num' => '...', 'url' => NULL,               'isCurrent' => false],
     *     ['num' => 3,     'url' => '/example/page/3',  'isCurrent' => false],
     *     ['num' => 4,     'url' => '/example/page/4',  'isCurrent' => true ],
     *     ['num' => 5,     'url' => '/example/page/5',  'isCurrent' => false],
     *     ['num' => '...', 'url' => NULL,               'isCurrent' => false],
     *     ['num' => 10,    'url' => '/example/page/10', 'isCurrent' => false],
     * ]
     *
     * @return array
     */
    public function getPages(): array
    {
        $pages = [];

        if ($this->numPages <= 1) {
            return [];
        }

        if ($this->numPages <= $this->maxPagesToShow) {
            for ($i = 1; $i <= $this->numPages; $i++) {
                $pages[] = $this->createPage($i, $i === $this->currentPage);
            }
        } else {

            // Determine the sliding range, centered around the current page.
            $numAdjacents = (int) floor(($this->maxPagesToShow - 3) / 2);

            if ($this->currentPage + $numAdjacents > $this->numPages) {
                $slidingStart = $this->numPages - $this->maxPagesToShow + 2;
            } else {
                $slidingStart = $this->currentPage - $numAdjacents;
            }
            if ($slidingStart < 2) {
                $slidingStart = 2;
            }

            $slidingEnd = $slidingStart + $this->maxPagesToShow - 3;
            if ($slidingEnd >= $this->numPages) {
                $slidingEnd = $this->numPages - 1;
            }

            // Build the list of pages.
            $pages[] = $this->createPage(1, $this->currentPage == 1);
            if ($slidingStart > 2) {
                $pages[] = $this->createPageEllipsis();
            }
            for ($i = $slidingStart; $i <= $slidingEnd; $i++) {
                $pages[] = $this->createPage($i, $i === $this->currentPage);
            }
            if ($slidingEnd < $this->numPages - 1) {
                $pages[] = $this->createPageEllipsis();
            }
            $pages[] = $this->createPage($this->numPages, $this->currentPage === $this->numPages);
        }


        return $pages;
    }


    /**
     * Create a page data structure.
     *
     * @param int $pageNum
     * @param bool $isCurrent
     * @return array
     */
    protected function createPage(int $pageNum, bool $isCurrent = false): array
    {
        return [
                'num' => $pageNum,
                'url' => $this->getPageUrl($pageNum),
                'isCurrent' => $isCurrent,
        ];
    }

    /**
     * @return array
     */
    protected function createPageEllipsis(): array
    {
        return [
            'num' => '...',
            'url' => null,
            'isCurrent' => false,
        ];
    }

    /**
     * Render an HTML pagination control.
     *
     * @return string
     */
    public function toHtml(): string
    {
        if ($this->numPages <= 1) {
            return '';
        }

        $html = '<ul class="pagination">';
        if ($this->getPrevUrl()) {
            $html .= '<li><a href="' . esc_html($this->getPrevUrl()) . '">&laquo; ' . $this->previousText . '</a></li>';
        }

        foreach ($this->getPages() as $page) {
            if ($page['url']) {
                $html .= '<li' . ($page['isCurrent'] ? ' class="active"' : '') . '>
                <a href="' . esc_html($page['url']) . '">' . esc_html($page['num']) . '</a>
                </li>';
            } else {
                $html .= '<li class="disabled"><span>' . esc_html($page['num']) . '</span></li>';
            }
        }

        if ($this->getNextUrl()) {
            $html .= '<li><a href="' . esc_html($this->getNextUrl()) . '">' . $this->nextText . ' &raquo;</a></li>';
        }
        $html .= '</ul>';

        return $html;
    }

    public function __toString()
    {
        return $this->toHtml();
    }

    public function getCurrentPageFirstItem(): float|int|null
    {
        $first = ($this->currentPage - 1) * $this->itemsPerPage + 1;

        if ($first > $this->totalItems) {
            return null;
        }

        return $first;
    }

    public function getCurrentPageLastItem(): float|int|null
    {
        $first = $this->getCurrentPageFirstItem();
        if ($first === null) {
            return null;
        }

        $last = $first + $this->itemsPerPage - 1;
        if ($last > $this->totalItems) {
            return $this->totalItems;
        }

        return $last;
    }

    public function setPreviousText(string $text): static
    {
        $this->previousText = $text;
        return $this;
    }

    public function setNextText(string $text): static
    {
        $this->nextText = $text;
        return $this;
    }
}
