<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig;

use Craft;
use craft\web\View;

/**
 * Loads Craft templates into Twig.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */

/** @noinspection PhpDeprecationInspection */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
    // Properties
    // =========================================================================

    /**
     * @var View
     */
    protected $view;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @inheritdoc
     */
    public function exists($name)
    {
        return $this->view->doesTemplateExist($name);
    }

    /**
     * @inheritdoc
     */
    public function getSourceContext($name)
    {
        $template = $this->_resolveTemplate($name);

        if (!is_readable($template)) {
            throw new TemplateLoaderException($name, Craft::t('app', 'Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
        }

        return new \Twig_Source(file_get_contents($template), $name, $template);
    }

    /**
     * Gets the cache key to use for the cache for a given template.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key (the path to the template)
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function getCacheKey($name)
    {
        return $this->_resolveTemplate($name);
    }

    /**
     * Returns whether the cached template is still up-to-date with the latest template.
     *
     * @param string  $name The template name
     * @param integer $time The last modification time of the cached template
     *
     * @return boolean
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function isFresh($name, $time)
    {
        // If this is a CP request and a DB update is needed, force a recompile.
        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest() && Craft::$app->getIsUpdating()) {
            return false;
        }

        if (is_string($name)) {
            $sourceModifiedTime = filemtime($this->_resolveTemplate($name));

            return $sourceModifiedTime <= $time;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the path to a given template, or throws a TemplateLoaderException.
     *
     * @param $name
     *
     * @return string
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    private function _resolveTemplate($name)
    {
        $template = $this->view->resolveTemplate($name);

        if ($template !== false) {
            return $template;
        }

        throw new TemplateLoaderException($name, Craft::t('app', 'Unable to find the template “{template}”.', ['template' => $name]));
    }
}
