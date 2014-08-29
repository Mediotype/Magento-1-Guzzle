<?php
/**
 * Adds, extracts, and persists cookies between HTTP requests
 */
class Cookie implements Mediotype_MagentoGuzzle_Model_Event_SubscriberInterface
{
    /** @var Mediotype_MagentoGuzzle_Model_Cookie_CookieJarInterface */
    private $cookieJar;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Cookie_CookieJarInterface $cookieJar Cookie jar used to hold cookies
     */
    public function __construct(Mediotype_MagentoGuzzle_Model_Cookie_CookieJarInterface $cookieJar = null)
    {
        $this->cookieJar = $cookieJar ?: new Mediotype_MagentoGuzzle_Model_Cookie_CookieJar();
    }

    public function getEvents()
    {
        // Fire the cookie plugin complete event before redirecting
        return array(
            'before'   => array('onBefore'),
            'complete' => array('onComplete', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::REDIRECT_RESPONSE + 10)
        );
    }

    /**
     * Get the cookie cookieJar
     *
     * @return Mediotype_MagentoGuzzle_Model_Cookie_CookieJarInterface
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    public function onBefore(Mediotype_MagentoGuzzle_Model_Event_BeforeEvent $event)
    {
        $this->cookieJar->addCookieHeader($event->getRequest());
    }

    public function onComplete(Mediotype_MagentoGuzzle_Model_Event_CompleteEvent $event)
    {
        $this->cookieJar->extractCookies(
            $event->getRequest(),
            $event->getResponse()
        );
    }
}
