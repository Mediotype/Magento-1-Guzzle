<?php
/**
 * Throws exceptions when a 4xx or 5xx response is received
 */
class Mediotype_MagentoGuzzle_Model_Subscriber_HttpError implements Mediotype_MagentoGuzzle_Model_Event_SubscriberInterface
{
    public function getEvents()
    {
        return array('complete' => array('onComplete', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::VERIFY_RESPONSE));
    }

    /**
     * Throw a RequestException on an HTTP protocol error
     *
     * @param Mediotype_MagentoGuzzle_Model_Event_CompleteEvent $event Emitted event
     * @throws Mediotype_MagentoGuzzle_Model_Exception_RequestException
     */
    public function onComplete(Mediotype_MagentoGuzzle_Model_Event_RequestEvents $event)
    {
        $code = (string) $event->getResponse()->getStatusCode();
        // Throw an exception for an unsuccessful response
        if ($code[0] === '4' || $code[0] === '5') {
            throw Mediotype_MagentoGuzzle_Model_Exception_RequestException::create($event->getRequest(), $event->getResponse());
        }
    }
}
