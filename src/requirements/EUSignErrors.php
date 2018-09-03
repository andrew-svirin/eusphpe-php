<?php

//========================================================================================

define("EM_RESULT_OK", 							0);
define("EM_RESULT_ERROR", 						1);
define("EM_RESULT_ERROR_WRONG_PARAMS", 			2);
define("EM_RESULT_ERROR_INITIALIZED", 			3);

//----------------------------------------------------------------------------------------

define("EU_ERROR_NONE",							0x0000);
define("EU_ERROR_UNKNOWN",						0xFFFF);
define("EU_ERROR_NOT_SUPPORTED",				0xFFFE);

define("EU_ERROR_NOT_INITIALIZED",				0x0001);
define("EU_ERROR_BAD_PARAMETER",				0x0002);
define("EU_ERROR_LIBRARY_LOAD",					0x0003);
define("EU_ERROR_READ_SETTINGS",				0x0004);
define("EU_ERROR_TRANSMIT_REQUEST",				0x0005);
define("EU_ERROR_MEMORY_ALLOCATION",			0x0006);
define("EU_WARNING_END_OF_ENUM",				0x0007);
define("EU_ERROR_PROXY_NOT_AUTHORIZED",			0x0008);
define("EU_ERROR_NO_GUI_DIALOGS",				0x0009);
define("EU_ERROR_DOWNLOAD_FILE",				0x000A);
define("EU_ERROR_WRITE_SETTINGS",				0x000B);
define("EU_ERROR_CANCELED_BY_GUI",				0x000C);
define("EU_ERROR_OFFLINE_MODE",					0x000D);

define("EU_ERROR_KEY_MEDIAS_FAILED",			0x0011);
define("EU_ERROR_KEY_MEDIAS_ACCESS_FAILED",		0x0012);
define("EU_ERROR_KEY_MEDIAS_READ_FAILED",		0x0013);
define("EU_ERROR_KEY_MEDIAS_WRITE_FAILED",		0x0014);
define("EU_WARNING_KEY_MEDIAS_READ_ONLY",		0x0015);
define("EU_ERROR_KEY_MEDIAS_DELETE",			0x0016);
define("EU_ERROR_KEY_MEDIAS_CLEAR",				0x0017);
define("EU_ERROR_BAD_PRIVATE_KEY",				0x0018);

define("EU_ERROR_PKI_FORMATS_FAILED",			0x0021);
define("EU_ERROR_CSP_FAILED",					0x0022);
define("EU_ERROR_BAD_SIGNATURE",				0x0023);
define("EU_ERROR_AUTH_FAILED",					0x0024);
define("EU_ERROR_NOT_RECEIVER",					0x0025);

define("EU_ERROR_STORAGE_FAILED",				0x0031);
define("EU_ERROR_BAD_CERT",						0x0032);
define("EU_ERROR_CERT_NOT_FOUND",				0x0033);
define("EU_ERROR_INVALID_CERT_TIME",			0x0034);
define("EU_ERROR_CERT_IN_CRL",					0x0035);
define("EU_ERROR_BAD_CRL",						0x0036);
define("EU_ERROR_NO_VALID_CRLS",				0x0037);

define("EU_ERROR_GET_TIME_STAMP",				0x0041);
define("EU_ERROR_BAD_TSP_RESPONSE",				0x0042);
define("EU_ERROR_TSP_SERVER_CERT_NOT_FOUND",	0x0043);
define("EU_ERROR_TSP_SERVER_CERT_INVALID",		0x0044);

define("EU_ERROR_GET_OCSP_STATUS",				0x0051);
define("EU_ERROR_BAD_OCSP_RESPONSE",			0x0052);
define("EU_ERROR_CERT_BAD_BY_OCSP",				0x0053);
define("EU_ERROR_OCSP_SERVER_CERT_NOT_FOUND",	0x0054);
define("EU_ERROR_OCSP_SERVER_CERT_INVALID",		0x0055);

define("EU_ERROR_LDAP_ERROR",					0x0061);

//========================================================================================

?>