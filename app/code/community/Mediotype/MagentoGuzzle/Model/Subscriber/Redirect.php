<?php
/**
 * Subscriber used to implement HTTP redirects.
 *
 * **Request options**
 *
 * - redirect: Associative array containing the 'max', 'strict', and 'referer'
 *   keys.
 *
 *   - max: Maximum number of redirects allowed per-request
 *   - strict: You can use strict redirects by setting this value to ``true``.
 *     Strict redirects adhere to strict RFC compliant redirection (e.g.,
 *     redirect POST with POST) vs doing what most clients do (e.g., redirect
 *     POST request with a GET request).
 *   - referer: Set to true to automatically add the "Referer" header when a
 *     redirect request is sent.
 */
class Mediotype_MagentoGuzzle_Model_Subscriber_Redirect implements Mediotype_MagentoGuzzle_Model_Event_SubscriberInterface
{
    public function getEvents()
    {
        return array('complete' => array('onComplete', Mediotype_MagentoGuzzle_Model_Event_RequestEvents::REDIRECT_RESPONSE));
    }

    /**
     * Rewind the entity body of the request if needed
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface $redirectRequest
     * @throws Mediotype_MagentoGuzzle_Model_Exception_CouldNotRewindStreamException
     */
    public static function rewindEntityBody(Mediotype_MagentoGuzzle_Model_Message_RequestInterface $redirectRequest)
    {
        // Rewind the entity body of the request if needed
        if ($redirectRequest->getBody()) {
            $body = $redirectRequest->getBody();
            // Only rewind the body if some of it has been read already, and
            // throw an exception if the rewind fails
            if ($body->tell() && !$body->seek(0)) {
                throw new Mediotype_MagentoGuzzle_Model_Exception_CouldNotRewindStreamException(
                    'Unable to rewind the non-seekable request body after redirecting',
                    $redirectRequest
                );
            }
        }
    }

    /**
     * Called when a request receives a redirect response
     *
     * @param Mediotype_MagentoGuzzle_Model_Event_CompleteEvent $event Event emitted
     * @throws Mediotype_MagentoGuzzle_Model_Exception_TooManyRedirectsException
     */
    public function onComplete(Mediotype_MagentoGuzzle_Model_Event_CompleteEvent $event)
    {
        $response = $event->getResponse();

        if (substr($response->getStatusCode(), 0, 1) != '3' ||
            !$response->hasHeader('Location')
        ) {
            return;
        }

        $redirectCount = 0;
        $request = $event->getRequest();
        $redirectResponse = $response;
        $max = $request->getConfig()->getPath('redirect/max') ?: 5;

        do {
            if (++$redirectCount > $max) {
                throw new Mediotype_MagentoGuzzle_Model_Exception_TooManyRedirectsException(
                    "Will not follow more than {$redirectCount} redirects",
                    $request
                );
            }
            $redirectRequest = $this->createRedirectRequest($request, $redirectResponse);
            $redirectResponse = $event->getClient()->send($redirectRequest);
        } while (substr($redirectResponse->getStatusCode(), 0, 1) == '3' &&
            $redirectResponse->hasHeader('Location')
        );

        if ($redirectResponse !== $response) {
            $event->intercept($redirectResponse);
        }
    }

    /**
     * Create a redirect request for a specific request object
     *
     * Takes into account strict RFC compliant redirection (e.g. redirect POST
     * with POST) vs doing what most clients do (e.g. redirect POST with GET).
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface  $request
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response
     *
     * @return Mediotype_MagentoGuzzle_Model_Message_RequestInterface Returns a new redirect request
     * @throws Mediotype_MagentoGuzzle_Model_Exception_CouldNotRewindStreamException If the body cannot be rewound.
     */
    private function createRedirectRequest(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $request,
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response
    ) {
        $config = $request->getConfig();

        // Use a GET request if this is an entity enclosing request and we are
        // not forcing RFC compliance, but rather emulating what all browsers
        // would do. Be sure to disable redirects on the clone.
        $redirectRequest = clone $request;
        $redirectRequest->getEmitter()->detach($this);
        $statusCode = $response->getStatusCode();

        if ($statusCode == 303 ||
            ($statusCode <= 302 && $request->getBody() &&
                !$config->getPath('redirect/strict'))
        ) {
            $redirectRequest->setMethod('GET');
            $redirectRequest->setBody(null);
        }

        $this->setRedirectUrl($redirectRequest, $response);
        $this->rewindEntityBody($redirectRequest);

        // Add the Referer header if it is told to do so and only
        // add the header if we are not redirecting from https to http.
        if ($config->getPath('redirect/referer') && (
            $redirectRequest->getScheme() == 'https' ||
            $redirectRequest->getScheme() == $request->getScheme()
        )) {
            $url = Mediotype_MagentoGuzzle_Model_Url::fromString($request->getUrl());
            $url->setUsername(null)->setPassword(null);
            $redirectRequest->setHeader('Referer', (string) $url);
        }

        return $redirectRequest;
    }

    /**
     * Set the appropriate URL on the request based on the location header
     *
     * @param Mediotype_MagentoGuzzle_Model_Message_RequestInterface  $redirectRequest
     * @param Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response
     */
    private function setRedirectUrl(
        Mediotype_MagentoGuzzle_Model_Message_RequestInterface $redirectRequest,
        Mediotype_MagentoGuzzle_Model_Message_ResponseInterface $response
    ) {
        $location = $response->getHeader('Location');
        $location = Mediotype_MagentoGuzzle_Model_Url::fromString($location);

        // Combine location with the original URL if it is not absolute.
        if (!$location->isAbsolute()) {
            $originalUrl = Mediotype_MagentoGuzzle_Model_Url::fromString($redirectRequest->getUrl());
            // Remove query string parameters and just take what is present on
            // the redirect Location header
            $originalUrl->getQuery()->clear();
            $location = $originalUrl->combine($location);
        }

        $redirectRequest->setUrl($location);
    }
}
