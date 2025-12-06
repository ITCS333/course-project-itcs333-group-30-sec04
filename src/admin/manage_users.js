/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by fetching/manipulating data from the PHP backend.
*/

// --- Global Data Store ---
// TODO: This array will hold the student data fetched from the backend.
let students = [];

// --- Element Selections ---
// TODO: Select elements after the DOM is parsed (using defer)

// TODO: Select the student table body (tbody)
const studentTableBody = document.querySelector("#student-table tbody");

// TODO: Select the "Add Student" form
const addStudentForm = document.querySelector("#add-student-form");

// TODO: Select the "Change Password" form
const changePasswordForm = document.querySelector("#password-form");

// TODO: Select the search input field
const searchInput = document.querySelector("#search-input");

// TODO: Select all table header (th) elements in thead
const tableHeaders = document.querySelectorAll("#student-table thead th");

// --- Functions ---

/**
 * TODO: Create a <tr> for a student
 * Input: student object {name, id, email}
 * Output: <tr> element with cells and Edit/Delete buttons
 */
function createStudentRow(student) {
  const tr = document.createElement("tr");

  const tdName = document.createElement("td");
  tdName.textContent = student.name;
  tr.appendChild(tdName);

  const tdId = document.createElement("td");
  tdId.textContent = student.id;
  tr.appendChild(tdId);

  const tdEmail = document.createElement("td");
  tdEmail.textContent = student.email;
  tr.appendChild(tdEmail);

  const tdActions = document.createElement("td");

  // TODO: Edit button
  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = student.id;
  tdActions.appendChild(editBtn);

  // TODO: Delete button
  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = student.id;
  tdActions.appendChild(deleteBtn);

  tr.appendChild(tdActions);

  return tr;
}

/**
 * TODO: Render table rows based on provided student array
 */
function renderTable(studentArray) {
  studentTableBody.innerHTML = "";
  studentArray.forEach(student => {
    studentTableBody.appendChild(createStudentRow(student));
  });
}

/**
 * TODO: Handle password change form
 */
function handleChangePassword(event) {
  event.preventDefault();

  const student_id = document.querySelector("#student-id-pw").value.trim();
  const current_password = document.querySelector("#current-password").value;
  const new_password = document.querySelector("#new-password").value;
  const confirm = document.querySelector("#confirm-password").value;

  // TODO: Validation
  if (new_password !== confirm) {
    alert("Passwords do not match.");
    return;
  }

  if (new_password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  // TODO: Send password update to PHP backend
  fetch(`students.php?action=change_password`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ student_id, current_password, new_password })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Password updated successfully!");
      } else {
        alert("Error: " + data.error);
      }
      document.querySelector("#current-password").value = "";
      document.querySelector("#new-password").value = "";
      document.querySelector("#confirm-password").value = "";
    })
    .catch(err => console.error(err));
}

/**
 * TODO: Handle Add Student form submission
 */
function handleAddStudent(event) {
  event.preventDefault();

  const name = document.querySelector("#student-name").value.trim();
  const id = document.querySelector("#student-id").value.trim();
  const email = document.querySelector("#student-email").value.trim();
  const password = document.querySelector("#default-password").value.trim();

  // TODO: Validate inputs
  if (!name || !id || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }

  // TODO: Send new student to PHP backend
  fetch("students.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ student_id: id, name, email, password })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        loadStudents(); // reload table after adding
      } else {
        alert("Error: " + data.error);
      }
      document.querySelector("#student-name").value = "";
      document.querySelector("#student-id").value = "";
      document.querySelector("#student-email").value = "";
      document.querySelector("#default-password").value = "password123";
    })
    .catch(err => console.error(err));
}

/**
 * TODO: Handle Edit/Delete clicks on table using event delegation
 */
function handleTableClick(event) {
  const target = event.target;

  // TODO: Delete student
  if (target.classList.contains("delete-btn")) {
    const studentId = target.dataset.id;
    fetch(`students.php?student_id=${studentId}`, { method: "DELETE" })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          loadStudents();
        } else {
          alert("Error: " + data.error);
        }
      })
      .catch(err => console.error(err));
  }

  // TODO: Edit student
  if (target.classList.contains("edit-btn")) {
    const studentId = target.dataset.id;
    const student = students.find(s => s.id === studentId);
    if (student) {
      const newName = prompt("Enter new name:", student.name);
      const newEmail = prompt("Enter new email:", student.email);
      if (newName && newEmail) {
        fetch("students.php", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ student_id: studentId, name: newName, email: newEmail })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) loadStudents();
            else alert("Error: " + data.error);
          })
          .catch(err => console.error(err));
      }
    }
  }
}

/**
 * TODO: Search students by name
 */
function handleSearch(event) {
  const term = searchInput.value.toLowerCase();
  if (!term) renderTable(students);
  else renderTable(students.filter(s => s.name.toLowerCase().includes(term)));
}

/**
 * TODO: Sort table when headers clicked
 */
function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  let prop;
  switch (index) {
    case 0: prop = "name"; break;
    case 1: prop = "id"; break;
    case 2: prop = "email"; break;
    default: return;
  }

  const currentDir = event.currentTarget.dataset.sortDir || "asc";
  const newDir = currentDir === "asc" ? "desc" : "asc";
  event.currentTarget.dataset.sortDir = newDir;

  students.sort((a, b) => {
    if (prop === "id") return newDir === "asc" ? a.id - b.id : b.id - a.id;
    return newDir === "asc" ? a[prop].localeCompare(b[prop]) : b[prop].localeCompare(a[prop]);
  });

  renderTable(students);
}

/**
 * TODO: Load students from PHP backend
 */
async function loadStudents() {
  try {
    const response = await fetch("students.php");
    students = await response.json();
    renderTable(students);
  } catch (error) {
    console.error(error);
  }
}

/**
 * TODO: Initialize event listeners
 */
function initialize() {
  changePasswordForm.addEventListener("submit", handleChangePassword);
  addStudentForm.addEventListener("submit", handleAddStudent);
  studentTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);
  tableHeaders.forEach(th => th.addEventListener("click", handleSort));
}

// --- Initial Page Load ---
loadStudents().then(initialize);
