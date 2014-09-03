<?php
/**
 * Trait that implements the methods of HasEmitterInterface
 */
trait Mediotype_MagentoGuzzle_Trait_Event_HasEmitterTrait
{
    /** @var EmitterInterface */
    private $emitter;

    public function getEmitter()
    {
        if (!$this->emitter) {
            $this->emitter = new Mediotype_MagentoGuzzle_Model_Event_Emitter();
        }

        return $this->emitter;
    }
}
