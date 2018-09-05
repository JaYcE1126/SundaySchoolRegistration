<?php
session_start();

$username = $_POST["username"];
$password = $_POST["password"];

$returnCode = -1; //default, indicating unkown error.  This value should only be changed on validation of successful run.
$cSession = curl_init(); 
curl_setopt($cSession, CURLOPT_URL,"https://stgeorgenj.ccbchurch.com/api.php?srv=individual_profile_from_login_password");
//curl_setopt($cSession, CURLOPT_HEADER, 1);
curl_setopt($cSession, CURLOPT_USERPWD, "johnchalet" . ":" . "Johnc123!");
curl_setopt($cSession, CURLOPT_RETURNTRANSFER,true);
curl_setopt($cSession, CURLOPT_POST, 2);
curl_setopt($cSession, CURLOPT_POSTFIELDS, "login=".$username."&password=".$password );
curl_setopt($cSession, CURLOPT_HTTPHEADER, array('Accept: application/json'));

$result=curl_exec($cSession);
if ($result === false){
    $result = curl_error($cSession);
    curl_close($cSession);
    $returnCode = -2;//-2 indicates and issue occured with retrieving the data from the API
    echo json_encode(array("returnCode"=>$returnCode, "result"=>$result), JSON_PRETTY_PRINT);
    exit;
} else {
    curl_close($cSession);
}

//echo (string)$result;
//exit;

$xml = simplexml_load_string($result) or die("Error: Cannot create object");

//Validation of data received
$response = $xml->response[0];
if (count($response->errors)>0){
    $returnCode = (string)$response->errors[0]->error["number"];
    $result = (string)$response->errors[0]->error;
    echo json_encode(array("returnCode"=>$returnCode, "result"=>$result, "username"=>$username), JSON_PRETTY_PRINT);
    //echo (string)array("returnCode"=>$returnCode, "result"=>$result);
    exit;
} elseif($response->individuals["count"] == 0){
    $returnCode = "-3"; //-3 User not found or invalid username/password
    $result = "Username and Password not found.";
    echo json_encode(array("returnCode"=>$returnCode, "result"=>$result), JSON_PRETTY_PRINT);
    exit;
}

$individual = $xml->response[0]->individuals[0]->individual[0];
$_SESSION["first_name"] = (string)$individual->first_name;
$_SESSION["last_name"] = (string)$individual->last_name;
$_SESSION["middle_name"] = (string)$individual->middle_name;
$_SESSION["img"] = (string)$individual->image;

$family = array();
$family_members = $individual->family_members[0];
//echo $family_members->family_member[0]->individual;

foreach($family_members as $family_member) { 
    //$family_member_info = array("name"=>$family_member->individual, "id"=>$family_member->individual["id"], "position"=>$family_member->family_position);
    $family[] = array("name"=>(string)$family_member->individual, "id"=>(string)$family_member->individual["id"], "position"=>(string)$family_member->family_position);
    //echo $family_member->individual . ", ";
} 
$_SESSION["family"] = $family;

$address = $individual->addresses[0]->address[0];
$_SESSION["street"] = (string)$address->street_address;
$_SESSION["city"] = (string)$address->city;
$_SESSION["state"] = (string)$address->state;
$_SESSION["zip"] = (string)$address->zip;

$phones = $individual->phones[0];
foreach($phones as $phone) { 
    //echo (string)$phone["type"] . "->" . (string)$phone;
    $_SESSION[(string)$phone["type"]] = (string)$phone;
} 
$returnCode = 0;//0 indicates a successfully run PHP script;
echo json_encode(array("returnCode"=>$returnCode, "result"=>"success"));

//Prints array in readable format
//echo "<pre>";
//print_r( $_SESSION );
//echo "</pre>";

//$_SESSION["family_count"] = $xml->response[0]->individuals[0]->individual[0]->family_members[0];
//echo $result;

?>


