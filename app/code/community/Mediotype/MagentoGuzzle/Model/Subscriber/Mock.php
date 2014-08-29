<?php
/**
 * Queues mock responses or exceptions and delivers mock responses or
 * exceptions in a fifo order.
 */
class Mediotype_MagentoGuzzle_Model_Subscriber_Mock implements Mediotype_MagentoGuzzle_Model_Event_SubscriberInterface, Countable
{
    /** @var array Array of mock responses / exceptions */
    private $queue = array();

    /** @var bool Whether or not to consume an entity body when mocking */
    private $readBodies;

    /** @var Mediotype_MagentoGuzzle_Model_Message_MessageFactory */
    private $factory;

    /**
     * @param array $items      Array of responses or exceptions to queue
     * @param bool  $readBodies Set to false to not consume the entity body of
     *                          a request when a mock is served.
     */
    public function __construct(array $items = array(), $readBodies = true)
    {
        $this->factory = new Mediotype_MagentoGuzzle_Model_Message_MessageFactory();
        $this->readBodies = $readBodies;
        $this->addMultiple($items);
    }

    public function getEvents()
    {
        // Fire the event last, after signing
        return ['before' => ['onBefore', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::SIGN_REQUEST - 10]];
    }

    /**
     * @throws OutOfBoundsException|Exception
     */
    public function onBefore(Mediotype_MagentoGuzzle_Model_Event_BeforeEvent $event)
    {
        if (!$item = array_shift($this->queue)) {
            throw new OutOfBoundsException('Mock queue is empty');
        } elseif ($item instanceof Mediotype_MagentoGuzzle_Model_Exception_RequestException) {
            throw $item;
        }

        // Emulate the receiving of the response headers
        $request = $event->getRequest();
        $transaction = new Mediotype_MagentoGuzzle_Model_Adapter_Transaction($event->getClient(), $request);
        $transaction->setResponse($item);
        $request->getEmitter()->emit(
            'headers',
            new Mediotype_MagentoGuzzle_Model_Event_HeadersEvent($transaction)
        );

        // Emulate reading a response body
        if ($this->readBodies && $request->getBody()) {
            while (!$request->getBody()->eof()) {
                $request->getBody()->read(8096);
            }
        }

        $event->intercept($item);
    }

    public function count()
    {
        return count($this->queue);
    }

    /**
     * Add a response to the end of the queue
     *
     * @param string|Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response or path to response file
     *
     * @return self
     * @throws InvalidArgumentException if a string or Response is not passed
     */
    public function addResponse($response)
    {
        if (is_string($response)) {
            $response = file_exists($response)
                ? $this->factory->fromMessage(file_get_contents($response))
                : $this->factory->fromMessage($response);
        } elseif (!($response instanceof Mediotype_MagentoGuzzle_Model_Message_ResponseInterface)) {
            throw new InvalidArgumentException('Response must a message '
                . 'string, response object, or path to a file');
        }

        $this->queue[] = $response;

        return $this;
    }

    /**
     * Add an exception to the end of the queue
     *
     * @param Mediotype_MagentoGuzzle_Model_Exception_RequestException $e Exception to throw when the request is executed
     *
     * @return self
     */
    public function addException(Mediotype_MagentoGuzzle_Model_Exception_RequestException $e)
    {
        $this->queue[] = $e;

        return $this;
    }

    /**
     * Add multiple items to the queue
     *
     * @param array $items Items to add
     */
    public function addMultiple(array $items)
    {
        foreach ($items as $item) {
            if ($item instanceof Mediotype_MagentoGuzzle_Model_Exception_RequestException) {
                $this->addException($item);
            } else {
                $this->addResponse($item);
            }
        }
    }

    /**
     * Clear the queue
     */
    public function clearQueue()
    {
        $this->queue = array();
    }
}
