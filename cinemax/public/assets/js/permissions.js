function openUpdatePermissionModal(element) {
    const permissionId = element.dataset.permissionId || "";
    const permissionCode = element.dataset.permissionCode || "";
    const description = element.dataset.permissionDescription || "";

    // tách module và action
    const separator = Math.max(permissionCode.lastIndexOf('.'), permissionCode.lastIndexOf('_'));
    const module = separator > 0 ? permissionCode.slice(0, separator) : permissionCode;
    const action = separator > 0 ? permissionCode.slice(separator + 1) : '';
    for (const [id, value] of [['update_module',module],['update_action',action]]) {
      const select = document.getElementById(id);
      if (value && !Array.from(select.options).some(option => option.value === value)) select.add(new Option(value, value));
    }

    document.getElementById("update_permission_id").value = permissionId;
    document.getElementById("update_module").value = module;
    document.getElementById("update_action").value = action;
    document.getElementById("update_permission_description").value = description;
  openModal("updatePermissionModal");
}

function confirmDeletePermission(permissionId) {
  if (confirm("Bạn có chắc chắn muốn xóa quyền này không?")) {
    window.location.href =
      "../admin/index.php?page=permissions&action=delete&id=" + permissionId;
  }
}
