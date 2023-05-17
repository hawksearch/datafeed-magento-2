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
    public function setOptionsUsed(TaskOptions $optionsUsed) : void
    {
        $this->optionsUsed = $optionsUsed;
    }
}
