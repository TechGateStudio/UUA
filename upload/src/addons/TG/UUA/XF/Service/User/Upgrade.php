<?php

namespace TG\UUA\XF\Service\User;

class Upgrade extends XFCP_Upgrade
{
	public function upgrade() 
	{
		if (!$this->finalSetup)
		{
			$this->finalSetup();
		}

		$active = $this->activeUpgrade;
		$upgrade = $this->userUpgrade;
		$user = $this->user;

		if (!$active->user_upgrade_record_id)
		{
			if (!$upgrade->canPurchase() && !$this->ignoreUnpurchasable)
			{
				return false;
			}
		}

		$db = $this->db();
		$db->beginTransaction();

		if (!$active->save(true, false))
		{
			$db->rollback();
			return false;
		}

		/** @var UserGroupChange $userGroupChange */
		$userGroupChange = $this->service('XF:User\UserGroupChange');
		$userGroupChange->addUserGroupChange(
			$user->user_id, 'userUpgrade-' . $upgrade->user_upgrade_id, $upgrade->extra_group_ids
		);

		/** @var \XF\Repository\UserAlert $alertRepo */
		$alertRepo = $this->repository('XF:UserAlert');
		$alertRepo->fastDeleteAlertsFromUser($user->user_id, 'user', $user->user_id, 'upgrade_end');
	

		$alertUsers = $this->finder('XF:User')
			->where('user_id', '=', \XF::app()->options()->tg_uua_users)->fetch();

		foreach ($alertUsers as $alertUser) {
			$extra = [
				'link' => \XF::app()->router('public')->buildLink('members', $user), 
				'upgrade' => $upgrade->title,
				'user' => $user->username
			];

			$alertRepo->alert($alertUser, $user->user_id, '', 'user', $user->user_id, 'tguua', $extra);
		}
		$db->commit();

		return $active;
	}
}