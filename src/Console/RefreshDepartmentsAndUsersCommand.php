<?php

namespace Webman\DingTalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webman\DingTalk\DingMessage;
use Webman\DingTalk\Services\DingTalkService;

class RefreshDepartmentsAndUsersCommand extends Command
{
    protected static $defaultName = 'dingtalk:RefreshDepartmentsAndUsers';
    protected static $defaultDescription = 'Refresh departments and users';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $dept_ids = DingTalkService::departmentIds();
        DingMessage::dispatch([
            'CorpId' => config('plugin.srako.dingtalk.app.corpid'),
            'EventType' => 'org_dept_modify',
            'DeptId' => $dept_ids
        ]);
        foreach ($dept_ids as $dept_id) {
            $user_ids = DingTalkService::getDeptUserIds($dept_id);
            if (blank($user_ids)) {
                continue;
            }
            DingMessage::dispatch([
                'CorpId' => config('plugin.srako.dingtalk.app.corpid'),
                'EventType' => 'user_modify_org',
                'UserId' => $user_ids
            ]);
        }

        return 0;
    }
}
