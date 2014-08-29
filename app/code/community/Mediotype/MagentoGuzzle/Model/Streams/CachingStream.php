<?php
/**
 * Stream decorator that can cache previously read bytes from a sequentially
 * read stream.
 */
class Mediotype_MagentoGuzzle_Model_Streams_CachingStream implements Mediotype_MagentoGuzzle_Model_Streams_StreamInterface, Mediotype_MagentoGuzzle_Model_Streams_MetadataStreamInterface
{
    use Mediotype_MagentoGuzzle_Trait_Streams_StreamDecoratorTrait;

    /** @var Mediotype_MagentoGuzzle_Model_Streams_StreamInterface Stream being wrapped */
    private $remoteStream;

    /** @var int Number of bytes to skip reading due to a write on the buffer */
    private $skipReadBytes = 0;

    /**
     * We will treat the buffer object as the body of the stream
     *
     * @param Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream Stream to cache
     * @param Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $target Optionally specify where data is cached
     */
    public function __construct(
        Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream,
        Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $target = null
    ) {
        $this->remoteStream = $stream;
        $this->stream = $target ?: new Mediotype_MagentoGuzzle_Model_Streams_Stream(fopen('php://temp', 'r+'));
    }

    public function getSize()
    {
        return max($this->stream->getSize(), $this->remoteStream->getSize());
    }

    /**
     * {@inheritdoc}
     * @throws Mediotype_MagentoGuzzle_Model_Streams_Exception_SeekException When seeking with SEEK_END or when seeking
     *     past the total size of the buffer stream
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence == SEEK_SET) {
            $byte = $offset;
        } elseif ($whence == SEEK_CUR) {
            $byte = $offset + $this->tell();
        } else {
            return false;
        }

        // You cannot skip ahead past where you've read from the remote stream
        if ($byte > $this->stream->getSize()) {
            throw new Mediotype_MagentoGuzzle_Model_Streams_Exception_SeekException(
                $this,
                $byte,
                sprintf('Cannot seek to byte %d when the buffered stream only'
                    . ' contains %d bytes', $byte, $this->stream->getSize())
            );
        }

        return $this->stream->seek($byte);
    }

    public function read($length)
    {
        // Perform a regular read on any previously read data from the buffer
        $data = $this->stream->read($length);
        $remaining = $length - strlen($data);

        // More data was requested so read from the remote stream
        if ($remaining) {
            // If data was written to the buffer in a position that would have
            // been filled from the remote stream, then we must skip bytes on
            // the remote stream to emulate overwriting bytes from that
            // position. This mimics the behavior of other PHP stream wrappers.
            $remoteData = $this->remoteStream->read(
                $remaining + $this->skipReadBytes
            );

            if ($this->skipReadBytes) {
                $len = strlen($remoteData);
                $remoteData = substr($remoteData, $this->skipReadBytes);
                $this->skipReadBytes = max(0, $this->skipReadBytes - $len);
            }

            $data .= $remoteData;
            $this->stream->write($remoteData);
        }

        return $data;
    }

    public function write($string)
    {
        // When appending to the end of the currently read stream, you'll want
        // to skip bytes from being read from the remote stream to emulate
        // other stream wrappers. Basically replacing bytes of data of a fixed
        // length.
        $overflow = (strlen($string) + $this->tell()) - $this->remoteStream->tell();
        if ($overflow > 0) {
            $this->skipReadBytes += $overflow;
        }

        return $this->stream->write($string);
    }

    public function eof()
    {
        return $this->stream->eof() && $this->remoteStream->eof();
    }

    /**
     * Close both the remote stream and buffer stream
     */
    public function close()
    {
        $this->remoteStream->close() && $this->stream->close();
    }
}
