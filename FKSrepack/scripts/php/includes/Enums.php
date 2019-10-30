<?PHP namespace Enums;
// Load "enums.php" if it exists
if(is_file(__DIR__ . '/../config/enums.php')) {
	include(__DIR__ . '/../config/enums.php');
}

// Create classes if they don't exist
if(!class_exists('Enums\LogActionsX'))	{ class LogActionsX {}; }

/*----------------------------------------------
	Log Actions
----------------------------------------------*/
abstract class LogActions extends LogActionsX {
	CONST LOGIN							=	array('id' => 1,	'title' => 'Login');
	CONST LOGIN_FAILURE					=	array('id' => 2,	'title' => 'Login Failure');
	CONST LOGIN_FAILURE_VERIFICATION	=	array('id' => 3,	'title' => 'Login Failure (Verification)');
	CONST LOGOUT_MANUAL					=	array('id' => 4,	'title' => 'Logout (Manual)');
	CONST LOGOUT_INACTIVE				=	array('id' => 5,	'title' => 'Logout (Inactive)');
	CONST LOGOUT_UNKNOWN				=	array('id' => 6,	'title' => 'Logout (Unknown)');
	CONST MEMBER_CREATED				=	array('id' => 7,	'title' => 'Member Created');
	CONST MEMBER_MODIFIED				=	array('id' => 8,	'title' => 'Member Modified');
	CONST MENU_CREATED					=	array('id' => 9,	'title' => 'Menu Created');
	CONST MENU_MODIFIED					=	array('id' => 10,	'title' => 'Menu Modified');
	CONST MENU_ITEM_CREATED				=	array('id' => 11,	'title' => 'Menu Item Created');
	CONST MENU_ITEM_MODIFIED			=	array('id' => 12,	'title' => 'Menu Item Modified');
	CONST ACCESS_GROUP_CREATED			=	array('id' => 13,	'title' => 'Access Group Created');
	CONST ACCESS_GROUP_MODIFIED			=	array('id' => 14,	'title' => 'Access Group Modified');
	CONST SITE_SETTINGS_MODIFIED		=	array('id' => 15,	'title' => 'Site Settings Modified');
	CONST MENU_ITEM_PAGES_CREATED		=	array('id' => 16,	'title' => 'Menu Item Pages Created');
	CONST ANNOUNCEMENT_CREATED			=	array('id' => 17,	'title' => 'Announcement Created');
	CONST ANNOUNCEMENT_MODIFIED			=	array('id' => 18,	'title' => 'Announcement Modified');
	CONST CHANGELOG_CREATED				=	array('id' => 19,	'title' => 'Changelog Created');
	CONST CHANGELOG_MODIFIED			=	array('id' => 20,	'title' => 'Changelog Modified');
	CONST CHANGELOG_NOTE_CREATED		=	array('id' => 21,	'title' => 'Changelog Note Created');
	CONST CHANGELOG_NOTE_MODIFIED		=	array('id' => 22,	'title' => 'Changelog Note Modified');
	CONST CHANGELOG_NOTE_DELETED		=	array('id' => 23,	'title' => 'Changelog Note Deleted');
	CONST SITE_ERROR_DELETED			=	array('id' => 24,	'title' => 'Site Error Deleted');
	
	public static function flip() {
		$oClass = new \ReflectionClass(__CLASS__);
		$oClass = $oClass->getConstants();
		foreach($oClass as $k => $v) {
			$oClass[$k] = $v['id'];
		}
		return array_flip($oClass);
	}
	
	public static function name($id) {
		return self::flip()[$id];
	}
	
	public static function title($id) {
		$oClass = new \ReflectionClass(__CLASS__);
		$oClass = $oClass->getConstants();
		foreach($oClass as $k => $v) {
			if($v['id'] == $id) { return $v['title']; }
		}
		return false;
	}
}
?>