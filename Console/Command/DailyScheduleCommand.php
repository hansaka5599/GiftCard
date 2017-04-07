<?php
/**
 * Netstarter Pty Ltd.
 *
 * @category    Rag
 * @package     Rag_GiftCard
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2016 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */
namespace Rag\GiftCard\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DailyScheduleCommand.
 */
class DailyScheduleCommand extends Command
{
    /**
     * App state
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * Cron process
     *
     * @var \Rag\GiftCard\Cron\DailySchedule
     */
    protected $cronProcess;

    /**
     * DailyScheduleCommand constructor.
     *
     * @param \Magento\Framework\App\State     $appState
     * @param \Rag\GiftCard\Cron\DailySchedule $cron
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Rag\GiftCard\Cron\DailySchedule $cron
    ) {
        $this->appState = $appState;
        $this->cronProcess = $cron;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cron:send-giftcards')
            ->setDescription('Send scheduled gift-cards');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cronProcess->execute();
    }
}
