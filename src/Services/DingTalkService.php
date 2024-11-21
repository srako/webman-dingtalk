<?php

namespace Webman\DingTalk\Services;

use Illuminate\Support\Collection;
use Webman\DingTalk\DingTalk;
use Webman\DingTalk\Exceptions\RequestException;
use Webman\DingTalk\Messages\DingSyncMessage;

class DingTalkService
{

    public function __construct(private string $corpId)
    {
    }

    /**
     * 获取钉钉所有部门ID
     * @return array
     * @throws RequestException
     */
    public function departmentIds(): array
    {
        return array_merge($this->getDepartmentAllSubIds(1), [1]);
    }

    /**
     * 获取递归下级部门ID
     * @param int $dept_id
     * @return array
     * @throws RequestException
     */
    public function getDepartmentAllSubIds(int $deptId): array
    {
        $res = DingTalk::corp($this->corpId)->post('/topapi/v2/department/listsubid', ['dept_id' => $deptId]);
        $subDeptIds = $res->result->dept_id_list;
        foreach ($subDeptIds as $id) {
            $subDeptIds = array_merge($subDeptIds, $this->getDepartmentAllSubIds($id));
        }
        return $subDeptIds;
    }

    /**
     * 获取部门下UserIds
     * @param int $deptId
     * @return array
     * @throws RequestException
     */
    public function getDeptUserIds(int $deptId): array
    {
        $res = DingTalk::corp($this->corpId)->post('/topapi/user/listid', ['dept_id' => $deptId]);
        return $res->result->userid_list;
    }

    /**
     * 发送消息给钉钉用户
     *
     * @param array|string|Collection $userid
     * @param array $msg
     * @return string|null
     * @throws RequestException
     */
    public function messageToUser(array|string|Collection $userid, array $msg): ?string
    {
        if (is_string($userid)) {
            $userid = explode(',', $userid);
        }
        if (is_array($userid)) {
            $userid = collect($userid);
        }
        if ($userid instanceof Collection) {
            $userid = $userid
                ->filter(function ($item) {
                    return !empty($item);
                })
                ->unique();
        }
        if ($userid->isEmpty()) {
            return null;
        }

        $response = DingTalk::corp($this->corpId)->post('/topapi/message/corpconversation/asyncsend_v2', [
            'agent_id' => config('plugin.srako.dingtalk.app.agentid'),
            'userid_list' => $userid->join(','),
            'msg' => json_encode($msg),
        ]);
        return $response->task_id;
    }


    /**
     * 同步钉钉部门员工数据
     * @return void
     * @throws RequestException
     */
    public function syncDepartmentsAndUsers(): void
    {
        $deptIds = $this->departmentIds();

        // 发送全部部门列表消息，用于部门差集校验
        DingSyncMessage::dispatch(['CropId' => $this->corpId, 'EventType' => 'org_dept_all', 'DeptId' => $deptIds]);

        foreach ($deptIds as $deptId) {
            $userIds = $this->getDeptUserIds($deptId);
            if (blank($userIds)) {
                continue;
            }
            DingSyncMessage::dispatch([
                'CorpId' => $this->corpId,
                'EventType' => 'dept_user_all',
                'DeptId' => [$deptId],
                'UserId' => $userIds
            ]);
        }
    }


    public static function __callStatic(string $name, array $arguments)
    {
        return (new self(config('plugin.srako.dingtalk.app.corpid')))->{$name}(...$arguments);
    }
}
