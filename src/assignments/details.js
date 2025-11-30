console.log('details page with per-user editable comments loaded');

const ASSIGNMENTS_API = 'index.php?resource=assignments';
const COMMENTS_API    = 'index.php?resource=comments';

let assignments         = [];
let currentAssignmentId = null;
let currentComments     = [];
let editingCommentId    = null;

// ----- DOM Elements -----
const assignmentTitle       = document.getElementById('assignment-title');
const assignmentDueDate     = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList   = document.getElementById('assignment-files-list');

const commentList   = document.getElementById('comment-list');
const commentForm   = document.getElementById('comment-form');
const commentAuthor = document.getElementById('comment-author'); // "Your name (optional)"
const commentText   = document.getElementById('comment-text');
const commentStatus = document.getElementById('comment-status');

// ----- Helpers -----
function getAssignmentIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    console.log('ID from URL =', id);
    return id;
}

function renderAssignmentDetails(assignment) {
    if (!assignment) {
        assignmentTitle.textContent = 'Assignment not found';
        assignmentDueDate.innerHTML = '<strong>Due: —</strong>';
        assignmentDescription.textContent =
            'No assignment matches the id in the URL.';
        assignmentFilesList.innerHTML = '<li>No files.</li>';
        return;
    }

    assignmentTitle.textContent = assignment.title;
    assignmentDueDate.innerHTML = `<strong>Due: ${assignment.dueDate}</strong>`;
    assignmentDescription.textContent = assignment.description;

    assignmentFilesList.innerHTML = '';
    if (assignment.files && assignment.files.length > 0) {
        assignment.files.forEach(file => {
            const li = document.createElement('li');
            const a  = document.createElement('a');
            a.href = '#'; 
            a.textContent = file;
            li.appendChild(a);
            assignmentFilesList.appendChild(li);
        });
    } else {
        assignmentFilesList.innerHTML = '<li>No files attached</li>';
    }
}

function createCommentArticle(comment) {
    const article = document.createElement('article');
    article.dataset.id = comment.id;

    const p = document.createElement('p');
    p.textContent = comment.text;

    const footer = document.createElement('footer');
    const small  = document.createElement('small');
    const author = comment.author || 'Anonymous';
    const when   = comment.createdAt
        ? new Date(comment.createdAt).toLocaleString()
        : '';
    small.textContent = `Posted by ${author}${when ? ' on ' + when : ''}`;

    footer.appendChild(small);

    // Only allow edit/delete for non-readonly comments
    if (!comment.readOnly) {
        footer.appendChild(document.createElement('br'));

        const btnEdit = document.createElement('button');
        btnEdit.type = 'button';
        btnEdit.textContent = 'Edit';
        btnEdit.className = 'secondary edit-comment-btn';
        btnEdit.dataset.id = comment.id;

        const btnDelete = document.createElement('button');
        btnDelete.type = 'button';
        btnDelete.textContent = 'Delete';
        btnDelete.className = 'secondary delete-comment-btn';
        btnDelete.dataset.id = comment.id;

        footer.appendChild(btnEdit);
        footer.appendChild(document.createTextNode(' '));
        footer.appendChild(btnDelete);
    }

    article.appendChild(p);
    article.appendChild(footer);

    return article;
}

function renderComments() {
    commentList.innerHTML = '';

    if (!currentComments || currentComments.length === 0) {
        commentList.innerHTML =
            '<p>No comments yet. Be the first to ask a question!</p>';
        return;
    }

    currentComments.forEach(c => {
        const article = createCommentArticle(c);
        commentList.appendChild(article);
    });
}

// ----- Load comments from API (DB only) -----
// currentUserName controls who can edit/delete (only their own comments)
async function loadUserComments(currentUserName) {
    if (!currentAssignmentId) return [];

    try {
        const res  = await fetch(
            `${COMMENTS_API}&assignment_id=${encodeURIComponent(currentAssignmentId)}`
        );
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || `HTTP ${res.status}`);
        }

        if (!Array.isArray(data)) {
            throw new Error('Comments API did not return an array');
        }

        return data.map(c => {
            const author = c.author || 'Anonymous';
            return {
                ...c,
                id: String(c.id),
                // Only comments where author matches currentUserName are editable
                readOnly: author !== currentUserName
            };
        });
    } catch (err) {
        console.error('Error loading user comments:', err);
        return [];
    }
}

async function loadComments() {
    commentList.innerHTML = '<p>Loading comments...</p>';

    // Current user = value in "Your name (optional)" box, or 'Anonymous'
    const currentUserName = commentAuthor
        ? (commentAuthor.value.trim() || 'Anonymous')
        : 'Anonymous';

    const userComments = await loadUserComments(currentUserName);
    currentComments = userComments;
    renderComments();
}

// ----- Edit helpers -----
function enterEditMode(comment) {
    if (comment.readOnly) return; 

    editingCommentId = comment.id;
    commentAuthor.value = comment.author || '';
    commentText.value = comment.text;
    commentStatus.textContent = 'Editing comment...';
    commentForm.querySelector('button[type="submit"]').textContent =
        'Update Comment';
}

function exitEditMode() {
    editingCommentId = null;
    commentForm.reset();
    commentStatus.textContent = '';
    commentForm.querySelector('button[type="submit"]').textContent =
        'Post Comment';
}

// ----- Submit / Save comment -----
async function handleCommentSubmit(event) {
    event.preventDefault();
    if (!currentAssignmentId) return;

    const text   = commentText.value.trim();
    const author = commentAuthor.value.trim() || 'Anonymous';

    if (!text) {
        alert('Please enter a comment.');
        return;
    }

    commentStatus.textContent = editingCommentId
        ? 'Updating comment...'
        : 'Posting comment...';

    try {
        let method = 'POST';
        let body   = {
            assignmentId: currentAssignmentId,
            author,
            text
        };

        if (editingCommentId) {
            const comment = currentComments.find(c => c.id === editingCommentId);
            if (!comment || comment.readOnly) {
                throw new Error('You can only edit your own comments.');
            }

            method = 'PUT';
            body.id = editingCommentId;
        }

        const res = await fetch(COMMENTS_API, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || `HTTP ${res.status}`);
        }

        await loadComments();
        exitEditMode();
        commentStatus.textContent = editingCommentId
            ? 'Comment updated!'
            : 'Comment posted!';
    } catch (err) {
        console.error('Error saving comment:', err);
        commentStatus.textContent = 'Error: ' + err.message;
    }
}

// ----- Click handler for edit/delete -----
async function handleCommentListClick(event) {
    const editBtn = event.target.closest('.edit-comment-btn');
    const delBtn  = event.target.closest('.delete-comment-btn');

    if (editBtn) {
        const id = editBtn.dataset.id;
        const comment = currentComments.find(c => c.id === id);
        if (comment && !comment.readOnly) {
            enterEditMode(comment);
        } else {
            alert('You can only edit your own comments.');
        }
    }

    if (delBtn) {
        const id = delBtn.dataset.id;
        const comment = currentComments.find(c => c.id === id);

        if (!comment || comment.readOnly) {
            alert('You can only delete your own comments.');
            return;
        }

        if (!confirm('Delete this comment?')) return;

        try {
            const res  = await fetch(
                `${COMMENTS_API}&id=${encodeURIComponent(id)}`,
                { method: 'DELETE' }
            );
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.error || `HTTP ${res.status}`);
            }

            await loadComments();
            if (editingCommentId === id) {
                exitEditMode();
            }
            commentStatus.textContent = 'Comment deleted.';
        } catch (err) {
            console.error('Error deleting comment:', err);
            commentStatus.textContent =
                'Error deleting comment: ' + err.message;
        }
    }
}

// ----- Init page -----
async function initPage() {
    currentAssignmentId = getAssignmentIdFromURL();

    if (!currentAssignmentId) {
        assignmentTitle.textContent = 'Error: no assignment id provided';
        return;
    }

    // Load assignments
    try {
        const res  = await fetch(ASSIGNMENTS_API);
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || `HTTP error ${res.status}`);
        }

        if (!Array.isArray(data)) {
            throw new Error('Assignments API did not return an array');
        }

        assignments = data;

        const assignment = assignments.find(
            a => String(a.id) === String(currentAssignmentId)
        );

        renderAssignmentDetails(assignment);
    } catch (err) {
        console.error('Error loading assignment details:', err);
        assignmentTitle.textContent = 'Error loading assignment';
        assignmentDescription.textContent =
            'There was an error loading the assignment details.';
    }

    // Load comments
    await loadComments();

    // Event listeners
    commentForm.addEventListener('submit', handleCommentSubmit);
    commentList.addEventListener('click', handleCommentListClick);

    // When the user changes their name, recompute which comments are editable
    if (commentAuthor) {
        commentAuthor.addEventListener('change', () => {
            loadComments();
        });
    }
}

initPage();
