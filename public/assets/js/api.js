/**
 * API Helper - Fetch dữ liệu từ API
 */

// Đường dẫn API - tự động detect dựa trên URL hiện tại
const API_BASE_URL = "/web_cinemax/public/assets/api/test_api.php";

// Debug: log đường dẫn API (có thể xóa sau)
console.log(
  "API Base URL:",
  API_BASE_URL,
  "from path:",
  window.location.pathname,
);

/**
 * Fetch dữ liệu từ API
 */
async function fetchAPI(controller, action, params = {}) {
  try {
    let url = `${API_BASE_URL}?controller=${controller}&action=${action}`;

    // Thêm params vào URL
    Object.keys(params).forEach((key) => {
      url += `&${key}=${encodeURIComponent(params[key])}`;
    });

    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || data.message || "Có lỗi xảy ra");
    }

    return data.data;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

/**
 * POST dữ liệu lên API
 */
async function postAPI(controller, action, data, params = {}) {
  try {
    let url = `${API_BASE_URL}?controller=${controller}&action=${action}`;

    // Thêm params vào URL
    Object.keys(params).forEach((key) => {
      url += `&${key}=${encodeURIComponent(params[key])}`;
    });

    const formData = new FormData();
    Object.keys(data).forEach((key) => {
      formData.append(key, data[key]);
    });

    const response = await fetch(url, {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || result.message || "Có lỗi xảy ra");
    }

    return result;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

/**
 * DELETE dữ liệu từ API
 */
async function deleteAPI(controller, action, id) {
  try {
    const url = `${API_BASE_URL}?controller=${controller}&action=${action}&id=${id}`;

    const response = await fetch(url, {
      method: "POST",
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || result.message || "Có lỗi xảy ra");
    }

    return result;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

// ============================
// HALLS API
// ============================

async function getAllHalls(cinemaId = null) {
  const params = cinemaId ? { cinema_id: cinemaId } : {};
  return await fetchAPI("halls", "getAll", params);
}

async function getHallById(id) {
  return await fetchAPI("halls", "getById", { id });
}

async function getAllHallStatuses() {
  return await fetchAPI("halls", "getStatuses");
}

async function getAllCinemas() {
  return await fetchAPI("halls", "getCinemas");
}

async function createHall(data) {
  return await postAPI("halls", "create", data);
}

async function updateHall(id, data) {
  return await postAPI("halls", "update", data, { id });
}

async function deleteHall(id) {
  return await deleteAPI("halls", "delete", id);
}

// ============================
// SEAT TYPES API
// ============================

async function getAllSeatTypes() {
  return await fetchAPI("seat_types", "getAll");
}

async function getSeatTypeById(id) {
  return await fetchAPI("seat_types", "getById", { id });
}

async function createSeatType(data) {
  return await postAPI("seat_types", "create", data);
}

async function updateSeatType(id, data) {
  return await postAPI("seat_types", "update", data, { id });
}

async function deleteSeatType(id) {
  return await deleteAPI("seat_types", "delete", id);
}

// ============================
// CINEMAS API
// ============================

async function getAllCinemas() {
  return await fetchAPI("cinemas", "getAll");
}

async function getCinemaById(id) {
  return await fetchAPI("cinemas", "getById", { id });
}

async function getAllLocations() {
  return await fetchAPI("cinemas", "getLocations");
}

async function getAllCinemaStatuses() {
  return await fetchAPI("cinemas", "getStatuses");
}

async function createCinema(data) {
  return await postAPI("cinemas", "create", data);
}

async function updateCinema(id, data) {
  return await postAPI("cinemas", "update", data, { id });
}

async function deleteCinema(id) {
  return await deleteAPI("cinemas", "delete", id);
}

// ============================
// SHOWS API
// ============================

async function getShowById(id) {
  return await fetchAPI("shows", "getById", { id });
}

async function getUserShows(params = {}) {
  return await fetchAPI("shows", "getAllForUser", params);
}

// ============================
// SEATS API
// ============================

async function getSeatsByHall(hallId) {
  return await fetchAPI("seats", "getByHall", { hall_id: hallId });
}

async function getSeatById(id) {
  return await fetchAPI("seats", "getById", { id });
}

async function createSeat(data) {
  return await postAPI("seats", "create", data);
}

async function updateSeat(id, data) {
  return await postAPI("seats", "update", data, { id });
}

async function deleteSeat(id) {
  return await deleteAPI("seats", "delete", id);
}

async function deleteAllSeatsByHall(hallId) {
  const formData = new FormData();
  formData.append("hall_id", hallId);

  const url = `${API_BASE_URL}?controller=seats&action=deleteAll&hall_id=${hallId}`;
  const response = await fetch(url, {
    method: "POST",
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const result = await response.json();

  if (!result.success) {
    throw new Error(result.error || result.message || "Có lỗi xảy ra");
  }

  return result;
}

async function createBulkSeats(hallId, seats) {
  const formData = new FormData();
  formData.append("hall_id", hallId);
  formData.append("seats", JSON.stringify(seats));

  const url = `${API_BASE_URL}?controller=seats&action=createBulk`;
  const response = await fetch(url, {
    method: "POST",
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const result = await response.json();

  if (!result.success) {
    throw new Error(result.error || result.message || "Có lỗi xảy ra");
  }

  return result;
}

// Export functions
if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    fetchAPI,
    postAPI,
    deleteAPI,
    getAllHalls,
    getHallById,
    getAllHallStatuses,
    getAllCinemas,
    createHall,
    updateHall,
    deleteHall,
    getAllSeatTypes,
    getSeatTypeById,
    createSeatType,
    updateSeatType,
    deleteSeatType,
    getAllCinemas,
    getCinemaById,
    getAllLocations,
    getAllCinemaStatuses,
    createCinema,
    updateCinema,
    deleteCinema,
    getShowById,
    getUserShows,
    getSeatsByHall,
    getSeatById,
    createSeat,
    updateSeat,
    deleteSeat,
    deleteAllSeatsByHall,
    createBulkSeats,
  };
}
