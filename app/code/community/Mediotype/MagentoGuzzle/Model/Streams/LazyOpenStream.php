<?php
/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 */
class Mediotype_MagentoGuzzle_Model_Streams_LazyOpenStream implements Mediotype_MagentoGuzzle_Model_Streams_StreamInterface, Mediotype_MagentoGuzzle_Model_Streams_MetadataStreamInterface
{
    use Mediotype_MagentoGuzzle_Trait_Streams_StreamDecoratorTrait;

    /** @var string File to open */
    private $filename;

    /** @var string $mode */
    private $mode;

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return Mediotype_MagentoGuzzle_Model_Streams_StreamInterface
     */
    protected function createStream()
    {
        return Mediotype_MagentoGuzzle_Model_Streams_Stream::factory(Mediotype_MagentoGuzzle_Model_Streams_Utils::open($this->filename, $this->mode));
    }
}
