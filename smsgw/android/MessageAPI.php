<?php
require_once(__DIR__ . '/../../android/AndroidAPI.php');
require_once(__DIR__ . '/Message.php');
require_once(__DIR__ . '/Group.php');
require_once(__DIR__ . '/Contact.php');
require_once(__DIR__ . '/User.php');

/**
 * API Calls from a valid user from an Android System.
 *
 *
 * The Response JSONObject will Contain the following Top Level Nodes
 *
 * 1. $Resp['API'] => boolean Status of the API Call
 * 2. $Resp['DB'] => Data to be sent depending upon the Called API
 * 3. $Resp['MSG'] => Message to be displayed after API Call
 * 4. $Resp['ET'] => Execution Time of the Script in Seconds
 * 5. $Resp['ST'] => Server Time of during the API Call
 *
 * @example Sample API Call
 *
 * Request:
 *   JSONObject={"API":"AG",
 *               "MDN":"9876543210",
 *               "OTP":"987654"}
 *
 * Response:
 *    JSONObject={"API":true,
 *               "DB":[{"GRP":"All BDOs"},{"GRP":"All SDOs"}],
 *               "MSG":"Total Groups: 2",
 *               "ET":2.0987,
 *               "ST":"Wed 20 Aug 08:31:23 PM"}
 *
 */
class MessageAPI extends AndroidAPI {

  /**
   * All Groups: Retrieve All Groups
   *
   * Request:
   *   JSONObject={"API":"AG",
   *               "MDN":"9876543210",
   *               "OTP":"987654"}
   *
   * Response:
   *    JSONObject={"API":true,
   *               "DB":[{"GRP":"All BDOs"},{"GRP":"All SDOs"}],
   *               "MSG":"Total Groups: 2",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function AG() {
    $this->Resp['DB']  = Group::getAllGroups();
    $this->Resp['API'] = true;
    $this->Resp['MSG'] = 'All Groups Loaded';
    //$this->setExpiry(3600); // 60 Minutes
  }

  /**
   * Send SMS To a Group.
   *
   * Request:
   *   JSONObject={"API":"SM",
   *               "MDN":"9876543210",
   *               "TXT":"Hello",
   *               "GRP":"BDO",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":"Return Message ID".
   *               "MSG":"Message Sent",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function SM() {
    $AuthUser = new AuthOTP();
    if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
      $Msg               = new Message();
      $User              = new User($this->Req->MDN);
      $Mid               = $Msg->createSMS($User, $this->Req->TXT, $this->Req->GRP);
      $Contact           = new Contact();
      $count             = $Contact->countContactByGroup($this->Req->GRP);
      $this->Resp['DB']  = $Mid;
      $this->Resp['API'] = true;
      $this->Resp['MSG'] = 'Message Sent to ' . $count
        . ' Contacts of ' . $this->Req->GRP . ' Group';
    } else {
      $this->Resp['API'] = false;
      $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    }
  }

  /**
   * Delivery Status of a Message
   *
   * Request:
   *   JSONObject={"API":"DS",
   *               "MDN":"9876543210",
   *               "MID":"123",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":"Return Message ID".
   *               "MSG":"Message Sent",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function DS() {
    //$AuthUser = new AuthOTP();
    $DB   = new MySQLiDB();
    $Data = new MySQLiDBHelper();
    //if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
    $RowCount = $DB->do_sel_query("Select * from " . MySQL_Pre . "SMS_ViewDlrData");
    $Result   = array();
    for ($i = 0; $i < $RowCount; $i++) {
      $Row             = $DB->get_row();
      $Record['MsgID'] = $Row['MsgID'];

      $Record['MsgData']        = json_decode(htmlspecialchars_decode($Row['MsgData']), true);
      $MsgData['MessageID']     = $Record['MsgData']['a2wackid'];
      $MsgData['MobileNo']      = $Record['MsgData']['mnumber'];
      $MsgData['DlrStatus']     = $Record['MsgData']['a2wstatus'];
      $MsgData['CarrierStatus'] = $Record['MsgData']['carrierstatus'];
      $MsgData['SentOn']        = $Record['MsgData']['submitdt'];
      $MsgData['DeliveredOn']   = $Record['MsgData']['lastutime'];
      if ($MsgData['CarrierStatus'] == 'DELIVRD') {
        $MsgData['UnDelivered'] = 0;
      }
      $Data->where('MessageID', $MsgData['MessageID']);
      $Rows = $Data->get(MySQL_Pre . 'SMS_DlrReports');
      if (count($Rows) == 0) {
        $this->Resp['Data'] = $MsgData;
        $Updated            = $Data->insert(MySQL_Pre . "SMS_DlrReports", $MsgData);
      } else {
        $Data->where('UnDelivered', 1);
        $Data->where('MessageID', $MsgData['MessageID']);
        $Data->update(MySQL_Pre . "SMS_DlrReports", $MsgData);
        $Updated = 1;
      }
      if ($Updated > 0) {
        $Data->where('MsgID', $Record['MsgID']);
        $UpdateData['ReadUnread'] = 1;
        $this->Resp['DB']['Updated']=$Data->update("SMS_Data", $UpdateData);
      }
      array_push($Result, $Record);
    }
    $DB->do_close();
    unset($Data);
    $this->Resp['DB']  = $Result;
    $this->Resp['API'] = true;
    //} else {
    //  $this->Resp['API'] = false;
    //  $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    //}
  }

  /**
   * Get All Members in a Group
   *
   * Request:
   *   JSONObject={"API":"GM",
   *               "MDN":"9876543210",
   *               "GRP":"BDO",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":[
   *                      {
   *                        "ContactID": 186,
   *                        "ContactName": "Test Contact",
   *                        "Designation": "",
   *                        "GroupID": 9,
   *                        "MobileNo": "8348691719"
   *                      }
   *                ],
   *               "MSG":"Loaded 1 Contacts of DIO NIC Group",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function GM() {
    $AuthUser = new AuthOTP();
    if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
      $Contact           = new Contact();
      $count             = $Contact->countContactByGroup($this->Req->GRP);
      $Contacts             = $Contact->getGroupMembers($this->Req->GRP);
      $this->Resp['DB']  = $Contacts;
      $this->Resp['API'] = true;
      $this->Resp['MSG'] = 'Loaded ' . $count
        . ' Contacts of ' . $this->Req->GRP . ' Group';
    } else {
      $this->Resp['API'] = false;
      $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    }
  }

  /**
   * Contact Groups: Retrieve All Contact Groups
   *
   * Request:
   *   JSONObject={"API":"CG",
   *               "MDN":"9876543210",
   *               "OTP":"987654"}
   *
   * Response:
   *    JSONObject={"API":true,
   *               "DB":[{"GRP":"All BDOs"},{"GRP":"All SDOs"}],
   *               "MSG":"Total Groups: 2",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function CG() {
    $this->Resp['DB']  = Group::getContactGroups();
    $this->Resp['API'] = true;
    $this->Resp['MSG'] = 'All Groups Loaded';
    //$this->setExpiry(3600); // 60 Minutes
  }

  /**
   * Add new Contact
   *
   * Request:
   *   JSONObject={"API":"RM",
   *               "MDN":"9876543210",
   *               "MN":"9876543210",
   *               "NM":"Contact Name",
   *               "DG":"Designation",
   *               "GRP":"BDO",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":10,
   *               "MSG":"Added to BDO Group",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function AC() {
    $AuthUser = new AuthOTP();
    if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
      $Contact           = new Contact();
      $ContactID = $Contact->createContact($this->Req->MN,$this->Req->NM,$this->Req->DG);
      $Group = new Group();
      $Group->setGroup($this->Req->GRP);
      $Gid = $Group->addMember($ContactID);
      $this->Resp['DB']  = $Gid;
      $this->Resp['API'] = true;
      $this->Resp['MSG'] = 'Added to ' . $this->Req->GRP . ' Group';
    } else {
      $this->Resp['API'] = false;
      $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    }
  }

  /**
   * Add a Member from a Group
   *
   * Request:
   *   JSONObject={"API":"RM",
   *               "MDN":"9876543210",
   *               "CID":"20",
   *               "GRP":"BDO",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":10,
   *               "MSG":"Added to BDO Group",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function AM() {
    $AuthUser = new AuthOTP();
    if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
      $Group = new Group();
      $Group->setGroup($this->Req->GRP);
      $Gid = $Group->addMember($this->Req->CID);
      $this->Resp['DB']  = $Gid;
      $this->Resp['API'] = true;
      $this->Resp['MSG'] = 'Added to ' . $this->Req->GRP . ' Group';
    } else {
      $this->Resp['API'] = false;
      $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    }
  }

  /**
   * Remove a Member from a Group
   *
   * Request:
   *   JSONObject={"API":"RM",
   *               "MDN":"9876543210",
   *               "CID":"20",
   *               "GRP":"BDO",
   *               "OTP":"987654"}
   *
   * Response:
   *   JSONObject={"API":true,
   *               "DB":10,
   *               "MSG":"Removed from BDO Group",
   *               "ET":2.0987,
   *               "ST":"Wed 20 Aug 08:31:23 PM"}
   */
  protected function RM() {
    $AuthUser = new AuthOTP();
    if ($AuthUser->authenticateUser($this->Req->MDN, $this->Req->OTP) OR $this->getNoAuthMode()) {
      $Group = new Group();
      $Group->setGroup($this->Req->GRP);
      $Gid = $Group->delMember($this->Req->CID);
      $this->Resp['DB']  = $Gid;
      $this->Resp['API'] = true;
      $this->Resp['MSG'] = 'Removed from ' . $this->Req->GRP . ' Group';
    } else {
      $this->Resp['API'] = false;
      $this->Resp['MSG'] = 'Invalid OTP ' . $this->Req->OTP;
    }
  }
}
