<?php
/**
 * HTTP adapter that uses PHP's HTTP stream wrapper.
 *
 * When using the StreamAdapter, custom stream context options can be specified
 * using the **stream_context** option in a request's **config** option. The
 * structure of the "stream_context" option is an associative array where each
 * key is a transport name and each option is an associative array of options.
 */
class Mediotype_MagentoGuzzle_Model_Adapter_StreamAdapter implements Mediotype_MagentoGuzzle_Model_Adapter_AdapterInterface
{
    /** @var Mediotype_MagentoGuzzle_Model_Message_MessageFactoryInterface */
    private $messageFactory;

    /**
     * @param Mediotype_MagentoGuzzle_Model_Message_MessageFactoryInterface $messageFactory
     */
    public function __construct(Mediotype_MagentoGuzzle_Model_Message_MessageFactoryInterface $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    public function send(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        // HTTP/1.1 streams using the PHP stream wrapper require a
        // Connection: close header. Setting here so that it is added before
        // emitting the request.before_send event.
        $request = $transaction->getRequest();
        if ($request->getProtocolVersion() == '1.1' &&
            !$request->hasHeader('Connection')
        ) {
            $transaction->getRequest()->setHeader('Connection', 'close');
        }

        Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitBefore($transaction);
        if (!$transaction->getResponse()) {
            $this->createResponse($transaction);
            Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitComplete($transaction);
        }

        return $transaction->getResponse();
    }

    private function createResponse(Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction)
    {
        $request = $transaction->getRequest();
        $stream = $this->createStream($request, $http_response_header);
        $this->createResponseObject(
            $request,
            $http_response_header,
            $transaction,
            new Mediotype_MagentoGuzzle_Model_Streams_Stream($stream)
        );
    }

    private function createResponseObject(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        array $headers,
        Mediotype_MagentoGuzzle_Model_Adapter_TransactionInterface $transaction,
        Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream
    ) {
        $parts = explode(' ', array_shift($headers), 3);
        $options = ['protocol_version' => substr($parts[0], -3)];

        if (isset($parts[2])) {
            $options['reason_phrase'] = $parts[2];
        }

        $response = $this->messageFactory->createResponse(
            $parts[1],
            $this->headersFromLines($headers),
            null,
            $options
        );

        // Automatically decode responses when instructed.
        if ($request->getConfig()->get('decode_content')) {
            switch ($response->getHeader('Content-Encoding')) {
                case 'gzip':
                case 'deflate':
                    $stream = new Mediotype_MagentoGuzzle_Model_Streams_InflateStream($stream);
                    break;
            }
        }

        // Drain the stream immediately if 'stream' was not enabled.
        if (!$request->getConfig()['stream']) {
            $stream = $this->getSaveToBody($request, $stream);
        }

        $response->setBody($stream);
        $transaction->setResponse($response);
        Mediotype_MagentoGuzzle_Model_Event_RequestEvents::emitHeaders($transaction);

        return $response;
    }

    /**
     * Drain the stream into the destination stream
     */
    private function getSaveToBody(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        Mediotype_MagentoGuzzle_Model_Streams_StreamInterface $stream
    ) {
        if ($saveTo = $request->getConfig()['save_to']) {
            // Stream the response into the destination stream
            $saveTo = is_string($saveTo)
                ? new Mediotype_MagentoGuzzle_Model_Streams_Stream(Mediotype_MagentoGuzzle_Model_Streams_Utils::open($saveTo, 'r+'))
                : Mediotype_MagentoGuzzle_Model_Streams_Stream::factory($saveTo);
        } else {
            // Stream into the default temp stream
            $saveTo = Mediotype_MagentoGuzzle_Model_Streams_Stream::factory();
        }

        Mediotype_MagentoGuzzle_Model_Streams_Utils::copyToStream($stream, $saveTo);
        $saveTo->seek(0);
        $stream->close();

        return $saveTo;
    }

    private function headersFromLines(array $lines)
    {
        $responseHeaders = [];

        foreach ($lines as $line) {
            $headerParts = explode(':', $line, 2);
            $responseHeaders[$headerParts[0]][] = isset($headerParts[1])
                ? trim($headerParts[1])
                : '';
        }

        return $responseHeaders;
    }

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable         $callback Callable that returns stream resource
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request  Request used when throwing exceptions
     * @param array            $options  Options used when throwing exceptions
     *
     * @return resource
     * @throws Mediotype_MagentoGuzzle_Model_Exception_RequestException on error
     */
    private function createResource(callable $callback, Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, $options)
    {
        // Turn off error reporting while we try to initiate the request
        $level = error_reporting(0);
        $resource = call_user_func($callback);
        error_reporting($level);

        // If the resource could not be created, then grab the last error and
        // throw an exception.
        if (!is_resource($resource)) {
            $message = 'Error creating resource. [url] ' . $request->getUrl() . ' ';
            if (isset($options['http']['proxy'])) {
                $message .= "[proxy] {$options['http']['proxy']} ";
            }
            foreach ((array) error_get_last() as $key => $value) {
                $message .= "[{$key}] {$value} ";
            }
            throw new Mediotype_MagentoGuzzle_Model_Exception_RequestException(trim($message), $request);
        }

        return $resource;
    }

    /**
     * Create the stream for the request with the context options.
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request              Request being sent
     * @param mixed            $http_response_header Populated by stream wrapper
     *
     * @return resource
     */
    private function createStream(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        &$http_response_header
    ) {
        static $methods;
        if (!$methods) {
            $methods = array_flip(get_class_methods(__CLASS__));
        }

        $params = [];
        $options = $this->getDefaultOptions($request);
        foreach ($request->getConfig()->toArray() as $key => $value) {
            $method = "add_{$key}";
            if (isset($methods[$method])) {
                $this->{$method}($request, $options, $value, $params);
            }
        }

        $this->applyCustomOptions($request, $options);
        $context = $this->createStreamContext($request, $options, $params);

        return $this->createStreamResource(
            $request,
            $options,
            $context,
            $http_response_header
        );
    }

    private function getDefaultOptions(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request)
    {
        $headers = Mediotype_MagentoGuzzle_Model_Message_AbstractMessage::getHeadersAsString($request);

        $context = array(
            'http' => array(
                'method'           => $request->getMethod(),
                'header'           => trim($headers),
                'protocol_version' => $request->getProtocolVersion(),
                'ignore_errors'    => true,
                'follow_location'  => 0
            )
        );

        if ($body = $request->getBody()) {
            $context['http']['content'] = (string) $body;
            // Prevent the HTTP adapter from adding a Content-Type header.
            if (!$request->hasHeader('Content-Type')) {
                $context['http']['header'] .= "\r\nContent-Type:";
            }
        }

        return $context;
    }

    private function add_proxy(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, &$options, $value, &$params)
    {
        if (!is_array($value)) {
            $options['http']['proxy'] = $value;
            $options['http']['request_fulluri'] = true;
        } else {
            $scheme = $request->getScheme();
            if (isset($value[$scheme])) {
                $options['http']['proxy'] = $value[$scheme];
                $options['http']['request_fulluri'] = true;
            }
        }
    }

    private function add_timeout(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, &$options, $value, &$params)
    {
        $options['http']['timeout'] = $value;
    }

    private function add_verify(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, &$options, $value, &$params)
    {
        if ($value === true || is_string($value)) {
            $options['http']['verify_peer'] = true;
            if ($value !== true) {
                if (!file_exists($value)) {
                    throw new RuntimeException("SSL certificate authority file not found: {$value}");
                }
                $options['http']['allow_self_signed'] = true;
                $options['http']['cafile'] = $value;
            }
        } elseif ($value === false) {
            $options['http']['verify_peer'] = false;
        }
    }

    private function add_cert(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, &$options, $value, &$params)
    {
        if (is_array($value)) {
            $options['http']['passphrase'] = $value[1];
            $value = $value[0];
        }

        if (!file_exists($value)) {
            throw new RuntimeException("SSL certificate not found: {$value}");
        }

        $options['http']['local_cert'] = $value;
    }

    private function add_debug(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request, &$options, $value, &$params)
    {
        static $map = array(
            STREAM_NOTIFY_CONNECT       => 'CONNECT',
            STREAM_NOTIFY_AUTH_REQUIRED => 'AUTH_REQUIRED',
            STREAM_NOTIFY_AUTH_RESULT   => 'AUTH_RESULT',
            STREAM_NOTIFY_MIME_TYPE_IS  => 'MIME_TYPE_IS',
            STREAM_NOTIFY_FILE_SIZE_IS  => 'FILE_SIZE_IS',
            STREAM_NOTIFY_REDIRECTED    => 'REDIRECTED',
            STREAM_NOTIFY_PROGRESS      => 'PROGRESS',
            STREAM_NOTIFY_FAILURE       => 'FAILURE',
            STREAM_NOTIFY_COMPLETED     => 'COMPLETED',
            STREAM_NOTIFY_RESOLVE       => 'RESOLVE'
        );

        static $args = array('severity', 'message', 'message_code',
            'bytes_transferred', 'bytes_max');

        if (!is_resource($value)) {
            $value = fopen('php://output', 'w');
        }

        $params['notification'] = function () use ($request, $value, $map, $args) {
            $passed = func_get_args();
            $code = array_shift($passed);
            fprintf($value, '<%s> [%s] ', $request->getUrl(), $map[$code]);
            foreach (array_filter($passed) as $i => $v) {
                fwrite($value, $args[$i] . ': "' . $v . '" ');
            }
            fwrite($value, "\n");
        };
    }

    private function applyCustomOptions(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        array &$options
    ) {
        // Overwrite any generated options with custom options
        if ($custom = $request->getConfig()['stream_context']) {
            if (!is_array($custom)) {
                throw new Mediotype_MagentoGuzzle_Model_Exception_AdapterException('stream_context must be an array');
            }
            $options = array_replace_recursive($options, $custom);
        }
    }

    private function createStreamContext(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        array $options,
        array $params
    ) {
        return $this->createResource(
            function () use ($request, $options, $params) {
                return stream_context_create($options, $params);
            },
            $request,
            $options
        );
    }

    private function createStreamResource(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        array $options,
        $context,
        &$http_response_header
    ) {
        $url = $request->getUrl();

        return $this->createResource(
            function () use ($url, &$http_response_header, $context) {
                if (false === strpos($url, 'http')) {
                    trigger_error("URL is invalid: {$url}", E_USER_WARNING);
                    return null;
                }
                return fopen($url, 'r', null, $context);
            },
            $request,
            $options
        );
    }
}
