<?php

use Behat\Behat\Context\Context;

/**
 * Base WordPress context for Behat tests.
 * Provides minimal functionality for REST API testing.
 */
abstract class BaseWordpressContext implements Context
{
    /**
     * WordPress site URL.
     *
     * @var string
     */
    private $site_url = 'http://localhost:8888';

    /**
     * Get the WordPress site URL.
     *
     * @return string Site URL.
     */
    protected function getSiteUrl()
    {
        return rtrim($this->site_url, '/');
    }

    /**
     * Set the WordPress site URL.
     *
     * @param string $site_url Site URL.
     */
    protected function setSiteUrl($site_url)
    {
        $this->site_url = $site_url;
    }
}
