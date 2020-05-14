<?php


namespace HawkSearch\Datafeed\Model\Task\Datafeed;


class TaskResults
{
    /** @var TaskOptions */
    private $optionsUsed;

    /**
     * @return TaskOptions
     */
    public function getOptionsUsed() : TaskOptions
    {
        return $this->optionsUsed;
    }

    /**
     * @param TaskOptions $optionsUsed
     */
    public function setOptionsUsed( TaskOptions $optionsUsed ) : void
    {
        $this->optionsUsed = $optionsUsed;
    }
}
