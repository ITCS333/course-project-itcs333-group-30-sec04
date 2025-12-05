/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm=document.querySelector('#new-topic-form');
// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer =document.querySelector('#topic-list-container');

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  // ... your implementation here ...
  const article = document.createElement('article');
  article.classList.add('topic');

  const h3=document.createElement('h3');
  const a =document.createElement('a');
  a.href=`topic.html?id=${topic.id}`;
  a.textContent=topic.subject;
  h3.appendChild(a);
  article.appendChild(h3);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  article.appendChild(footer);

  const actionsDiv =document.createElement('div');
  actionsDiv.classList.add('topic-action');
    const editBtn = document.createElement('button');
  editBtn.classList.add('edit-btn');
  editBtn.textContent = 'Edit';

  const deleteBtn = document.createElement('button');
  deleteBtn.classList.add('delete-btn');
  deleteBtn.setAttribute('data-id', topic.id);
  deleteBtn.textContent = 'Delete';

  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);

  article.appendChild(actionsDiv);

  return article;

}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  // ... your implementation here ...
    topicListContainer.innerHTML = ''; 
  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
function handleCreateTopic(event) {
  // ... your implementation here ...
    event.preventDefault();

  const subjectInput = document.querySelector('#topic-subject');
  const messageInput = document.querySelector('#topic-message');

  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subjectInput.value,
    message: messageInput.value,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };

  topics.push(newTopic);
  renderTopics();
  newTopicForm.reset();
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
function handleTopicListClick(event) {
  // ... your implementation here ...
    if (event.target.classList.contains('delete-btn')) {
    const clickedId = event.target.getAttribute('data-id');
    topics = topics.filter(topic => topic.id !== clickedId);
    renderTopics();
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try{
    const response= await fetch('topics.json');
        topics = await response.json();
  } catch (error) {
    console.warn('Could not load topics.json, using empty list');
    topics = [];
  }

  renderTopics();

  newTopicForm.addEventListener('submit', handleCreateTopic);
  topicListContainer.addEventListener('click', handleTopicListClick);
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
