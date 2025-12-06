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
 tdId.textContent = student.id;
 tr.appendChild(tdId);

 const tdEmail = document.createElement("td");
 tdEmail.textContent = student.email;
 tr.appendChild(tdEmail);

 const tdActions = document.createElement("td");

 const editBtn = document.createElement("button");
 editBtn.textContent = "Edit";
 editBtn.classList.add("edit-btn");
 editBtn.dataset.id = student.id;
 tdActions.appendChild(editBtn);

 const deleteBtn = document.createElement("button");
 deleteBtn.textContent = "Delete";
 deleteBtn.classList.add("delete-btn");
 deleteBtn.dataset.id = student.id;
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

function handleChangePassword(event) {
 event.preventDefault();

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

 alert("Password updated successfully!");
 document.querySelector("#current-password").value = "";
 document.querySelector("#new-password").value = "";
 document.querySelector("#confirm-password").value = "";
}

function handleAddStudent(event) {
 event.preventDefault();

 const name = document.querySelector("#student-name").value.trim();
 const id = document.querySelector("#student-id").value.trim();
 const email = document.querySelector("#student-email").value.trim();

 if (!name || !id || !email) {
   alert("Please fill out all required fields.");
   return;
 }

 if (students.some(s => s.id === id)) {
   alert("Student ID already exists.");
   return;
 }

 students.push({ name, id, email });
 renderTable(students);

 document.querySelector("#student-name").value = "";
 document.querySelector("#student-id").value = "";
 document.querySelector("#student-email").value = "";
 document.querySelector("#default-password").value = "password123";
}

function handleTableClick(event) {
 const target = event.target;

 if (target.classList.contains("delete-btn")) {
   const id = target.dataset.id;
   students = students.filter(s => s.id !== id);
   renderTable(students);
 }

 if (target.classList.contains("edit-btn")) {
   const id = target.dataset.id;
   const student = students.find(s => s.id === id);
   if (student) {
     const newName = prompt("Enter new name:", student.name);
     const newEmail = prompt("Enter new email:", student.email);
     if (newName) student.name = newName;
     if (newEmail) student.email = newEmail;
     renderTable(students);
   }
 }
}

function handleSearch(event) {
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

async function loadStudentsAndInitialize() {
 try {
   const response = await fetch("students.json");
   if (!response.ok) throw new Error("Failed to fetch students.json");

   students = await response.json();
   renderTable(students);

   changePasswordForm.addEventListener("submit", handleChangePassword);
   addStudentForm.addEventListener("submit", handleAddStudent);
   studentTableBody.addEventListener("click", handleTableClick);
   searchInput.addEventListener("input", handleSearch);
   tableHeaders.forEach(th => th.addEventListener("click", handleSort));
 } catch (error) {
   console.error(error);
 }
}

loadStudentsAndInitialize();
