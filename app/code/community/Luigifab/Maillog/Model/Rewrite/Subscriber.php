<?php
/**
 * Created L/27/11/2017
 * Updated M/27/02/2018
 *
 * Copyright 2015-2018 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * Copyright 2015-2016 | Fabrice Creuzot <fabrice.creuzot~label-park~com>
 * Copyright 2017-2018 | Fabrice Creuzot <fabrice~reactive-web~fr>
 * https://www.luigifab.info/magento/maillog
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

class Luigifab_Maillog_Model_Rewrite_Subscriber extends Mage_Newsletter_Model_Subscriber {

	public function sendConfirmationRequestEmail() {

		$storeId = $this->getData('store_id');

		if (empty(Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_TEMPLATE, $storeId)) ||
		    empty(Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_IDENTITY, $storeId)) ||
		    $this->getImportMode())
			return $this;

		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);

		// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
		// identique de Magento 1.4 à 1.9, fait autrement pour que cela fonctionne depuis le back-office (une histoire de storeId)
		//$email = Mage::getModel('core/email_template');
		//$email->sendTransactional(
		//	Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_TEMPLATE),
		//	Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_IDENTITY),
		//	$this->getEmail(),
		//	$this->getName(),
		//	array('subscriber' => $this),
		//);

		$locale = Mage::getStoreConfig('general/locale/code', $storeId);
		$layout = Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_TEMPLATE, $storeId);
		$sender = Mage::getStoreConfig(self::XML_PATH_CONFIRM_EMAIL_IDENTITY, $storeId);

		$template = Mage::getModel('core/email_template');
		$template->setSentSuccess(false);
		$template->loadDefault($layout, $locale);
		$template->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
		$template->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
		$template->setSentSuccess($template->send($this->getEmail(), $this->getName(), array('subscriber' => $this)));

		$translate->setTranslateInline(true);
		return $this;
	}

	public function sendConfirmationSuccessEmail() {

		$storeId = $this->getData('store_id');

		if (empty(Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_TEMPLATE, $storeId)) ||
		    empty(Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_IDENTITY, $storeId)) ||
		    $this->getImportMode() || !Mage::getStoreConfigFlag('newsletter/subscription/success_send', $storeId))
			return $this;

		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);

		// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
		// identique de Magento 1.4 à 1.9, fait autrement pour que cela fonctionne depuis le back-office (une histoire de storeId)
		//$email = Mage::getModel('core/email_template');
		//$email->sendTransactional(
		//	Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_TEMPLATE),
		//	Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_IDENTITY),
		//	$this->getEmail(),
		//	$this->getName(),
		//	array('subscriber' => $this),
		//	$this->getData('store_id') // ici
		//);

		$locale = Mage::getStoreConfig('general/locale/code', $storeId);
		$layout = Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_TEMPLATE, $storeId);
		$sender = Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_IDENTITY, $storeId);

		$template = Mage::getModel('core/email_template');
		$template->setSentSuccess(false);
		$template->loadDefault($layout, $locale);
		$template->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
		$template->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
		$template->setSentSuccess($template->send($this->getEmail(), $this->getName(), array('subscriber' => $this)));

		$translate->setTranslateInline(true);
		return $this;
	}

	public function sendUnsubscriptionEmail() {

		$storeId = $this->getData('store_id');

		if (empty(Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE, $storeId)) ||
		    empty(Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_IDENTITY, $storeId)) ||
		    $this->getImportMode() || !Mage::getStoreConfigFlag('newsletter/subscription/un_send', $storeId))
			return $this;

		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);

		// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
		// identique de Magento 1.4 à 1.9, fait autrement pour que cela fonctionne depuis le back-office (une histoire de storeId)
		//$email = Mage::getModel('core/email_template');
		//$email->sendTransactional(
		//	Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE),
		//	Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_IDENTITY),
		//	$this->getEmail(),
		//	$this->getName(),
		//	array('subscriber' => $this),
		//);

		$locale = Mage::getStoreConfig('general/locale/code', $storeId);
		$layout = Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE, $storeId);
		$sender = Mage::getStoreConfig(self::XML_PATH_UNSUBSCRIBE_EMAIL_IDENTITY, $storeId);

		$template = Mage::getModel('core/email_template');
		$template->setSentSuccess(false);
		$template->loadDefault($layout, $locale);
		$template->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
		$template->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
		$template->setSentSuccess($template->send($this->getEmail(), $this->getName(), array('subscriber' => $this)));

		$translate->setTranslateInline(true);
		return $this;
	}
}