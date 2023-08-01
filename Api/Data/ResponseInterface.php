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
declare(strict_types=1);

namespace HawkSearch\Datafeed\Api\Data;

/**
 * Interface ResponseInterface
 * @api
 */
interface ResponseInterface
{
    /**#@+
     * Constants for keys of data array
     */
    const STATUS = 'status';
    const MESSAGE = 'message';
    const RESPONSE_DATA = 'response_data';
    /**#@-*/

    /**
     * @return string
     */
    public function getStatus() : string;

    /**
     * @param string $value
     * @return $this
     */
    public function setStatus(string $value);

    /**
     * @return string
     */
    public function getMessage() : string;

    /**
     * @param string $value
     * @return $this
     */
    public function setMessage(string $value);

    /**
     * @return mixed
     */
    public function getResponseData();

    /**
     * @param mixed $value
     * @return $this
     */
    public function setResponseData($value);
}
