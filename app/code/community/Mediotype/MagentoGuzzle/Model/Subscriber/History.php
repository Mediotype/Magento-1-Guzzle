<?php
/**
 * Maintains a list of requests and responses sent using a request or client
 */
class Mediotype_MagentoGuzzle_Model_Subscriber_History implements Mediotype_MagentoGuzzle_Model_Event_SubscriberInterface, IteratorAggregate, Countable
{
    /** @var int The maximum number of requests to maintain in the history */
    private $limit;

    /** @var array Requests and responses that have passed through the plugin */
    private $transactions = array();

    public function __construct($limit = 10)
    {
        $this->limit = $limit;
    }

    public function getEvents()
    {
        return array(
            'complete' => array('onComplete', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::EARLY),
            'error'    => array('onError', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::EARLY),
    );
    }

    /**
     * Convert to a string that contains all request and response headers
     *
     * @return string
     */
    public function __toString()
    {
        $lines = array();
        foreach ($this->transactions as $entry) {
            $response = isset($entry['response']) ? $entry['response'] : '';
            $lines[] = '> ' . trim($entry['request']) . "\n\n< " . trim($response) . "\n";
        }

        return implode("\n", $lines);
    }

    public function onComplete(Mediotype_MagentoGuzzle_Model_Event_CompleteEvent $event)
    {
        $this->add($event->getRequest(), $event->getResponse());
    }

    public function onError(Mediotype_MagentoGuzzle_Model_Event_ErrorEvent $event)
    {
        // Only track when no response is present, meaning this didn't ever
        // emit a complete event
        if (!$event->getResponse()) {
            $this->add($event->getRequest());
        }
    }

    /**
     * Returns an Iterator that yields associative array values where each
     * associative array contains a 'request' and 'response' key.
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->transactions);
    }

    /**
     * Get all of the requests sent through the plugin
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface[]
     */
    public function getRequests()
    {
        return array_map(function ($t) {
            return $t['request'];
        }, $this->transactions);
    }

    /**
     * Get the number of requests in the history
     *
     * @return int
     */
    public function count()
    {
        return count($this->transactions);
    }

    /**
     * Get the last request sent
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface
     */
    public function getLastRequest()
    {
        return end($this->transactions)['request'];
    }

    /**
     * Get the last response in the history
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface|null
     */
    public function getLastResponse()
    {
        return end($this->transactions)['response'];
    }

    /**
     * Clears the history
     */
    public function clear()
    {
        $this->transactions = array();
    }

    /**
     * Add a request to the history
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface  $request  Request to add
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response of the request
     */
    private function add(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response = null
    ) {
        $this->transactions[] = array('request' => $request, 'response' => $response);
        if (count($this->transactions) > $this->limit) {
            array_shift($this->transactions);
        }
    }
}
