<?php
/*
 * PHP version 5.5
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (http://www.eelly.com)
 * @link      http://www.eelly.com
 * @license   衣联网版权所有
 */

namespace Swallow\ThirdParty\Easemob\Service;

use Swallow\Core\Log;
use Swallow\ThirdParty\Easemob\Exception as EasemobException;

/**
 * 环信用户体系集成.
 *
 * @see http://docs.easemob.com/start/100serverintegration/20users
 *
 * @author hehui<hehui@eelly.net>
 *
 * @since 2016年10月1日
 *
 * @version 1.0
 */
class UserService extends AbstractService
{
    /**
     * 注册 IM 用户[单个]
     * “授权注册”模式.
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $nickname 昵称
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function createUser($username, $password, $nickname = '')
    {
        $body = [
            'username' => $username,
            'password' => $password,
            'nickname' => $nickname,
        ];
        $result = $this->getManager()->request('users', self::POST, $body);

        return $result;
    }

    /**
     * 注册 IM 用户[批量]
     * 批量注册的用户数量不要过多，建议在20-60之间.
     *
     * @param array $users 用户列表
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function createUsers(array $users)
    {
        $result = $this->getManager()->request('users', self::POST, $users);

        return $result;
    }

    /**
     * 获取单个用户.
     *
     * @param string $username
     *
     * @throws EasemobException
     *
     * @return array
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月5日
     */
    public function getUser($username)
    {
        $service = 'users/'.$username;
        $result = $this->getManager()->request($service, self::GET);

        return $result;
    }

    /**
     * 获取 IM 用户[批量]
     *
     * @param int $limit
     * @param string $cursor
     * @return array
     */
    public function getUsers($cursor = null, $limit = 20)
    {
        $service = 'users?limit=' . $limit;
        if (null != $cursor) {
            $service .= '&cursor=' . $cursor;
        }
        $result = $this->getManager()->request($service, self::GET);

        return $result;
    }

    /**
     * 获取用户.
     *
     * > 如果创建失败就去获取
     *
     * @param string $username
     * @param string $password
     * @param string $nickname
     *
     * @throws EasemobException
     *
     * @return array
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月6日
     */
    public function getCreatedUser($username, $password = '123456', $nickname = null)
    {
        static $retryTimes = 0;
        try {
            $result = $this->createUser($username, $password, $nickname ?: $username);
        } catch (EasemobException $e) {
            if (400 == $e->getCode()) {
                try {
                    $result = $this->getUser($username);
                } catch (EasemobException $e1) {
                    if (404 == $e1->getCode()) {
                        Log::debug(__METHOD__, [$username, $password, $nickname]);
                        sleep(1);
                        ++$retryTimes;
                        if (5 < $retryTimes) {
                            throw $e1;
                        }
                        $this->getCreatedUser($username, $password, $nickname);
                    }
                }
                $this->updatePassword($username, $password);
            } else {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * 删除 IM 用户[单个].
     *
     * @param string $username
     *
     * @return string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月6日
     */
    public function deleteUser($username)
    {
        $service = 'users/'.$username;
        $result = $this->getManager()->request($service, self::DELETE);

        return $result;
    }

    /**
     * 重置 IM 用户密码
     *
     *
     * @param string $username
     * @param string $newPassword
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function updatePassword($username, $newPassword)
    {
        $body = [
            'newpassword' => $newPassword,
        ];
        $result = $this->getManager()->request("users/$username/password", self::PUT, $body);

        return $result;
    }

    /**
     * 给 IM 用户添加好友.
     *
     * @param string $ownerUsername
     * @param string $friendUsername
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    public function addFriend($ownerUsername, $friendUsername)
    {
        return $this->contactsUsers($ownerUsername, $friendUsername, self::POST);
    }

    /**
     * 解除 IM 用户的好友关系
     * 从 IM 用户的好友列表中移除一个用户.
     *
     * @param string $ownerUsername
     * @param string $friendUsername
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    public function deleteFriend($ownerUsername, $friendUsername)
    {
        return $this->contactsUsers($ownerUsername, $friendUsername, self::DELETE);
    }

    /**
     * 往 IM 用户的黑名单中加人.
     *
     * 使用示例：
     *
     * ```
     * // 获取用户服务
     * $userService = \Swallow\ThirdParty\Easemob\Manager::userService();
     * // 加单个人到黑名单
     * $userService->addBlockUsers('xiaoming2', 'xiaoming3');
     * // 加多个人到黑名单
     * $userService->addBlockUsers('xiaoming2', ['xiaoming1','xiaoming3']);
     * ```
     *
     * @param string       $ownerUsername 用户名(要添加好友的用户名)
     * @param stirng|array $usernames     黑名单(被添加的用户名)
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since 2016年10月1日
     */
    public function addBlockUsers($ownerUsername, $usernames)
    {
        $body = [
            'usernames' => (array) $usernames,
        ];
        $result = $this->getManager()->request("users/$ownerUsername/blocks/users", self::POST, $body);

        return $result;
    }

    /**
     * 从 IM 用户的黑名单中减人
     * 从一个 IM 用户的黑名单中减人。将用户从黑名单移除后，恢复好友关系，可以互相收发消息.
     *
     * @param string $ownerUsername
     * @param string $blockedUsername
     *
     * @return mixed
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    public function deleteBlockedUser($ownerUsername, $blockedUsername)
    {
        $result = $this->getManager()->request("users/$ownerUsername/blocks/users/$blockedUsername", self::DELETE);

        return $result;
    }

    /**
     * 获取 IM 用户的黑名单
     * 获取一个IM用户的黑名单。黑名单中的用户无法给该 IM 用户发送消息。
     *
     *
     * @param string $username
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月6日
     */
    public function getBlockedUsers($username)
    {
        $service = 'users/'.$username.'/blocks/users';
        $result = $this->getManager()->request($service, self::GET);

        return $result;
    }

    /**
     * 获取用户token.
     *
     *
     * @param string $username
     * @param string $password
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年11月9日
     */
    public function getUserToken($username, $password)
    {
        $body = [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
        ];
        $result = $this->getManager()->request('token', self::POST, $body, [], [], false);

        return $result;
    }

    /**
     * 修改用户昵称.
     *
     *
     * @param string $username
     * @param string $nickname
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年11月25日
     */
    public function updateNickname($username, $nickname)
    {
        $body = [
            'nickname' => $nickname,
        ];
        $service = 'users/'.$username;
        $result = $this->getManager()->request($service, self::PUT, $body);

        return $result;
    }

    /**
     * 查看用户在线状态
     *
     *
     * @param unknown $username
     *
     * @return string
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年12月22日
     */
    public function status($username)
    {
        $service = 'users/'.$username.'/status';
        $result = $this->getManager()->request($service, self::GET);

        return $result;
    }

    /**
     * 给 IM 用户添加好友
     * 或
     * 解除 IM 用户的好友关系.
     *
     * @param string $ownerUsername  用户名(要添加好友的用户名)
     * @param string $friendUsername 用户名(被添加的用户名)
     * @param string $method         post 添加 delete 解除
     *
     * @author hehui<hehui@eelly.net>
     *
     * @since  2016年10月1日
     */
    private function contactsUsers($ownerUsername, $friendUsername, $method)
    {
        $tryTimes = 3;
        while (true) {
            try {
                $result = $this->getManager()->request("users/$ownerUsername/contacts/users/$friendUsername", $method);
            } catch (EasemobException $e) {
                if (0 > -- $tryTimes) {
                    throw $e;
                } else {
                    usleep(100000);
                    continue;
                }
            }
            break;
        }
        return $result;
    }
}
