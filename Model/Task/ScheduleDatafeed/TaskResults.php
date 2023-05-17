<?php
/**
 * Copyright (c) 2023 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace HawkSearch\Datafeed\Model\Task\ScheduleDatafeed;

class TaskResults
{
    /** @var int Job Entity ID */
    private $jobEntityId = 0;

    /** @var string Job creation timestamp */
    private $createdAt = '';

    /** @var string Job scheduled-at timestamp */
    private $scheduledAt = '';

    /**
     * @return int
     */
    public function getJobEntityId() : int
    {
        return $this->jobEntityId;
    }

    /**
     * @param int $jobEntityId
     */
    public function setJobEntityId(int $jobEntityId) : void
    {
        $this->jobEntityId = $jobEntityId;
    }

    /**
     * @return string
     */
    public function getCreatedAt() : string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt) : void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getScheduledAt() : string
    {
        return $this->scheduledAt;
    }

    /**
     * @param string $scheduledAt
     */
    public function setScheduledAt(string $scheduledAt) : void
    {
        $this->scheduledAt = $scheduledAt;
    }
}
