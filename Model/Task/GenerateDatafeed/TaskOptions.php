<?php


namespace HawkSearch\Datafeed\Model\Task\GenerateDatafeed;


class TaskOptions
{
    /** @var bool */
    private $forceMode = false;

    /**
     * @return bool
     */
    public function isForceMode() : bool
    {
        return $this->forceMode;
    }

    /**
     * @param bool $forceMode
     */
    public function setForceMode( bool $forceMode ) : void
    {
        $this->forceMode = $forceMode;
    }
}
