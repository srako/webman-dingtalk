<?php

namespace Webman\DingTalk\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webman\DingTalk\DingTalk;

class DingTalkService
{

    /**
     * 获取钉钉所有部门ID
     * @return array
     */
    public static function departmentIds(): array
    {
        return array_merge(self::getDepartmentAllSubIds(1), [1]);
    }

    /**
     * 获取递归下级部门ID
     * @param $dept_id
     * @return array
     */
    public static function getDepartmentAllSubIds($dept_id): array
    {
        $res = DingTalk::post('/topapi/v2/department/listsubid', ['dept_id' => $dept_id]);
        $sub_ids = $res->result->dept_id_list;
        foreach ($sub_ids as $id) {
            $sub_ids = array_merge($sub_ids, self::getDepartmentAllSubIds($id));
        }
        return $sub_ids;
    }

    /**
     * 获取部门下UserIds
     * @param $dept_ids
     * @return array
     */
    public static function getDeptUserIds($dept_ids): array
    {
        $dept_ids = Arr::wrap($dept_ids);
        $user_ids = [];

        foreach ($dept_ids as $dept_id) {
            $res = DingTalk::post('/topapi/user/listid', ['dept_id' => $dept_id]);
            $user_ids = array_merge($user_ids, $res->result->userid_list);
        }

        return collect($user_ids)->unique()->toArray();
    }

    /**
     * 发送消息给钉钉用户
     *
     * @param array|string|Collection $userid
     * @param array $msg
     * @return string|null
     */
    public static function messageToUser(array|string|Collection $userid, array $msg): ?string
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

        $response = DingTalk::post('/topapi/message/corpconversation/asyncsend_v2', [
            'agent_id' => config('plugin.srako.dingtalk.agentid'),
            'userid_list' => $userid->join(','),
            'msg' => $msg,
        ]);
        return $response->task_id;

    }
}
