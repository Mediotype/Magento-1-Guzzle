<?php
/**
 * Converts a sequence of request objects into a transaction.
 * @internal
 */
class Mediotype_MagentoGuzzle_Model_Adapter_TransactionIterator implements Iterator
{
    use Mediotype_MagentoGuzzle_Trait_Event_ListenerAttacherTrait;

    /** @var Iterator */
    private $source;

    /** @var Mediotype_MagentoGuzzle_Model_ClientInterface */
    private $client;

    /** @var array Listeners to attach to each request */
    private $eventListeners = array();

    public function __construct(
        $source,
        Mediotype_MagentoGuzzle_Model_ClientInterface $client,
        array $options
    ) {
        $this->client = $client;
        $this->eventListeners = $this->prepareListeners(
            $options,
            ['before', 'complete', 'error']
        );
        if ($source instanceof Iterator) {
            $this->source = $source;
        } elseif (is_array($source)) {
            $this->source = new ArrayIterator($source);
        } else {
            throw new InvalidArgumentException('Expected an Iterator or array');
        }
    }

    public function current()
    {
        $request = $this->source->current();
        if (!$request instanceof Mediotype_MagentoGuzzle_Model_Message_RequestInterface) {
            throw new RuntimeException('All must implement RequestInterface');
        }

        $this->attachListeners($request, $this->eventListeners);

        return new Mediotype_MagentoGuzzle_Model_Adapter_Transaction($this->client, $request);
    }

    public function next()
    {
        $this->source->next();
    }

    public function key()
    {
        return $this->source->key();
    }

    public function valid()
    {
        return $this->source->valid();
    }

    public function rewind()
    {
        if (!($this->source instanceof \Generator)) {
            $this->source->rewind();
        }
    }
}
