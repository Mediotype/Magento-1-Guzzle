<?php
/**
 * Event object emitted before a request is sent.
 *
 * You may change the Response associated with the request using the
 * intercept() method of the event.
 */
class Mediotype_MagentoGuzzle_Model_Event_BeforeEvent extends Mediotype_MagentoGuzzle_Model_Event_AbstractRequestEvent
{
    /**
     * Intercept the request and associate a response
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response to set
     */
    public function intercept(Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response)
    {
        $this->getTransaction()->setResponse($response);
        $this->stopPropagation();
        Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitComplete($this->getTransaction());
    }
}
