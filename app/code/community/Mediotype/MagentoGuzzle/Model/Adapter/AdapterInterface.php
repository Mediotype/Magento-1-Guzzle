<?php
/**
 * Adapter interface used to transfer HTTP requests.
 *
 * @link http://docs.guzzlephp.org/en/guzzle4/adapters.html for a full
 *     explanation of adapters and their responsibilities.
 */
interface Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface
{
    /**
     * Transfers an HTTP request and populates a response
     *
     * @param Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction Transaction abject to populate
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface
     */
    public function send(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction);
}
