<?php
/**
 * Created by PhpStorm.
 * User: astayart
 * Date: 10/17/18
 * Time: 3:27 PM
 */

namespace HawkSearch\Datafeed\Console\Command;

use HawkSearch\Datafeed\Model\Datafeed as DatafeedTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use HawkSearch\Datafeed\Helper\Data as Helper;


class DataFeed extends Command
{
    const FORCE_MODE = 'force';
    /**
     * @var Task
     */
    private $task;
    /**
     * @var State
     */
    private $state;
    /**
     * @var Helper
     */
    private $helper;

    protected function configure()
    {
        $this->setName('hawksearch:generate-feed')
            ->setDescription('Generate the HawkSearch data feed')
            ->setDefinition([
                new InputOption(
                    self::FORCE_MODE,
                    ['-f', '--force'],
                    InputOption::VALUE_NONE,
                    'Force datafeed to run even if lock present.'
                )
            ]);
        parent::configure();
    }

    public function __construct(
        DatafeedTask $task,
        State $state,
        Helper $helper,
        $name = null)
    {
        parent::__construct($name);
        $this->task = $task;
        $this->state = $state;
        $this->helper = $helper;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        if ($input->getOption(self::FORCE_MODE) === self::FORCE_MODE) {
            $this->helper->removeFeedLocks(true);
        }
        if ($this->helper->createFeedLocks('datafeed')) {
            $this->task->generateFeed();
        } else {
            $output->writeln("Unable to create feed lock file, feed not generating");
        }

        $output->writeln("Done");
    }
}