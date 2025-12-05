  /*
    Requirement: Populate the weekly detail page and discussion forum.

    Instructions:
    1. Link this file to `details.html` using:
      <script src="details.js" defer></script>

    2. In `details.html`, add the following IDs:
      - To the <h1>: `id="week-title"`
      - To the start date <p>: `id="week-start-date"`
      - To the description <p>: `id="week-description"`
      - To the "Exercises & Resources" <ul>: `id="week-links-list"`
      - To the <div> for comments: `id="comment-list"`
      - To the "Ask a Question" <form>: `id="comment-form"`
      - To the <textarea>: `id="new-comment-text"`

    3. Implement the TODOs below.
  */


  // --- Global Data Store ---
  // These will hold the data related to *this* specific week.
  let currentWeekId = null;
  let currentComments = [];

  // --- Element Selections ---
  // TODO: Select all the elements you added IDs for in step 2.
  const weekTitle=document.getElementById("week-title");
  const weekStartDate=document.getElementById("week-start-date");
  const weekDescription=document.getElementById("week-description");
  const weekLinksList=document.getElementById("week-links-list");
  const commentList=document.getElementById("comment-list");
  const commentForm=document.getElementById("comment-form");
  const newCommentText=document.getElementById("new-comment-text");

  // --- Functions ---

  /**
   * TODO: Implement the getWeekIdFromURL function.
   * It should:
   * 1. Get the query string from `window.location.search`.
   * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
   * 3. Return the id.
   */
  function getWeekIdFromURL() {
    // ... your implementation here ...
    const queryString= window.location.search;
    const urlParmObj= new URLSearchParams(queryString);
    const id= urlParmObj.get('id');
    return id;



  }

  /**
   * TODO: Implement the renderWeekDetails function.
   * It takes one week object.
   * It should:
   * 1. Set the `textContent` of `weekTitle` to the week's title.
   * 2. Set the `textContent` of `weekStartDate` to "Starts on: " + week's startDate.
   * 3. Set the `textContent` of `weekDescription`.
   * 4. Clear `weekLinksList` and then create and append `<li><a href="...">...</a></li>`
   * for each link in the week's 'links' array. The link's `href` and `textContent`
   * should both be the link URL.
   */
  function renderWeekDetails(week) {
    // ... your implementation here ...
    weekTitle.textContent=week.title;
    weekStartDate.textContent=`Starts on: ${week.startDate}`;
    weekDescription.textContent=week.description;

    weekLinksList.innerHTML="";
    week.links.forEach(link => { //fix it 
      const li= document.createElement("li");
      const a= document.createElement("a");

      a.href=link;
      a.textContent=link;
      a.target = "_blank"; 
      
      li.appendChild(a);
      weekLinksList.appendChild(li);
      
    });
    
  }

  /**
   * TODO: Implement the createCommentArticle function.
   * It takes one comment object {author, text}.
   * It should return an <article> element matching the structure in `details.html`.
   * (e.g., an <article> containing a <p> and a <footer>).
   */
  function createCommentArticle(comment) {
    // ... your implementation here ...
    const article= document.createElement("article");

    const p= document.createElement("p");
    p.textContent= comment.text;
    article.appendChild(p);

    const footer= document.createElement('footer');
    footer.textContent= `Posted by: ${comment.author}`;
    article.appendChild(footer);

    return article;

  }

  /**
   * TODO: Implement the renderComments function.
   * It should:
   * 1. Clear the `commentList`.
   * 2. Loop through the global `currentComments` array.
   * 3. For each comment, call `createCommentArticle()`, and
   * append the resulting <article> to `commentList`.
   */
  function renderComments() {
    // ... your implementation here ...
    commentList.innerHTML="";
    currentComments.forEach(comment => {
      const article= createCommentArticle(comment);
      commentList.appendChild(article);
    });

  }

  /**
   * TODO: Implement the handleAddComment function.
   * This is the event handler for the `commentForm` 'submit' event.
   * It should:
   * 1. Prevent the form's default submission.
   * 2. Get the text from `newCommentText.value`.
   * 3. If the text is empty, return.
   * 4. Create a new comment object: { author: 'Student', text: commentText }
   * (For this exercise, 'Student' is a fine hardcoded author).
   * 5. Add the new comment to the global `currentComments` array (in-memory only).
   * 6. Call `renderComments()` to refresh the list.
   * 7. Clear the `newCommentText` textarea.
   */
  function handleAddComment(event) {
    // ... your implementation here ...
    event.preventDefault();

    const commentText= newCommentText.value.trim();
    if(!commentText){
      return;
    }  

    const newComment={
      author: 'Student', 
      text: commentText
    };

    currentComments.push(newComment);
    renderComments();
    newCommentText.value="";
  }

  /**
   * TODO: Implement an `initializePage` function.
   * This function needs to be 'async'.
   * It should:
   * 1. Get the `currentWeekId` by calling `getWeekIdFromURL()`.
   * 2. If no ID is found, set `weekTitle.textContent = "Week not found."` and stop.
   * 3. `fetch` both 'weeks.json' and 'week-comments.json' (you can use `Promise.all`).
   * 4. Parse both JSON responses.
   * 5. Find the correct week from the weeks array using the `currentWeekId`.
   * 6. Get the correct comments array from the comments object using the `currentWeekId`.
   * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
   * 7. If the week is found:
   * - Call `renderWeekDetails()` with the week object.
   * - Call `renderComments()` to show the initial comments.
   * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
   * 8. If the week is not found, display an error in `weekTitle`.
   */
  async function initializePage() {
    // ... your implementation here ...

    currentWeekId= getWeekIdFromURL();
    //console.log("Current Week ID from URL:", currentWeekId);

    if(!currentWeekId){
      weekTitle.textContent="week not found.";
      return;
    }
    const commentKey = currentWeekId;
    

    try{
      const [weeksResponse, commentsResponse] = await Promise.all([
      fetch("api/weeks.json"),
      fetch("api/comments.json")
      ]);


      const weeksData= await weeksResponse.json();
      const commentData= await commentsResponse.json();

      console.log("Weeks loaded:", weeksData);
      console.log("Comments loaded:", commentData);

      const foundWeek= weeksData.find(week => week.id=== currentWeekId);
      ///console.log("Found week:", foundWeek);

      currentComments = commentData[commentKey] || [];
      console.log("Current comments:", currentComments);


      //currentComments = Object.values(commentData).flat();
      if (foundWeek) {

        renderWeekDetails(foundWeek);
        renderComments();
        commentForm.addEventListener("submit", handleAddComment);
        console.log("Week details and comments rendered.");
        
      }
      else{
        weekTitle.textContent = `Week with ID '${currentWeekId}' not found in weeks.json.`;
        console.log("Week not found in weeks.json.");
      }
      
      }

      catch(error){
        console.log('Error:', error);
      }


  }

  // --- InitialPage Load ---
  initializePage();
