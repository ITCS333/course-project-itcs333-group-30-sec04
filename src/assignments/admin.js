
let assignments = [];
const API_URL = 'index.php?resource=assignments';

const assignmentForm = document.getElementById('assignment-form');
const assignmentsTableBody = document.getElementById('assignments-tbody');
const assignmentTitleInput = document.getElementById('assignment-title');
const assignmentDescriptionInput = document.getElementById('assignment-description');
const assignmentDueDateInput = document.getElementById('assignment-due-date');
const assignmentFilesInput = document.getElementById('assignment-files');

function createAssignmentRow(assignment) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${assignment.title}</td>
        <td>${assignment.dueDate}</td>
        <td>
            <button class="edit-btn" data-id="${assignment.id}">Edit</button>
            <button class="delete-btn" data-id="${assignment.id}">Delete</button>
        </td>
    `;
    return row;
}

function renderTable() {
    assignmentsTableBody.innerHTML = '';
    
    assignments.forEach(assignment => {
        const row = createAssignmentRow(assignment);
        assignmentsTableBody.appendChild(row);
    });
}

async function handleAddAssignment(event) {
    event.preventDefault();
    
    const title = assignmentTitleInput.value.trim();
    const description = assignmentDescriptionInput.value.trim();
    const dueDate = assignmentDueDateInput.value;
    const filesText = assignmentFilesInput.value.trim();
    
    if (!title || !description || !dueDate) {
        alert('Please fill in all required fields');
        return;
    }
    
    const files = filesText
        ? filesText.split('\n').filter(file => file.trim() !== '')
        : [];
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title,
                description,
                dueDate,
                files
            })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Failed to create assignment');
        }
        
        await loadAssignments();
        assignmentForm.reset();
        alert('Assignment added successfully!');
    } catch (error) {
        console.error('Error creating assignment:', error);
        alert('Error creating assignment: ' + error.message);
    }
}

async function handleTableClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const assignmentId = event.target.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this assignment?')) {
            try {
                const response = await fetch(`index.php?resource=assignments&id=${assignmentId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to delete assignment');
                }
                
                await loadAssignments();
                alert('Assignment deleted successfully!');
            } catch (error) {
                console.error('Error deleting assignment:', error);
                alert('Error deleting assignment: ' + error.message);
            }
        }
    } else if (event.target.classList.contains('edit-btn')) {
        const assignmentId = event.target.getAttribute('data-id');
        const assignment = assignments.find(a => a.id === assignmentId);
        
        if (assignment) {
            assignmentTitleInput.value = assignment.title;
            assignmentDescriptionInput.value = assignment.description;
            assignmentDueDateInput.value = assignment.dueDate;
            assignmentFilesInput.value = assignment.files ? assignment.files.join('\n') : '';
            
            const submitButton = document.getElementById('add-assignment');
            submitButton.textContent = 'Update Assignment';
            submitButton.onclick = async function(e) {
                e.preventDefault();
                await handleUpdateAssignment(assignmentId);
            };
        }
    }
}

async function handleUpdateAssignment(assignmentId) {
    const title = assignmentTitleInput.value.trim();
    const description = assignmentDescriptionInput.value.trim();
    const dueDate = assignmentDueDateInput.value;
    const filesText = assignmentFilesInput.value.trim();
    const files = filesText
        ? filesText.split('\n').filter(file => file.trim() !== '')
        : [];
    
    try {
        const response = await fetch(API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: assignmentId,
                title,
                description,
                dueDate,
                files
            })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Failed to update assignment');
        }
        
        await loadAssignments();
        
        assignmentForm.reset();
        const submitButton = document.getElementById('add-assignment');
        submitButton.textContent = 'Add Assignment';
        submitButton.onclick = handleAddAssignment;
        
        alert('Assignment updated successfully!');
    } catch (error) {
        console.error('Error updating assignment:', error);
        alert('Error updating assignment: ' + error.message);
    }
}

async function loadAssignments() {
    try {
        const response = await fetch(API_URL);
        assignments = await response.json();
        
        if (!response.ok) {
            throw new Error(assignments.error || 'Failed to load assignments');
        }
        
        renderTable();
    } catch (error) {
        console.error('Error loading assignments:', error);
        assignmentsTableBody.innerHTML =
            '<tr><td colspan="3">Error loading assignments</td></tr>';
    }
}

async function loadAndInitialize() {
    await loadAssignments();
    assignmentForm.addEventListener('submit', handleAddAssignment);
    assignmentsTableBody.addEventListener('click', handleTableClick);
}

loadAndInitialize();
