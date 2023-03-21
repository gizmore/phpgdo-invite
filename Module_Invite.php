<?php
namespace GDO\Invite;

use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Int;
use GDO\Register\GDO_UserActivation;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\User\GDO_User;

/**
 * Invite users via email. @version 6.10
 *
 * @since 6.09
 * @see \GDO\Invite\Method\Form
 *
 * Configure max pending requests.
 *
 * @author gizmore
 */
final class Module_Invite extends GDO_Module
{

	public function onLoadLanguage(): void { $this->loadLanguage('lang/invite'); }

	public function getClasses(): array
	{
		return [
			GDO_Invitation::class,
		];
	}


	public function getConfig(): array
	{
		return [
			GDT_Int::make('invite_max_pending')->notNull()->initial('3'),
			GDT_Checkbox::make('hook_right_bar')->initial('1'),
		];
	}

	public function onInitSidebar(): void
	{
// 	    if ($this->cfgRightBar())
		{
			if (GDO_User::current()->isAuthenticated())
			{
				$bar = GDT_Page::$INSTANCE->rightBar();
				$bar->addField(GDT_Link::make('link_invite')->href(href('Invite', 'Form')));
			}
		}
	}

	public function cfgMaxPending() { return $this->getConfigValue('invite_max_pending'); }

	#############
	### Hooks ###
	#############

	public function cfgRightBar() { return $this->getConfigValue('hook_right_bar'); }

	public function hookUserActivated(GDO_User $user, GDO_UserActivation $activation = null)
	{
		GDO_Invitation::hookUserActivated($user, $activation);
	}

}
