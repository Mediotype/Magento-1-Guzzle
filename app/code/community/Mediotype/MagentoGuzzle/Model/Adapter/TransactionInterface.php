<?php
/**
 * Represents a transactions that consists of a request, response, and client
 */
interface Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface
{
    /**
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface
     */
    public function getRequest();

    /**
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface|null
     */
    public function getResponse();

    /**
     * Set a response on the transaction
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response to set
     */
    public function setResponse(Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response);

    /**
     * @return Mediotype_MagentoGuzzle_Model_ClientInterface
     */
    public function getClient();
}
