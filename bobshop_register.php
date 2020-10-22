<?php
/**
 * copy and modified by bobshop
 * 
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//begin copy from tiki-register.php

$inputConfiguration = [
	['staticKeyFilters' => [
		'email' => 'email',
		'name' => 'text',
		'pass' => 'text',
		'passAgain' => 'text',
	]]
];

$auto_query_args = [];

require_once('tiki-setup.php');

ask_ticket('register');

if (isset($redirect) && ! empty($redirect)) {
	header('Location: ' . $redirect);
	exit;
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

if ($prefs['allowRegister'] != 'y') {
	header("location: index.php");
	die;
}

if (! empty($prefs['registerKey']) && (empty($_GET['key']) || $_GET['key'] !== $prefs['registerKey'])) {
	$access->redirect('', '', 404);
}

global $user, $prefs;
//if (! empty($prefs['feature_alternate_registration_page']) && $prefs['feature_alternate_registration_page'] !== 'tiki-register.php') {
//	header("location: " . $prefs['feature_alternate_registration_page']);
//	die;
//}
$smarty->assign('user_exists', TikiLib::lib('user')->user_exists($user));

$re = $userlib->get_group_info(isset($_REQUEST['chosenGroup']) ? $_REQUEST['chosenGroup'] : 'Registered');
$tr = TikiLib::lib('trk')->get_tracker($re['usersTrackerId']);
if (! empty($tr['description'])) {
	$smarty->assign('userTrackerHasDescription', true);
}

//echo '<h1>hallo</h1>';



//end copy from tiki-register.php
if($_REQUEST['register'] == 'Register')
{
	$smarty->fetch('tiki-register.tpl');

	//echo '<hr>request: '; print_r($_REQUEST);
	//echo '<hr>check user<hr>';
	//$requestedUser = $_REQUEST['name'];
	$ret = $userlib->validate_user($_REQUEST['name'], $_REQUEST['pass']);
	//$ret = $userlib->validate_user($requestedUser, 'kennwort');
	if (count($ret) == 3) {$ret[] = null;}
	list($isvalid, $requestedUser, $error, $method) = $ret;
	//error -5 = user not found
	//print_r($ret);

	//copy from tiki-login.php line 258 tiki 21.2
	//if ($isvalid && $access->checkCsrf('page'))
	if ($isvalid )
	{
		$isdue = $userlib->is_due($requestedUser, $method);
		$user = $requestedUser;

		$userlib->set_unsuccessful_logins($requestedUser, 0);
		if ($isdue) {
			// Redirect the user to the screen where he must change his password.
			// Note that the user is not logged in he's just validated to change his password
			// The user must re-enter his old password so no security risk involved
			$url = 'tiki-change_password.php?user=' . urlencode($user);
		} else {
			// User is valid and not due to change pass.. start session
			$userlib->update_expired_groups();
			TikiLib::lib('login')->activateSession($user);
		}
		$url = $_SESSION['loginfrom'];
		$logslib->add_log('login', 'logged from ' . $url);

	}

	//copy from tiki-login.php line 559
	//if (isset($user) && $access->checkCsrf('page')) {
	if (isset($user)) {
		TikiLib::events()->trigger(
			'tiki.user.login',
			[
				'type' => 'user',
				'object' => $user,
				'user' => $user,
			]
		);
	}

	if (defined('SID') && SID != '') {
		$url .= ((strpos($url, '?') === false) ? '?' : '&') . SID;
	}

	if(!empty($user))
	{
		header("location: tiki-index.php?page=bobshop_cashierpage");
		exit;
	}
//	else
//	{
//		header("location: tiki-index.php?page=Home");
//		exit;
//	}

}
else
{


$smarty->assign('mid', 'tiki-register.tpl');
$smarty->display('tiki.tpl');
}