<?php /** @noinspection DuplicatedCode */

namespace Evirma\Bundle\EssentialsBundle\Pager\Template;

use Evirma\Bundle\EssentialsBundle\Pager\Pager;

class PagerTemplateBem extends AbstractPagerTemplate
{
    protected Pager $pager;

    protected array $options = [
        'proximity' => 6,
        'proximity_on_mobile' => 3,
        'max_index_pages' => 100,
        'show_digit_pages' => true,
        'next_page_button_prepend' => false,
        'show_next_page_button' => false,
    ];

    private int $proximity;
    private int $proximityOnMobile;
    private int $page;
    private int $pages;
    private int $startPage;
    private int $endPage;
    private int $startPageMobile;
    private int $endPageMobile;

    public function render(Pager $pager, callable $routeGenerator, array $options = []): string
    {
        if ($pager->getPages() <= 1) {
            return '';
        }

        $this->pager = $pager;
        $this->options = array_merge($this->options, $options);

        $this->proximity = $this->option('proximity');
        $this->proximityOnMobile = $this->option('proximity_on_mobile');
        $this->page = $pager->getPage();
        $this->pages = $pager->getPages();
        $this->calculateStartAndEndPage();
        $this->setRouteGenerator($routeGenerator);

        $result = '<div class="pager">';

        $nextPage = $this->page + 1;
        if ($nextPage <= $this->pages) {
            $result .= $this->next($nextPage);
        }

        if ($this->option('show_digit_pages')) {
            $navLabel = $this->locale->isRu() ? 'Постраничная навигация' : 'Pagination Navigation';
            $result .= '<nav class="pager__nav"><ul class="pager__pages" aria-label="'. $navLabel . '">';
            $result .= $this->first();

            if ($this->startPage > 1) {
                $result .= $this->separator();
            }

            for ($p = $this->startPage; $p <= $this->endPage; $p++) {
                $isHidden = ($p < $this->startPageMobile) || ($p > $this->endPageMobile);
                $result .= $this->page($p, $isHidden);
            }

            if ($this->endPage < $this->pages) {
                $result .= $this->separator();
                $result .= $this->page($this->pages);
            }

            $result .= '</ul></nav>';
        }

        $result .= '</div>';

        return $result;
    }

    public function next($page): string
    {
        if (!$this->option('show_next_page_button')) {
            return '';
        }

        $href = $this->generateRoute(max($page, 1));
        $maxIndexPages = $this->option('max_index_pages');
        $rel = ($page > $maxIndexPages) ? ' rel="noindex,nofollow"' : '';

        $nextPageButtonPrepend = $this->option('next_page_button_prepend');

        $nextPageText = $this->locale->isRu() ? 'Следующая страница' : 'Show More';
        $navNextLabel = $this->locale->isRu() ? 'Быстрая навигация' : 'Fast Navigation';

        $class = ($this->pages < 2) ? ' pager__next--single'  : '';
        return "<nav class=\"pager__next\"><ul class=\"pager__next-list$class\" aria-label=\"$navNextLabel\">$nextPageButtonPrepend<li class=\"pager__next-item\"><a$rel class=\"pager__next-link\" href=\"$href\">$nextPageText &rarr;</a></li></ul></nav>";
    }

    public function separator(): string
    {
        return '<li class="pager__dots">…</li>';
    }

    public function getName(): string
    {
        return 'default';
    }

    private function first(): string
    {
        if ($this->startPage > 1) {
            return $this->page(1);
        }

        return '';
    }

    private function page($page, $isHidden = false): string
    {
        $hiddenClass = $isHidden ? ' pager__page-desktop' : '';

        if ($this->locale->isRu()) {
            $text = '<span class="sr-only">Страница №</span>'.$page;
        } else {
            $text = '<span class="sr-only">Page </span>'.$page;
        }
        $href = $this->generateRoute(max($page, 1));

        $maxIndexPages = $this->option('max_index_pages');

        $rel = ($page > $maxIndexPages) ? ' rel="noindex,nofollow"' : '';

        if ($page == $this->page) {
            $result = '<li class="pager__page pager__page-active'.$hiddenClass.'"><span class="pager__page-current" aria-current="page">'.$text.'</span></li>';
        } else {
            $result = '<li class="pager__page'.$hiddenClass.'"><a'.$rel.' href="'.$href.'">'.$text.'</a></li>';

        }
        return $result;
    }

    public function current($page): string
    {
        $pageText = $this->locale->isRu() ? 'Страница №' : 'Page ';
        $text = "<span class=\"sr-only\">$pageText</span>" . trim($page.' '.$this->option('active_suffix'));
        return '<li class="pager__page pager__page-active"><span>'.$text.'</span></li>';
    }


    private function calculateStartAndEndPage(): void
    {
        $startPage = $this->page - $this->proximity;
        $endPage = $this->page + $this->proximity;

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $this->pages);
            $startPage = 1;
        }

        if ($endPage > $this->pages) {
            $startPage = max($startPage - ($endPage - $this->pages), 1);
            $endPage = $this->pages;
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;

        $this->calculateMobileStartAndEndPage();
    }

    private function calculateMobileStartAndEndPage(): void
    {
        $startPage = $this->page - $this->proximityOnMobile;
        $endPage = $this->page + $this->proximityOnMobile;

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $this->pages);
            $startPage = 1;
        }

        if ($endPage > $this->pages) {
            $startPage = max($startPage - ($endPage - $this->pages), 1);
            $endPage = $this->pages;
        }

        $this->startPageMobile = $startPage;
        $this->endPageMobile = $endPage;
    }

}
