<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

session_start();
$adminId = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$systemName = isset($input['systemName']) ? trim($input['systemName']) : '';
$language = isset($input['language']) ? trim($input['language']) : '';
$timezone = isset($input['timezone']) ? trim($input['timezone']) : '';
$dateFormat = isset($input['dateFormat']) ? trim($input['dateFormat']) : '';
$currency = isset($input['currency']) ? trim($input['currency']) : '';

$rental = isset($input['rental']) && is_array($input['rental']) ? $input['rental'] : [];
$minPeriod = isset($rental['minPeriod']) ? intval($rental['minPeriod']) : 1;
$maxDays = isset($rental['maxDays']) ? intval($rental['maxDays']) : 0;
$autoLate = isset($rental['autoLate']) ? (bool)$rental['autoLate'] : false;
$requireDeposit = isset($rental['requireDeposit']) ? (bool)$rental['requireDeposit'] : false;

if ($systemName === '' || $language === '' || $timezone === '' || $dateFormat === '' || $currency === '') {
    echo json_encode(["success" => false, "error" => "invalid_input"]);
    sqlsrv_close($conn);
    exit;
}

$sql = "EXEC dbo.sp_UpdateGeneralSettings @SystemName=?, @Language=?, @Timezone=?, @DateFormat=?, @Currency=?, @RentalMinPeriod=?, @RentalMaxDays=?, @AutoLate=?, @RequireDeposit=?, @UpdatedBy=?";
$params = [$systemName, $language, $timezone, $dateFormat, $currency, $minPeriod, $maxDays, $autoLate ? 1 : 0, $requireDeposit ? 1 : 0, $adminId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);
echo json_encode(["success" => true]);
?>
