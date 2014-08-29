<?php
/**
 * Sends streaming requests to a streaming compatible adapter while sending all
 * other requests to a default adapter.
 *
 * This, for example, could be useful for taking advantage of the performance
 * benefits of the CurlAdapter while still supporting true streaming through
 * the StreamAdapter.
 */
class Mediotype_MagentoGuzzle_Model_Adapter_StreamingProxyAdapter implements Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface
{
    private $defaultAdapter;
    private $streamingAdapter;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $defaultAdapter   Adapter used for non-streaming responses
     * @param Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $streamingAdapter Adapter used for streaming responses
     */
    public function __construct(
        Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $defaultAdapter,
        Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface $streamingAdapter
    ) {
        $this->defaultAdapter = $defaultAdapter;
        $this->streamingAdapter = $streamingAdapter;
    }

    public function send(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        return $transaction->getRequest()->getConfig()['stream']
            ? $this->streamingAdapter->send($transaction)
            : $this->defaultAdapter->send($transaction);
    }
}
