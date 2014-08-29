<?php
/**
 * Uses PHP's zlib.inflate filter to inflate deflate or gzipped content.
 *
 * This stream decorator skips the first 10 bytes of the given stream to remove
 * the gzip header, converts the provided stream to a PHP stream resource,
 * then appends the zlib.inflate filter. The stream is then converted back
 * to a Guzzle stream resource to be used as a Guzzle stream.
 *
 * @link http://tools.ietf.org/html/rfc1952
 * @link http://php.net/manual/en/filters.compression.php
 */
class Mediotype_MagentoGuzzle_Model_Streams_InflateStream implements Mediotype_MagentoGuzzle_Model_Streams_MetadataStreamInterface
{
    use Mediotype_MagentoGuzzle_Trait_Streams_StreamDecoratorTrait;

    public function __construct(Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream)
    {
        // Skip the first 10 bytes
        $stream = new Mediotype_MagentoGuzzle_Model_Streams_LimitStream($stream, -1, 10);
        $resource = Mediotype_MagentoGuzzle_Model_Streams_GuzzleStreamWrapper::getResource($stream);
        stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ);
        $this->stream = new Mediotype_MagentoGuzzle_Model_Streams_Stream($resource);
    }
}
