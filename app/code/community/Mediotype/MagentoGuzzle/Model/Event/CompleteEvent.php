<?php
/**
 * Event object emitted after a request has been completed.
 *
 * You may change the Response associated with the request using the
 * intercept() method of the event.
 */
class Mediotype_MagentoGuzzle_Model_Event_CompleteEvent extends Mediotype_MagentoGuzzle_Model_Event_AbstractTransferEvent
{
    /**
     * Intercept the request and associate a response
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response to set
     */
    public function intercept(Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response)
    {
        $this->stopPropagation();
        $this->getTransaction()->setResponse($response);
    }

    /**
     * Get the response of the request
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface
     */
    public function getResponse()
    {
        return $this->getTransaction()->getResponse();
    }
}
