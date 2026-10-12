<?php
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/BillService.php';
class UserController
{
    private UserService $userService;
    private ?BillService $billService;

    public function __construct(UserService $userService, ?BillService $billService = null)
    {
        $this->userService = $userService;
        $this->billService = $billService;
    }

   public function getUserById($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            if (!$user) {
                throw new Exception("User not found");
            }
            return $user;
        } catch (Exception $e) {
            // Log error or handle it as needed
            error_log("Error in UserController::getUserById - " . $e->getMessage());
            return null;
        }   
    }

    public function getBillsByUserId($userId)
    {
        if (!$this->billService) {
            throw new Exception("BillService is not set");
        }
        return $this->billService->getBillsByUserId($userId);
    }
}