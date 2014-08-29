<?php
/**
 * Exception when a client is unable to parse the response body as XML or JSON
 */
class Mediotype_MagentoGuzzle_Model_Exception_ParseException extends Mediotype_MagentoGuzzle_Model_Exception_TransferException

{
    /** @var Mediotype_MagentoGuzzle_Model_Message_ResponseInterface */
    private $response;

    public function __construct(
        $message = '',
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response = null,
        Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->response = $response;
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
}
