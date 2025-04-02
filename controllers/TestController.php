<?php
class TestController extends BaseController {
    public function index() {
        echo "Это TestController::index()";
    }
    
    public function action($param1 = null, $param2 = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'controller' => 'TestController',
            'method' => 'action',
            'params' => [$param1, $param2],
            'url' => $_SERVER['REQUEST_URI'],
            'method_req' => $_SERVER['REQUEST_METHOD'],
            'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false
        ]);
        exit;
    }
}