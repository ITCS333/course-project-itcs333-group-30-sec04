// --- Global Data Store ---
let resources = [];
// --- Element Selections ---
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');
const titleInput = document.querySelector('#resource-title');
const descriptionInput = document.querySelector('#resource-description');
const linkInput = document.querySelector('#resource-link');
// ينشئ صف واحد في الجدول
function createResourceRow(resource) {
 const tr = document.createElement('tr');
 tr.innerHTML = `
<td>${resource.title}</td>
<td>${resource.description}</td>
<td>
<button class="edit-btn" data-id="${resource.id}">Edit</button>
<button class="delete-btn" data-id="${resource.id}">Delete</button>
</td>
 `;
 return tr;
}
// يعيد رسم الجدول من المصفوفة resources
function renderTable() {
 resourcesTableBody.innerHTML = '';
 resources.forEach((resource) => {
   const row = createResourceRow(resource);
   resourcesTableBody.appendChild(row);
 });
}
// لما تضغط Add Resource
function handleAddResource(event) {
 event.preventDefault();
 const title = titleInput.value.trim();
 const description = descriptionInput.value.trim();
 const link = linkInput.value.trim();
 if (!title || !link) {
   alert('Please enter a title and a link.');
   return;
 }
 const newResource = {
   id: `res_${Date.now()}`,
   title,
   description,
   link,
 };
 resources.push(newResource);
 renderTable();
 resourceForm.reset();
}
// لما تضغط على زر Delete في الجدول
function handleTableClick(event) {
 const target = event.target;
 if (target.classList.contains('delete-btn')) {
   const idToDelete = target.dataset.id;
   resources = resources.filter((resource) => resource.id !== idToDelete);
   renderTable();
 }
}
// تحميل البيانات من resources.json + ربط الأحداث
async function loadAndInitialize() {
 try {
   const response = await fetch('api/resources.json'); // ← هنا المهم
   if (response.ok) {
     resources = await response.json();
   } else {
     resources = [];
     console.warn('resources.json not found, starting with empty list');
   }
 } catch (e) {
   console.error('Error loading resources.json:', e);
   resources = [];
 }
 renderTable();
 resourceForm.addEventListener('submit', handleAddResource);
 resourcesTableBody.addEventListener('click', handleTableClick);
}
// --- Initial Page Load ---
loadAndInitialize();