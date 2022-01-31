<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
										"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>EUSignPHP Demo</title>
	<meta name="AUTHOR" content="Copyright JSC IIT. All rights reserved.">
	<meta content="text/html; charset=windows-1251" http-equiv="Content-Type">
	<meta http-equiv="pragma" content="no-cache">
	 <meta charset="charset=windows-1251"> 
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" align="center" width="100%">
<tr><td width="100%">
<?php

//========================================================================================

include('EUSignConsts.php');

//========================================================================================

$sData = '--Test data string - 1234567890!@#$%^&*()--';

$sResipientCert_1 = './TestData/Env_1.cer'; /* Must be own certificate */
$sResipientCert_2 = './TestData/Env_2.cer';

$sFileWithData = 'C:\Temp\TestData\Data.txt';

$sFileWithSigData = 'C:\Temp\TestData\Data.txt.p7s';
$sFileWithVerData = 'C:\Temp\TestData\Data.new.txt';

$sFileWithEnvData = 'C:\Temp\TestData\Data.txt.p7e';
$sFileWithDevData = 'C:\Temp\TestData\Data.new.txt';

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
		euspe_geterrdescr($iErrorCode, &$sErrorDescription);

		$sResultMsg = "Error, result code - ".$iResult.
			"error code - ".$iErrorCode." : ".$sErrorDescription;
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

//========================================================================================

/* Initialize library */

$iResult = euspe_init(&$iErrorCode);
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
	&$sFileStorePath,
	&$bCheckCRLs, &$bAutoRefresh, &$bOwnCRLsOnly, 
	&$bFullAndDeltaCRLs, &$bAutoDownloadCRLs, 
	&$bSaveLoadedCerts, &$iExpireTime,
	&$iErrorCode);
if (!handle_result("GetFileStoreSettings", $iResult, $iErrorCode))
	Exit;

print_result('File store settings', '');
print_result('    path', $sFileStorePath);
print_result('    check crls', bool_to_string($bCheckCRLs));
print_result('    auto refresh', bool_to_string($bAutoRefresh));
print_result('    own crls only', bool_to_string($bOwnCRLsOnly));
print_result('    full and delta crls', bool_to_string($bFullAndDeltaCRLs));
print_result('    auto download crls', bool_to_string($bAutoDownloadCRLs));
print_result('    save loaded certs', bool_to_string($bSaveLoadedCerts));
print_result('    expire time', $iExpireTime);

//----------------------------------------------------------------------------------------

/* Read private key */

$iResult = euspe_readprivatekey(&$iErrorCode);
if (!handle_result("ReadPrivateKey", $iResult, $iErrorCode))
	Exit;

//----------------------------------------------------------------------------------------

/* Parse certificate */

$certInfo = '';
$certData = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$iResult = euspe_parsecert($certData, &$certInfo, &$iErrorCode);
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

$iResult = euspe_signcreate($sData, &$sSign, &$iErrorCode);
if (!handle_result("SignCreate", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_getsignscount($sSign, 
	&$sSignsCount, &$iErrorCode);
if (!handle_result("GetSignsCount", $iResult, $iErrorCode))
	Exit;

print_result('Signers', $sSignsCount);

$iResult = euspe_getsignerinfoex(0, $sSign, 
	&$signerInfo, &$signerCert, &$iErrorCode);
if (!handle_result("GetSignerInfoEx(certificate info)", $iResult, $iErrorCode))
	Exit;

print_result('Signer certificate info', '');
var_dump($signerInfo);
echo "<br>";

$certFileName = './EU-'.$signerInfo['serial'].'.cer';
file_put_contents($certFileName, $signerCert);
print_result('Signer certificate file', $certFileName);

//----------------------------------------------------------------------------------------

/* Hash data */

$sHash = '';

$iResult = euspe_hashdata(
	$sData, &$sHash, &$iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Hash data continue */

$sHash = '';

$iResult = euspe_hashdatacontinue($sData, &$iErrorCode);
if (!handle_result("HashDataContinue-1", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_hashdatacontinue($sData, &$iErrorCode);
if (!handle_result("HashDataContinue-2", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_hashdataend(&$sHash, &$iErrorCode);
if (!handle_result("HashDataEnd", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Hash file */

$sHash = '';

$iResult = euspe_hashfile(
	$sFileWithData, &$sHash, &$iErrorCode);
if (!handle_result("HashFile", $iResult, $iErrorCode))
	Exit;

print_result('Hash', $sHash);

//----------------------------------------------------------------------------------------

/* Sign hash */

$sHash = '';
$sSign = '';

$iResult = euspe_hashdata(
	$sData, &$sHash, &$iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_signhash(
	$sHash, &$sSign, &$iErrorCode);
if (!handle_result("SignHash", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_verifyhashsign(
	$sHash, $sSign, 
	&$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("VerifyHash", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data internal */

$sSign = '';
$sVerData = '';

$iResult = euspe_signcreate(
	$sData, &$sSign, &$iErrorCode);
if (!handle_result("SignData (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverify(
	$sSign, &$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$sVerData, &$iErrorCode);
if (!handle_result("VerifySign (internal)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Sign data external */

$sSign = '';

$iResult = euspe_signcreateext(
	$sData, &$sSign, &$iErrorCode);
if (!handle_result("SignData (external)", $iResult, $iErrorCode))
	Exit;

print_result('Sign', $sSign);

$iResult = euspe_signverifyext(
	$sData, $sSign, 
	&$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("VerifySign (external)", $iResult, $iErrorCode))
	Exit;

print_result('Verified data', $sVerData);
print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign hash */

$sHash = '';
$sSign = '';

$iResult = euspe_hashdata(
	$sData, &$sHash, &$iErrorCode);
if (!handle_result("HashData", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_rawsignhash(
	$sHash, &$sSign, &$iErrorCode);
if (!handle_result("RawSignHash", $iResult, $iErrorCode))
	Exit;

print_result('RawSign', $sSign);

$iResult = euspe_rawverifyhashsign(
	$sHash, $sSign, 
	&$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("RawVerifyHash", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign data */

$sSign = '';

$iResult = euspe_rawsign(
	$sData, &$sSign, &$iErrorCode);
if (!handle_result("RawSignData", $iResult, $iErrorCode))
	Exit;

print_result('RawSign', $sSign);

$iResult = euspe_rawverifysign(
	$sData, $sSign, 
	&$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("RawVerifySign", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Raw sign file */

$iResult = euspe_rawsignfile(
	$sFileWithData, $sFileWithSigData, &$iErrorCode);
if (!handle_result("RawSignFile", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_rawverifyfilesign(
	$sFileWithSigData, $sFileWithData, 
	&$sSignTime, &$bIsTSPUse,
	&$spIssuer, &$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("RawVerifySignFile", $iResult, $iErrorCode))
	Exit;

print_result('Signer info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Envelop data */

$sEnv = '';
$spDevData = '';

$cert1 = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$cert2 = file_get_contents($sResipientCert_2, FILE_USE_INCLUDE_PATH);
$certs = array($cert1, $cert2);

$iResult = euspe_envelop_to_recipients(
	$certs, false, $sData, &$sEnv, &$iErrorCode);
if (!handle_result("Envelop", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_develop(
	$sEnv, &$spDevData, &$sSignTime,
	&$bIsTSPUse, &$spIssuer,
	&$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("Develop", $iResult, $iErrorCode))
	Exit;

print_result('Developed string', $spDevData);
print_result('Sender info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

//----------------------------------------------------------------------------------------

/* Envelop file */

$cert1 = file_get_contents($sResipientCert_1, FILE_USE_INCLUDE_PATH);
$cert2 = file_get_contents($sResipientCert_2, FILE_USE_INCLUDE_PATH);
$certs = array($cert1, $cert2);

$iResult = euspe_envelopfile_to_recipients(
	$certs, false, $sFileWithData, $sFileWithEnvData, &$iErrorCode);
if (!handle_result("EnvelopFile", $iResult, $iErrorCode))
	Exit;

$iResult = euspe_developfile(
	$sFileWithEnvData, $sFileWithDevData, &$sSignTime,
	&$bIsTSPUse, &$spIssuer,
	&$spIssuerCN, &$spSerial,
	&$spSubject, &$spSubjCN,
	&$spSubjOrg, &$spSubjOrgUnit,
	&$spSubjTitle, &$spSubjState,
	&$spSubjLocality, &$spSubjFullName,
	&$spSubjAddress, &$spSubjPhone,
	&$spSubjEMail, &$spSubjDNS,
	&$spSubjEDRPOUCode, &$spSubjDRFOCode,
	&$iErrorCode);
if (!handle_result("DevelopFile", $iResult, $iErrorCode))
	Exit;

print_result('Developed file', $sFileWithDevData);
print_result('Sender info', '');
print_result('    subject', $spSubjCN);
print_result('    serial', $spSerial);
print_result('    issuer', $spIssuerCN);

?>