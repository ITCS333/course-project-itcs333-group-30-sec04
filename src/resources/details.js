
 /*

  Requirement: Populate the resource detail page and discussion forum.

*/

// --- Global Data Store ---

let currentResourceId = null;

let currentComments = [];

// --- Element Selections ---

const resourceTitle = document.querySelector('#resource-title');

const resourceDescription = document.querySelector('#resource-description');

const resourceLink = document.querySelector('#resource-link');

const commentList = document.querySelector('#comment-list');

const commentForm = document.querySelector('#comment-form');

const newComment = document.querySelector('#new-comment');

// --- Functions ---

// 1) استخراج الـ id من الـ URL

function getResourceIdFromURL() {

  const params = new URLSearchParams(window.location.search);

  const id = params.get('id');

  return id;

}

// 2) تعبئة تفاصيل المورد

function renderResourceDetails(resource) {

  resourceTitle.textContent = resource.title;

  resourceDescription.textContent = resource.description;

  resourceLink.href = resource.link;

}

// 3) إنشاء عنصر <article> للتعليق الواحد

function createCommentArticle(comment) {

  const article = document.createElement('article');

  const p = document.createElement('p');

  p.textContent = comment.text;

  const footer = document.createElement('footer');

  footer.textContent = `— ${comment.author}`;

  article.appendChild(p);

  article.appendChild(footer);

  return article;

}

// 4) رسم كل التعليقات في القائمة

function renderComments() {

  commentList.innerHTML = '';

  currentComments.forEach((comment) => {

    const article = createCommentArticle(comment);

    commentList.appendChild(article);

  });

}

// 5) إضافة تعليق جديد

function handleAddComment(event) {

  event.preventDefault();

  const commentText = newComment.value.trim();

  if (!commentText) return;

  const newCommentObj = {

    author: 'Student',

    text: commentText,

  };

  currentComments.push(newCommentObj);

  renderComments();

  newComment.value = '';

}

// 6) تهيئة الصفحة

async function initializePage() {

  // 1) نجيب الـ id من الـ URL

  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {

    resourceTitle.textContent = 'Resource not found.';

    return;

  }

  try {

    // 3) نجلب البيانات من resources.json و resource-comments.json

    const [resourcesRes, commentsRes] = await Promise.all([

      fetch('api/resources.json'),

      fetch('api/comments.json'),

    ]);

    const resources = await resourcesRes.json();

    const commentsData = await commentsRes.json();

    // 5) نلقى المورد المطلوب

    const resource = resources.find(

      (item) => item.id === currentResourceId

    );

    // 6) نجيب تعليقات هذا المورد

    currentComments = commentsData[currentResourceId] || [];

    if (resource) {

      renderResourceDetails(resource);

      renderComments();

      // 7) نربط الفورم

      commentForm.addEventListener('submit', handleAddComment);

    } else {

      resourceTitle.textContent = 'Resource not found.';

    }

  } catch (error) {

    console.error('Error initializing resource details page:', error);

    resourceTitle.textContent = 'Error loading resource.';

  }

}

// --- Initial Page Load ---

initializePage();

 