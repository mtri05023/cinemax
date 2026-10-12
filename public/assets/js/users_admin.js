function openUpdateUserModal(element) {
  openModal("updateUserModal");

  setTimeout(() => {
    document.getElementById("update_user_id").value =
      element.dataset.userId || "";

    document.getElementById("update_full_name").value =
      element.dataset.fullName || "";

    document.getElementById("update_phone").value = element.dataset.phone || "";

    document.getElementById("update_email").value = element.dataset.email || "";

    document.getElementById("update_role_id").value =
      element.dataset.roleId || "";
  }, 0);
}

function confirmDeleteUser(element) {
  const userId = element.dataset.userId;
  const confirmation = confirm(
    "Bạn có chắc chắn muốn xóa người dùng này không?",
  );
  if (confirmation) {
    window.location.href = `../admin/index.php?page=users&action=delete&id=${userId}`;
  }
}
