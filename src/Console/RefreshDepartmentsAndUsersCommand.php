<?php
/**
 * 获取钉钉所有部门组和员工
 * @author srako
 * @date 2024/11/20 14:33
 * @page http://srako.github.io
 */

namespace Webman\DingTalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webman\DingTalk\Services\DingTalkService;

class RefreshDepartmentsAndUsersCommand extends Command
{
    protected static $defaultName = 'dingtalk:RefreshDepartmentsAndUsers';
    protected static $defaultDescription = 'Refresh departments and users';


    protected function configure()
    {
        $this->addArgument('corp_id', InputArgument::OPTIONAL, '钉钉企业ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $corpId = $input->getArgument('corp_id') ?: config('plugin.srako.dingtalk.app.corpid');

        $output->writeln("Refresh departments and users for corp_id: $corpId");
        $dingTalkService = new DingTalkService($corpId);
        $dingTalkService->syncDepartmentsAndUsers();
        return 0;
    }
}
