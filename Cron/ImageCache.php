<?php
/**
 * Created by PhpStorm.
 * User: astayart
 * Date: 10/18/18
 * Time: 8:41 AM
 */

namespace HawkSearch\Datafeed\Cron;


class ImageCache
{
    public function cronGenerateImagecache()
    {
        if ($this->helper->getCronImagecacheEnable()) {
            $vars = [];
            $vars['jobTitle'] = 'Imagecache';
            if ($this->helper->isFeedLocked()) {
                $vars['message'] = "Hawksearch is currently locked, not generating the Imagecache at this time.";
            } else {
                try {
                    $this->helper->createFeedLocks();
                    $this->refreshImageCache();
                    $vars['message'] = "HawkSeach Imagecache Generated!";
                } catch (\Exception $e) {
                    $vars['message'] = sprintf('There has been an error: %s', $e->getMessage());
                    $this->helper->removeFeedLocks();
                }
            }
            $this->email->sendEmail($vars);
        }
    }

}