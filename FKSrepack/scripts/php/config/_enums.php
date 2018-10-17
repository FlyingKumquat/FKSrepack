<?PHP namespace Enums;
/***********************************************
	Additional Enums to load
	- rename to "enums.php" to use
***********************************************/
/*----------------------------------------------
	Log Actions (Extended)
----------------------------------------------*/
abstract class LogActionsX {
	// Started at 100 to leave room for FKS
	CONST ENUM_NAME	=	array('id' => 100,	'title' => 'Enum Title');
}

/*----------------------------------------------
	Data Types (Extended)
----------------------------------------------*/
abstract class DataTypesX {
	// Started at 100 to leave room for FKS
	CONST DATA_TYPE_NAME = array(
		'id' => 100,
		'title' => 'Data Type Title',
		'help_text' => '',
		'read_only' => 0,
		'validation' => '',
		'hidden' => 1,
		'input_type' => 'text'
	);
}
?>