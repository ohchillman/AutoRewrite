<?php
// test_ajax.php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Это тестовый AJAX-ответ'
]);
exit;