<?php
/**
 * Event object emitted after the response headers of a request have been
 * received.
 *
 * You may intercept the exception and inject a response into the event to
 * rescue the request.
 */
class Mediotype_MagentoGuzzle_Model_Event_HeadersEvent extends Mediotype_MagentoGuzzle_Model_Event_AbstractRequestEvent
{
    /**
     * @param Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction Transaction that contains the
     *     request and response.
     * @throws RuntimeException
     */
    public function __construct(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        parent::__construct($transaction);
        if (!$transaction->getResponse()) {
            throw new RuntimeException('A response must be present');
        }
    }

    /**
     * Get the response the was received
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface
     */
    public function getResponse()
    {
        return $this->getTransaction()->getResponse();
    }
}
