<?php
/**
 * Adapter that can be used to associate mock responses with a transaction
 * while still emulating the event workflow of real adapters.
 */
class Mediotype_MagentoGuzzle_Model_Adapter_MockAdapter implements Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface
{
    private $response;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface|callable $response Response to serve or function
     *     to invoke that handles a transaction
     */
    public function __construct($response = null)
    {
        $this->setResponse($response);
    }

    /**
     * Set the response that will be served by the adapter
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface|callable $response Response to serve or
     *     function to invoke that handles a transaction
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function send(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitBefore($transaction);
        if (!$transaction->getResponse()) {

            // Read the request body if it is present
            if ($transaction->getRequest()->getBody()) {
                $transaction->getRequest()->getBody()->__toString();
            }

            $response = is_callable($this->response)
                ? call_user_func($this->response, $transaction)
                : $this->response;
            if (!$response instanceof Mediotype_MagentoGuzzle_Model_Message_ResponseInterface) {
                throw new RuntimeException('Invalid mocked response');
            }

            $transaction->setResponse($response);
            Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitHeaders($transaction);
            Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitComplete($transaction);
        }

        return $transaction->getResponse();
    }
}
