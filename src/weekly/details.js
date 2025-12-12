let currentWeekId = null;
let currentComments = [];

const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");
function getWeekIdFromURL() {
  const queryString = window.location.search;
  const urlParmObj = new URLSearchParams(queryString);
  return urlParmObj.get('id');
}

function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = `Starts on: ${week.start_date || week.startDate}`;
  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";
  const links = week.links || [];
  links.forEach(link => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = link;
    a.textContent = link;
    a.target = "_blank";
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  const article = document.createElement("article");
  const p = document.createElement("p");
  p.textContent = comment.text;
  article.appendChild(p);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

async function handleAddComment(event) {
    event.preventDefault();

    const commentText = newCommentText.value.trim();
    if (!commentText) {
      alert("Please enter a comment");
      return;
    }
    const user_name = localStorage.getItem('user_name') || 'Anonymous';

    const commentData = {
      week_id: currentWeekId,
      text: commentText
    };

    try {
      const response = await fetch('api/index.php?resource=comments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(commentData)
      });

      const result = await response.json();

      if (result.success) {
        newCommentText.value = "";
        await loadComments();
      } else {
        alert("Error adding comment: " + (result.message || result.error));
      }
    } catch (error) {
      console.error('Error adding comment:', error);
      alert("Failed to add comment. Please try again.");
    }
  }

async function loadComments() {
    try {
      const response = await fetch(`api/index.php?resource=comments&week_id=${currentWeekId}`);
      const result = await response.json();

      if (result.success) {
        currentComments = result.data || [];
        renderComments();
      } else {
        console.error('Error loading comments:', result.message || result.error);
      }
    } catch (error) {
      console.error('Error loading comments:', error);
    }
  }

  async function initializePage() {
    console.log('=== DEBUG: Checking localStorage ===');
    console.log('user_name:', localStorage.getItem('user_name'));
    console.log('user_id:', localStorage.getItem('user_id'));
    console.log('user_email:', localStorage.getItem('user_email'));
    console.log('is_admin:', localStorage.getItem('is_admin'));
    console.log('logged_in:', localStorage.getItem('logged_in'));
    console.log('==============================');




    
    currentWeekId = getWeekIdFromURL();

    if (!currentWeekId) {
      weekTitle.textContent = "Week not found.";
      return;
    }

    try {
      const weeksResponse = await fetch(`api/index.php?resource=weeks&id=${currentWeekId}`);
      const weeksResult = await weeksResponse.json();

      if (weeksResult.success && weeksResult.data) {
        renderWeekDetails(weeksResult.data);
        await loadComments();
        commentForm.addEventListener("submit", handleAddComment);
      } else {
        weekTitle.textContent = `Week with ID '${currentWeekId}' not found.`;
        console.error("Week not found");
      }
    } catch (error) {
      console.error('Error:', error);
      weekTitle.textContent = "Error loading week details.";
    }
  }

initializePage();
