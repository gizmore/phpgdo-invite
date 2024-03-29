<?php
namespace GDO\Invite\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Template;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_Validator;
use GDO\Form\MethodForm;
use GDO\Invite\GDO_Invitation;
use GDO\Invite\Module_Invite;
use GDO\Mail\GDT_Email;
use GDO\Mail\Mail;
use GDO\UI\GDT_Link;
use GDO\User\GDO_User;

/**
 * Invite other users via email.
 *
 * @since 6.09
 * @author gizmore
 */
final class Form extends MethodForm
{

	protected function createForm(GDT_Form $form): void
	{
		$email = GDT_Email::make('invite_email')->notNull();
		$form->addField($email);
		$form->addField(GDT_Validator::make()->validator($form, $email, [$this, 'validateInvitation']));

		$form->actions()->addField(GDT_Submit::make());
		$form->addField(GDT_AntiCSRF::make());
	}

	public function formValidated(GDT_Form $form): GDT
	{
		$this->sendInvitationMail(GDO_User::current(), $form->getFormVar('invite_email'));

		return $this->message('msg_invited');
	}

	public function sendInvitationMail(GDO_User $user, $inviteEmail)
	{
		GDO_Invitation::blank(['invite_email' => $inviteEmail])->insert();

		$mail = Mail::botMail();
		$mail->setReceiver($inviteEmail);
		$mail->setSubject(t('invite_mail_subj', [sitename()]));
		$mail->setReply($user->getMail());
		$url = url(GDO_MODULE, GDO_METHOD);
		$linkSite = GDT_Link::make()->href($url)->labelRaw($url)->renderHTML();
		$args = ['user' => $user, 'email' => $inviteEmail, 'link_site' => $linkSite];
		$mail->setBody(GDT_Template::php('Invite', 'mail/invitation_mail.php', $args));
		$mail->sendAsText();
	}

	public function validateInvitation(GDT_Form $form, GDT $field, $email)
	{
		$user = GDO_User::current();
		$module = Module_Invite::instance();
		$max = $module->cfgMaxPending();

		if (($max >= 0) && (GDO_Invitation::getPendingCount($user) >= $max))
		{
			return $field->error('err_invite_max_pendings', [$max]);
		}

		if (GDO_User::withSetting('Mail', 'email', $email))
		{
// 			return $field->error('err_invite_already_member');
			return $field->error('err_already_invited');
		}

		if (GDO_Invitation::getBy('invite_email', $email))
		{
			return $field->error('err_already_invited');
		}

		return true;
	}

	public function validateInvitationMessage($email)
	{
		$user = GDO_User::current();
		$module = Module_Invite::instance();
		$max = $module->cfgMaxPending();

		if (($max >= 0) && (GDO_Invitation::getPendingCount($user) >= $max))
		{
			return $this->error('err_invite_max_pendings', [$max]);
		}

		if (GDO_User::getBy('user_email', $email))
		{
			return $this->error('err_invite_already_member');
		}

		if (GDO_Invitation::getBy('invite_email', $email))
		{
			return $this->error('err_already_invited');
		}

		return $this->message('msg_invited');
	}

}
