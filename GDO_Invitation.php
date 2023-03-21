<?php
namespace GDO\Invite;

use GDO\Core\GDO;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Hook;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\Mail\GDT_Email;
use GDO\Register\GDO_UserActivation;
use GDO\User\GDO_User;

final class GDO_Invitation extends GDO
{

	public static function getPendingCount(GDO_User $user)
	{
		return self::table()->countWhere("invite_completed IS NOT NULL AND invite_creator={$user->getID()}");
	}

	public static function hookUserActivated(GDO_User $user, GDO_UserActivation $activation = null)
	{
		if ($email = $user->getMail())
		{
			if ($invitation = self::getBy('invite_email', $email))
			{
				if (!$invitation->isCompleted())
				{
					$invitation->complete($user);
				}
			}
		}
	}

	public function getMail() { return $this->gdoVar('invite_email'); }

	public function isCompleted() { return $this->getCompleted() !== null; }

	################
	### Complete ###
	################

	public function getCompleted() { return $this->gdoVar('invite_completed'); }

	public function complete(GDO_User $user = null)
	{
		if ($user || ($user = GDO_User::getBy('user_email', $this->getMail())))
		{
			$this->saveVar('invite_completed', Time::getDate());
			GDT_Hook::callWithIPC('InviteCompleted', $this->getCreator(), $user);
		}
	}

	##############
	### Static ###
	##############

	/**
	 * @return GDO_User
	 */
	public function getCreator() { return $this->gdoValue('invite_creator'); }

	public function gdoColumns(): array
	{
		return [
			GDT_Email::make('invite_email')->primary(),
			GDT_CreatedBy::make('invite_creator'),
			GDT_CreatedAt::make('invite_created'),
			GDT_DateTime::make('invite_completed'),
		];
	}


}
