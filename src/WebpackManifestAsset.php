<?php
/**
 * @copyright Copyright &copy; 2020 BeastBytes - All Rights Reserved
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://www.yiiframework.com/
 * @link https://github.com/beastbytes/yii3-webpackmanifestasset
 */

declare(strict_types=1);

namespace BeastBytes\WebpackManifestAsset;

use BeastBytes\WebpackManifestAsset\Exception\InvalidArgumentException;
use BeastBytes\WebpackManifestAsset\Exception\InvalidConfigException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Json;

/**
 * Asset bundle for assets defined in a {@link https://webpack.js.org/ Webpack} {@link https://www.npmjs.com/package/webpack-manifest-plugin manifest}
 *
 * @package BeastBytes\WebpackManifestAsset
 */
class WebpackManifestAsset
{
    /** @var string Character indicating an alias */
    const ALIAS = '@';
    /** @var string File extension for CSS files */
    const EXT_CSS = 'css';
    /** @var string File extension for JavaScript files */
    const EXT_JS  = 'js';

    /**
     * @var string|null the Web-accessible directory that contains the asset files in this bundle.
     *
     * If {@see sourcePath} is set, this property will be *overwritten* by {@see AssetManager} when it publishes the
     * asset files from {@see sourcePath}.
     *
     * You can use either a directory or an alias of the directory.
     */
    public ?string $basePath = null;

    /**
     * @var string|null the base URL for the relative asset files listed in {@see js} and {@see css}.
     *
     * If {@see {sourcePath} is set, this property will be *overwritten* by {@see {AssetManager} when it publishes the
     * asset files from {@see {sourcePath}.
     *
     * You can use either a URL or an alias of the URL.
     */
    public ?string $baseUrl = null;

    /**
     * @var array the options that will be passed to {@see \Yiisoft\View\WebView::registerCssFile()} when registering
     * the CSS files in this bundle.
     */
    public array $cssOptions = [];

    /**
     * @var array list of bundle class names that this bundle depends on.
     *
     * For example:
     *
     * ```php
     * public $depends = [
     *    \Yiisoft\Jquery\YiiAsset::class,
     *    \Yiisoft\Bootstrap4\BootstrapAsset::class,
     * ];
     * ```
     */
    public array $depends = [];

    /**
     * @var string Location of the manifest file. Can be an absolute path and filename or an alias.
     */
    public string $manifest = '@public/manifest.json';

    /**
     * @var array the options that will be passed to {@see Yiisoft\View\WebView::registerJsFile()} when registering the
     * JS files in this bundle.
     */
    public array $jsOptions = [];

    /**
     * @var array the options to be passed to {@see AssetManager::publish()} when the asset bundle is being published.
     * This property is used only when {@see sourcePath} is set.
     */
    public array $publishOptions = [];

    /**
     * @var string|null the directory that contains the source asset files for this asset bundle. A source asset file is a
     * file that is part of your source code repository of your Web application.
     *
     * You must set this property if the directory containing the source asset files is not Web accessible. By setting
     * this property, {@see AssetManager} will publish the source asset files to a Web-accessible directory automatically
     * when the asset bundle is registered on a page.
     *
     * If you do not set this property, it means the source asset files are located under {@see basePath}.
     *
     * You can use either a directory or an alias of the directory.
     *
     * {@see publishOptions}
     */
    public ?string $sourcePath = null;

    /**
     * @var array list of CSS files that this bundle contains.
     */
    private array $css = [];

    /**
     * @var array list of JavaScript files that this bundle contains
     */
    private array $js = [];

    /**
     * Returns a list of Webpack css or js assets.
     *
     * If the asset lists are empty the Webpack manifest is parsed to generate them.
     * Do not call this method directly.
     *
     * @param string $name the asset type
     * @return array the assets
     * @throws InvalidArgumentException
     */
    public function __get(string $name): array
    {
        if ($name !== self::EXT_CSS && $name !== self::EXT_JS) {
            throw new InvalidArgumentException("Invalid asset type: $name - must be either " . self::EXT_CSS . " or " . self::EXT_JS);
        }

        if (empty($this->css) && empty($this->js)) {
            $this->loadManifest();
        }

        $getter = 'get' . $name;
        return $this->$getter();
    }

    /**
     * @return array CSS assets
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * @return array JavaScript Assets
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * Load and parse the Webpack manifest file.
     *
     * Place chunks defined in the manifest into the asset lists.
     *
     * @throws InvalidConfigException
     */
    private function parseManifest(): void
    {
        $manifest = (substr($this->manifest, 0, 1) === self::ALIAS)
            ? Aliases::get($this->manifest)
            : $this->manifest
        ;

        if (!file_exists($manifest)) {
            throw new InvalidConfigException("Webpack manifest not found: $manifest");
        }

        $manifest = file_get_contents($manifest);
        $manifest = Json::decode($manifest);

        foreach ($manifest as $chunkName => $file) {
            $extension = substr($chunkName, strrpos($chunkName, '.') + 1);

            if ($extension === self::EXT_CSS) {
                $this->css[] = $file;
            } else if ($extension === self::EXT_JS) {
                $this->js[] = $file;
            }
        }
    }
}
