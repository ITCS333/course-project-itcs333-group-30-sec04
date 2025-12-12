/*
  Weekly Course Breakdown Admin Panel
  Manages CRUD operations for weekly content via backend API
*/

let weeks = [];
let editingWeekId = null;

const weekForm = document.getElementById("week-form");
const weekTbody = document.getElementById("weeks-tbody");
const API_BASE_URL = 'api/index.php';

function createWeekRow(week) {
  const newtr = document.createElement("tr");
  newtr.dataset.weekId = week.id;

  const titletd = document.createElement("td");
  titletd.textContent = week.title;
  newtr.appendChild(titletd);

  const startDatetd = document.createElement("td");
  startDatetd.textContent = week.start_date;
  newtr.appendChild(startDatetd);

  const descrtd = document.createElement("td");
  descrtd.textContent = week.description;
  descrtd.title = week.description;
  newtr.appendChild(descrtd);

  const tdbutton = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = week.id;
  editBtn.textContent = "Edit";
  tdbutton.appendChild(editBtn);

  const deletebtn = document.createElement("button");
  deletebtn.className = "delete-btn";
  deletebtn.dataset.id = week.id;
  deletebtn.textContent = "Delete";
  tdbutton.appendChild(deletebtn);

  newtr.appendChild(tdbutton);

  return newtr;
}

function renderTable() {
  weekTbody.innerHTML = "";
  weeks.forEach(week => {
    const row = createWeekRow(week);
    weekTbody.appendChild(row);
  });
}

async function handleTableClick(event) {
  const weekId = event.target.dataset.id;

  if (event.target.classList.contains("delete-btn")) {
    if (!confirm("Are you sure you want to delete this week?")) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}?resource=weeks&id=${weekId}`, {
        method: 'DELETE'
      });

      const result = await response.json();

      if (result.success) {
        alert("Week deleted successfully!");
        await loadWeeks();
      } else {
        alert("Error deleting week: " + (result.message || result.error));
      }
    } catch (error) {
      console.error('Error deleting week:', error);
      alert("Failed to delete week. Please try again.");
    }
  }  else if (event.target.classList.contains("edit-btn")) {

    const week = weeks.find(w => String(w.id) === String(weekId));
    if (week) {
      populateFormForEdit(week);
    } else {
      console.warn('Edit clicked but week not found for id:', weekId);
    }
  }
}

function populateFormForEdit(week) {
  editingWeekId = week.id;
  document.getElementById("week-title").value = week.title;
  document.getElementById("week-start-date").value = week.start_date;
  document.getElementById("week-description").value = week.description;
  document.getElementById("week-links").value = (week.links || []).join("\n");



  const submitBtn = weekForm.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.textContent = "Update Week";
  }
}

async function handleUpdateWeek(weekData) {
  try {
    const response = await fetch(`${API_BASE_URL}?resource=weeks`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(weekData)
    });

    const result = await response.json();

    if (result.success) {
      alert("Week updated successfully!");
      editingWeekId = null;
      weekForm.reset();
      const submitBtn = weekForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.textContent = "Add Week";
      }
      await loadWeeks();
    } else {
      alert("Error updating week: " + (result.message || result.error));
    }
  } catch (error) {
    console.error('Error updating week:', error);
    alert("Failed to update week. Please try again.");
  }
}

async function loadWeeks() {
  try {
    const response = await fetch(`${API_BASE_URL}?resource=weeks`);
    const result = await response.json();

    if (result.success) {
      weeks = result.data || [];
      renderTable();
    } else {
      console.error('Error loading weeks:', result.message || result.error);
      alert("Failed to load weeks");
    }
  } catch (error) {
    console.error('Error loading weeks:', error);
    alert("Failed to load weeks. Please check your connection.");
  }
}

async function handleFormSubmit(event) {
  event.preventDefault();

  const title = document.getElementById("week-title").value.trim();
  const startDate = document.getElementById("week-start-date").value.trim();
  const description = document.getElementById("week-description").value.trim();
  const weekLinks = document.getElementById("week-links").value;
  const linkArray = weekLinks.split("\n").filter(link => link.trim() !== "");

  if (!title || !startDate || !description) {
    alert("Please fill in all required fields");
    return;
  }

  const weekData = {
    title: title,
    start_date: startDate,
    description: description,
    links: linkArray
  };

  if (editingWeekId) {
    weekData.id = editingWeekId;
    await handleUpdateWeek(weekData);
  } else {
    await handleCreateWeek(weekData);
  }
}

async function handleCreateWeek(weekData) {
  try {
    const response = await fetch(`${API_BASE_URL}?resource=weeks`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(weekData)
    });

    const result = await response.json();

    if (result.success) {
      alert("Week created successfully!");
      weekForm.reset();
      await loadWeeks();
    } else {
      alert("Error creating week: " + (result.message || result.error));
    }
  } catch (error) {
    console.error('Error creating week:', error);
    alert("Failed to create week. Please try again.");
  }
}

async function loadAndInitialize() {
  await loadWeeks();
  weekForm.addEventListener('submit', handleFormSubmit);
  weekTbody.addEventListener('click', handleTableClick);
  
}
loadAndInitialize()






