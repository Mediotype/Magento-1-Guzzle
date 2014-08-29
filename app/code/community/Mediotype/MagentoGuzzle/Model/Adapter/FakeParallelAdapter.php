<?php
/**
 * Decorates a regular AdapterInterface object and creates a
 * ParallelAdapterInterface object that sends multiple HTTP requests serially.
 */
class Mediotype_MagentoGuzzle_Model_Adapter_FakeParallelAdapter implements Mediotype_MagentoGuzzle_Model_Adapter_ParallelAdapterInterface
{
    /** @var Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface */
    private $adapter;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $adapter Adapter used to send requests
     */
    public function __construct(Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function sendAll(Iterator $transactions, $parallel)
    {
        foreach ($transactions as $transaction) {
            try {
                $this->adapter->send($transaction);
            } catch (Mediotype_MagentoGuzzle_Model_Exception_RequestException $e) {
                if ($e->getThrowImmediately()) {
                    throw $e;
                }
            }
        }
    }
}
