<?php defined("_JEXEC") or die();

H_PublicAccess::check();

F_SimplecomponentHelper::authorise(!JFactory::getUser()->guest);

F_SimpleComponentHelper::setNavigationParams("view");

JLoader::import('joomla.environment.browser');
$is_mobile = strstr(strtolower(JBrowser::getInstance()->getAgentString()), "android") !== FALSE;
define("LM_MOBILE", $is_mobile);