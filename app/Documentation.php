<?php

namespace App;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\DomCrawler\Crawler;

class Documentation
{
    /**
     * The filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The cache implementation.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Create a new documentation instance.
     *
     * @param  Filesystem  $files
     * @param  Cache  $cache
     * @return void
     */
    public function __construct(Filesystem $files, Cache $cache)
    {
        $this->files = $files;
        $this->cache = $cache;
    }

    /**
     * Get the documentation index page.
     *
     * @param  string  $version
     * @return string
     */
    public function getIndex($version)
    {
        return $this->cache->remember('docs.'.$version.'.index', 5, function () use ($version) {
            $path = base_path('resources/docs/'.$version.'/documentation.md');

            if ($this->files->exists($path)) {
                return $this->replaceLinks($version, markdown($this->files->get($path)));
            }

            return null;
        });
    }

    /**
     * Get the given documentation page.
     *
     * @param  string  $version
     * @param  string  $page
     * @return string
     */
    public function get($version, $page)
    {
        return $this->cache->remember('docs.'.$version.'.'.$page, 5, function () use ($version, $page) {
            $path = base_path('resources/docs/'.$version.'/'.$page.'.md');

            if ($this->files->exists($path)) {
                $content = markdown($this->files->get($path));
                $content = $this->replaceLinks($version, $content);
                $content = $this->replaceIcons($content);
                return $content;
            }

            return null;
        });
    }

    /**
     * Replace the version place-holder in links.
     *
     * @param  string  $version
     * @param  string  $content
     * @return string
     */
    public static function replaceLinks($version, $content)
    {
        return str_replace('{{version}}', $version, $content);
    }

    /**
     * Replace the icon place-holder text with their respective svgs.
     * @param  string  $content
     * @return string
     */
    public static function replaceIcons($content)
    {
        preg_match('/\{(.*?)\}/', $content, $match);

        if (!$match) {
            return $content;
        }

        $icon = $match[1];
        $word = $match[1];

        if ($icon == "note"
            || $icon == "tip"
            || $icon == "laracast"
            || $icon == "video"
        ) {
            $icon = svg($icon);
        } else {
            return $content;
        }

        $content = preg_replace('/\{(.*?)\}/', "<span class=\"flag\"><span class=\"svg\"> $icon </span></span>",
            $content, 1);

        $crawler = (new Crawler($content));
        $blockquote = $crawler->filterXPath('//blockquote[descendant::span[contains(@class, "flag")]]')->getNode(0);
        if ($blockquote) {
            $blockquote->setAttribute("class", "has-icon $word");
            $content = $crawler->html();
        }

        return $content;
    }

    /**
     * Check if the given section exists.
     *
     * @param  string  $version
     * @param  string  $page
     * @return boolean
     */
    public function sectionExists($version, $page)
    {
        return $this->files->exists(
            base_path('resources/docs/'.$version.'/'.$page.'.md')
        );
    }

    /**
     * Get the publicly available versions of the documentation
     *
     * @return array
     */
    public static function getDocVersions()
    {
        return [
            'master' => 'Master',
            '5.4' => '5.4',
            '5.3' => '5.3',
            '5.2' => '5.2',
            '5.1' => '5.1',
            '5.0' => '5.0',
            '4.2' => '4.2',
        ];
    }
}
