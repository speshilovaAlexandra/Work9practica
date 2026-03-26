<?php
include("../settings/jwt.php");
include("../settings/connect_datebase.php");

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
http_response_code(400);
echo json_encode(['error' => 'Заполните все поля']);
exit;
}

// ищем пользователя
$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='".$login."' AND `password`= '".$password."';");

$id = -1;
$user_data = null;
while($user_read = $query_user->fetch_row()) {
$id = $user_read[0];
}

// Получаем данные пользователя заново для получения роли
if($id != -1) {
$query_user->data_seek(0);
$user_data = $query_user->fetch_assoc();
}

if($id != -1 && $user_data) {
// Генерируем JWT токен
$secret_key = "permaviat_jwt_token";

$roll = $user_data['roll'] ?? 0;

// Создаем payload
$header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
$payload = json_encode([
'user_id' => $id,
'login' => $login,
'roll' => $roll,
'exp' => time() + 3600 // токен действителен 1 час
]);

// Кодируем в base64url
$base64_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$base64_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

// Создаем подпись
$signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $base64_header . '.' . $base64_payload, $secret_key, true)));

// Формируем токен
$jwt = $base64_header . '.' . $base64_payload . '.' . $signature;

// Устанавливаем куки с токеном
setcookie('token', $jwt, time() + 3600, '/', '', false, false);

echo json_encode(['token' => $jwt, 'success' => true]);
} else {
http_response_code(401);
echo json_encode(['error' => 'Неверный логин или пароль']);
}
?>
