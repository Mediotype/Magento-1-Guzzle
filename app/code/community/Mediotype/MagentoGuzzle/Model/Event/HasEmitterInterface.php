<?php
/**
 * Holds an event emitter
 */
interface Mediotype_MagentoGuzzle_Model_Event_HasEmitterInterface
{
    /**
     * Get the event emitter of the object
     *
     * @return Mediotype_MagentoGuzzle_Model_Event_EmitterInterface
     */
    public function getEmitter();
}
