<?php

class Role_permissionsService
{
    private $rolePermissionsModel;

    public function __construct($rolePermissionsModel)
    {
        $this->rolePermissionsModel = $rolePermissionsModel;
    }

    public function getAll()
    {
        return $this->rolePermissionsModel->getAll();
    }

    public function save($rolePermissions)
    {
        $this->rolePermissionsModel->save($rolePermissions);
    }
}