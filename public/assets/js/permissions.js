function openUpdatePermissionModal(element) {
  openModal("updatePermissionModal");

  setTimeout(() => {
    const permissionId = element.dataset.permissionId || "";
    const permissionCode = element.dataset.permissionCode || "";
    const description = element.dataset.permissionDescription || "";

    // tách module và action
    const parts = permissionCode.split(".");
    const module = parts[0] || "";
    const action = parts[1] || "";

    document.getElementById("update_permission_id").value = permissionId;
    document.getElementById("update_module").value = module;
    document.getElementById("update_action").value = action;
    document.getElementById("update_permission_description").value = description;
  }, 0);
}

function confirmDeletePermission(permissionId) {
  if (confirm("Bạn có chắc chắn muốn xóa quyền này không?")) {
    window.location.href =
      "../admin/index.php?page=permissions&action=delete&id=" + permissionId;
  }
}
