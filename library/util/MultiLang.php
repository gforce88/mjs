<?php

class MultiLang {

	public static function getText($key, $language, $params = null) {
		$language = strtoupper($language);
		switch ($language) {
			case COUNTRY_JP :
				$msg = self::getJapaneseText($key);
				break;
			default :
				$msg = self::getEnglishText($key);
		}
		
		if ($params != null) {
			$msg = self::replaceParams($msg, $params);
		}
		
		$msg = self::replaceColor($msg);
		
		return $msg;
	}

	public static function getText2($key, $language, $param1, $param2) {
		$params = array (
			$param1,
			$param2 
		);
		return self::getText($key, $language, $params);
	}

	public static function getText3($key, $language, $param1, $param2, $param3) {
		$params = array (
			$param1,
			$param2,
			$param3 
		);
		return self::getText($key, $language, $params);
	}

	public static function replaceParams($msg, $params) {
		if (!is_array($params)) {
			$params = array (
				$params 
			);
		}
		
		$i = 1;
		foreach ($params as $key => $value) {
			$msg = str_replace("%" . $i . "s", $value, $msg);
			$msg = str_replace("[$key]", $value, $msg);
			$i++;
		}
		return $msg;
	}

	private static function replaceColor($msg) {
		$msg = str_replace("<name>", "<span class='name'>", $msg);
		$msg = str_replace("</name>", "</span>", $msg);
		
		return $msg;
	}

	private static function getEnglishText($key) {
		$texts = Zend_Registry::get('ENGLISH_TEXTS');
		return $texts[$key];
	}

	private static function getJapaneseText($key) {
		$texts = Zend_Registry::get('JAPANESE_TEXTS');
		if ($texts[$key] == null) {
			return self::getEnglishText($key);
		} else {
			return $texts[$key];
		}
	}

}