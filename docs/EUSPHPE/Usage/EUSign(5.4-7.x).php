<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
										"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>EUSignPHP Demo</title>
	<meta name="AUTHOR" content="Copyright JSC IIT. All rights reserved.">
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
	<meta http-equiv="pragma" content="no-cache">
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" align="center" width="100%">
<tr><td width="100%">
<?php

//========================================================================================

include('EUSignConsts.php');

//========================================================================================

$iEncoding = EM_ENCODING_UTF8; // EM_ENCODING_CP1251 - default library encoding

$sData = '--Test data string - 1234567890!@#$%^&*()--';

$sTestDataFolder = 'D:\\TestData\\PHP\\';

$iPrivateKeyKMTypeIndex = 7;
$iPrivateKeyKMDeviceIndex = 2;
$sPrivateKeyKMPassword = '12345677';

$sPrivateKey = $sTestDataFolder.'Key-6.dat';
$sPrivateKeyPassword = '12345677';

$sPrivateKeyCtx1 = $sTestDataFolder.'Key-6.dat';
$sPrivateKeyCtxPassword1 = '12345677';
$sPrivateKeyCtx2 = $sTestDataFolder.'Key-6(2).dat';
$sPrivateKeyCtxPassword2 = '12345677';

$sJKSPrivateKey = $sTestDataFolder.'boss.jks';
$sJKSPrivateKeyPassword = '1111111111';

$sHashCert_GOST34311 = $sTestDataFolder.'Sign_1.cer';
$sResipientCert_1 = $sTestDataFolder.'Env_1.cer'; /* Must be own certificate */
$sResipientCert_2 = $sTestDataFolder.'Env_2.cer';

$sServerCert = $sTestDataFolder.'Env_1.cer'; /* Must be own certificate */

$sFileWithData = $sTestDataFolder.'Data.txt';

$sFileWithSigData = $sTestDataFolder.'Data.txt.p7s';
$sFileWithVerData = $sTestDataFolder.'Data.new.txt';

$sFileWithEnvData = $sTestDataFolder.'Data.txt.p7e';
$sFileWithDevData = $sTestDataFolder.'Data.new.txt';

$sFileWithFullCRRequest = $sTestDataFolder.'EU.p10';

//========================================================================================

$iResult = 0;
$iErrorCode = 0;

//========================================================================================

/* Signer & Sender info */

$bIsTSPUse = 0;
$sErrorDescription = "";
$sResultData = "";
$sInputData = "";
$sInputSignData = "";
$sSignTime = "";
$inputData = "";
$spIssuer = "";
$spIssuerCN = "";
$spSerial = "";
$spSubject = "";
$spSubjCN = "";
$spSubjOrg = "";
$spSubjOrgUnit = "";
$spSubjTitle = "";
$spSubjState = "";
$spSubjLocality = "";
$spSubjFullName = "";
$spSubjAddress = "";
$spSubjPhone = "";
$spSubjEMail = "";
$spSubjDNS = "";
$spSubjEDRPOUCode = "";
$spSubjDRFOCode = "";

//========================================================================================

function handle_result($sMsg, $iResult, $iErrorCode) {
	$sErrorDescription = "";
	$bError = ($iResult != EM_RESULT_OK) ;
	$sColor = ($bError) ? "#FF0000" : "#10C610";
	$sResultMsg = "";

	if ($bError) {
		euspe_geterrdescr($iErrorCode, $sErrorDescription);

		$sResultMsg = "Error, result code - ".$iResult.
			" error code - ".$iErrorCode." : ".$sErrorDescription;
	} else {
		$sResultMsg = "No error";
	}

	echo "EUSignPHP: ".$sMsg." - <font style=\"color:".$sColor."\">".$sResultMsg."</font><br>";

	return !$bError;
}

function print_result($sMsg, $sResultMsg) {
	$sColor = "#000FF0";
	$sSeparator = " - ";

	if ($sResultMsg == '')
		$sSeparator = ' : ';

	echo "EUSignPHP: ".$sMsg.$sSeparator."<font style=\"color:".$sColor."\">".$sResultMsg."</font><br>";
}

function bool_to_string($val) {
	return $val ? 'true' : 'false';
}

$sTAB = '&nbsp&nbsp&nbsp&nbsp';

//========================================================================================

/* Set charset for in/out strings parameters */

$iResult = euspe_setcharset($iEncoding);
if (!handle_result("SetCharset", $iResult, 0))
	Exit;

//----------------------------------------------------------------------------------------

/* Initialize library */

$iResult = euspe_init($iErrorCode);
if (!handle_result("Initialize", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Get filestore settings */

$sFileStorePath = '';
$bCheckCRLs = false;
$bAutoRefresh = false;
$bOwnCRLsOnly = false;
$bFullAndDeltaCRLs = false;
$bAutoDownloadCRLs = false;
$bSaveLoadedCerts = false;
$iExpireTime = 0;
$iResult = euspe_getfilestoresettings(
	$sFileStorePath,
	$bCheckCRLs, $bAutoRefresh, $bOwnCRLsOnly, 
	$bFullAndDeltaCRLs, $bAutoDownloadCRLs, 
	$bSaveLoadedCerts, $iExpireTime,
	$iErrorCode);
if (!handle_result("GetFileStoreSettings", $iResult, $iErrorCode))
	Exit;

print_result('File store settings', '');
print_result($sTAB.'path', $sFileStorePath);
print_result($sTAB.'check crls', bool_to_string($bCheckCRLs));
print_result($sTAB.'auto refresh', bool_to_string($bAutoRefresh));
print_result($sTAB.'own crls only', bool_to_string($bOwnCRLsOnly));
print_result($sTAB.'full and delta crls', bool_to_string($bFullAndDeltaCRLs));
print_result($sTAB.'auto download crls', bool_to_string($bAutoDownloadCRLs));
print_result($sTAB.'save loaded certs', bool_to_string($bSaveLoadedCerts));
print_result($sTAB.'expire time', $iExpireTime);

//----------------------------------------------------------------------------------------

/* Enum key medias */

$iTypeIndex = 0;
$sTypeName = '';
while (1) {
	$iResult = euspe_enumkeymediatypes($iTypeIndex, $sTypeName, $iErrorCode);
	if ($iResult != EM_RESULT_OK) {
		if ($iErrorCode == EU_WARNING_END_OF_ENUM)
			break;

		if (!handle_result("EnumKeyMediaTypes", $iResult, $iErrorCode))
			Exit;
	}

	print_result(($iTypeIndex).'', $sTypeName);

	$iDeviceIndex = 0;
	$sDeviceName = '';

	while (1) {
		$iResult = euspe_enumkeymediadevices($iTypeIndex,  $iDeviceIndex,
			$sDeviceName, $iErrorCode);
		if ($iResult != EM_RESULT_OK) {
			if ($iErrorCode == EU_WARNING_END_OF_ENUM)
				break;

			if (!handle_result("EnumKeyMediaDevices", $iResult, $iErrorCode))
				Exit;
		}

		print_result($sTAB.($iDeviceIndex).'', $sDeviceName);
		$iDeviceIndex++;
	}
	
	$iTypeIndex++;
}

//----------------------------------------------------------------------------------------

/* Read private key from settings */

$iResult = euspe_readprivatekey($iErrorCode);
if (!handle_result("ReadPrivateKey", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Check that the key is read */

$bIsPrivateKeyReaded = false;
$iResult = euspe_isprivatekeyreaded($bIsPrivateKeyReaded, $iErrorCode);
if (!handle_result("IsPrivateKeyReaded", $iResult, $iErrorCode))
	Exit;

print_result('IsPrivateKeyReaded', bool_to_string($bIsPrivateKeyReaded));

//----------------------------------------------------------------------------------------

/* Reset private key */

$iErrorCode = 0;
$iResult = euspe_resetprivatekey();
if (!handle_result("ResetPrivateKey", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Check that the key isn`t read */

$bIsPrivateKeyReaded = false;
$iResult = euspe_isprivatekeyreaded($bIsPrivateKeyReaded, $iErrorCode);
if (!handle_result("IsPrivateKeyReaded", $iResult, $iErrorCode))
	Exit;

print_result('IsPrivateKeyReaded', bool_to_string($bIsPrivateKeyReaded));

//----------------------------------------------------------------------------------------

/* Read private key silently */

$iResult = euspe_readprivatekeysilently(
	$iPrivateKeyKMTypeIndex, $iPrivateKeyKMDeviceIndex, 
	$sPrivateKeyKMPassword, $iErrorCode);
if (!handle_result("ReadPrivateKeySilently", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Read private key binary */

$sPrivateKeyData = file_get_contents($sPrivateKey, FILE_USE_INCLUDE_PATH);
$iResult = euspe_readprivatekeybinary(
	$sPrivateKeyData, $sPrivateKeyPassword, $iErrorCode);
if (!handle_result("ReadPrivateKeyBinary", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Read private key file */

$iResult = euspe_readprivatekeyfile(
	$sPrivateKey, $sPrivateKeyPassword, $iErrorCode);
if (!handle_result("ReadPrivateKeyFile", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Enum and read jks private key */

$iResult = euspe_setruntimeparameter(
	EU_RESOLVE_OIDS_PARAMETER, false, $iErrorCode);
if (!handle_result("SetRuntimeParameter(RESOLVE_OIDS)", $iResult, $iErrorCode))
	Exit;

$sJKSPrivateKeyData = file_get_contents($sJKSPrivateKey, FILE_USE_INCLUDE_PATH);

$iKeyIndex = 0;
$sKeyAlias = '';
$sPrivateKeyData = null;
$aCertificates = null;
$certInfo = '';

while (1) {
	$iResult = euspe_enumjksprivatekeys(
		$sJKSPrivateKeyData, $iKeyIndex, $sKeyAlias, $iErrorCode);
	if ($iResult != EM_RESULT_OK) {
		if ($iErrorCode == EU_WARNING_END_OF_ENUM)
			break;

		if (!handle_result("EnumJKSPrivateKey", $iResult, $iErrorCode))
			Exit;
	}

	print_result(($iKeyIndex).'', $sKeyAlias);
	$iResult = euspe_getjksprivatekey($sJKSPrivateKeyData, 
		$sKeyAlias, $sPrivateKeyData, $aCertificates, $iErrorCode);
	if (!handle_result("GetJKSPrivateKey", $iResult, $iErrorCode))
		Exit;

	for ($i = 0; $i < count($aCertificates); $i++) {
		$iResult = euspe_parsecert($aCertificates[$i], $certInfo, $iErrorCode);
		if (!handle_result("ParseCertificate", $iResult, $iErrorCode))
			Exit;

		if ($certInfo['subjType'] != EU_SUBJECT_TYPE_END_USER)
			continue;

		print_result('Certificate info', '');
		print_result($sTAB.'subject', $certInfo['subject']);
		print_result($sTAB.'serial', $certInfo['serial']);
		print_result($sTAB.'issuer', $certInfo['issuer']);
		print_result($sTAB.'keyUsage', $certInfo['keyUsage']);

		$isDigitalStamp = false;
		$keyUsages = explode(",", $certInfo['extKeyUsages']);
		foreach ($keyUsages as &$keyUsage) {
			$keyUsage = str_replace(' ', '', $keyUsage);
			if ($keyUsage == EU_OID_EXT_KEY_USAGE_STAMP) {
				$isDigitalStamp = true;
				break;
			}
		}

		print_result($sTAB.'isDigitalStamp', 
			bool_to_string($isDigitalStamp));
	}

	$iResult = euspe_readprivatekeybinary(
		$sPrivateKeyData, $sJKSPrivateKeyPassword, $iErrorCode);
	if (!handle_result("ReadPrivateKeyBinary(JKS)", $iResult, $iErrorCode))
		Exit;

	euspe_resetprivatekey();
	
	$iKeyIndex++;
}

$iResult = euspe_setruntimeparameter(
	EU_RESOLVE_OIDS_PARAMETER, true, $iErrorCode);
if (!handle_result("SetRuntimeParameter(RESOLVE_OIDS)", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Parse certificate */

$certInfo = '';
$certData = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$iResult = euspe_parsecert($certData, $certInfo, $iErrorCode);
if (!handle_result("ParseCertificate", $iResult, $iErrorCode))
	Exit;

print_result('Certificate info', '');
var_dump($certInfo);
echo "<br>";

//----------------------------------------------------------------------------------------

/* Get signer certificate & info */

$sSign = "";
$sSignsCount = 0;
$signerInfo = '';
$signerCert = '';

$iResult = euspe_signcreate($sData, $sSign, $iErrorCode);
if (!handle_result("SignCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_getsignscount($sSign, 
	$sSignsCount, $iErrorCode);
if (!handle_result("GetSignsCount", $iResult, $iErrorCode))
	Exit;

print_result('Signers', $sSignsCount);

$iResult = euspe_getsignerinfoex(0, $sSign, 
	$signerInfo, $signerCert, $iErrorCode);
if (!handle_result("GetSignerInfoEx", $iResult, $iErrorCode))
	Exit;

print_result('Signer certificate info', '');
var_dump($signerInfo);
echo "<br>";

$certFileName = './EU-'.$signerInfo['serial'].'.cer';
file_put_contents($certFileName, $signerCert);
print_result('Signer certificate file', $certFileName);

//----------------------------------------------------------------------------------------

/* Get sign time info */

$sSign = "";
$sSignsCount = 0;
$timeInfo = '';

$iResult = euspe_signcreate($sData, $sSign, $iErrorCode);
if (!handle_result("SignCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_getsigntimeinfo(0, $sSign, 
	$timeInfo, $iErrorCode);
if (!handle_result("GetSignTimeInfo", $iResult, $iErrorCode))
	Exit;

print_result('Sign time info', '');
var_dump($timeInfo);
echo "<br>";

//----------------------------------------------------------------------------------------

/* Hash data */

$sHash = '';

$iResult = euspe_hashdata(
	$sData, $sHash, $iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Hash data continue (not thread safe) */

$sHash = '';

$iResult = euspe_hashdatacontinue($sData, $iErrorCode);
if (!handle_result("HashDataContinue-1", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_hashdatacontinue($sData, $iErrorCode);
if (!handle_result("HashDataContinue-2", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_hashdataend($sHash, $iErrorCode);
if (!handle_result("HashDataEnd", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Hash file */

$sHash = '';

$iResult = euspe_hashfile(
	$sFileWithData, $sHash, $iErrorCode);
if (!handle_result("HashFile", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Sign hash */

$sHash = '';
$sSign = '';

$iResult = euspe_hashdata(
	$sData, $sHash, $iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_signhash(
	$sHash, $sSign, $iErrorCode);
if (!handle_result("SignHash", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_verifyhashsign(
	$sHash, $sSign, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("VerifyHash", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data internal */

$sSign = '';
$sVerData = '';

$iResult = euspe_signcreate(
	$sData, $sSign, $iErrorCode);
if (!handle_result("SignData (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverify(
	$sSign, $sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$sVerData,
	$iErrorCode);
if (!handle_result("VerifySign (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data external */

$sSign = '';

$iResult = euspe_signcreateext(
	$sData, $sSign, $iErrorCode);
if (!handle_result("SignData (external)", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverifyext(
	$sData, $sSign, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("VerifySign (external)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign file internal */

$bExternal = false;

$iResult = euspe_signfile(
	$sFileWithData, $sFileWithSigData, $bExternal, $iErrorCode);
if (!handle_result("SignFile (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Signed file', $sFileWithSigData);

$iResult = euspe_verifyfile(
	$sFileWithSigData, $sFileWithVerData, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("VerifyFile (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Verified file', $sFileWithVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign hash */

$sHash = '';
$sSign = '';

$iResult = euspe_hashdata(
	$sData, $sHash, $iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_rawsignhash(
	$sHash, $sSign, $iErrorCode);
if (!handle_result("RawSignHash", $iResult, $iErrorCode))
	Exit;

print_result('RawSign', $sSign);

$iResult = euspe_rawverifyhashsign(
	$sHash, $sSign, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("RawVerifyHash", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign data */

$sSign = '';

$iResult = euspe_rawsign(
	$sData, $sSign, $iErrorCode);
if (!handle_result("RawSignData", $iResult, $iErrorCode))
	Exit;

print_result('RawSign', $sSign);

$iResult = euspe_rawverifysign(
	$sData, $sSign, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("RawVerifySign", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign file */

$iResult = euspe_rawsignfile(
	$sFileWithData, $sFileWithSigData, $iErrorCode);
if (!handle_result("RawSignFile", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_rawverifyfilesign(
	$sFileWithSigData, $sFileWithData, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("RawVerifySignFile", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data separate (external) */

$sHash = '';
$sSigner = '';
$sPreviousSign = '';
$sSign = '';
$sSignerCert = null;

$iResult = euspe_hashdata(
	$sData, $sHash, $iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_createsigner(
	$sHash, $sSigner, $iErrorCode);
if (!handle_result("CreateSigner", $iResult, $iErrorCode))
	Exit;

print_result('Signer', $sSigner);

$iResult = euspe_createemptysign(
	null, $sPreviousSign, $iErrorCode);
if (!handle_result("CreateEmptySign", $iResult, $iErrorCode))
	Exit;

print_result('PreviousSign', $sPreviousSign);

$iResult = euspe_appendsigner(
	$sSigner, $sSignerCert, $sPreviousSign, $sSign, $iErrorCode);
if (!handle_result("AppendSigner", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverifyext(
	$sData, $sSign, 
	$sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("VerifySign (external)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data separate (internal) */

$sHash = '';
$sSigner = '';
$sPreviousSign = '';
$sSign = '';
$sSignerCert = null;

$iResult = euspe_hashdata(
	$sData, $sHash, $iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_createsigner(
	$sHash, $sSigner, $iErrorCode);
if (!handle_result("CreateSigner", $iResult, $iErrorCode))
	Exit;

print_result('Signer', $sSigner);

$iResult = euspe_createemptysign(
	$sData, $sPreviousSign, $iErrorCode);
if (!handle_result("CreateEmptySign", $iResult, $iErrorCode))
	Exit;

print_result('PreviousSign', $sPreviousSign);

$iResult = euspe_appendsigner(
	$sSigner, $sSignerCert, $sPreviousSign, $sSign, $iErrorCode);
if (!handle_result("AppendSigner", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverify(
	$sSign, $sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$sVerData,
	$iErrorCode);
if (!handle_result("VerifySign (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Envelop data */

$sEnv = '';
$spDevData = '';

$cert1 = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$cert2 = file_get_contents($sResipientCert_2, FILE_USE_INCLUDE_PATH);
$certs = array($cert1, $cert2);

$iResult = euspe_envelop_to_recipients(
	$certs, false, $sData, $sEnv, $iErrorCode);
if (!handle_result("Envelop", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_develop(
	$sEnv, $spDevData, $sSignTime,
	$bIsTSPUse, $spIssuer,
	$spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("Develop", $iResult, $iErrorCode))
	Exit;

print_result('Developed string', $spDevData);
print_result('Sender info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Envelop file */

$cert1 = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$cert2 = file_get_contents($sResipientCert_2, FILE_USE_INCLUDE_PATH);
$certs = array($cert1, $cert2);

$iResult = euspe_envelopfile_to_recipients(
	$certs, false, $sFileWithData, $sFileWithEnvData, $iErrorCode);
if (!handle_result("EnvelopFile", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_developfile(
	$sFileWithEnvData, $sFileWithDevData, $sSignTime,
	$bIsTSPUse, $spIssuer,
	$spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("DevelopFile", $iResult, $iErrorCode))
	Exit;

print_result('Developed file', $sFileWithDevData);
print_result('Sender info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Session test (encrypt/decrypt) */

function session_test_encrypt($clientSession, $serverSession) {
	$sData = '--Data to test session envelop 12345677--';
	$sEnvData = '';
	$sDevData = '';
	$peerCertInfo = '';

	$bIsSessionInitialized = '';
	$iErrorCode = '';
	$iResult = '';

	$iResult = euspe_sessionisinitialized($serverSession, 
		$bIsSessionInitialized, $iErrorCode);
	if (!handle_result("SessionIsInitialized (client)", $iResult, $iErrorCode))
		return false;

	print_result('IsSessionInitialized (client)', 
		bool_to_string($bIsSessionInitialized));

	$iResult = euspe_sessionisinitialized($serverSession, 
		$bIsSessionInitialized, $iErrorCode);
	if (!handle_result("SessionIsInitialized (server)", $iResult, $iErrorCode))
		return false;

	print_result('IsSessionInitialized (server)', 
		bool_to_string($bIsSessionInitialized));

	$iResult = euspe_sessioncheckcertificates($serverSession, $iErrorCode);
	if (!handle_result("SessionCheckCertificates (server)", $iResult, $iErrorCode))
		return false;

	$iResult = euspe_sessiongetpeercertinfo(
		$serverSession, $peerCertInfo, $iErrorCode);
	if (!handle_result("SessionGetPeerCertInfo (server)", $iResult, $iErrorCode))
		false;

	print_result('Client certificate info', '');
	var_dump($peerCertInfo);
	echo "<br>";

	$iResult = euspe_sessiongetpeercertinfo(
		$clientSession, $peerCertInfo, $iErrorCode);
	if (!handle_result("SessionGetPeerCertInfo (client)", $iResult, $iErrorCode))
		false;

	print_result('Server certificate info', '');
	var_dump($peerCertInfo);
	echo "<br>";

	$iResult = euspe_sessionencrypt(
		$clientSession, $sData, $sEnvData, $iErrorCode);
	if (!handle_result("SessionEncrypt", $iResult, $iErrorCode))
		false;

	$iResult = euspe_sessiondecrypt(
		$serverSession, $sEnvData, $sDevData, $iErrorCode);
	if (!handle_result("SessionDecrypt", $iResult, $iErrorCode))
		false;

	print_result('Decrypted string', $sDevData);

	$sEnvData = substr($sData, 0);
	$iResult = euspe_sessionencryptcontinue(
		$clientSession, $sEnvData, $iErrorCode);
	if (!handle_result("SessionEncryptContinue", $iResult, $iErrorCode))
		false;

	print_result('Encrypted string', $sEnvData);

	$iResult = euspe_sessiondecryptcontinue(
		$serverSession, $sEnvData, $iErrorCode);
	if (!handle_result("SessionDecryptContinue", $iResult, $iErrorCode))
		false;

	print_result('Decrypted string', $sEnvData);
	
	return true;
}

//----------------------------------------------------------------------------------------

/* Session test (general) */

$iExpireTime = 3600;
$clientSession = '';
$serverSession = '';
$sClientData = '';
$sServerData = '';
$sClientSessionData = '';
$sServerSessionData = '';

$iResult = euspe_clientsessioncreate_step1(
	$iExpireTime, $clientSession, $sClientData, $iErrorCode);
if (!handle_result("ClientSessionCreateStep1", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_serversessioncreate_step1(
	$iExpireTime, $sClientData, $serverSession, $sServerData, $iErrorCode);
if (!handle_result("ServerSessionCreateStep1", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	Exit;
}

$iResult = euspe_clientsessioncreate_step2(
	$clientSession, $sServerData, $sClientData, $iErrorCode);
if (!handle_result("ClientSessionCreateStep2", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

$iResult = euspe_serversessioncreate_step2(
	$serverSession, $sClientData, $iErrorCode);
if (!handle_result("ServerSessionCreateStep2", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

if (!session_test_encrypt($clientSession, $serverSession))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

/* Session test (save/load) */

$iResult = euspe_sessionsave($clientSession, 
	$sClientSessionData, $iErrorCode);
if (!handle_result("SessionSave (client)", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

$iResult = euspe_sessionsave($serverSession, 
	$sServerSessionData, $iErrorCode);
if (!handle_result("SessionSave (server)", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

euspe_sessiondestroy($clientSession);
euspe_sessiondestroy($serverSession);

$iResult = euspe_sessionload($sClientSessionData,
	$clientSession, $iErrorCode);
if (!handle_result("SessionLoad (client)", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_sessionload($sServerSessionData,
	$serverSession, $iErrorCode);
if (!handle_result("SessionLoad (server)", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	Exit;
}

if (!session_test_encrypt($clientSession, $serverSession))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

euspe_sessiondestroy($clientSession);
euspe_sessiondestroy($serverSession);

//----------------------------------------------------------------------------------------

/* Session test (client dynamic key) */

$iExpireTime = 3600;
$clientSession = '';
$serverSession = '';
$sClientData = '';
$serverCert = '';
$serverCertInfo = '';

$serverCert = file_get_contents($sServerCert, FILE_USE_INCLUDE_PATH);

$iResult = euspe_parsecert($serverCert, $serverCertInfo, $iErrorCode);
if (!handle_result("ParseCertificate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_clientdynamickeysessioncreate_cert(
	$iExpireTime, $serverCert,
	$clientSession, $sClientData, $iErrorCode);
if (!handle_result("ClientDynamicKeySessionCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_serverdynamickeysessioncreate(
	$iExpireTime, $sClientData, $serverSession, $iErrorCode);
if (!handle_result("ServerDynamicKeySessionCreate", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	Exit;
}

if (!session_test_encrypt($clientSession, $serverSession))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

euspe_sessiondestroy($clientSession);
euspe_sessiondestroy($serverSession);

$iResult = euspe_clientdynamickeysessioncreate(
	$iExpireTime, $serverCertInfo['issuer'], $serverCertInfo['serial'],
	$clientSession, $sClientData, $iErrorCode);
if (!handle_result("ClientDynamicKeySessionCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_serverdynamickeysessioncreate(
	$iExpireTime, $sClientData, $serverSession, $iErrorCode);
if (!handle_result("ServerDynamicKeySessionCreate", $iResult, $iErrorCode))
{
	euspe_sessiondestroy($clientSession);
	Exit;
}

if (!session_test_encrypt($clientSession, $serverSession))
{
	euspe_sessiondestroy($clientSession);
	euspe_sessiondestroy($serverSession);
	Exit;
}

euspe_sessiondestroy($clientSession);
euspe_sessiondestroy($serverSession);

//----------------------------------------------------------------------------------------

/* Parse certificate request */

$crInfo = '';
$crData = file_get_contents($sFileWithFullCRRequest, FILE_USE_INCLUDE_PATH);
$iResult = euspe_getcrinfo($crData, $crInfo, $iErrorCode);
if (!handle_result("GetCRInfo", $iResult, $iErrorCode))
	Exit;

print_result('CR info', '');
var_dump($crInfo);
echo "<br>";

//----------------------------------------------------------------------------------------

/* Create context */

$context = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxfree($context);
if (!handle_result("CtxFree", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Read private key from settings to context */

$context = '';
$pkContext = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekey($context, $pkContext, $iErrorCode);
if (!handle_result("CtxReadPrivateKey", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxfreeprivatekey($pkContext);
if (!handle_result("CtxFreePrivateKey", $iResult, $iErrorCode))
{
	euspe_ctxfree($pkContext);
	Exit;
}

$iResult = euspe_ctxfree($context);
if (!handle_result("CtxFree", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Read private key silently to context */

$context = '';
$pkContext = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekeysilently($context,
	$iPrivateKeyKMTypeIndex, $iPrivateKeyKMDeviceIndex, 
	$sPrivateKeyKMPassword, $pkContext, $iErrorCode);
if (!handle_result("CtxReadPrivateKeySilently", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfreeprivatekey($pkContext);
euspe_ctxfree($context);

//----------------------------------------------------------------------------------------

/* Read private key binary to context */

$context = '';
$pkContext = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$sPrivateKeyData = file_get_contents($sPrivateKey, FILE_USE_INCLUDE_PATH);
$iResult = euspe_ctxreadprivatekeybinary($context,
	$sPrivateKeyData, $sPrivateKeyPassword, $pkContext, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyBinary", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfreeprivatekey($pkContext);
euspe_ctxfree($context);

//----------------------------------------------------------------------------------------

/* Read private key file to context */

$context = '';
$pkContext = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKey, $sPrivateKeyPassword, $pkContext, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfreeprivatekey($pkContext);
euspe_ctxfree($context);

//----------------------------------------------------------------------------------------

/* Hash data with context */

$context = '';
$sCertificate = null;
$sHash = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxhashdata(
	$context, EU_CTX_HASH_ALGO_GOST34311,
	$sCertificate, $sData, $sHash, $iErrorCode);
if (!handle_result("CtxHashData", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

print_result('Hash', $sHash);

$sCertificate = file_get_contents($sHashCert_GOST34311, FILE_USE_INCLUDE_PATH);
$iResult = euspe_ctxhashdata($context, EU_CTX_HASH_ALGO_GOST34311,
	$sCertificate , $sData, $sHash, $iErrorCode);
if (!handle_result("CtxHashData(params from cert)", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfree($context);

//----------------------------------------------------------------------------------------

/* Hash data continue with context */

$context = '';
$sCertificate = null;

$sHash = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

function ctx_test_hash_itterative($context, $iHashAlgo, $sData, $sCertificate) {
	$iErrorCode = '';
	$iResult = '';
	$hashContext = '';

	$iResult = euspe_ctxhashdatabegin(
		$context, $iHashAlgo, $sCertificate, $hashContext, $iErrorCode);
	if (!handle_result("CtxHashDataBegin", $iResult, $iErrorCode))
		return false;

	$iResult = euspe_ctxhashdatacontinue(
		$hashContext, $sData, $iErrorCode);
	if (!handle_result("CtxHashDataContinue-1", $iResult, $iErrorCode))
	{
		euspe_ctxfreehash($hashContext);
		return false;
	}

	$iResult = euspe_ctxhashdatacontinue(
		$hashContext, $sData, $iErrorCode);
	if (!handle_result("CtxHashDataContinue-2", $iResult, $iErrorCode))
	{
		euspe_ctxfreehash($hashContext);
		return false;
	}

	$iResult = euspe_ctxhashdataend(
		$hashContext, $sHash, $iErrorCode);
	if (!handle_result("CtxHashDataEnd", $iResult, $iErrorCode))
	{
		euspe_ctxfreehash($hashContext);
		return false;
	}

	euspe_ctxfreehash($hashContext);

	print_result('Hash', $sHash);

	return true;
}

$iHashAlgo = EU_CTX_HASH_ALGO_GOST34311;
print_result('Hash data with context (GOST34311)', '');
if (!ctx_test_hash_itterative($context, $iHashAlgo, $sData, null))
{
	euspe_ctxfree($context);
	Exit;
}

$iHashAlgo = EU_CTX_HASH_ALGO_GOST34311;
$sCertificate = file_get_contents($sHashCert_GOST34311, FILE_USE_INCLUDE_PATH);
print_result('Hash data with context (GOST34311, certificate)', '');
if (!ctx_test_hash_itterative($context, $iHashAlgo, $sData, $sCertificate))
{
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfree($context);

//----------------------------------------------------------------------------------------

/* Sign hash data with context */

$context = '';
$pkContext1 = '';
$sSignCerificateInfo1 = '';
$sSignCertificate1 = '';
$pkContext2 = '';
$sSignCertificate2 = '';
$sSignCerificateInfo2 = '';
$sHash1 = '';
$sHash2 = '';
$sSign = '';
$bIsAlreadySigned = false;
$bAppendCert = true;
$sSignsCount = 0;
$signerInfo = '';
$signerCert = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKeyCtx1, $sPrivateKeyCtxPassword1, $pkContext1, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile-1", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKeyCtx2, $sPrivateKeyCtxPassword2, $pkContext2, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile-2", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxgetowncertificate($pkContext1, 
	EU_CERT_KEY_TYPE_DSTU4145, EU_KEY_USAGE_DIGITAL_SIGNATURE,
	$sSignCerificateInfo1, $sSignCertificate1, $iErrorCode);
if (!handle_result("CtxGetOwnCertificate-1", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('Signer-1 certificate info', '');
var_dump($sSignCerificateInfo1);
echo "<br>";

$iResult = euspe_ctxgetowncertificate($pkContext2, 
	EU_CERT_KEY_TYPE_DSTU4145, EU_KEY_USAGE_DIGITAL_SIGNATURE,
	$sSignCerificateInfo2, $sSignCertificate2, $iErrorCode);
if (!handle_result("CtxGetOwnCertificate-2", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('Signer-2 certificate info', '');
var_dump($sSignCerificateInfo2);
echo "<br>";

$iResult = euspe_ctxhashdata(
	$context, EU_CTX_HASH_ALGO_GOST34311,
	$sSignCertificate1, $sData, $sHash1, $iErrorCode);
if (!handle_result("CtxHashData-1", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('Hash-1', $sHash1);

$iResult = euspe_ctxhashdata(
	$context, EU_CTX_HASH_ALGO_GOST34311,
	$sSignCertificate2, $sData, $sHash2, $iErrorCode);
if (!handle_result("CtxHashData-2", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('Hash-2', $sHash2);

$iResult = euspe_ctxsignhashvalue($pkContext1, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sHash1, 
	$bAppendCert, $sSign, $iErrorCode);
if (!handle_result("CtxSignHashValue", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxisalreadysigned($pkContext1, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode);
if (!handle_result("CtxIsAlreadySigned", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('CtxIsAlreadySigned-1', bool_to_string($bIsAlreadySigned));

$iResult = euspe_ctxisalreadysigned($pkContext2, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode);
if (!handle_result("CtxIsAlreadySigned", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('CtxIsAlreadySigned-2', bool_to_string($bIsAlreadySigned));

$iResult = euspe_ctxappendsignhashvalue($pkContext2, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sHash2, $sSign, 
	$bAppendCert, $sSign, $iErrorCode);
if (!handle_result("CtxAppendSignHashValue", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfreeprivatekey($pkContext2);
euspe_ctxfreeprivatekey($pkContext1);
euspe_ctxfree($context);

$iResult = euspe_getsignscount($sSign, 
	$sSignsCount, $iErrorCode);
if (!handle_result("GetSignsCount", $iResult, $iErrorCode))
	Exit;

print_result('Signers', $sSignsCount);

for ($i = 0; $i < $sSignsCount; $i++)
{
	$iResult = euspe_getsignerinfoex($i, $sSign, 
		$signerInfo, $signerCert, $iErrorCode);
	if (!handle_result("GetSignerInfoEx", $iResult, $iErrorCode))
	{
		Exit;
	}
	
	print_result('Signer certificate info-'.($i + 1), '');
	var_dump($signerInfo);
	echo "<br>";
}

//----------------------------------------------------------------------------------------

/* Sign data with context */

$context = '';
$pkContext1 = '';
$pkContext2 = '';
$sSign = '';
$bIsAlreadySigned = false;
$bExternal = true;
$bAppendCert = true;
$sSignsCount = 0;
$signerInfo = '';
$signerCert = '';

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKeyCtx1, $sPrivateKeyCtxPassword1, $pkContext1, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile-1", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKeyCtx2, $sPrivateKeyCtxPassword2, $pkContext2, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile-2", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxsigndata($pkContext1, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sData, 
	$bExternal, $bAppendCert, $sSign, $iErrorCode);
if (!handle_result("CtxSignData", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxisalreadysigned($pkContext1, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode);
if (!handle_result("CtxIsAlreadySigned", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('CtxIsAlreadySigned-1', bool_to_string($bIsAlreadySigned));

$iResult = euspe_ctxisalreadysigned($pkContext2, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sSign, $bIsAlreadySigned, $iErrorCode);
if (!handle_result("CtxIsAlreadySigned", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

print_result('CtxIsAlreadySigned-2', bool_to_string($bIsAlreadySigned));

$iResult = euspe_ctxappendsign($pkContext2, 
	EU_CTX_SIGN_DSTU4145_WITH_GOST34311, $sData, $sSign, 
	$bAppendCert, $sSign, $iErrorCode);
if (!handle_result("CtxAppendSign", $iResult, $iErrorCode))
{
	euspe_ctxfreeprivatekey($pkContext2);
	euspe_ctxfreeprivatekey($pkContext1);
	euspe_ctxfree($context);
	Exit;
}

euspe_ctxfreeprivatekey($pkContext2);
euspe_ctxfreeprivatekey($pkContext1);
euspe_ctxfree($context);

$iResult = euspe_getsignscount($sSign, 
	$sSignsCount, $iErrorCode);
if (!handle_result("GetSignsCount", $iResult, $iErrorCode))
	Exit;

print_result('Signers', $sSignsCount);

for ($i = 0; $i < $sSignsCount; $i++)
{
	$iResult = euspe_getsignerinfoex($i, $sSign, 
		$signerInfo, $signerCert, $iErrorCode);
	if (!handle_result("GetSignerInfoEx", $iResult, $iErrorCode))
	{
		Exit;
	}
	
	print_result('Signer certificate info-'.($i + 1), '');
	var_dump($signerInfo);
	echo "<br>";
}

//----------------------------------------------------------------------------------------

/* Envelop data with context*/

$context = '';
$pkContext = '';
$bSign = false;
$bAppendCert = false;
$sEnv = '';
$spDevData = '';
$sSenderCert = null;

$cert1 = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$cert2 = file_get_contents($sResipientCert_2, FILE_USE_INCLUDE_PATH);
$certs = array($cert1, $cert2);

$iResult = euspe_ctxcreate($context, $iErrorCode);
if (!handle_result("CtxCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_ctxreadprivatekeyfile($context,
	$sPrivateKey, $sPrivateKeyPassword, $pkContext, $iErrorCode);
if (!handle_result("CtxReadPrivateKeyFile", $iResult, $iErrorCode))
{
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxenvelopdata($pkContext, $certs, 
	EU_RECIPIENT_APPEND_TYPE_BY_ISSUER_SERIAL, 
	$bSign, $bAppendCert, $sData, $sEnv, $iErrorCode);
if (!handle_result("CtxEnvelopData", $iResult, $iErrorCode))
{
	euspe_ctxfree($pkContext);
	euspe_ctxfree($context);
	Exit;
}

$iResult = euspe_ctxdevelopdata($pkContext, 
	$sEnv, $sSenderCert, $spDevData, $sSignTime,
	$bIsTSPUse, $spIssuer,
	$spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$iErrorCode);
if (!handle_result("CtxDevelopData", $iResult, $iErrorCode))
	Exit;

print_result('Developed string', $spDevData);
print_result('Sender info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Make internal sign from external sign */

$sSign = '';
$sSigner = '';
$sPreviousSign = '';
$signerInfo = null;
$sSignerCert = null;

$iResult = euspe_signcreateext(
	$sData, $sSign, $iErrorCode);
if (!handle_result("SignData (external)", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_getsigner(
	0, $sSign, $sSigner, $iErrorCode);
if (!handle_result("GetSigner", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_getsignerinfoex(
	0, $sSign, $signerInfo, $sSignerCert, $iErrorCode);
if (!handle_result("GetSignerInfoEx", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_appendvalidationdatatosigner(
	$sSigner, $sSignerCert, $sSigner, $iErrorCode);
if (!handle_result("AppendValidationDataToSigner", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_createemptysign(
	$sData, $sPreviousSign, $iErrorCode);
if (!handle_result("CreateEmptySign", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_appendsigner(
	$sSigner, $sSignerCert, $sPreviousSign, $sSign, $iErrorCode);
if (!handle_result("AppendSigner", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverify(
	$sSign, $sSignTime, $bIsTSPUse,
	$spIssuer, $spIssuerCN, $spSerial,
	$spSubject, $spSubjCN,
	$spSubjOrg, $spSubjOrgUnit,
	$spSubjTitle, $spSubjState,
	$spSubjLocality, $spSubjFullName,
	$spSubjAddress, $spSubjPhone,
	$spSubjEMail, $spSubjDNS,
	$spSubjEDRPOUCode, $spSubjDRFOCode,
	$sVerData,
	$iErrorCode);
if (!handle_result("VerifySign (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result($sTAB.'subject', $spSubjCN);
print_result($sTAB.'serial', $spSerial);
print_result($sTAB.'issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Finalize */

euspe_finalize();

?>