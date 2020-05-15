<?php
/**
 * Copyright (c) 2018 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
$opts = getopt('r:t:i:');

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();

$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

/**
 * @var \HawkSearch\Datafeed\Helper\Data $helper
*/
$helper = $obj->get('HawkSearch\Datafeed\Helper\Data');

if (isset($opts['i'])) {
    if ($helper->isImageCacheLocked()) {
        throw new \Exception('Image Cache currently locked. Image Cache not regenerating.');
    }
    if ($helper->createImageCacheLocks($opts['t'])) {
        $ic = $obj->get('HawkSearch\Datafeed\Model\ImageCache');
        $ic->refreshImageCache();
        $helper->removeImageCacheLocks($opts['t']);
    }
} else {
    if ($helper->isFeedLocked()) {
        throw new \Exception('One or more feeds are being generated. Generation temporarily locked.');
    }
    if ($helper->createFeedLocks($opts['t'])) {
        $datafeed = $obj->get('HawkSearch\Datafeed\Model\Datafeed');
        $datafeed->generateFeed();
        $helper->removeFeedLocks($opts['t']);
    }
}

unlink($opts['t']);
