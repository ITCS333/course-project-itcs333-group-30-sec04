console.log('details page with seeded + editable comments loaded');

const ASSIGNMENTS_API = 'index.php?resource=assignments';
const COMMENTS_API    = 'index.php?resource=comments';
const COMMENTS_JSON   = 'comments.json';

// All DB comments with id <= this number are treated as "already written"
// (the ones inserted by schema.sql) and are read-only.
const SEED_COMMENT_MAX_ID = 6;

let assignments         = [];
let currentAssignmentId = null;
let currentComments     = [];
let editingCommentId    = null;

// Assignment detail elements
const assignmentTitle       = document.getElementById('assignment-title');
const assignmentDueDate     = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList   = document.getElementById('assignment-files-list');

// Comments UI elements
const commentList   = document.getElementById('comment-list');
const commentForm   = document.getElementById('comment-form');
const commentAuthor = document.getElementById('comment-author');
const commentText   = document.getElementById('comment-text');
const commentStatus = document.getElementById('comment-status');

// -------------------------------------------------------------
// Helpers
// -------------------------------------------------------------
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
            a.href = file;
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

    // Only NON–read-only comments get Edit/Delete buttons
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

// -------------------------------------------------------------
// Seed comments from comments.json → read-only
// -------------------------------------------------------------
async function loadSeedComments() {
    if (!currentAssignmentId) return [];

    try {
        const res  = await fetch(COMMENTS_JSON);
        const data = await res.json();

        if (typeof data !== 'object' || data === null) {
            throw new Error('comments.json did not contain an object');
        }

        let key = String(currentAssignmentId);
        let seedList = data[key];

        if (!Array.isArray(seedList)) {
            const fallbackKey = 'asg_' + String(currentAssignmentId);
            seedList = data[fallbackKey];
        }

        if (!Array.isArray(seedList)) {
            return [];
        }

        // All JSON-based comments are read-only
        return seedList.map((c, i) => ({
            id: `seed-${currentAssignmentId}-${i}`,
            assignmentId: currentAssignmentId,
            author: c.author || 'Anonymous',
            text: c.text || '',
            createdAt: null,
            readOnly: true
        }));
    } catch (err) {
        console.warn('Error loading seed comments:', err);
        return [];
    }
}

// -------------------------------------------------------------
// User comments from API (DB)
// IDs 1..SEED_COMMENT_MAX_ID are "already there" → read-only
// IDs > SEED_COMMENT_MAX_ID are new → editable
// -------------------------------------------------------------
async function loadUserComments() {
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
            const numericId = Number(c.id);
            const isSeed = Number.isFinite(numericId) &&
                           numericId <= SEED_COMMENT_MAX_ID;

            return {
                ...c,
                id: String(c.id),
                readOnly: isSeed // ← old DB comments become read-only
            };
        });
    } catch (err) {
        console.error('Error loading user comments:', err);
        return [];
    }
}

// -------------------------------------------------------------
// Load + merge comments
// -------------------------------------------------------------
async function loadComments() {
    commentList.innerHTML = '<p>Loading comments...</p>';

    const [seed, user] = await Promise.all([
        loadSeedComments(),
        loadUserComments()
    ]);

    currentComments = [...seed, ...user];
    renderComments();
}

// -------------------------------------------------------------
// Editing logic – only for NON–read-only comments
// -------------------------------------------------------------
function enterEditMode(comment) {
    if (comment.readOnly) return;  // cannot edit seed/old comments

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

// -------------------------------------------------------------
// Submit (create or update) comments
// -------------------------------------------------------------
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
                // cannot edit seed / read-only comments
                throw new Error('Cannot edit this comment.');
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

// -------------------------------------------------------------
// Click handler for Edit/Delete buttons
// -------------------------------------------------------------
async function handleCommentListClick(event) {
    const editBtn = event.target.closest('.edit-comment-btn');
    const delBtn  = event.target.closest('.delete-comment-btn');

    if (editBtn) {
        const id = editBtn.dataset.id;
        const comment = currentComments.find(c => c.id === id);
        if (comment && !comment.readOnly) {
            enterEditMode(comment);
        }
    }

    if (delBtn) {
        const id = delBtn.dataset.id;
        const comment = currentComments.find(c => c.id === id);

        // Cannot delete read-only comments
        if (!comment || comment.readOnly) {
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

// -------------------------------------------------------------
// Init
// -------------------------------------------------------------
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

    // Load comments (seed + user)
    await loadComments();

    // Event listeners
    commentForm.addEventListener('submit', handleCommentSubmit);
    commentList.addEventListener('click', handleCommentListClick);
}

initPage();
