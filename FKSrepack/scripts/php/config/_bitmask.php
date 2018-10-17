<?PHP
/***********************************************
	Custom Bitmask(s) to use
	- rename to "bitmask.php" to use
***********************************************/
/*----------------------------------------------
	Test Bitmask
----------------------------------------------*/
class TestBitmask extends Bitmask {
	CONST NO_ACCESS				=	0;
	
	CONST ACCESS_1				=	1;
	CONST ACCESS_2				=	2;
	CONST ACCESS_3				=	4;
	CONST ACCESS_4				=	8;
	CONST ACCESS_5				=	16;
	
	CONST MAX					=	31;
}
?>