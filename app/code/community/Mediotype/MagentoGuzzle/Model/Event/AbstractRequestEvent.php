<?php
abstract class Mediotype_MagentoGuzzle_Model_Event_AbstractRequestEvent extends Mediotype_MagentoGuzzle_Model_Event_AbstractEvent
{
    /** @var Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface */
    private $transaction;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction
     */
    public function __construct(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the client associated with the event
     *
     * @return Mediotype_MagentoGuzzle_Model_ClientInterface
     */
    public function getClient()
    {
        return $this->transaction->getClient();
    }

    /**
     * Get the request object
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface
     */
    public function getRequest()
    {
        return $this->transaction->getRequest();
    }

    /**
     * @return Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface
     */
    protected function getTransaction()
    {
        return $this->transaction;
    }
}
