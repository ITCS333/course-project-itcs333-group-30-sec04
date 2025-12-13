// --- Global Data Store ---
let students = [];

// --- Element Selections ---
const studentTableBody = document.querySelector("#student-table tbody");
const addStudentForm = document.querySelector("#add-student-form");
const changePasswordForm = document.querySelector("#password-form");
const searchInput = document.querySelector("#search-input");
const tableHeaders = document.querySelectorAll("#student-table thead th");

// --- Functions ---

function createStudentRow(student) {
  const tr = document.createElement("tr");

  const tdName = document.createElement("td");
  tdName.textContent = student.name;
  tr.appendChild(tdName);

  const tdId = document.createElement("td");
  tdId.textContent = student.student_id;
  tr.appendChild(tdId);

  const tdEmail = document.createElement("td");
  tdEmail.textContent = student.email;
  tr.appendChild(tdEmail);

  const tdActions = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = student.student_id;
  tdActions.appendChild(editBtn);

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = student.student_id;
  tdActions.appendChild(deleteBtn);

  tr.appendChild(tdActions);
  return tr;
}

function renderTable(studentArray) {
  studentTableBody.innerHTML = "";
  studentArray.forEach(student => {
    studentTableBody.appendChild(createStudentRow(student));
  });
}

// --- API Calls ---
async function loadStudents() {
  try {
    const res = await fetch("api.php"); // Adjust path if needed
    const data = await res.json();
    if (data.success) {
      students = data.data;
      renderTable(students);
    } else {
      alert("Failed to load students: " + data.error);
    }
  } catch (err) {
    console.error(err);
  }
}

async function handleAddStudent(event) {
  event.preventDefault();
  const name = document.querySelector("#student-name").value.trim();
  const id = document.querySelector("#student-id").value.trim();
  const email = document.querySelector("#student-email").value.trim();
  const password = "password123"; // default password

  if (!name || !id || !email) {
    alert("Please fill out all required fields.");
    return;
  }

  try {
    const res = await fetch("api.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ student_id: id, name, email, password })
    });
    const result = await res.json();
    if (result.success) {
      alert("Student added successfully!");
      addStudentForm.reset();
      loadStudents();
    } else {
      alert(result.error);
    }
  } catch (err) {
    console.error(err);
  }
}

async function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;
    if (!confirm("Are you sure you want to delete this student?")) return;

    try {
      const res = await fetch(`api.php?student_id=${id}`, { method: "DELETE" });
      const result = await res.json();
      if (result.success) {
        alert(result.message);
        loadStudents();
      } else {
        alert(result.error);
      }
    } catch (err) {
      console.error(err);
    }
  }

  if (target.classList.contains("edit-btn")) {
    const id = target.dataset.id;
    const student = students.find(s => s.student_id === id);
    if (!student) return;

    const newName = prompt("Enter new name:", student.name);
    const newEmail = prompt("Enter new email:", student.email);
    if (!newName && !newEmail) return;

    try {
      const res = await fetch("api.php", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ student_id: id, name: newName, email: newEmail })
      });
      const result = await res.json();
      if (result.success) {
        alert(result.message);
        loadStudents();
      } else {
        alert(result.error);
      }
    } catch (err) {
      console.error(err);
    }
  }
}

async function handleChangePassword(event) {
  event.preventDefault();
  const student_id = document.querySelector("#student-id-pass").value.trim();
  const current = document.querySelector("#current-password").value;
  const newPass = document.querySelector("#new-password").value;
  const confirm = document.querySelector("#confirm-password").value;

  if (newPass !== confirm) {
    alert("Passwords do not match.");
    return;
  }
  if (newPass.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  try {
    const res = await fetch("api.php?action=change_password", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ student_id, current_password: current, new_password: newPass })
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message);
      changePasswordForm.reset();
    } else {
      alert(result.error);
    }
  } catch (err) {
    console.error(err);
  }
}

// --- Search and Sort ---
function handleSearch() {
  const term = searchInput.value.toLowerCase();
  if (!term) {
    renderTable(students);
  } else {
    renderTable(students.filter(s => s.name.toLowerCase().includes(term)));
  }
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  let prop;
  switch (index) {
    case 0: prop = "name"; break;
    case 1: prop = "student_id"; break;
    case 2: prop = "email"; break;
    default: return;
  }
  const currentDir = event.currentTarget.dataset.sortDir || "asc";
  const newDir = currentDir === "asc" ? "desc" : "asc";
  event.currentTarget.dataset.sortDir = newDir;

  students.sort((a, b) => {
    if (prop === "student_id") return newDir === "asc" ? a[prop] - b[prop] : b[prop] - a[prop];
    return newDir === "asc" ? a[prop].localeCompare(b[prop]) : b[prop].localeCompare(a[prop]);
  });

  renderTable(students);
}

// --- Initialize ---
function initialize() {
  addStudentForm.addEventListener("submit", handleAddStudent);
  changePasswordForm.addEventListener("submit", handleChangePassword);
  studentTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);
  tableHeaders.forEach(th => th.addEventListener("click", handleSort));
  loadStudents();
}

initialize();
