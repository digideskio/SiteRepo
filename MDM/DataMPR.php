<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once ( __DIR__ . '/../lib.inc.php');

$Data               = new MySQLiDBHelper();
$_SESSION['action'] = 0;
$Query              = '';
if (WebLib::GetVal($_POST, 'FormToken') !== NULL) {
  if (WebLib::GetVal($_POST, 'FormToken') !==
      WebLib::GetVal($_SESSION, 'FormToken')) {
    $_SESSION['action'] = 1;
  } else {

// Authenticated Inputs
    switch (WebLib::GetVal($_POST, 'CmdSubmit')) {
      case 'Create Department':
        $DataMPR['DeptName']    = WebLib::GetVal($_POST, 'DeptName');
        $DataMPR['HODName']     = WebLib::GetVal($_POST, 'HODName');
        $DataMPR['HODMobile']   = WebLib::GetVal($_POST, 'HODMobile');
        $DataMPR['HODEmail']    = WebLib::GetVal($_POST, 'HODEmail');
        $DataMPR['DeptNumber']  = WebLib::GetVal($_POST, 'DeptNumber');
        $DataMPR['Strength']    = WebLib::GetVal($_POST, 'Strength');
        $DataMPR['DeptAddress'] = WebLib::GetVal($_POST, 'DeptAddress');

        if (strlen($DataMPR['DeptName']) > 2) {
          $DataMPR['UserMapID'] = $_SESSION['UserMapID'];
          $Query                = MySQL_Pre . 'MPR_Departments';
          $_SESSION['Msg']      = 'Department Created Successfully!';
        } else {
          $Query           = '';
          $_SESSION['Msg'] = 'Department Name must be at least 3 characters or more.';
        }
        break;

      case 'Create Scheme':
        $DataMPR['SchemeName']       = WebLib::GetVal($_POST, 'SchemeName');
        $DataMPR['DeptID']           = WebLib::GetVal($_POST, 'DeptID');
        $DataMPR['SectorID']         = WebLib::GetVal($_POST, 'SectorID');
        $DataMPR['BlockID']          = WebLib::GetVal($_POST, 'BlockID');
        $DataMPR['PhysicalTargetNo'] = WebLib::GetVal($_POST, 'PhysicalTargetNo');
        $DataMPR['Executive']        = WebLib::GetVal($_POST, 'Executive');
        $DataMPR['SchemeCost']       = WebLib::GetVal($_POST, 'SchemeCost');
        $DataMPR['AlotmentAmount']   = WebLib::GetVal($_POST, 'AlotmentAmount');
        $DataMPR['StartDate']        = WebLib::ToDBDate(WebLib::GetVal($_POST,
                                                                       'StartDate'));
        $DataMPR['AlotmentDate']     = WebLib::ToDBDate(WebLib::GetVal($_POST,
                                                                       'AlotmentDate'));
        $DataMPR['TenderDate']       = WebLib::ToDBDate(WebLib::GetVal($_POST,
                                                                       'TenderDate'));
        $DataMPR['WorkOrderDate']    = WebLib::ToDBDate(WebLib::GetVal($_POST,
                                                                       'WorkOrderDate'));
        if ((strlen($DataMPR['SchemeName']) > 2) && ($DataMPR['DeptID'] !== null) && ($DataMPR['SectorID'] !== null)) {
          $DataMPR['UserMapID'] = $_SESSION['UserMapID'];
          $Query                = MySQL_Pre . 'MPR_Schemes';
          $_SESSION['Msg']      = 'Scheme Created Successfully!';
        } else {
          $Query           = '';
          $_SESSION['Msg'] = 'Scheme Name must be at least 3 characters or more.';
        }
        break;

      case 'Save Progress':
        $DataMPR['SchemeID']          = WebLib::GetVal($_POST, 'SchemeID');
        $DataMPR['ReportDate']        = WebLib::ToDBDate(WebLib::GetVal($_POST,
                                                                        'ReportDate'));
        $DataMPR['PhysicalProgress']  = WebLib::GetVal($_POST,
                                                       'PhysicalProgress');
        $DataMPR['FinancialProgress'] = WebLib::GetVal($_POST,
                                                       'FinancialProgress');
        $DataMPR['Remarks']           = WebLib::GetVal($_POST, 'Remarks');
        $OldPhysicalProgress          = WebLib::GetVal($_POST,
                                                       'OldPhysicalProgress');
        $OldFinancialProgress         = WebLib::GetVal($_POST,
                                                       'OldFinancialProgress');
        if (($DataMPR['PhysicalProgress'] < $OldPhysicalProgress) ||
            ($DataMPR['FinancialProgress']) < $OldFinancialProgress) {
          $Query           = '';
          $_SESSION['Msg'] = 'Physical & Financial Progress can not be decreased than'
              . ' previous report';
        } else {
          if ((strlen($DataMPR['Remarks']) > 2) && ($DataMPR['SchemeID'] !== null)) {
            $DataMPR['UserMapID'] = $_SESSION['UserMapID'];
            $Query                = MySQL_Pre . 'MPR_Progress';
            $_SESSION['Msg']      = 'Progress Created Successfully!';
          } else {
            $Query           = '';
            $_SESSION['Msg'] = 'Report must be at least 3 characters or more.';
          }
        }
        break;
      case 'GetREPORTData':
        $_SESSION['POST'] = $_POST;
        $Query            = 'Select `ReportID`, `UserMapID`, `ReportDate`, '
            . '`ProjectID`, `PhysicalProgress`, `FinancialProgress`, `Remarks`'
            . ' From `' . MySQL_Pre . 'MPR_Progress`'
            . ' Where `ProjectID`=?';
        doQuery($DataResp, $Query, array(WebLib::GetVal($_POST, 'ProjectID')));
        break;
    }
    if ($Query !== '') {
      $Inserted = $Data->insert($Query, $DataMPR);
      if ($Inserted === false) {
        $_SESSION['CheckVal'] = 'false';
        $_SESSION['Msg']      = 'Unable to '
            . WebLib::GetVal($_POST, 'CmdSubmit')
            . '! Inserted data already present';
      }
    }
  }
}
//$_SESSION['OldFormToken'] = $_SESSION['FormToken'];
$_SESSION['FormToken'] = md5($_SERVER['REMOTE_ADDR'] . session_id() . microtime());
unset($DataMPR);
unset($Data);

/**
 * Perfroms Select Query to the database
 *
 * @param ref     $DataResp
 * @param string  $Query
 * @param array   $Params
 * @example GetData(&$DataResp, "Select a,b,c from Table Where c=? Order By b LIMIT ?,?", array('1',30,10))
 */
function doQuery(&$DataResp,
                 $Query,
                 $Params = NULL) {
  $Data             = new MySQLiDBHelper();
  $Result           = $Data->rawQuery($Query, $Params);
  $DataResp['Data'] = $Result;
  $DataResp['Msg']  = 'Total Rows: ' . count($Result);
  unset($Result);
  unset($Data);
}

?>