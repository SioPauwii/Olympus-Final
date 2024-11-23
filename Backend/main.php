<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    exit;
}

date_default_timezone_set("Asia/Manila");
set_time_limit(1000);

$rootPath = $_SERVER["DOCUMENT_ROOT"];
$apiPath = $rootPath . "/Olympus/Backend";

require_once($apiPath . '/configs/Connection.php');
require_once($apiPath . '/model/Global.model.php');
require_once($apiPath . '/model/Admin.model.php');
require_once($apiPath . '/model/Auth.model.php');
require_once($apiPath . '/model/Member.model.php');
require_once($apiPath . '/model/mailer.model.php');
require_once($apiPath . '/model/coach.model.php');

$db = new ConnectionFinProj();
$pdo = $db->connect();
$rm = new ResponseMethodsProj();
$auth = new Auth($pdo, $rm);
$adminCon = new adminControls($pdo, $rm);
$member = new member($pdo, $rm);
$mailer = new mailer($pdo, $rm);
$coach = new coach($pdo, $rm, $mailer);

$data = json_decode(file_get_contents("php://input"));

$req = [];
if (isset($_REQUEST['request']))
    $req = explode('/', rtrim($_REQUEST['request'], '/'));
else $req = array("errorcatcher");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($req[0] == 'Get') {
            $tokenRes = $auth->verifyToken('admin');
            if ($tokenRes['is_valid'] !== true) {
                if ($req[1] == 'One') {$rm->errorhandling($data);echo json_encode($adminCon->getOneAcc($data));return;}
                if ($req[1] == 'All') {$rm->errorhandling($data);echo json_encode($adminCon->getAllAcc());return;}
            }
        }

        if ($req[0] == 'Member') {
            $tokenResMem = $auth->verifyToken('member');
            if ($tokenResMem['is_valid'] !== true) {
                if ($req[1] == 'ViewInfo') {$rm->errorhandling($data);echo json_encode($member->viewInfo());return;}
            }
        }

        if ($req[0] == 'VerifyToken') {
            $rm->errorhandling($data);
            $tokenRes = $auth->verifyToken(); 
            echo json_encode($tokenRes);
            return;
        }

        if($req[0] == 'Coach'){
            $tokenResMem = $auth->verifyToken('coach');
            if ($tokenResMem['is_valid'] !== true) {
                if ($req[1] == 'View-Clients') {$rm->errorhandling($data);echo json_encode($coach->getAllClients());return;}
                if ($req[1] == 'View-one-Client') {$rm->errorhandling($data);echo json_encode($coach->seeMemDet($data));return;}
            }
        }

        $rm->notFound();
        break;

    case 'POST':
        if ($req[0] == 'Create') {
            if ($req[1] == 'Member') {$rm->errorhandling($data);echo json_encode($adminCon->createAcc($data));return;}
            if ($req[1] == 'Coach') {$rm->errorhandling($data);echo json_encode($adminCon->coachCreate($data));return;}
            if ($req[1] == 'Admin') {$rm->errorhandling($data);echo json_encode($auth->adminReg($data));return;}
        }
        

        if ($req[0] == 'Login') {
            $usertype = $rm->getUserTypeFromToken();
            if(isset($_COOKIE['Authorization']) && $_COOKIE['Authorization'] !== ''){
                echo json_encode(($rm->responsePayload(null, 'failed', 'Already logged in', 403)));
                return;
            }else{
                if ($req[1] == 'Admin'){$rm->errorhandling($data);echo json_encode($auth->adminLogin($data));return;}
                if ($req[1] == 'Member'){$rm->errorhandling($data);echo json_encode($auth->memLogin($data));return;}
                if ($req[1] == 'Coach'){$rm->errorhandling($data);echo json_encode($auth->coachLogin($data));return;}
            }
        }
        
        if ($req[0] == 'logout') {$rm->errorhandling($data);echo json_encode($auth->logout());return;}                                            

        if($req[0] == 'Member'){
            $tokenResMem = $auth->verifyToken('member');
            if($tokenResMem['is_valid'] !== true && isset($_COOKIE['Authorization'])) {
                if($req[1] == 'setAlarm'){$rm->errorhandling($data);echo json_encode($member->setAlarm($data));return;}
                if($req[1] == 'setSession'){$rm->errorhandling($data);echo json_encode($member->setSession($data));return;}
                if($req[1] == 'Daily-Calories'){$rm->errorhandling($data);echo json_encode($member->calcBodCalcNeed($data));return;}
                if($req[1] == 'Food-Calories'){$rm->errorhandling($data);echo json_encode($member->calcFoodCalor($data));return;}
                if($req[1] == 'Get-Recommendation'){$rm->errorhandling($data);echo json_encode($member->getRecomm($data));return;}
                if($req[1] == 'Enroll-Class'){$rm->errorhandling($data);echo json_encode($member->enrollClass($data));return;}
            }else{
                echo json_encode(($rm->responsePayload(null, 'failed', 'Login first', 403)));
                return;
            }
        }

        if($req[0] == 'Coach'){
            $tokenResMem = $auth->verifyToken('coach');
            if($tokenResMem['is_valid'] !== true && isset($_COOKIE['Authorization'])) {
                if($req[1] == "Send-Message"){$rm->errorhandling($data);echo json_encode($coach->sendMessage($data));return;}
            }else{
                echo json_encode(($rm->responsePayload(null, 'failed', 'Login first', 403)));
                return;
            }
        }

        if($req[0] == 'Send'){
            if($req[1] == 'Expiry'){$rm->errorhandling($data);echo json_encode($mailer->Expiry());return;}            
            if($req[1] == 'Session'){$rm->errorhandling($data);echo json_encode($mailer->Session());return;}
            if($req[1] == 'Alarm'){$rm->errorhandling($data);echo json_encode($mailer->Alarm());return;}
        }

        break;

    case 'PUT':
        if ($req[0] == 'UpdateStat') {$rm->errorhandling($data);echo json_encode($adminCon->archStat($data));return;}

        if ($req[0] == 'Member') {
            $tokenResMem = $auth->verifyToken('member');
            if ($tokenResMem['is_valid'] !== true) {
                if ($req[1] == 'UpdateInfo') {$rm->errorhandling($data);echo json_encode($member->editInfo($data));return;}
            }
        }

        if ($req[0] == 'ChangeSubStat'){$rm->errorhandling($data);echo json_encode($adminCon->changePaymentStatus());return;}
        break;

    case 'DELETE':
        if ($req[0] == 'Delete') {$rm->errorhandling($data);echo json_encode($adminCon->delAcc($data));return;}
        break;

    default:
        $rm->notFound();
        break;
}
