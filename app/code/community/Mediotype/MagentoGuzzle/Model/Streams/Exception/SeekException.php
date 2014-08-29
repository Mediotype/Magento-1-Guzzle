<?php
/**
 * Exception thrown when a seek fails on a stream.
 */
class Mediotype_MagentoGuzzle_Model_Streams_Exception_SeekException extends RuntimeException
{
    private $stream;

    public function __construct(Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream, $pos = 0, $msg = '')
    {
        $this->stream = $stream;
        $msg = $msg ?: 'Could not seek the stream to position ' . $pos;
        parent::__construct($msg);
    }

    /**
     * @return Mediotype_MagentoGuzzle_Model_Streams_StreamInterface
     */
    public function getStream()
    {
        return $this->stream;
    }
}
