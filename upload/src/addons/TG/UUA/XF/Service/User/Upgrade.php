<?php

namespace TG\UUA\XF\Service\User;

class Upgrade extends XFCP_Upgrade
{
    public function upgrade()
    {
        $active = parent::upgrade();

        if ($active)
        {
            /** @var \XF\Repository\UserAlert $alertRepo */
            $alertRepo = $this->repository('XF:UserAlert');

            $alertUsers = $this->finder('XF:User')
                ->where('user_id', '=', \XF::app()->options()->tg_uua_users)->fetch();

            foreach ($alertUsers as $alertUser) {
                $extra = [
                    'link' => \XF::app()->router('public')->buildLink('members', $this->user),
                    'upgrade' => $this->userUpgrade->title,
                    'user' => $this->user->username
                ];

                $alertRepo->alert($alertUser, $this->user->user_id, '', 'user', $this->user->user_id, 'tguua', $extra);
            }
        }

        return $active;
    }
}