<?php
/**
 * Basic event class that can be extended.
 */
abstract class Mediotype_MagentoGuzzle_Model_Event_AbstractEvent implements Mediotype_MagentoGuzzle_Model_Event_EventInterface
{
    private $propagationStopped = false;

    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }

    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }
}
