function openUpdateRoleModal(element) {
  openModal("updateRoleModal");

  setTimeout(() => {
    document.getElementById("update_role_id").value =
      element.dataset.roleId || "";

    document.getElementById("update_role_name").value =
      element.dataset.roleName || "";

    document.getElementById("update_role_description").value =
      element.dataset.roleDescription || "";
  }, 0);
}

function confirmDeleteRole(element) {
  const roleId = element.dataset.roleId;
  const confirmation = confirm("Bạn có chắc chắn muốn xóa vai trò này không?");
  if (confirmation) {
    window.location.href = `../admin/index.php?page=roles&action=delete&id=${roleId}`;
  }
}
