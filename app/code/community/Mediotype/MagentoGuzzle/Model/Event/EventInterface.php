<?php
/**
 * Base event interface used when dispatching events to listeners using an
 * event emitter.
 */
interface Mediotype_MagentoGuzzle_Model_Event_EventInterface
{
    /**
     * Returns whether or not stopPropagation was called on the event.
     *
     * @return bool
     * @see Event::stopPropagation
     */
    public function isPropagationStopped();

    /**
     * Stops the propagation of the event, preventing subsequent listeners
     * registered to the same event from being invoked.
     */
    public function stopPropagation();
}
