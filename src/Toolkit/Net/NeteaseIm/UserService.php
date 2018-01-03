<?php

namespace Swallow\Toolkit\Net\NeteaseIm;

use Whoops\Exception\ErrorException;

class UserService extends Service
{
    /**
     * 创建网易云信id
     *
     * @param string $accid 网易云信id
     * @param string $name 网易云信昵称
     * @param string $props json属性，第三方可选填，最大长度1024字符
     * @param string $icon 网易云信头像url
     * @param string $token 网易云信登录token
     * @return array
     * @uri("user/create.action")
     */
    public function addUser($accid, $name = '', $props = '{}', $icon = '', $token = '')
    {
        $args = [
            'accid' => $accid,
            'name' => $name,
            'props' => $props,
            'icon' => $icon,
            'token' => $token
        ];

        return $this->getResponse($args);
    }

    /**
     * 更新网易云信id
     *
     * @param string $accid 网易云信id
     * @param string $props json属性，第三方可选填，最大长度1024字符
     * @param string $token 网易云信登录token
     * @return array
     * @uri("user/update.action")
     */
    public function updateUser($accid, $props = '', $token = '')
    {
        $args = [
            'accid' => $accid,
        ];
        !empty($props) && $args['props'] = $props;
        !empty($token) && $args['token'] = $token;

        return $this->getResponse($args);
    }

    /**
     * 获取用户信息
     *
     * @param array $accids 网易云信id
     * @return array
     * @uri("user/getUinfos.action")
     */
    public function getUserInfos(array $accids)
    {
        if (200 < count($accids)){
            throw new ErrorException('一次查询最多为200');
        }
        $args = [
            'accids' => json_encode($accids),
        ];

        return $this->getResponse($args);
    }
}