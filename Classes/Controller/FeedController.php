<?php

namespace Fab\RssDisplay\Controller;

/*
 * This file is part of the Fab/RssDisplay project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * RSS display that will fetch the content of a RSS Feed and display it onto the Frontend.
 */
class FeedController extends ActionController
{

    const PLUGIN_TYPE_USER_INT = 'USER_INT';

    const PLUGIN_TYPE_USER = 'USER';

    /**
     * @var TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    private $cache;

    public function __construct(FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Initialize object
     * @throws \RuntimeException
     */
    public function initializeAction()
    {
        if (empty($this->settings['template'])) {
            $this->settings['template'] = 'EXT:rss_display/Resources/Private/Templates/Feed/Show.html';
        }

        // Check the template is a valid URL
        if (false === filter_var($this->settings['feedUrl'], FILTER_VALIDATE_URL)) {
            $message = sprintf('Feed URL is not valid "%s". Update your settings.', $this->settings['feedUrl']);
            throw new \RuntimeException($message, 1320651278);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function showAction()
    {

        // @todo !! better add check if the Feed URL is alive. If not log it and send an email to the webmaster.

        // Configure the template path dynamically
        $pathAbs = GeneralUtility::getFileAbsFileName($this->settings['template']);
        $this->view->setTemplatePathAndFilename($pathAbs);

        if ($this->canFetchResultFromCache()) {
            // Get content from the caching framework.
            $result = $this->cache->get($this->getCacheIdentifier());
        } else {
            $feed = $this->getSimplePie($this->settings['feedUrl']);

            $this->view->assign('title', $feed->get_title());
            $this->view->assign('items', $feed->get_items(0, $this->settings['numberOfItems']));
            $this->view->assign('settings', $this->settings);
            $cObj = $this->configurationManager->getContentObject();
            $this->view->assign('data', $cObj->data);
            $result = $this->view->render();

            if ($this->isResultCachedForUserIntPlugin()) {
                // Set cache for next use
                $this->cache->set($this->getCacheIdentifier(), $result, array('type' => 'result'), $this->settings['cacheDuration']);
            }
        }

        return $result;
    }

    /**
     * Tell whether the result must be cached in the Caching Framework.
     *
     * @return boolean
     */
    protected function isResultCachedForUserIntPlugin()
    {
        $result = false;
        if ($this->getPluginType() === self::PLUGIN_TYPE_USER_INT) {
            $result = true;
        }
        return $result;
    }

    /**
     * Tell whether the caching framework can be used to fetch the result
     *
     * @return boolean
     */
    protected function canFetchResultFromCache()
    {
        $result = false;
        if ($this->getPluginType() === self::PLUGIN_TYPE_USER_INT
            && $this->cache->has($this->getCacheIdentifier())
            && !GeneralUtility::_GET('no_cache')
        ) {
            $result = true;
        }
        return $result;
    }

    /**
     * Return a SimplePie object
     * @param string $feedUrl
     * @return \SimplePie
     */
    protected function getSimplePie($feedUrl)
    {
        // Create a new instance of the SimplePie object and fetch the feed.
        $feed = new \SimplePie();
        //external request by use of a proxy
        $feed->set_raw_data(GeneralUtility::getUrl($feedUrl));
        $location = PATH_site . 'typo3temp';
        $feed->set_cache_location($location);
        $feed->init();
        return $feed;
    }

    /**
     * Return the feed identifier for the caching framework
     *
     * @return string
     */
    protected function getCacheIdentifier()
    {
        return md5($this->settings['feedUrl'] . $this->settings['template'] . $this->settings['numberOfItems']);
    }

    /**
     * Return whether the plugin is of type USER_INT (default) OR USER
     * @return string
     */
    protected function getPluginType()
    {
        try {
            /** @var ExtensionConfiguration $pluginType */
            $pluginType = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('rss_display', 'plugin_type');
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            $pluginType = self::PLUGIN_TYPE_USER_INT;
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $pluginType = self::PLUGIN_TYPE_USER_INT;
        }
        return $pluginType;
    }
}
