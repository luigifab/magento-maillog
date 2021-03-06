<?php
/**
 * Created D/22/03/2015
 * Updated D/21/02/2021
 *
 * Copyright 2015-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * Copyright 2015-2016 | Fabrice Creuzot <fabrice.creuzot~label-park~com>
 * Copyright 2017-2018 | Fabrice Creuzot <fabrice~reactive-web~fr>
 * Copyright 2020-2021 | Fabrice Creuzot <fabrice~cellublue~com>
 * https://www.luigifab.fr/openmage/maillog
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

class Luigifab_Maillog_Model_Email extends Mage_Core_Model_Abstract {

	protected $_eventPrefix = 'maillog_email';

	public function _construct() {
		$this->_init('maillog/email');
	}


	public function getDefaultBgColor() {
		return '#F6F6F6';
	}

	public function getDefaultTxtColor() {
		return '#000';
	}

	public function getSubject() {
		return Mage::helper('maillog')->escapeEntities($this->getData('mail_subject'));
	}

	public function getEmailParts() {
		$parts = $this->getData('mail_parts');
		$parts = empty($parts) ? [] : @unserialize(gzdecode($parts), ['allowed_classes' => ['Zend_Mime_Part']]);
		return empty($parts) ? [] : $parts;
	}


	public function setMailSender($data, bool $decode = true) {
		return $this->setData('mail_sender', $decode ? iconv_mime_decode($data, 0, 'utf-8') : $data);
	}

	public function setMailRecipients($data, bool $decode = true) {
		return $this->setData('mail_recipients', $decode ? iconv_mime_decode($data, 0, 'utf-8') : $data);
	}

	public function setMailSubject($data, bool $decode = true) {
		return $this->setData('mail_subject', trim($decode ? iconv_mime_decode($data, 0, 'utf-8') : $data));
	}

	public function setMailContent($parts) {

		$this->setData('uniqid', mb_substr(sha1(time().$this->getData('mail_recipients').$this->getData('mail_subject')), 0, 30));

		// récupération des données du mail
		// $parts[0]->getContent() = $zend->body si le mail n'a pas de pièce jointe
		if (is_string($parts)) {
			$body  = $parts;
			$parts = [];
		}
		else {
			$body = trim(quoted_printable_decode($parts[0]->getContent()));
			array_shift($parts);
		}

		if (mb_stripos($body, '<!--@vars') !== false)
			$body = mb_substr($body, mb_stripos($body, '-->') + 3);

		// recherche et remplace <!-- maillog / maillog --> par rien
		if ((mb_stripos($body, '<!-- maillog') !== false) && (mb_stripos($body, 'maillog -->') !== false)) {
			$body = preg_replace('#\s*<!-- maillog\s*#', ' ', $body);
			$body = preg_replace('#\s*maillog -->\s*#', ' ', $body);
		}

		// minifie le code HTML en fonction de la configuration
		// recherche et remplace #online# #online#storeId# #readimg# par leur valeurs uniquement si c'est un mail au format HTML
		if ((mb_stripos($body, '</p>') !== false) || (mb_stripos($body, '</td>') !== false) || (mb_stripos($body, '</div>') !== false)) {

			if (Mage::getStoreConfigFlag('maillog/general/minify') && extension_loaded('tidy') && class_exists('tidy', false))
				$body = $this->cleanWithTidy($body);

			if (mb_stripos($body, '#online#') !== false)
				$body = preg_replace_callback('/#online#(\d{0,5})/', function ($matches) {
					return $this->getEmbedUrl('index', empty($matches[1]) ? [] : ['_store' => (int) $matches[1]]);
				}, $body);

			if (mb_stripos($body, '#readimg#') !== false)
				$body = str_replace('#readimg#', '<img src="'.$this->getEmbedUrl('mark').'" width="1" height="1" alt="">', $body);
		}

		// recherche et remplace #uniqid# par sa valeur
		if (mb_stripos($body, '#uniqid#') !== false)
			$body = str_replace('#uniqid#', $this->getData('uniqid'), $body);

		// recherche et remplace #mailid# par rien
		if (mb_stripos($body, '#mailid#') !== false) {
			$mailid = mb_substr($body, mb_stripos($body, '#mailid#'));
			$mailid = mb_substr($mailid, strlen('#mailid#'));
			$mailid = mb_substr($mailid, 0, mb_stripos($mailid, '#'));
			$body = str_replace('#mailid#'.$mailid.'#', '', $body);
			$this->setData('type', trim($mailid));
		}

		$this->setData('mail_body', trim($body));
		$this->setData('mail_parts', empty($parts) ? null : gzencode(serialize($parts), 9));

		return $this;
	}

	private function cleanWithTidy($html) {

		$tidy = new Tidy();
		$tidy->parseString($html, Mage::getModuleDir('etc', 'Luigifab_Maillog').'/tidy.conf', 'utf8');
		$tidy->cleanRepair();

		$html = $tidy;
		$html = str_replace("\"\n   ", '"', $html); // doctype


		return preg_replace([
			'#css">\s+/\*<!\[CDATA\[\*/#',
			'#/\*]]>\*/\s+</style#',
			'#" />#',
			'#>\s*</script>#',
			'#-->\s{2,}#',
			'#\s*<!--\[if([^]]+)]>\s*<#',
			'#>\s*<!\[endif]-->#',
			'#>\s*/?/?<!\[CDATA\[#',
			'#\s*/?/?]]></script>#',
			'#<br ?/?>\s+#',
			'#</code>\s</pre>#',
			'#\s+</textarea>#',
			'#\n?<!--[^\->]+-->#'
		], [
			'css">',
			'</style',
			'"/>',
			'></script>',
			"-->\n",
			"\n<!--[if$1]><",
			'><![endif]-->',
			'>//<![CDATA[',
			"\n//]]></script>",
			'<br>', // ici pas de /
			'</code></pre>',
			'</textarea>',
			''
		], $html);
	}

	private function getColors() {

		$config = @unserialize(Mage::getStoreConfig('maillog/general/special_config'), ['allowed_classes' => ['Zend_Mime_Part']]);
		if (!empty($config) && is_array($config)) {

			foreach ($config as $key => $value) {
				if (!empty($value) && ($key == $this->getData('type').'_back_color'))
					$bgColor = $value;
				if (!empty($value) && ($key == $this->getData('type').'_text_color'))
					$ttColor = $value;
			}

			if (empty($bgColor) && !empty($config['without_back_color']) && in_array($this->getData('type'), ['--', '', null]))
				$bgColor = $config['without_back_color'];
			if (empty($ttColor) && !empty($config['without_text_color']) && in_array($this->getData('type'), ['--', '', null]))
				$ttColor = $config['without_text_color'];

			if (empty($bgColor) && !empty($config['all_back_color']))
				$bgColor = $config['all_back_color'];
			if (empty($ttColor) && !empty($config['all_text_color']))
				$ttColor = $config['all_text_color'];
		}

		return [
			empty($bgColor) ? $this->getDefaultBgColor()  : $bgColor,
			empty($ttColor) ? $this->getDefaultTxtColor() : $ttColor
		];
	}

	public function sendNow() {

		$now = time();
		if (empty($this->getId()))
			Mage::throwException('You must load an email before trying to send it.');

		try {
			$this->setData('status', 'sending');
			$this->save();

			$heads = $this->getData('mail_header');
			$email = $this->getData('mail_recipients');
			$allow = Mage::getStoreConfigFlag('maillog/general/send') ? Mage::helper('maillog')->canSend($email) : false;

			// modifie les entêtes
			$heads .= "\r\n".'X-Maillog: '.$this->getData('uniqid');
			$encoding = Mage::getStoreConfig('maillog/general/encoding');
			if ($encoding != 'quoted-printable')
				$heads = str_replace('Content-Transfer-Encoding: quoted-printable', 'Content-Transfer-Encoding: '.$encoding, $heads);

			//$subject = $this->getData('encoded_mail_subject');
			//if ((mb_substr($subject, 0, 2) != '=?') || (mb_substr($subject, 0, 2) != '?=')) {
			//	//if ($encoding == 'quoted-printable')
			//		//$subject = '=?utf-8?Q?'.quoted_printable_encode($subject).'?=';
			//	$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
			//}

			// préparation des données
			if (empty($this->getData('mail_parts'))) {
				if ($encoding == 'quoted-printable')
					$body = quoted_printable_encode($this->getData('mail_body'));
				else if ($encoding == 'base64')
					$body = rtrim(chunk_split(base64_encode($this->getData('mail_body'))));
			}
			else {
				preg_match('#boundary="([^"]+)"#', $heads, $bound);
				$bound = $bound[1];

				$parts = $this->getEmailParts();
				$body  = $this->getData('mail_body');
				$type  = ((mb_stripos($body, '</p>') !== false) || (mb_stripos($body, '</td>') !== false) || (mb_stripos($body, '</div>') !== false)) ? 'text/html' : 'text/plain';

				$body  = 'This is a message in Mime Format.  If you see this, your mail reader does not support this format.'."\r\n\r\n";
				$body .= '--'.$bound."\r\n";
				$body .= 'Content-Type: '.$type.'; charset=utf-8'."\r\n";
				$body .= 'Content-Transfer-Encoding: '.$encoding."\r\n";
				$body .= 'Content-Disposition: inline'."\r\n\r\n";

				if ($encoding == 'quoted-printable')
					$body .= quoted_printable_encode($this->getData('mail_body'));
				else if ($encoding == 'base64')
					$body .= rtrim(chunk_split(base64_encode($this->getData('mail_body'))));

				foreach ($parts as $part) {
					$body .= "\r\n\r\n";
					$body .= '--'.$bound."\r\n";
					$body .= 'Content-Type: '.$part->type."\r\n";
					$body .= 'Content-Transfer-Encoding: '.$part->encoding."\r\n";
					$body .= 'Content-Disposition: '.$part->disposition.'; filename="'.$part->filename.'"'."\r\n\r\n";
					$body .= rtrim(chunk_split(str_replace("\n", '', $part->getContent())));
				}

				$body .= "\r\n\r\n".'--'.$bound."\r\n";
			}

			$this->setData('size',
				mb_strlen($this->getData('encoded_mail_recipients')) + // utilise les destinataires originaux
				mb_strlen($this->getData('encoded_mail_subject')) +    // utilise le sujet original
				mb_strlen($body) +                                     // utilise la nouvelle version encodée du contenu
				mb_strlen($heads) +                                    // utilise les entêtes originaux (où presque)
				mb_strlen($this->getData('mail_parameters'))           // utilise les paramètres originaux
			);

			// action (sauf si l'email est vide)
			$empty = empty($this->getData('mail_body')) || empty($this->getData('encoded_mail_subject'));
			if ($empty || ($allow !== true) || Mage::getSingleton('maillog/source_bounce')->isBounce($email)) {
				$this->setData('status', $allow ? 'bounce' : 'notsent');
				$this->setData('duration', time() - $now);
				$this->save();
			}
			else {
				$result = mail(
					$this->getData('encoded_mail_recipients'), // version originale
					$this->getData('encoded_mail_subject'),    // version originale
					$body,
					$heads,                                    // version originale (où presque)
					$this->getData('mail_parameters')          // version originale
				);

				if (in_array($this->getData('sent_at'), ['', '0000-00-00 00:00:00', null]))
					$this->setData('sent_at', date('Y-m-d H:i:s'));

				$this->setData('status', ($result === true) ? 'sent' : 'error');
				$this->setData('duration', time() - $now);
				$this->save();
			}
		}
		catch (Throwable $e) {
			Mage::logException($e);
		}
	}


	public function toHtml(bool $noMark = false) {

		if (empty($this->getId()))
			Mage::throwException('You must load an email before trying to display it.');

		// génération du code HTML
		$body = $this->getData('mail_body');
		$head = null;

		if (($pos = mb_stripos($body, '<head')) !== false) {
			$head = mb_substr($body, $pos);
			$head = mb_substr($head, mb_strpos($head, '>') + 1);
			$head = mb_substr($head, 0, mb_stripos($head, '</head>'));
			$head = preg_replace('#<title>.*</title>#s', '', $head);
		}

		if (($pos = mb_stripos($body, '<body')) !== false) {
			$body = mb_substr($body, $pos);
			$body = mb_substr($body, mb_strpos($body, '>') + 1);
			$body = mb_substr($body, 0, mb_stripos($body, '</body>'));
		}
		else if ((mb_stripos($body, '</p>') === false) && (mb_stripos($body, '</td>') === false) && (mb_stripos($body, '</div>') === false)) {
			$body = '<pre>'.$body.'</pre>';
		}

		// suppression de l'éventuelle image de marquage
		// lorsque l'administrateur visualise l'email en ligne
		if ($noMark && (mb_stripos($body, 'maillog/view/mark') !== false))
			$body = preg_replace('#[\-\s]*<img[^>]+maillog/view/mark[^>]+>[\-\s]*#', ' ', $body);

		// suppression de l'éventuel lien voir la version en ligne
		// lorsque le client (et non l'administrateur) visualise son email en ligne
		if (!$noMark && (mb_stripos($body, 'maillog/view/index') !== false))
			$body = preg_replace('#[\-\s]*<a[^>]+maillog/view/index.+</a>[\-\s]*#', ' ', $body);

		return Mage::getBlockSingleton('core/template')
			->setTemplate('luigifab/maillog/email.phtml')
			->setData('email', $this)
			->setData('mail_head', $head)
			->setData('mail_body', $body)
			->setData('colors', $this->getColors())
			->toHtml();
	}

	public function getEmbedUrl(string $action, array $params = []) {

		$store  = is_object(Mage::getDesign()->getStore()) ? Mage::getDesign()->getStore() : Mage::app()->getStore();
		$params = array_merge(['_secure' => false, 'key' => $this->getData('uniqid')], $params);

		if (empty($params['_store']) || Mage::app()->getStore()->isAdmin())
			$params['_store'] = Mage::app()->getDefaultStoreView()->getId();

		$url = preg_replace('#\?SID=.+$#', '', $store->getUrl('maillog/view/'.$action, $params));

		if (Mage::getStoreConfigFlag(Mage_Core_Model_Store::XML_PATH_USE_REWRITES, $store->getId()))
			$url = preg_replace('#/[^/]+\.php\d*/#', '/', $url);
		else
			$url = preg_replace('#/[^/]+\.php(\d*)/#', '/index.php$1/', $url);

		return $params['_secure'] ? str_replace('http:', 'https:', $url) : $url;
	}
}