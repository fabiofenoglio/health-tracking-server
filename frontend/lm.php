<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get an instance of the controller prefixed by HelloWorld
$controller = JControllerLegacy::getInstance('Lm');
 
// Perform the Request task
$input->def("view", "dashboard");

$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();