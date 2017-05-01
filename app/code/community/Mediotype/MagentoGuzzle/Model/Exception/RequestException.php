<?php
/**
 * HTTP Request exception
 */
class Mediotype_MagentoGuzzle_Model_Exception_RequestException extends Mediotype_MagentoGuzzle_Model_Exception_TransferException

{
    /** @var bool */
    private $emittedErrorEvent = false;

    /** @var Mediotype_MagentoGuzzle_Model_Message_RequestInterface */
    private $request;

    /** @var Mediotype_MagentoGuzzle_Model_Message_ResponseInterface */
    private $response;

    /** @var bool */
    private $throwImmediately = false;

    public function __construct(
        $message = '',
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response = null,
        Exception $previous = null
    ) {
        $code = $response ? $response->getStatusCode() : 0;
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface  $request  Request
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response Response received
     * @param \Exception        $previous Previous exception
     *
     * @return self
     */
    public static function create(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response = null,
        Exception $previous = null
    ) {
        if (!$response) {
            return new self('Error completing request', $request, null, $previous);
        }

        $level = $response->getStatusCode()[0];
        if ($level == '4') {
            $label = 'Client error response';
            $className = 'Mediotype_MagentoGuzzle_Model_Exception_ClientException';
        } elseif ($level == '5') {
            $label = 'Server error response';
            $className = 'Mediotype_MagentoGuzzle_Model_Exception_ServerException';
        } else {
            $label = 'Unsuccessful response';
            $className = __CLASS__;
        }

        $message = $label . ' [url] ' . $request->getUrl()
            . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new $className($message, $request, $response, $previous);
    }

    /**
     * Get the request that caused the exception
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the associated response
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Check if a response was received
     *
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * Check or set if the exception was emitted in an error event.
     *
     * This value is used in the RequestEvents::emitBefore() method to check
     * to see if an exception has already been emitted in an error event.
     *
     * @param bool|null Set to true to set the exception as having emitted an
     *     error. Leave null to retrieve the current setting.
     *
     * @return null|bool
     * @throws \InvalidArgumentException if you attempt to set the value to false
     */
    public function emittedError($value = null)
    {
        if ($value === null) {
            return $this->emittedErrorEvent;
        } elseif ($value === true) {
            $this->emittedErrorEvent = true;
        } else {
            throw new InvalidArgumentException('You cannot set the emitted '
                . 'error value to false.');
        }
    }

    /**
     * Sets whether or not parallel adapters SHOULD throw the exception
     * immediately rather than handling errors through asynchronous error
     * handling.
     *
     * @param bool $throwImmediately
     *
     */
    public function setThrowImmediately($throwImmediately)
    {
        $this->throwImmediately = $throwImmediately;
    }

    /**
     * Gets the setting specified by setThrowImmediately().
     *
     * @return bool
     */
    public function getThrowImmediately()
    {
        return $this->throwImmediately;
    }
}
