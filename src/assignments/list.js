console.log('list.js loaded');


const listSection = document.getElementById('assignment-list-section');
const API_URL = 'index.php?resource=assignments';


function createAssignmentArticle(assignment) {
    const article = document.createElement('article');

    const titleEl = document.createElement('h2');
    titleEl.textContent = assignment.title;

    const dueEl = document.createElement('p');
    const strong = document.createElement('strong');
    strong.textContent = `Due: ${assignment.dueDate}`;
    dueEl.appendChild(strong);

    const descEl = document.createElement('p');
    
    descEl.textContent = assignment.description;

    const linkP = document.createElement('p');
    const link = document.createElement('a');
    link.href = `details.html?id=${assignment.id}`;
    link.textContent = 'View Details & Discussion';
    linkP.appendChild(link);

    article.appendChild(titleEl);
    article.appendChild(dueEl);
    article.appendChild(descEl);
    article.appendChild(linkP);

    return article;
}

async function loadAssignments() {
    listSection.innerHTML = '<p>Loading assignments...</p>';

    try {
        const response = await fetch(API_URL);
        console.log('Assignments status:', response.status);
        const data = await response.json();
        console.log('Assignments data:', data);

        if (!response.ok) {
            const msg = (data && data.error) || `HTTP error ${response.status}`;
            throw new Error(msg);
        }

        if (!Array.isArray(data)) {
            throw new Error('API did not return an array of assignments');
        }

        if (data.length === 0) {
            listSection.innerHTML = '<p>No assignments found.</p>';
            return;
        }

        listSection.innerHTML = '';

        data.forEach(assignment => {
            const article = createAssignmentArticle(assignment);
            listSection.appendChild(article);
        });
    } catch (error) {
        console.error('Error loading assignments:', error);
        listSection.innerHTML = `<p>Error loading assignments. ${error.message}</p>`;
    }
}
loadAssignments();
