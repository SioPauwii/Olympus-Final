<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: '. ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
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

try{
    switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $genToken = $auth->verifyTokenBackend();
        if($genToken['is_valid'] == true){
        if ($req[0] == 'Get') {
            $tokenRes = $auth->verifyTokenBackend('admin');
            if ($tokenRes['is_valid'] == true) {
                if ($req[1] == 'One') {echo json_encode($adminCon->getOneAcc($data));return;}
                if ($req[1] == 'All') {echo json_encode($adminCon->getAllAcc());return;}
            }
        }

        if ($req[0] == 'Member') {
            $tokenResMem = $auth->verifyTokenBackend('member');
            if ($tokenResMem['is_valid'] == true) {
                if ($req[1] == 'ViewInfo') {echo json_encode($member->viewInfo($data));return;}
            }
        }

        if($req[0] == 'Coach'){
            $tokenResMem = $auth->verifyTokenBackend('coach');
            if ($tokenResMem['is_valid'] == true) {
                if ($req[1] == 'View-Clients') {echo json_encode($coach->getAllClients());return;}
                if ($req[1] == 'View-Coach-Info') {echo json_encode($coach->viewInfo());return;}
            }
        }

        if ($req[0] == 'Coaches') {echo json_encode($adminCon->getAllCoach());return;}

        $rm->notFound();
    }
        break;

    case 'POST':
        if ($req[0] == 'Front') {
            if($req[1] == 'verifyToken'){echo json_encode($auth->verifyToken($data));return;}
            if($req[1] == 'getUserType'){echo json_encode($rm->getUserTypeFromToken($data));return;}
        }

        if ($req[0] == 'Create') {
            if ($req[1] == 'Member') {echo json_encode($adminCon->createAcc($data));return;}
            if ($req[1] == 'Coach') {echo json_encode($adminCon->coachCreate($data));return;}
            if ($req[1] == 'Admin') {echo json_encode($auth->adminReg($data));return;}
        }
        

        if ($req[0] == 'Login') {
            // $usertype = $rm->getUserTypeFromToken();
            if(isset($_COOKIE['Authorization']) && $_COOKIE['Authorization'] !== ''){
                echo json_encode(($rm->responsePayload(null, 'failed', 'Already logged in', 403)));
                return;
            }else{
                if ($req[1] == 'Admin'){echo json_encode($auth->adminLogin($data));return;}
                if ($req[1] == 'Member'){echo json_encode($auth->memLogin($data));return;}
                if ($req[1] == 'Coach'){echo json_encode($auth->coachLogin($data));return;}
            }
        }
        
        if ($req[0] == 'Logout') {echo json_encode($auth->logout());return;}                                            

        if($req[0] == 'Member'){
            $tokenResMem = $auth->verifyTokenBackend('member');
            if($tokenResMem['is_valid'] == true) {
                if($req[1] == 'setAlarm'){echo json_encode($member->setAlarm($data));return;}
                if($req[1] == 'setSession'){echo json_encode($member->setSession($data));return;}
                if($req[1] == 'Daily-Calories'){echo json_encode($member->calcBodCalcNeed($data));return;}
                if($req[1] == 'Food-Calories'){echo json_encode($member->calcFoodCalor($data));return;}
                if($req[1] == 'Get-Recommendation'){echo json_encode($member->getRecomm($data));return;}
                if($req[1] == 'Enroll-Class'){echo json_encode($member->enrollClass($data));return;}
                if($req[1] == 'CoachesInfo'){echo json_encode($member->ViewCoachInfo($data));return;}
                if($req[1] == 'isEnrolled'){echo json_encode($member->isUserEnrolledInClass($data));return;}
                if($req[1] == 'Drop-Coach'){echo json_encode($member->dropCoach($data));return;}
            }else{
                echo json_encode(($rm->responsePayload($_COOKIE['Authorization'], 'failed', 'Login first', 403)));
                return;
            }
        }

        if($req[0] == 'Coach'){
            $tokenResMem = $auth->verifyTokenBackend('coach');
            if($tokenResMem['is_valid'] == true && isset($_COOKIE['Authorization'])) {
                if($req[1] == "Send-Message"){echo json_encode($coach->sendMessage($data));return;}
                if($req[1] == "Update-Info"){echo json_encode($coach->updateInfo($data));return;}
                if ($req[1] == 'View-one-Client') {echo json_encode($coach->seeMemDet($data));return;}
            }else{
                echo json_encode(($rm->responsePayload(null, 'failed', 'Login first', 403)));
                return;
            }
        }

        if($req[0] == 'Send'){
            if($req[1] == 'Expiry'){echo json_encode($mailer->Expiry());return;}            
            if($req[1] == 'Session'){echo json_encode($mailer->Session());return;}
            if($req[1] == 'Alarm'){echo json_encode($mailer->Alarm());return;}
        }

        if($req[0] == 'cookieTest'){
            echo json_encode($auth->verifyTokenBackend('member')); return;
        }

        if($req[0] == 'Admin'){
            $tokenResMem = $auth->verifyTokenBackend('admin');
            if ($tokenResMem['is_valid'] == true) {
                if($req[1] == 'Delete'){echo json_encode($adminCon->delAcc($data));return;}
                if($req[1] == 'DeleteCoach'){echo json_encode($adminCon->delCoach($data));return;}
            }
        }

        break;

    case 'PUT':
        if ($req[0] == 'UpdateStat') {echo json_encode($adminCon->archStat($data));return;}

        if ($req[0] == 'Member') {
            $tokenResMem = $auth->verifyTokenBackend('member');
            if ($tokenResMem['is_valid'] == true) {
                if ($req[1] == 'UpdateInfo') {echo json_encode($member->editInfo($data));return;}
            }
        }

        if($req[0] == 'Admin'){
            $tokenResMem = $auth->verifyTokenBackend('admin');
            if ($tokenResMem['is_valid'] == true) {
            if ($req[1] == 'ChangeSubStat'){echo json_encode($adminCon->changePaymentStatus());return;}
            if ($req[1] == 'setPaid'){echo json_encode($adminCon->setPaid($data));return;}
            }
        }
        break;

    case 'DELETE':
        break;

    default:
        $rm->notFound();
        break;
    }
}catch(exception $e){
    $response = $rm->errorhandling($e);
    echo json_encode($response);   
}