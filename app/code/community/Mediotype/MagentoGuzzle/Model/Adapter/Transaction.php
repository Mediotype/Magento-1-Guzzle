<?php
class Mediotype_MagentoGuzzle_Model_Adapter_Transaction implements Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface
{
    /** @var Mediotype_MagentoGuzzle_Model_ClientInterface */
    private $client;
    /** @var Mediotype_MagentoGuzzle_Model_Message_RequestInterface */
    private $request;
    /** @var Mediotype_MagentoGuzzle_Model_Message_ResponseInterface */
    private $response;

    /**
     * @param Mediotype_MagentoGuzzle_Model_ClientInterface  $client  Client that is used to send the requests
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request
     */
    public function __construct(
        Mediotype_MagentoGuzzle_Model_ClientInterface $client,
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request
    ) {
        $this->client = $client;
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getClient()
    {
        return $this->client;
    }
}
