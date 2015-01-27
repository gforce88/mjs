<?php

class SoundSpeaker {

	private $ivrService;

	public function SoundSpeaker($accountId, $language) {
		$this->accountId = $accountId;
		$this->language = $language;
		$this->ivrService = new IvrService($accountId, $language);
	}

	public function promptInviteeGrerting() {
		return $this->ivrService->getIvrAudio("inviter_greeting");
	}

	public function promptInviteeGreeting() {
		return $this->ivrService->getIvrAudio("invitee_greeting");
	}

}
